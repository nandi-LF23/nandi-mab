<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;

use App\Models\Role;
use App\Models\SecurityTemplate;
use App\Models\SecurityRule;
use App\Models\Subsystem;
use App\Models\Permission;

class SecurityTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('cors');
        $this->middleware(function ($request, $next) { $this->acc = Auth::user(); return $next($request); });
    }

    // fetch
    public function get()
    {
        $templates = [];
        $grants = [];

        if($this->acc->is_admin){
            
            $templates = SecurityTemplate::select(
                'security_templates.id',
                'security_templates.template_name',
                'security_templates.company_id',
                'companies.company_name'
            )
            ->join('companies', 'security_templates.company_id', 'companies.id')
            ->get();

        } else {
            $grants = $this->acc->requestAccess(['Security Templates' => ['p' => ['All'] ] ]);
            if(!empty($grants['Security Templates']['View']['O']))
            {
                $templates = SecurityTemplate::select(
                    'security_templates.id',
                    'security_templates.template_name',
                    'security_templates.company_id',
                    'companies.company_name'
                )
                ->whereIn('security_templates.id', $grants['Security Templates']['View']['O'])
                ->join('companies', 'security_templates.company_id', 'companies.id')
                ->get();
            }
        }

        $tpls = [];

        if($templates){
            foreach($templates as $tpl){
                $tpls[] = [
                    'id' => $tpl->id,
                    'name' => $tpl->template_name,
                    'company_name' => $tpl->company_name,
                    'company_id' => $tpl->company_id
                ];
            }
        }

        $details = !empty($grants['Security Templates']['View']['C']) ?
            ('Company IDs: ' . implode(',', $grants['Security Templates']['View']['C'])) :
            ($this->acc->is_admin ? 'All Objects' : 'Access Denied');

        $this->acc->logActivity('View', 'Security Templates', $details);

        return response()->json(['templates' => $tpls]);
    }

    // apply a security template to a role (replacing it's rules) (sec_tpl_apply)
    public function apply(Request $request)
    {
        $request->validate([
            'id'      => 'required|integer',
            'role_id' => 'required|exists:roles,id'
        ]);

        if(!$this->acc->is_admin){
            // permission check
            $grants = $this->acc->requestAccess(['Security Rules' => ['p' => ['All'] ] ]);
            // Need 'Add' and 'Delete' to be able to apply Security Templates
            if( empty($grants['Security Rules']['Add']['C']) || 
                empty($grants['Security Rules']['Delete']['C'])
            ){ return response()->json(['message' => 'access_denied'], 403); }
        }

        $sec_template = SecurityTemplate::where('id', $request->id)->first();
        if(!$sec_template){
            return response()->json(['message' => 'nonexistent']);
        }

        $subsystems_by_name  = Subsystem::all()->keyBy('subsystem_name')->toArray();
        $permissions_by_name = Permission::all()->keyBy('permission_name')->toArray();

        $convertRulesToApplicationFormat = function($rules) use ($subsystems_by_name, $permissions_by_name)
        {
            $apply_rules = [];

            foreach($rules as $rule){

                $subsystem_id = $subsystems_by_name[ $rule['subsystem'] ]['id'];
                $permission_ids = [];

                foreach($rule['permissions'] as $perm_name){
                    $permission_ids[] = $permissions_by_name[ $perm_name ]['id'];
                }

                $apply_rules[] = [
                    'subsystem_id'   => $subsystem_id,
                    'permission_ids' => $permission_ids
                ];
            }

            return $apply_rules;
        };

        // get role's associated company id
        $role_id = $request->role_id;
        $role = Role::where('id', $role_id)->first();
        $company_id = $role->company_id;

        // delete all previous rules
        SecurityRule::where('role_id', $role_id)->delete();

        $template_rules = $convertRulesToApplicationFormat(json_decode($sec_template->template_data, true));

        foreach($template_rules as $r){

            // create new security Rules
            $rule = new SecurityRule();
            $rule->company_id = $company_id;
            $rule->role_id = $role_id;
            $rule->subsystem_id = $r['subsystem_id'];
            $rule->save();

            // attach permissions
            foreach($r['permission_ids'] as $perm_id){
                DB::table('security_rules_permissions')->insert([
                    'security_rule_id' => $rule->id,
                    'permission_id' => $perm_id
                ]);
            }

            // attach role's company
            DB::table('security_rules_companies')->insert([
                'security_rule_id' => $rule->id,
                'company_id' => $company_id
            ]);
        }

        // update permission cache
        $rc = SecurityRule::getCompanyRules($company_id);
        Cache::forever(config('mab.instance')."_mab_perms_{$company_id}", $rc);

        $details = "Apply Template: T: {$sec_template->template_name} R: {$role->role_name}";
        $this->acc->logActivity('Add', 'Security Rules', $details);

        return response()->json(['message' => 'template_applied']);
    }

    // add/update
    public function save(Request $request)
    {
        $request->validate([
            'role_id'             => 'required|exists:roles,id',
            'template.name'       => 'required|string',
            'template.rules'      => 'required|array'
        ]);

        $role = Role::where('id', $request->role_id)->first();
        if(!$role){ return response()->json(['message' => 'nonexistent']); }

        // get company id via role
        $company_id = $role->company_id;

        $subsystems_by_id  = Subsystem::all()->keyBy('id')->toArray();
        $permissions_by_id = Permission::all()->keyBy('id')->toArray();

        // convert to template friendly format first
        $convertRulesToStorageFormat = function($rules) use ($subsystems_by_id, $permissions_by_id)
        {
            $store_rules = [];

            if($rules){
                foreach($rules as $rule){

                    $subsystem_name = $subsystems_by_id[ $rule['subsystem_id'] ]['subsystem_name'];
                    $permission_names = [];

                    foreach($rule['permission_ids'] as $perm_id){
                        $permission_names[] = $permissions_by_id[ $perm_id ]['permission_name'];
                    }

                    $store_rules[] = [
                        'subsystem'   => $subsystem_name,
                        'permissions' => $permission_names
                    ];
                }
            }

            return $store_rules;
        };

        $sec_template = SecurityTemplate::where('template_name', $request->template['name'])
        ->where('company_id', $company_id)->first();

        $stored_rules = json_encode($convertRulesToStorageFormat($request->template['rules']));

        if(!$sec_template){

            if(!$this->acc->is_admin){
                $grants = $this->acc->requestAccess(['Security Templates' => ['p' => ['Add'], 'o' => $company_id, 't' => 'C' ] ]);
                if(empty($grants['Security Templates']['Add']['C'])){
                    return response()->json(['message' => 'access_denied'], 403);
                }
            }

            // new (add)
            $sec_template = SecurityTemplate::create([
                'template_name' => $request->template['name'],
                'company_id'    => $company_id,
                'template_data' => $stored_rules
            ]);

            $this->acc->logActivity('Add', 'Security Templates', "{$sec_template->template_name} ({$sec_template->id})");

        } else {

            if(!$this->acc->is_admin){
                $grants = $this->acc->requestAccess(['Security Templates' => ['p' => ['Edit'], 'o' => $sec_template->id, 't' => 'O' ] ]);
                if(empty($grants['Security Templates']['Add']['C'])){
                    return response()->json(['message' => 'access_denied'], 403);
                }
            }

            // existing (update)
            $sec_template->template_name = $request->template['name'];
            $sec_template->company_id    = $company_id;
            $sec_template->template_data = $stored_rules;
            $sec_template->save();

            $this->acc->logActivity('Edit', 'Security Templates', "{$sec_template->template_name} ({$sec_template->id})");
        }

        return response()->json([ 'message' => 'template_saved' ]);
    }

    // remove
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:security_templates,id'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Security Templates' => ['p' => ['All'], 'o' => $request->id, 't' => 'O'] ]);
            if(empty($grants['Security Templates']['Delete']['O'])){ return response()->json(['message' => 'access_denied']); }
        }

        $sec_template = SecurityTemplate::where('id', $request->id)->first();
        if(!$sec_template){ return response()->json(['message' => 'nonexistent']); }
        $sec_template->delete();

        $this->acc->logActivity('Delete', 'Security Templates', "{$sec_template->template_name} ({$sec_template->id})");

        return response()->json([
            'message' => 'template_removed'
        ]);
    }
}