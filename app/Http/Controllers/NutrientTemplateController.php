<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Models\nutrient_templates;
use App\Models\nutrient_template_data;
use App\Models\fields;
use App\User;

class NutrientTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('cors');
        $this->middleware(function ($request, $next) {
            $this->acc = Auth::user();
            return $next($request);
        });
    }

    // Load Nutrient Growth Stages Templates
    public function load_templates(Request $request, $company_id)
    {
        $templates = [];
        $grants = [];

        if ($this->acc->is_admin && $company_id) {
            $templates = nutrient_templates::where('company_id', $company_id)->get();
            if ($templates->count()) {
                foreach ($templates as $index => $tpl) {
                    $templates[$index]['user_name'] = User::where('id', $tpl->user_id)->value('name');
                }
            }
            return response()->json(['templates' => $templates]);
        } else {
            $grants = $this->acc->requestAccess(['Nutrient Templates' => ['p' => ['All']]]);
            // if company access, load all templates for company
            // otherwise load groups
            if (!empty($grants['Nutrient Templates']['View']['O'])) {
                $templates = nutrient_templates::whereIn('id', $grants['Nutrient Templates']['View']['O'])->get();
                if ($templates->count()) {
                    foreach ($templates as $index => $tpl) {
                        $templates[$index]['user_name'] = User::where('id', $tpl->user_id)->value('name');
                    }
                }
            }
        }

        $details = !empty($grants['Nutrient Templates']['View']['C']) ?
            ('Company IDs: ' . implode(',', $grants['Nutrient Templates']['View']['C'])) : ($this->acc->is_admin ? 'All Objects' : 'Access Denied');

        $this->acc->logActivity('View', 'Nutrient Templates', $details);

        return response()->json(['templates' => $templates]);
    }

    // Save Nutrient Growth Stages Template (As a JSON String)
    public function save_template(Request $request)
    {
        $request->validate([
            'new' => 'required',
            'company_id' => 'required|exists:companies,id'
        ]);



        $grants = [];

        // Add
        if ($request->new == 'yes') {

            $request->validate([
                'name'     => 'required',
                'template' => 'required'
            ]);

            // permission check
            if (!$this->acc->is_admin) {
                $grants = $this->acc->requestAccess(['Nutrient Templates' => ['p' => ['All']]]);
                if (empty($grants['Nutrient Templates']['Add']['C'])) {
                    return response()->json(['message' => 'access_denied', 'debug' => "Add,Nutrient Templates"], 403);
                }
            }

            $data = json_decode($request->template, true);
            // Ensure Valid JSON
            if ($data === null) {
                return response()->json(['message' => 'invalid_json']);
            }
            // Unset ID field for new Templates
            if (array_key_exists('id', $data)) {
                unset($data['id']);
            }
            // Convert back to JSON
            $json = json_encode($data);

            // Prevent Manually Creating Default Template
            if ($request->name == 'Default Template') {
                return response()->json(['message' => 'access_denied'], 403);
            }

            $tpl = new nutrient_templates();
            $tpl->name = $request->name;
            $tpl->template = $json;
            $tpl->company_id = $request->company_id;
            $tpl->user_id = (int) $this->acc->id;
            $created = $tpl->save();

            if ($created) {
                $this->acc->logActivity('Add', 'Nutrient Templates', "Template Name: {$tpl->name}");
            }

            return response()->json(!$created ? ['status' => 'template_save_error'] : ['status' => 'template_saved', 'grants' => $grants]);

            // Update
        } else {

            $request->validate([
                'id'       => 'required|exists:nutrient_templates,id',
                'name'     => 'required',
                'template' => 'required'
            ]);

            // permission check
            if (!$this->acc->is_admin) {
                $grants = $this->acc->requestAccess(['Nutrient Templates' => ['p' => ['Edit'], 'o' => $request->id, 't' => 'O']]);
                if (empty($grants['Nutrient Templates']['Edit']['O'])) {
                    return response()->json(['message' => 'access_denied', 'debug' => "Edit,Nutrient Templates,{$request->id},O"], 403);
                }
            }

            // Prevent Saving/Changing Default Template
            $tpl = nutrient_templates::where('id', $request->id)->first();

            if ($tpl && $tpl->name == 'Default Template') {
                return response()->json(['message' => 'access_denied'], 403);
            }

            // Ensure Valid JSON
            $data = json_decode($request->template, true);
            if ($data === null) {
                return response()->json(['message' => 'invalid_json']);
            }
            // Unset ID field for new Templates
            if (array_key_exists('id', $data)) {
                unset($data['id']);
            }
            // Convert back to JSON
            $json = json_encode($data);

            // Update Fields
            $tpl->name = $request->name;
            $tpl->template = $json;
            $updated = $tpl->save();

            if ($updated) {
                $this->acc->logActivity('Edit', 'Nutrient Templates', "Template Name: {$tpl->name}");
            }

            return response()->json(!$updated ? ['status' => 'template_save_error'] : ['status' => 'template_saved']);
        }
    }

    public function remove_template(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:nutrient_templates,id'
        ]);

        $grants = [];

        // permission check
        if (!$this->acc->is_admin) {
            $grants = $this->acc->requestAccess(['Nutrient Templates' => ['p' => ['All'], 'o' => $request->id, 't' => 'O']]);
            if (empty($grants['Nutrient Templates']['Delete']['O'])) {
                return response()->json(['message' => 'access_denied', 'debug' => "Delete,Nutrient Templates,{$request->id},O"], 403);
            }
        }

        // confirm template still exists
        $ct = nutrient_templates::where('id', $request->id)->first();
        if (!$ct) {
            return response()->json(['message' => 'nonexistent']);
        }

        $ct->delete();

        $this->acc->logActivity('Delete', 'Nutrient Templates', "Template Name: {$ct->name} ({$ct->id})");

        return response()->json([
            'status' => 'template_removed',
            'grants' => $grants
        ]);
    }

    public function apply_template(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:nutrient_templates,id',
            'node_address' => 'required'
        ]);

        // permission check
        if (!$this->acc->is_admin) {
            $grants = $this->acc->requestAccess(['Nutrient Templates' => ['p' => ['Edit'], 'o' => $request->id, 't' => 'O']]);
            // Apply == Edit in this exceptional case
            if (empty($grants['Nutrient Templates']['Edit']['O'])) {
                return response()->json(['message' => 'access_denied', 'debug' => "Apply,Nutrient Templates,{$request->id},O"], 403);
            }
        }

        fields::where('node_id', $request->node_address)->update([
            'nutrient_template_id' => $request->id
        ]);

        return response()->json([
            'status' => 'template_applied',
            /*'grants' => $grants*/
        ]);
    }

    //for pickybacking
    public function loadnodedata($node_address)
    {

        $node_data = nutri_data::where('node_address', '=', $node_address)->first();
        $node_data = collect($node_data);
        return response()->json($node_data);
    }

    public function loadNutriTemplateData(Request $request)
    {
        $count = nutrient_template_data::where('nutriprobe', $request->nutriprobe)->count();

        if ($count) {
            $item = nutrient_template_data::where('nutriprobe', $request->nutriprobe)->first();
            $item->makeHidden(['id', 'nutriprobe']);

            return json_encode($item);
        }
    }


    public function saveNutriTemplateDataGroup(Request $request)
    {
        $count = nutrient_template_data::where('nutriprobe', $request->nutriprobe)->count();

        if ($count) {
            $item = nutrient_template_data::where('nutriprobe', $request->nutriprobe)->first();


            $item->M3_1_GROUP = $request->nutrient_groups['M3_1'];
            $item->M3_2_GROUP = $request->nutrient_groups['M3_2'];
            $item->M3_3_GROUP = $request->nutrient_groups['M3_3'];
            $item->M3_4_GROUP = $request->nutrient_groups['M3_4'];

            $item->M4_1_GROUP = $request->nutrient_groups['M4_1'];
            $item->M4_2_GROUP = $request->nutrient_groups['M4_2'];
            $item->M4_3_GROUP = $request->nutrient_groups['M4_3'];
            $item->M4_4_GROUP = $request->nutrient_groups['M4_4'];

            $item->M5_1_GROUP = $request->nutrient_groups['M5_1'];
            $item->M5_2_GROUP = $request->nutrient_groups['M5_2'];
            $item->M5_3_GROUP = $request->nutrient_groups['M5_3'];
            $item->M5_4_GROUP = $request->nutrient_groups['M5_4'];

            $item->M6_1_GROUP = $request->nutrient_groups['M6_1'];
            $item->M6_2_GROUP = $request->nutrient_groups['M6_2'];
            $item->M6_3_GROUP = $request->nutrient_groups['M6_3'];
            $item->M6_4_GROUP = $request->nutrient_groups['M6_4'];

            $item->save();
        }
        return response()->json([
            'status' => 'nutrient_sensor_group_type_save',

        ]);
    }

    public function saveNutriTemplateData(Request $request)
    {
        $count = nutrient_template_data::where('nutriprobe', $request->nutriprobe)->count();

        if ($count > 0) {
            $item = nutrient_template_data::where('nutriprobe', $request->nutriprobe)->first();


            $item->M3_1 = $request->sensor_types['M3_1'];
            $item->M3_2 = $request->sensor_types['M3_2'];
            $item->M3_3 = $request->sensor_types['M3_3'];
            $item->M3_4 = $request->sensor_types['M3_4'];

            $item->M4_1 = $request->sensor_types['M4_1'];
            $item->M4_2 = $request->sensor_types['M4_2'];
            $item->M4_3 = $request->sensor_types['M4_3'];
            $item->M4_4 = $request->sensor_types['M4_4'];

            $item->M5_1 = $request->sensor_types['M5_1'];
            $item->M5_2 = $request->sensor_types['M5_2'];
            $item->M5_3 = $request->sensor_types['M5_3'];
            $item->M5_4 = $request->sensor_types['M5_4'];

            $item->M6_1 = $request->sensor_types['M6_1'];
            $item->M6_2 = $request->sensor_types['M6_2'];
            $item->M6_3 = $request->sensor_types['M6_3'];
            $item->M6_4 = $request->sensor_types['M6_4'];

            $item->save();

            log::debug($item);
        } else {
            $item = new nutrient_template_data();
            $item->nutriprobe = $request->nutriprobe;

            isset($request->sensor_types['M3_1']) ? $item->M3_1 = $request->sensor_types['M3_1'] : 0;
            isset($request->sensor_types['M3_2']) ? $item->M3_2 = $request->sensor_types['M3_2'] : 0;
            isset($request->sensor_types['M3_3']) ? $item->M3_3 = $request->sensor_types['M3_3'] : 0;
            isset($request->sensor_types['M3_4']) ? $item->M3_4 = $request->sensor_types['M3_4'] : 0;

            isset($request->sensor_types['M4_1']) ? $item->M4_1 = $request->sensor_types['M4_1'] : 0;
            isset($request->sensor_types['M4_2']) ? $item->M4_2 = $request->sensor_types['M4_2'] : 0;
            isset($request->sensor_types['M4_3']) ? $item->M4_3 = $request->sensor_types['M4_3'] : 0;
            isset($request->sensor_types['M4_4']) ? $item->M4_4 = $request->sensor_types['M4_4'] : 0;

            isset($request->sensor_types['M5_1']) ? $item->M5_1 = $request->sensor_types['M5_1'] : 0;
            isset($request->sensor_types['M5_2']) ? $item->M5_2 = $request->sensor_types['M5_2'] : 0;
            isset($request->sensor_types['M5_3']) ? $item->M5_3 = $request->sensor_types['M5_3'] : 0;
            isset($request->sensor_types['M5_4']) ? $item->M5_4 = $request->sensor_types['M5_4'] : 0;

            isset($request->sensor_types['M6_1']) ? $item->M6_1 = $request->sensor_types['M6_1'] : 0;
            isset($request->sensor_types['M6_2']) ? $item->M6_2 = $request->sensor_types['M6_2'] : 0;
            isset($request->sensor_types['M6_3']) ? $item->M6_3 = $request->sensor_types['M6_3'] : 0;
            isset($request->sensor_types['M6_4']) ? $item->M6_4 = $request->sensor_types['M6_4'] : 0;


            $item->save();

            log::debug($item);
        }
        return response()->json([
            'status' => 'nutrient_sensor_type_save',

        ]);
    }
    public function loadNutrientTemplate(Request $request, $id)
    {

        $polynomials = nutrient_templates::where('id', $id)->first();
        $data = json_decode($polynomials);
        $data = json_decode($data->template);
        return response()->json([
            'polynomials' => $data,

        ]);
    }
}
