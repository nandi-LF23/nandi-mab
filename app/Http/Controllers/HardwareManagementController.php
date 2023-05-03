<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

use App\Models\hardware_management;
use App\Models\Group;
use App\Models\Company;
use App\Models\Subsystem;
use App\Utils;

// Sensor Types
class HardwareManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('cors');
        $this->middleware(function ($request, $next) { $this->acc = Auth::user(); return $next($request); });
    }
    // get all (table)
    public function SensorTypesTable(Request $request)
    {
        $request->validate([
            'cur_page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:5',
            'initial'  => 'required',
            'filter'   => 'nullable',
            'sort_by'  => 'required',
            'sort_dir' => 'required',
            'entity'   => 'nullable'
        ]);

        $limit  = $request->per_page;
        $offset = ($request->cur_page-1) * $limit;

        $sortBy  = $request->sort_by;
        $sortDir = $request->sort_dir;
        
        // optional filter param
        $filter = !empty($request->filter) ? $request->filter : '';
        // optional entity param
        $entity  = !empty($request->entity) ? $request->entity : '';

        $ccs = [];
        $sensors = [];
        $grants  = [];
        $total = 0;

        $columns = [
            'hardware_management.id AS id',
            'company_name',
            'device_make',
            'device_type'
        ];

        $sortBy = Utils::findColumnAlias(Utils::findFromPartial($columns, $sortBy, 'date_time'));

        $sensors = hardware_management::select($columns)
        ->join('companies', 'hardware_management.company_id', '=', 'companies.id')
        ->when($entity, function($query) use($entity){
            // filter by entity (optional)
            $query->where('companies.id', $entity);
        })
        ->when($filter, function($query) use($filter){
            // filter by device_make, device_type or company_name' (optional)
            $query->where('device_make', 'like', "%$filter%")
            ->orWhere('device_type', 'like', "%$filter%")
            ->orWhere('company_name', 'like', "%$filter%");
        });

        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Sensor Types' => ['p' => ['All'] ] ]);
            if(!empty($grants['Sensor Types']['View']['O'])){
                $sensors->whereIn('hardware_management.id', $grants['Sensor Types']['View']['O']);
                $ccs = $grants['Sensor Types']['View']['C'];
                $ccs = Company::whereIn('id', $ccs)->orderBy('company_name')->get()->toArray();

            } else {
                $sensors = [];
            }
        } else {
            $ccs = Company::select(['id', 'company_name'])->orderBy('company_name')->get()->toArray();
        }

        if($sensors){
            $total = $sensors->count();
            if($total){
                if($sortBy && $sortDir){
                    $sensors->orderBy($sortBy, $sortDir);
                } else {
                    $sensors->orderBy('id', 'desc');
                }
                $sensors = $sensors->skip($offset)->take($limit)->get();
            }
        }

        if($request->initial){
            $details = !empty($grants['Sensor Types']['View']['C']) ?
                ('Company IDs: ' . implode(',', $grants['Sensor Types']['View']['C'])) :
                ($this->acc->is_admin ? 'All Objects' : 'Access Denied');
            $this->acc->logActivity('View', 'Sensor Types', $details);
        }

        return response()->json([
            'rows'   => $sensors,
            'total'  => $total,
            'grants' => $grants,
            'entities' => $ccs
        ]);
    }

    // get list (for ddlb)
    public function SensorTypesList(Request $request)
    {
        $request->validate([
            'device_type' => 'nullable',
            'company_id' => 'required|exists:companies,id',
        ]);

        // get full list of sensors (initially) (limited by type)
        $sensors = hardware_management::select(
            'hardware_management.id',
            'device_make',
            'device_type',
            'company_name'
        )->join('companies', 'companies.id','=','hardware_management.company_id')
        ->where('company_id', $request->company_id);

        // Used in HardwareConfigForm.vue
        if($request->device_type){
            $sensors->where('device_type', $request->device_type);
        }

        if(!$this->acc->is_admin){
            // optionally filter out sensors (if additional permissions were set)
            // permission check
            $grants = $this->acc->requestAccess(['Sensor Types' => ['p' => ['View'] ] ]);
            if(!empty($grants['Sensor Types']['View']['O'])){
                $sensors->whereIn('hardware_management.id', $grants['Sensor Types']['View']['O']);
            } else {
                $sensors = [];
            }
        }

        if($sensors){
            // Added in keyBy('id'), remove if causing errors (or adjust frontend)
            $sensors = $sensors->get()->keyBy('id')->toArray();
        }

        return response()->json([
            'hardware' => $sensors
        ]);
    }

    // get single
    public function SensorTypesForm(Request $request)
    {
        $sensors = [];

        if(!empty($request->id)){

            // permission check
            $grants = $this->acc->requestAccess(['Sensor Types' => ['p' => ['View'], 'o' => $request->id, 't' => 'O'] ]);
            if(empty($grants['Sensor Types']['View']['O'])){ return response()->json(['message' => 'access_denied'], 403); }

            $sensor_type = hardware_management::where('id', $request->id)->first();

            // Pre-Process Nutrient Type Probe Sensor Config
            if($sensor_type->sensor_config){
                $sensor_type['sensor_config'] = json_decode($sensor_type->sensor_config, true);
            }

            $this->acc->logActivity('View', 'Sensor Types', "{$sensor_type->device_make} ({$sensor_type->id})");
        }

        return response()->json([
            'hardware' => $sensor_type
        ]);
    }

    // add single
    public function new(Request $request)
    {
        $this->validateSensorTypeConfig($request, true);

        $grants = [];

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Sensor Types' => ['p' => ['All'] ] ]);
            if(empty($grants['Sensor Types']['Add']['C'])){ return response()->json(['message' => 'access_denied'], 403); }
        }

        $sensor_type = new hardware_management();
        $sensor_type->company_id = $request->company_id;
        $sensor_type->device_type = $request->device_type;
        $sensor_type->device_make = $request->device_make;
        $sensor_type->device_category = $request->device_category;
        $sensor_type->device_length = $request->device_length ?: "1500mm";

        // Process Sensor Placements for Soil Moisture Probe Device Type
        for($i = 1; $i <= 15; $i++){
            $prop = "sensor_placing_$i";
            $sensor_type->{$prop} = isset($request->{$prop}) && $request->{$prop} ? "on" : "off";
        }

        // Process Sensor Config for Nutrient Probe Device Type
        if($request->device_type == 'Nutrients' && !empty($request->sensor_config)){
            $sensor_type->sensor_config = json_encode($request->sensor_config);
        }

        $sensor_type->diameter         = $request->diameter ?: 1;
        $sensor_type->pulse_weight     = $request->pulse_weight ?: 1;
        $sensor_type->measurement_type = $request->measurement_type;
        $sensor_type->application_type = $request->application_type;

        $sensor_type->save();

        // add sensor to group
        if(Group::where('id', $request->group_id)->exists()){
            DB::table('groups_sensors')->insert(['group_id' => $request->group_id, 'object_id' => $sensor_type->id]);
        }

        // needed for table
        $sensor_type->company_name = Company::where('id', $request->company_id)->pluck('company_name')->first();

        $this->acc->logActivity('Add', 'Sensor Types', "{$sensor_type->device_make} ({$sensor_type->device_type}) ({$sensor_type->id})");

        return response()->json([
            'message' => 'sensor_added',
            'sensor' => $sensor_type,
            'grants' => $grants
        ]);
    }
    
    // update single
    public function save(Request $request)
    {
        if(empty($request->id)){
            return response()->json(['message' => 'missing_id' ]);
        }

        $this->validateSensorTypeConfig($request);

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Sensor Types' => ['p' => ['Edit'], 'o' => $request->id, 't' => 'O'] ]);
            if(empty($grants['Sensor Types']['Edit']['O'])){ return response()->json(['message' => 'access_denied'], 403); }
        }

        // get existing object
        $sensor_type = hardware_management::where('id', $request->id)->first();
        if(!$sensor_type){
            return response()->json(['message' => 'sensor_missing']);
        }

        // Soil Moisture (Update for All)
        for($i = 1; $i <= 15; $i++){
            $prop = "sensor_placing_$i";
            $sensor_type->$prop = isset($request->$prop) && $request->$prop ? "on" : "off";
        }

        // Process Sensor Config for Nutrient Probe Device Type
        if($request->device_type == 'Nutrients' && !empty($request->sensor_config)){
            $sensor_type->sensor_config = json_encode($request->sensor_config);
        }

        // SENSOR ENTITY CHANGED
        if($sensor_type->company_id != $request->company_id){
            $subsystems = $this->acc->subsystems();
            $groups = Group::where('company_id', $sensor_type->company_id)->where('subsystem_id', $subsystems['Sensor Types']['id'])->get();
            // remove sensor from old entities' sensor groups
            if($groups){
                foreach($groups as $g){
                    DB::table($subsystems['Sensor Types']['group_table'])->where('group_id', $g->id)->where('object_id', $request->id)->delete();
                }
            }
            $company_name = Company::where('id', $request->company_id)->pluck('company_name')->first();
            $this->acc->logActivity('Edit', 'Sensor Types', "{$sensor_type->device_make} ({$sensor_type->device_type}) ({$sensor_type->id}) Changed Entity: $company_name");
        }

        $sensor_type->update($request->all());

        $this->acc->logActivity('Edit', 'Sensor Types', "{$sensor_type->device_make} ({$sensor_type->device_type}) ({$sensor_type->id})");

        return response()->json([ 'message' => 'sensor_updated', 'req' => $request->all() ]);
    }

    // used by new and save
    public function validateSensorTypeConfig($request, $bIsNew = false)
    {
        $nodeTypes = ['Soil Moisture','Nutrients','Wells','Water Meter'];
        
        $request->validate([
            'device_type'     => [ 'required', 'string', Rule::in($nodeTypes) ],
            'device_make'     => 'required|string|max:128',
            'device_category' => 'required|string|max:64',
            'company_id'      => 'required|exists:companies,id'
        ]);

        if($bIsNew){
            $request->validate([
                'group_id' => 'integer|nullable|exists:groups,id'
            ]);
        }

        switch($request->device_type){
            case 'Nutrients':
                $request->validate([
                    'device_length' => 'required|string|max:128',
                    'sensor_config' => 'required'
                ]);
            break;
            case 'Soil Moisture':
                $request->validate([
                    'device_length' => 'required'
                ]);
            break;
            case 'Wells':
            case 'Water Meter':
                $request->validate([
                    'diameter'         => 'required|numeric',
                    'pulse_weight'     => 'required|numeric',
                    'measurement_type' => 'nullable|string',
                    'application_type' => 'nullable|string'
                ]);
            break;
        }
    }

    // Clone a sensor to another entity
    public function clone(Request $request)
    {
        $request->validate([
            'sensor_id' => 'required|exists:hardware_management,id',
            'company_id' => 'required|exists:companies,id'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(
                [ 'Entities'     => ['p' => ['View'], 'o' => $request->company_id, 't' => 'O'],
                  'Sensor Types' => ['p' => ['Clone'], 'o' => $request->sensor_id, 't' => 'O'] ]
            );
            if( empty($grants['Sensor Types']['Clone']['O']) &&
                empty($grants['Entities']['View']['O'])
            ){ return response()->json([ 'message' => 'access_denied' ], 403); }
        }

        // fetch source
        $sensor_type = hardware_management::find($request->sensor_id);
        if(!$sensor_type){
            return response()->json([ 'message' => 'nonexistent' ]);
        }

        // clone
        $cloned_sensor = $sensor_type->replicate();
        $cloned_sensor->company_id = $request->company_id;
        $cloned_sensor->save();

        $this->acc->logActivity('Clone', 'Sensor Types', "{$sensor_type->device_make} ({$sensor_type->id})");

        return response()->json([ 'message' => 'sensor_cloned' ]);
    }

    // delete single
    public function destroy(Request $request)
    {
        if(empty($request->id)){
            return response()->json([ 'message' => 'missing_id' ]);
        }

        if(!hardware_management::where('id', $request->id)->exists()){
            return response()->json([ 'message' => 'nonexistent' ]);
        }

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Sensor Types' => ['p' => ['Delete'], 'o' => $request->id, 't' => 'O'] ]);
            if(empty($grants['Sensor Types']['Delete']['O'])){ return response()->json([ 'message' => 'access_denied' ], 403); }
        }

        $sensor_type = hardware_management::find($request->id);
        $sensor_type->delete();

        $this->acc->logActivity('Delete', 'Sensor Types', "{$sensor_type->device_make} ({$sensor_type->id})");

        return response()->json([ 'message' => 'sensor_removed' ]);
    }
}
