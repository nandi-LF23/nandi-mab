<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\cultivars_templates;
use App\Models\cultivars_management;
use App\Models\cultivars;
use App\Models\fields;
use App\Models\node_data;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

// Note both `cultivars_management` (Cultivars) and `cultivars` (Growth Stages) are managed here
class CultivarController extends Controller
{
    public function __construct()
    {
        $this->middleware('cors');
        $this->middleware(function ($request, $next) { $this->acc = Auth::user(); return $next($request); });
        $this->timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
    }

    // like get()
    public function cultivar_manage(Request $request)
    {
        $grants = [];

        $cm = cultivars_management::where('field_id', $request->fid)->first();

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess([
                'Cultivars'          => [ 'p' => ['All'], 'o' => $cm->id, 't' => 'O' ],
                'Cultivar Stages'    => [ 'p' => ['All'] ],
                'Cultivar Templates' => [ 'p' => ['All'] ]
            ]);
            if(empty($grants['Cultivars']['View']['O'])){
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // TODO: add in field and stages permission checks later (when field is sorted out)
        $field = fields::where('id', $request->fid)->first();
        $stages = cultivars::where('cultivars_management_id', $cm->id)->get();

        // manual merge to prevent 'id' columns conflict
        $return = [
            /* Cultivar Management Table's Fields */
            'cm_id' => $cm->id,
            'crop_name' => $cm->crop_name, 
            'crop_type' => $cm->crop_type,
            'irrigation_type' => $cm->irrigation_type,
            'graph_model' => $field->graph_model,

            /* Fields Table's Fields */
            'field_id' => $field->id,
            'full' => $field->full, 
            'refill' => $field->refill,
            'ni' => $field->ni,
            'nr' => $field->nr,

            /* Used for the "Go to 'Graph'" Link */
            'field_name' => $field->field_name,
            'graph_start_date' => $field->graph_start_date,
            'graph_type' => $field->graph_type,
            'node_address' => $field->node_id,

            'company_id' => $field->company_id
        ];

        // NOTE: The `fields` table has an NI and NR column that serve as defaults for the stages.
        // NOTE: One to many relationship between `cultivars_management` (parent) and `cultivars` (children, growth stages)

        $details = !empty($grants['Cultivars']['View']['C']) ?
            ('Company IDs: ' . implode(',', $grants['Cultivars']['View']['C'])) : 
            ($this->acc->is_admin ? 'All Objects' : 'Access Denied');
        $this->acc->logActivity('View', 'Cultivars', $details);

        $result = [
            'grants' => $grants,
            'fields' => $return,
            'stages' => $stages,
        ];

        if($grants){ $result['grants'] = $grants; }

        return response()->json($result);
    }

    // update `cultivars_management` record
    public function update(Request $request)
    {
        $request->validate([

            'cm_id' => 'required',
            'crop_type' => 'required',
            'crop_name' => 'required',
            'irrigation_type' => 'required',
            'graph_model' => 'required',

            'field_id' => 'required',
            'full' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'refill' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'ni' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'nr' => 'required|regex:/^\d+(\.\d{1,2})?$/'

        ]);

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess([ 'Cultivars' => [ 'p' => ['Edit'], 'o' => $request->cm_id, 't' =>  'O' ] ]);
            if(empty($grants['Cultivars']['Edit']['O'])){
                return response()->json(['message' => 'access_denied'], 403);
            }
            // for now, we give fields a free pass
        }

        $cm_fields    = $request->only(['crop_type', 'crop_name', 'irrigation_type', 'field_id']);
        $field_fields = $request->only(['ni', 'nr', 'full', 'refill', 'graph_model']);

        $cm_fields['NI'] = $field_fields['ni'];
        $cm_fields['NR'] = $field_fields['nr'];

        cultivars_management::where('id', $request->cm_id)->update($cm_fields);
        fields::where('id', $request->field_id)->update($field_fields);

        $this->acc->logActivity('Edit', 'Cultivars', "{$cm_fields['crop_name']} ({$request->cm_id})");

        return response()->json(['message' => 'cultivar_updated']);
    }

