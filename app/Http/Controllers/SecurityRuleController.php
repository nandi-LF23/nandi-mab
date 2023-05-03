<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use App\Models\Company;
use App\Models\Role;
use App\Models\Group;
use App\Models\Subsystem;
use App\Models\SecurityRule;
use App\Models\Permission;
use App\User;

class SecurityRuleController extends Controller
{
    public static $restricted_subsystems = [
        'Security Rules',
        'Security Templates',
        'Nutrients',
        'Nutrient Templates'
    ];

    public function __construct()
    {
        $this->middleware('cors');
        $this->middleware(function ($request, $next) { $this->acc = Auth::user(); return $next($request); });
    }

    // should maybe called 'list' to fit companies & other
    public static function getSubsystems(Request $request)
    {
        $acc = Auth::user();

        if($acc->is_admin){
            $ss = Subsystem::select('id', 'subsystem_name')->get();
        } else {
            $ss = Subsystem::select('id', 'subsystem_name')
            ->whereNotIn('subsystem_name', self::$restricted_subsystems)->get();
        }
        
        $subsystems = [];
        
        foreach($ss as $s){ $subsystems[$s->id] = $s->toArray(); }

        return response()->json([
            'subsystems' => $subsystems
        ]); 
    }

    public static function getSubsystemMeta()
    {
        $acc = Auth::user();
        
        $authorized_subsystems = Subsystem::get(['id', 'subsystem_name'])->keyBy('id');

        if(!$acc->is_admin){
            $authorized_subsystems = $authorized_subsystems->filter(function($v, $k){
                return !in_array($v['subsystem_name'], self::$restricted_subsystems ); 
            });
        }

        foreach($authorized_subsystems as &$s){
            // Remove 'Add' and 'Delete' for Entities for Non-Admins
            if($s->subsystem_name == 'Entities' && !$acc->is_admin){
                $s->permissions = $s->permissions()->whereNotIn('permission_name', ['Add','Delete'])->get();
            } else {
                $s->permissions = $s->permissions()->get();
            }
        }

        return $authorized_subsystems;
    }