    // Create/Update/Replace multiple stages at once
    public function set_stages(Request $request)
    {
        $request->validate([
            'cm_id' => 'required|exists:cultivars_management,id',
            'company_id' => 'required|exists:companies,id',
            'stages' => 'required',
            'new' => 'required',
            'replace' => 'required'
        ]);

        if($this->acc->companyCheckFails($request->company_id)){
            return response()->json(['message' => 'access_denied'], 403);
        }

        $grants = [];

        // add new stage one by one
        $stages = $request->stages;
        if($stages){

            // for activity log
            $stage_names = [];
            $cm = cultivars_management::where('id', $request->cm_id)->first();

            if($request->new == 'yes'){

                // permission check
                if(!$this->acc->is_admin){
                    $grants = $this->acc->requestAccess(['Cultivar Stages' => ['p' => ['All'] ] ]);
                    if(empty($grants['Cultivar Stages']['Add']['C'])){
                        return response()->json(['message' => 'access_denied', 'debug' => "Add,Cultivar Stages1"], 403);
                    }
                }

                // create new
                if($request->replace == 'yes'){
                    // remove all previous stages
                    cultivars::where('cultivars_management_id', $request->cm_id)->delete();
                }
                // readd stages
                
                foreach($stages as $stage){
                    $cultivar = new cultivars;
                    $cultivar->stage_name = $stage['stage_name'];
                    $cultivar->stage_start_date = $stage['stage_start_date'];
                    $cultivar->duration = (int) $stage['duration'];
                    $cultivar->upper = (float) $stage['upper'];
                    $cultivar->lower = (float) $stage['lower'];
                    $cultivar->cultivars_management_id = $request->cm_id;
                    $cultivar->company_id = $request->company_id;
                    $cultivar->save();

                    $stage_names[] = $stage['stage_name'];
                }

                $this->acc->logActivity('Add', 'Cultivar Stages', "Replace Stages: " . implode(',', $stage_names) . " ($cm->crop_name)");

            } else if($request->new == 'no'){

                // permission check
                if(!$this->acc->is_admin){
                    $grants = $this->acc->requestAccess(['Cultivar Stages' => ['p' => ['All'] ] ]);
                    if(empty($grants['Cultivar Stages']['Edit']['O'])){
                        return response()->json(['message' => 'access_denied', 'debug' => "Edit,Cultivar Stages"], 403);
                    }
                }

                // update existing
                foreach($stages as $stage){
                    $stage['cultivars_management_id'] = $request->cm_id;
                    $stage['company_id'] = $request->company_id;
                    cultivars::where('id', $stage['id'])->update($stage);

                    $stage_names[] = $stage['stage_name'];
                }

                $this->acc->logActivity('Edit', 'Cultivar Stages', "Update Stages: " . implode(',', $stage_names) . " ($cm->crop_name)");

            }
        }
        $return = $request->new == 'yes' ? ['status' => 'stages_set', 'grants' => $grants] : ['status' => 'stages_set'];
        return response()->json($return);
    }

    // add `cultivars` record (growth stage)
    public function add_stage(Request $request)
    {
        $request->validate([
            'cm_id' => 'required|exists:cultivars_management,id',
            'stage_name' => 'required',
            'stage_start_date' => 'required',
            'duration' => 'required|integer',
            'upper' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'lower' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'company_id' => 'required|exists:companies,id'
        ]);

        if($this->acc->companyCheckFails($request->company_id)){
            return response()->json(['message' => 'access_denied'], 403);
        }

        $cm = cultivars_management::where('id', $request->cm_id)->first();

        $grants = [];

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess([ 'Cultivar Stages' => ['p' => ['All'] ] ]);
            if(empty($grants['Cultivar Stages']['Add']['C'])){
                return response()->json(['message' => 'access_denied', 'debug' => "Add,Cultivar Stages2"], 403);
            }
        }

        $this->tz = $this->timezones[$this->acc->timezone];
        if(!$this->tz){ $this->tz = 'UTC'; }

        $cultivar = new cultivars();
        
        $cultivar->cultivars_management_id = $request->cm_id;
        $cultivar->stage_name = $request->stage_name;
        $cultivar->stage_start_date = $request->stage_start_date;
        $cultivar->duration = $request->duration;
        $cultivar->lower = $request->lower;
        $cultivar->upper = $request->upper;
        $cultivar->company_id = $request->company_id;
        
        $cultivar->save();

        $this->acc->logActivity('Add', 'Cultivar Stages', "Stage: {$cultivar->stage_name} ({$cm->crop_name})");