    // role_rules_get: get permission rules by role (role_id)
    public function get(Request $request)
    {
        $grants = [];
        $rules  = [];
        $choosableCompanies = [];
        $choosableGroups = [];

        if(empty($request->role_id)){ return response()->json(['message' => 'missing_param'], 422); }

        $subsystem_meta = $this->getSubsystemMeta();

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess([
                'Roles' => ['p' => ['All'] ], /* Also needed for frontend */
                'Users' => ['p' => ['All'] ], /* Also needed for frontend */
                'Security Rules' => ['p' => ['All'] ], /* Also needed for frontend */
                'Security Templates' => ['p' => ['All'] ] /* Also needed for frontend */
            ]);

            if(empty($grants['Security Rules']['View']['O'])){
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // CHOOSABLE LIMITS

        $all_companies = Company::select([
            'companies.id AS id',               // id
            'companies.company_name AS label'   // label
        ])->orderBy('company_name')->get();

        foreach($subsystem_meta as &$s){
            if($this->acc->is_admin){
                $choosableCompanies = $all_companies->toArray();
                $choosableGroups    = $s->groups() // groups by subsystem
                ->join('companies', 'groups.company_id', '=', 'companies.id')
                ->get([
                    'groups.id AS id',                  // id
                    'groups.group_name AS label',       // label
                    'companies.company_name AS meta',   // meta
                    'groups.company_id AS company_id'   // company_id
                ])->toArray();

            } else if(!empty($grants['Security Rules']['Edit']['C'])){
                
                // get companies by allowed sec rule limits */
                $choosableCompanies = Company::whereIn('id', $grants['Security Rules']['View']['C'])
                ->get([
                    'companies.id AS id',               // id
                    'companies.company_name AS label'   // label
                ])->toArray();

                // groups by allowed company ids
                $choosableGroups = $s->groups()
                ->join('companies', 'groups.company_id', '=', 'companies.id')
                ->whereIn('company_id', $grants['Security Rules']['View']['C'])
                ->get([
                    'groups.id AS id',                  // id
                    'groups.group_name AS label',       // label
                    'companies.company_name AS meta',   // meta
                    'groups.company_id AS company_id'   // company_id
                ])->toArray();
            }

            // Add 'type' to each object
            // Add prefix to each id (to 'namespace' them) (required since same dropdown)
            if($choosableCompanies){
                foreach($choosableCompanies as &$c){
                    $c['id']   = 'c' . $c['id'];
                    $c['type'] = 'company';
                }
            }

            if($choosableGroups){
                foreach($choosableGroups as &$g){
                    $g['id']   = 'g' . $g['id'];
                    $g['type'] = 'group';
                }
            }

            // Front-end Limit Object Structure
            $s->limits = [
                'companies' => $choosableCompanies,
                'groups' => $choosableGroups
            ];
        }

        $subsystem_meta = $subsystem_meta->toArray();

        if($this->acc->is_admin){
            $rules = SecurityRule::where("role_id", $request->role_id)->orderBy('subsystem_id')->get();
        } else {
            // get rules limited by subsystem and security rule objects
            $rules = SecurityRule::where("role_id", $request->role_id)
            ->whereIn('id', $grants['Security Rules']['View']['O'])
            ->whereIn('subsystem_id', array_map(function($s){ return $s['id']; }, $subsystem_meta)) // limit sec rules by allowed subsystems
            ->orderBy('subsystem_id')
            ->get();
        }

        // CHOSEN LIMITS

        if(!$rules->isEmpty()){

            foreach($rules as &$rule)
            {
                // SUBSYSTEM
                $rule->subsystem->toArray();

                // PERMISSIONS
                $rule->permissions->toArray();

                // LIMITS
                $chosenCompanies = $rule->companies()->get([
                    'companies.id AS id',               // id
                    'companies.company_name AS label'   // label
                ])->toArray();

                $chosenGroups = $rule->groups()
                ->join('companies', 'groups.company_id', '=', 'companies.id')->get([
                    'groups.id AS id',                  // id
                    'groups.group_name AS label',       // label
                    'companies.company_name AS meta',   // meta
                    'groups.company_id AS company_id'   // company_id
                ])->toArray();

                if($chosenCompanies){
                    foreach($chosenCompanies as &$c){
                        $c['id'] = 'c' . $c['id'];
                        $c['type'] = 'company';
                    }
                }

                if($chosenGroups){
                    foreach($chosenGroups as &$g){
                        $g['id'] = 'g' . $g['id'];
                        $g['type'] = 'group';
                    }
                }

                // have to be merged to be in the same dropdown's v-model
                $rule->limits = array_merge($chosenCompanies, $chosenGroups);

                // Merge Choosable Companies with Chosen Companies (+Super Admin Injected Companies)
                $subsystem_meta[$rule->subsystem['id']]['limits']['companies'] = array_column(
                    array_merge(
                        $subsystem_meta[$rule->subsystem['id']]['limits']['companies'],
                        $chosenCompanies
                    ),
                    NULL,
                    'id'
                );

                // Merge Choosable Groups with Chosen Groups (+Super Admin Injected Groups)
                $subsystem_meta[$rule->subsystem['id']]['limits']['groups'] = array_column(
                    array_merge(
                        $subsystem_meta[$rule->subsystem['id']]['limits']['groups'],
                        $chosenGroups
                    ),
                    NULL,
                    'id'
                );
            }
            $rules = $rules->toArray();

        }

        // logging
        $details = !empty($grants['Security Rules']['View']['C']) ?
        ('Company IDs: ' . implode(',', $grants['Security Rules']['View']['C'])) :
        ($this->acc->is_admin ? 'All Objects' : 'Access Denied');
        $this->acc->logActivity('View', 'Security Rules', $details);
        
        $output = [
            'security_rules' => $rules,          /* Existing Rules */
            'subsystem_meta' => $subsystem_meta, /* Rule Spec */
            'grants'         => $grants
        ];

        return response()->json($output);
    }

    // role_rule_add: add a new security rule (by role)
    public function add(Request $request)
    {
        $request->validate([
            'role_id'     => 'required|integer|min:1',
            'subsystem'   => 'required|array',
            'permissions' => 'required|array',
            'limits'      => 'required|array'
        ]);

        // util: in_array for array of assoc arrays (objects)
        $in_array_assoc = function($array, $key, $vals){
            if(empty($array)) return false;
            foreach($array as $row){
                $i = (array) $row;
                foreach($i as $k => $v){
                    if($k == $key && in_array($i[$k], $vals)){
                        return true;
                    } 
                }
            }
            return false;
        };

        // ROLE integrity check: ensure role exists
        $role = Role::where('id', $request->role_id)->first();
        if(!$role){ return response()->json(['message' => 'access_denied', 'ref' => 'R'], 403); }
        
        $grants = [];

        if(!$this->acc->is_admin){
            // permission check
            $grants = $this->acc->requestAccess(['Security Rules' => ['p' => ['Add'] ] ]);
            if(empty($grants['Security Rules']['Add']['C'])){
                return response()->json(['message' => 'access_denied', 'ref' => 'C1'], 403);
            }
            // COMPANY security check: ensure the role being added to is in the authorized list of companies
            if(!in_array($role->company_id, $grants['Security Rules']['Add']['C'])){
                return response()->json(['message' => 'access_denied', 'ref' => 'C2'], 403);
            }
        }
        
        $allowed_subsystem_ids = DB::table('subsystems')->pluck('id')->whereNotIn('subsystem_name', self::$restricted_subsystems)->toArray();
        $all_permission_verbs  = array_map(function($i){ return $i['permission_name'];}, $this->acc->permissions_by_id());

        // required for modification (cannot modify request directly)
        $perms  = $request->permissions;
        $limits = $request->limits;

        // SUBSYSTEM integrity check: ensure subsystem is within range
        if(
            empty($request->subsystem['id']) ||
            !in_array($request->subsystem['id'], $allowed_subsystem_ids)
        ){
            return response()->json(['message' => 'access_denied', 'ref' => 'S1'], 403);
        }

        // PERMISSIONS security check: ensure valid permission verbs
        foreach($perms as $perm){
            if(empty($perm['permission_name']) || !in_array($perm['permission_name'], $all_permission_verbs)){
                return response()->json(['message' => 'access_denied', 'ref' => 'P'], 403);
            }
        }

        foreach($limits as &$item){
            // LIMITS remove prefix
            $item['id'] = (int) str_ireplace(['g','c'], '', $item['id']);
            // LIMITS integrity check: ensure items have only two types: company / group
            if(empty($item['type']) || !in_array($item['type'], ['group','company'])){
                return response()->json(['message' => 'access_denied', 'ref' => 'T'], 403);
            }
            // LIMITS security check: ensure limits are WITHIN limits of grant objects
            if(!$this->acc->is_admin){
                $granted_company_ids = $grants['Security Rules']['Add']['C'];
                // get granted companies' inferred group ids
                $inferred_group_ids  = Group::whereIn('company_id', $granted_company_ids)->pluck('id')->toArray();
                if($item['type'] == 'company' && !in_array($item['id'], $granted_company_ids)){
                    return response()->json(['message' => 'access_denied', 'ref' => 'C3'], 403);
                } else if($item['type'] == 'group' && !in_array($item['id'], $inferred_group_ids)){
                    return response()->json(['message' => 'access_denied', 'ref' => 'G'], 403);
                }
            }
        }

        // add parent row
        $rule = new SecurityRule();
        $rule->company_id   = $role->company_id;
        $rule->role_id      = $role->id;
        $rule->subsystem_id = $request->subsystem['id'];
        $rule->save();

        // Streamline: Automatically add 'View' permission when 'Add'/'Edit'/'Delete' verbs were selected
        if(
            // if permissio doesn't include a specified 'View' verb
            !$in_array_assoc($perms, 'permission_name', ['View']) &&
            // but it does include any of these
             $in_array_assoc($perms, 'permission_name', ['Add', 'Edit', 'Delete'])
        ){
            // then Add a View permission verb
            array_unshift($perms, [
                'id' => Permission::where('permission_name', 'View')->pluck('id')->first(),
                'permission_name' => 'View'
            ]);
        }

        // handle permissions
        foreach($perms as $perm){
            // add permissions child rows
            DB::table('security_rules_permissions')->insert([
                'security_rule_id' => $rule->id,
                'permission_id' => $perm['id']
            ]);
        }

        // handle companies and groups
        foreach($limits as $lim){
            if($lim['type'] == 'company'){
                // add company child row
                DB::table('security_rules_companies')->insert([
                    'security_rule_id' => $rule->id,
                    'company_id' => $lim['id']
                ]);
            } else if($lim['type'] == 'group'){
                // add group child row
                DB::table('security_rules_groups')->insert([
                    'security_rule_id' => $rule->id,
                    'group_id' => $lim['id']
                ]);
            }
        }

        // update permission cache
        $rc = SecurityRule::getCompanyRules($rule->company_id);
        Cache::forever(config('mab.instance')."_mab_perms_{$rule->company_id}", $rc);

        // pabbrs = permission verb abbreviations (first letter of each perm verb)
        $pabbrs = implode(',', array_map(function($i){ return $i['permission_name'][0]; }, $request->permissions));
        $module = $request->subsystem['subsystem_name'];
        $limits = implode(',', array_map(function($i){ return $i['label']; }, $request->limits));
        $this->acc->logActivity('Add', 'Security Rules', "R: {$role->role_name} M: ({$module}) P: ($pabbrs) L: ($limits) ($rule->id)");

        return response()->json([
            'message' => 'rule_added',
            'rules' => $rc
        ]);
    }

    // role_rule_update: update existing permission rule (by role)
    public function update(Request $request)
    {
        $request->validate([
            'id'          => 'required|integer|min:1',
            'subsystem'   => 'required|array',
            'permissions' => 'required|array',
            'limits'      => 'required|array'
        ]);

        $allowed_subsystem_ids = DB::table('subsystems')->pluck('id')->whereNotIn('subsystem_name', self::$restricted_subsystems)->toArray();
        $all_permission_verbs  = array_map(function($i){ return $i['permission_name'];}, $this->acc->permissions_by_id());
        // required for modification (cannot modify request directly)
        $perms  = $request->permissions;
        $limits = $request->limits;
        $grants = [];

        // RULE integrity check: ensure rule exists
        $rule = SecurityRule::where('id', $request->id)->first();
        if(!$rule){ return response()->json(['message' => 'access_denied', 'ref' => 'R1'], 403); }

        // ROLE integrity check: ensure role exists
        $role = Role::where('id', $rule->role_id)->first();
        if(!$role){ return response()->json(['message' => 'access_denied', 'ref' => 'R2'], 403); }
        
        if(!$this->acc->is_admin){
            // permission check
            $grants = $this->acc->requestAccess(['Security Rules' => ['p' => ['All'] ] ]);
            if(
                empty($grants['Security Rules']['Edit']['O']) ||
                !in_array($request->id, $grants['Security Rules']['Edit']['O'])
            ){
                return response()->json(['message' => 'access_denied', 'ref' => 'C1'], 403);
            }
            // COMPANY security check: ensure the role being edited is included in the authorized list of companies
            if(!in_array($role->company_id, $grants['Security Rules']['Edit']['C'])){
                return response()->json(['message' => 'access_denied', 'ref' => 'C2'], 403);
            }
        }

        // SUBSYSTEM integrity check: ensure subsystem is within range
        if(empty($request->subsystem['id']) || !in_array($request->subsystem['id'], $allowed_subsystem_ids)){
            return response()->json(['message' => 'access_denied', 'ref' => 'S1'], 403);
        }

        // PERMISSION security check: ensure valid permission verbs
        foreach($perms as $perm){
            if(empty($perm['permission_name']) || !in_array($perm['permission_name'], $all_permission_verbs)){
                return response()->json(['message' => 'access_denied', 'ref' => 'P'], 403);
            }
        }

        foreach($limits as &$item){
            // LIMITS remove prefix
            $item['id'] = (int) str_ireplace(['g','c'], '', $item['id']);
            // LIMITS integrity check: ensure items have only two types: company / group
            if(empty($item['type']) || !in_array($item['type'], ['group','company'])){
                return response()->json(['message' => 'access_denied', 'ref' => 'T'], 403);
            }
            // LIMITS security check: ensure limits are WITHIN limits of grant objects
            if(!$this->acc->is_admin){
                $granted_company_ids = $grants['Security Rules']['Edit']['C'];
                // get granted companies' inferred group ids
                $inferred_group_ids  = Group::whereIn('company_id', $granted_company_ids)->pluck('id')->toArray();
                if($item['type'] == 'company' && !in_array($item['id'], $granted_company_ids)){
                    return response()->json(['message' => 'access_denied', 'ref' => 'C3'], 403);
                } else if($item['type'] == 'group' && !in_array($item['id'], $inferred_group_ids)){
                    return response()->json(['message' => 'access_denied', 'ref' => 'G'], 403);
                }
            }
        }

        // shortcut: optimize later. Do diffing instead of delete+add.
        DB::table('security_rules_permissions')->where('security_rule_id', $request->id)->delete();
        DB::table('security_rules_companies')->where('security_rule_id', $request->id)->delete();
        DB::table('security_rules_groups')->where('security_rule_id', $request->id)->delete();

        // insert new permission relations
        foreach($perms as $perm){
            DB::table('security_rules_permissions')->insert([
                'security_rule_id' => $rule->id,
                'permission_id' => $perm['id']
            ]);
        }

        foreach($limits as $lim){
            if($lim['type'] == 'company'){
                // insert new company security rule relations
                DB::table('security_rules_companies')->insert([
                    'security_rule_id' => $rule->id,
                    'company_id' => $lim['id']
                ]);
            } else if($lim['type'] == 'group'){
                // insert new group security rule relations
                DB::table('security_rules_groups')->insert([
                    'security_rule_id' => $rule->id,
                    'group_id' => $lim['id']
                ]);
            }
        }

        $rule->subsystem_id = $request->subsystem['id'];
        $rule->save();

        // update permission cache
        $rc = SecurityRule::getCompanyRules($rule->company_id);
        Cache::forever(config('mab.instance')."_mab_perms_{$rule->company_id}", $rc);

        $pabbrs = implode(',', array_map(function($i){ return $i['permission_name'][0]; }, $request->permissions));
        $module = $request->subsystem['subsystem_name'];
        $limits = implode(',', array_map(function($i){ return $i['label']; }, $request->limits));
        $this->acc->logActivity('Edit', 'Security Rules', "R: {$role->role_name} M: ($module) P: ($pabbrs) L: ($limits) ({$rule->id})");

        return response()->json([
            'message' => 'rule_updated',
            'rules' => $rc
        ]);
    }

    // role_rule_destroy: remove existing permission rule (if not referenced) (by role)
    public function destroy(Request $request)
    {
        $request->validate([ 'id' => 'required' ]);

        $rule = SecurityRule::where('id', $request->id)->first();
        if(!$rule){ return response()->json([ 'error' => 'nonexistent' ]); }

        if(!$this->acc->is_admin){
            // permission check
            $grants = $this->acc->requestAccess(['Security Rules' => ['p' => ['Delete'] ] ]);
            if(!in_array($request->id, $grants['Security Rules']['Delete']['O'])){
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        $role = Role::where('id', $rule->role_id)->first();
        $subsystem = Subsystem::where('id', $rule->subsystem_id)->first();

        $rule->delete();

        // update permission cache
        $rc = SecurityRule::getCompanyRules($rule->company_id);
        Cache::forever(config('mab.instance')."_mab_perms_{$rule->company_id}", $rc);

        $this->acc->logActivity('Delete', 'Security Rules', "R: {$role->role_name} M: ($subsystem->subsystem_name) ({$rule->id})");

        return response()->json([
            'message' => 'rule_removed',
            'rules' => $rc
        ]);
    }
}