        return response()->json([
            'status' => 'stage_added',
            'grants' => $grants
        ]);
    }

    // update existing `cultivars` record (growth stage)
    // also updates succeeding stages if duration/date was changed
    public function update_stages(Request $request)
    {
        $request->validate([
            'stage' => 'required',
            'stage.id' => 'required',
            'stage.cultivars_management_id' => 'required',
            'stage.stage_name' => 'required',
            'stage.stage_start_date' => 'required',
            'stage.duration' => 'required|integer',
            'stage.upper' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'stage.lower' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'stages' => 'required|array'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Cultivar Stages' => ['p' => ['All'] ] ]);
            if(empty($grants['Cultivar Stages']['Edit']['O'])){
                return response()->json(['message' => 'access_denied', 'debug' => "Edit,Cultivar Stages"], 403);
            }
        }

        $this->tz = $this->timezones[$this->acc->timezone];
        if(!$this->tz){ $this->tz = 'UTC'; }

        $curr_stage = $request->stage; // curr = current
        $prev_stage = cultivars::where('id', $curr_stage['id'])->first(); // prev = previously stored in DB (older version)

        $stages = $request->stages;

        $tz = new \DateTimeZone($this->tz);

        $dt_curr = new \DateTime($curr_stage['stage_start_date'], $tz);
        $dt_prev = new \DateTime($prev_stage->stage_start_date, $tz);

        $dt_diff = $dt_prev->diff($dt_curr);

        $diff_days = (int) $dt_diff->format('%r%a');
        $diff_duration = (int) $curr_stage['duration'] - (int) $prev_stage->duration;

        $stage_count = count($stages);

        // update changed stage
        cultivars::where('id', $curr_stage['id'])->update($curr_stage);

        // if there are rolling changes, do them
        if($diff_days || $diff_duration){
            $i = 0;
            for(; $i < $stage_count; $i++){
                if($stages[$i]['id'] == $curr_stage['id']){
                    if($i){ $stages[$i-1]['duration'] += $diff_days; }
                    $i++; break;
                };
            }
            $k = $j = $i;
            if($diff_duration){
                for(; $i < $stage_count; $i++){
                    $dt = new \DateTime($stages[$i]['stage_start_date'], $tz);
                    $dd_duration = abs($diff_duration);
                    $interval = new \DateInterval("P{$dd_duration}D");
                    if($diff_duration > 0){
                        $dt->add($interval);
                    } else {
                        $dt->sub($interval);
                    }
                    $stages[$i]['stage_start_date'] = $dt->format('Y-m-d');
                }
            }
            if($diff_days){
                for(; $j < $stage_count; $j++){
                    $dt = new \DateTime($stages[$j]['stage_start_date'], $tz);
                    $dd_days = abs($diff_days);
                    $interval = new \DateInterval("P{$dd_days}D");
                    if($diff_days > 0){
                        $dt->add($interval);
                    } else {
                        $dt->sub($interval);
                    }
                    $stages[$j]['stage_start_date'] = $dt->format('Y-m-d');
                }
            }
            if($diff_days || $diff_duration){
                for(; $k < $stage_count; $k++){
                    cultivars::where('id', $stages[$k]['id'])->update($stages[$k]);
                }
            }
        }

        $this->acc->logActivity('Edit', 'Cultivar Stages', "Stage: {$curr_stage['stage_name']}");

        return response()->json([
            'status' => 'stages_updated',
            'diff_days' => $diff_days,
            'diff_duration' => $diff_duration
        ]);
    }

    // delete last added `cultivars` record (growth stage)
    public function delete_last_stage(Request $request)
    {
        $request->validate([
            'cm_id' => 'required|exists:cultivars_management,id'
        ]);

        $grants = [];

        $last_stage = cultivars::where('cultivars_management_id', $request->cm_id)->orderBy('id','DESC')->first();
        if($last_stage){

            // permission check
            if(!$this->acc->is_admin){
                $grants = $this->acc->requestAccess(['Cultivar Stages' => ['p' => ['All'], 'o' => $last_stage->id, 't' => 'O'] ]);
                if(empty($grants['Cultivar Stages']['Delete']['O'])){
                    return response()->json(['message' => 'access_denied', 'debug' => "Delete,Cultivar Stages, {$last_stage->id}, O"], 403);
                }
            }

            cultivars::where('id', $last_stage->id)->delete();

            $this->acc->logActivity('Delete', 'Cultivar Stages', "Last Stage: {$last_stage->stage_name} ({$last_stage->id})");

            return response()->json([
                'status' => 'stage_deleted',
                'grants' => $grants
            ]);

        }
        return response()->json([
            'status' => 'stage_delete_error'
        ]);
    }

    // ------------------
    // Cultivar Templates
    // ------------------

    // Load Cultivar Growth Stages Templates
    public function load_templates(Request $request)
    {
        $templates = [];
        $grants = [];

        if($this->acc->is_admin){
            $templates = cultivars_templates::where('company_id', $request->company_id)->get();
            return response()->json(['templates' => $templates, 'x' => 'y']);
        } else {
            $grants = $this->acc->requestAccess(['Cultivar Templates' => ['p' => ['All'] ] ]);
            // if company access, load all templates for company
            // otherwise load groups
            if(!empty($grants['Cultivar Templates']['View']['O']))
            {
                $templates = cultivars_templates::whereIn('id', $grants['Cultivar Templates']['View']['O'])->get();
            }
        }

        $details = !empty($grants['Cultivar Templates']['View']['C']) ?
            ('Company IDs: ' . implode(',', $grants['Cultivar Templates']['View']['C'])) :
            ($this->acc->is_admin ? 'All Objects' : 'Access Denied');

        $this->acc->logActivity('View', 'Cultivar Templates', $details);

        return response()->json(['templates' => $templates ]);
    }

    // Save Cultivar Growth Stages Template (As a JSON String)
    public function save_template(Request $request)
    {
        $saved = false;

        $request->validate([
            'new' => 'required',
            'company_id' => 'required|exists:companies,id'
        ]);

        $grants = [];
        $return = [];

        // Add
        if($request->new == 'yes'){

            $request->validate([
                'template_name' => 'required',
                'stages' => 'required'
            ]);

            // permission check
            if(!$this->acc->is_admin){
                $grants = $this->acc->requestAccess([ 'Cultivar Templates' => ['p' => ['All'] ] ]);
                if(empty($grants['Cultivar Templates']['Add']['C'])){
                    return response()->json(['message' => 'access_denied', 'debug' => "Add,Cultivar Templates"], 403);
                }
            }

            $saved = cultivars_templates::create([
                'user_id' => (int) $this->acc->id,
                'name' => $request->template_name,
                'template' => $request->stages,
                'company_id' => $request->company_id
            ]);

            $this->acc->logActivity('Add', 'Cultivar Templates', "Template Name: {$saved->name}");

            return response()->json(!$saved ? ['status' => 'template_save_error'] : [ 'status' => 'template_saved', 'grants' => $grants ]);

        // Update
        } else {

            $request->validate([
                'id' => 'required|exists:cultivars_templates,id',
                'template_name' => 'required',
                'stages' => 'required'
            ]);

            // permission check
            if(!$this->acc->is_admin){
                $grants = $this->acc->requestAccess([ 'Cultivar Templates' => ['p' => ['Edit'], 'o' => $request->id, 't' => 'O' ] ]);
                if(empty($grants['Cultivar Templates']['Edit']['O'])){
                    return response()->json(['message' => 'access_denied', 'debug' => "Edit,Cultivar Templates,{$request->id},O"], 403);
                }
            }

            $saved = cultivars_templates::where('id', $request->id)->update([
                'template_name' => $request->template_name,
                'template' => $request->stages
            ]);

            $this->acc->logActivity('Edit', 'Cultivar Templates', "Template Name: {$saved->template_name}");

            return response()->json(!$saved ? ['status' => 'template_save_error'] : [ 'status' => 'template_saved' ]);
        }
    }

    public function remove_template(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:cultivars_templates,id'
        ]);

        $grants = [];

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Cultivar Templates' => ['p' => ['All'], 'o' => $request->id, 't' => 'O'] ]);
            if(empty($grants['Cultivar Templates']['Delete']['O'])){
                return response()->json(['message' => 'access_denied', 'debug' => "Delete,Cultivar Templates,{$request->id},O"], 403);
            }
        }

        // confirm template still exists
        $ct = cultivars_templates::where('id', $request->id)->first();
        if(!$ct){ return response()->json(['message' => 'nonexistent']); }

        $ct->delete();

        $this->acc->logActivity('Delete', 'Cultivar Templates', "Template Name: {$ct->name} ({$ct->id})");

        return response()->json([
            'status' => 'template_removed',
            'grants' => $grants
        ]);
    }
}
