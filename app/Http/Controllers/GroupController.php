<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Subsystem;
use App\Models\SecurityRule;
use App\Models\SecurityTemplate;
use App\Models\Role;
use App\Models\Group;
use App\Models\hardware_config;
use App\Models\hardware_management;
use App\Models\cultivars;
use App\Models\cultivars_management;
use App\Models\cultivars_templates;
use App\Models\nutrient_templates;

use App\Utils;
use App\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;

use DB;

/* ASSUMPTIONS: Subsystem will determine Resource Type (Nodes/Users/Sensors) */
/* NOTE: For now, we only allow Nodes/Users as Sensors need to be refactored */

class GroupController extends Controller
{
    public $subsystem_id; 

    public static $restricted_subsystems = [
        'Security Rules',
        'Security Templates',
        'Nutrients',
        'Nutrient Templates',
        'Entities',
        'Groups',
        'Roles'
    ];

    public function __construct()
    {
        $this->middleware('cors');
        $this->middleware(function ($request, $next) { $this->acc = Auth::user(); return $next($request); });
        $this->setupSubsystemResources();
    }

    // TODO: Maybe get these values from the subsystem_resources table
    public function setupSubsystemResources()
    {
        $this->res_nodes         = ['Map', 'Field Management', 'Dashboard', 'Node Config', 'Soil Moisture', 'Nutrients', 'Well Controls', 'Meters'];
        $this->res_sensors       = ['Sensor Types'];
        $this->res_cultivars     = ['Cultivars'];
        $this->res_ct_stages     = ['Cultivar Stages'];
        $this->res_ct_templates  = ['Cultivar Templates'];
        $this->res_nut_templates = ['Nutrient Templates'];
        $this->res_users         = ['Users'];
        $this->res_roles         = ['Roles'];
        $this->res_companies     = ['Entities'];
        $this->res_sec_rules     = ['Security Rules'];
        $this->res_sec_templates = ['Security Templates'];
    }

    public function index(Request $request)
    {
        $request->validate([
            'cur_page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:5',
            'initial'  => 'required',
            'filter'   => 'nullable',
            'entity'   => 'nullable',
            'sort_by'  => 'required',
            'sort_dir' => 'required'
        ]);

        $limit   = $request->per_page;
        $offset  = ($request->cur_page-1) * $limit;

        $sortBy  = $request->sort_by;
        $sortDir = $request->sort_dir;

        // optional filter param
        $filter = !empty($request->filter) ? $request->filter : '';
        // optional filter param
        $entity = !empty($request->entity) ? $request->entity : '';

        $company_id  = $this->acc->company_id;
        $groups_data = [];
        $ccs_by_id = [];

        // Filter out Subsystems here (optional)
        $excluded_subsystems = [];

        $groups = [];
        $grants = [];

        $total = 0;

        if($this->acc->is_admin){

            $total  = Group::count();
            $groups = Group::when($entity, function($query, $entity)  {
                // filter by entity (optional)
                $query->where('company_id', $entity);
            })
            ->skip($offset)
            ->take($limit)
            ->get();

        } else {
            // permission check
            $grants = $this->acc->requestAccess(['Groups' => ['p' => ['All'] ] ]);
            if(!empty($grants['Groups']['View']['O'])){

                $groups = Group::when($entity, function($query, $entity)  {
                    // filter by entity (optional)
                    $query->where('company_id', $entity);
                });

                $groups = $groups->whereIn('id', $grants['Groups']['View']['O']);
                $total  = $groups->count();
                $groups = $groups->skip($offset)->take($limit)->get();

            }
        }

        foreach($groups as $group){

            $group_subsystem_name = $group->subsystem->subsystem_name;

            $group_data = [
                'id'         => $group->id,
                'group_name' => $group->group_name,
                'company'    => $group->company()->get(['company_name', 'id'])->first(),
                'subsystem'  => $group->subsystem()->get(['subsystem_name', 'id'])->first(),
            ];

            // Populate Current Group Members

            // Nodes
            if(in_array($group_subsystem_name, $this->res_nodes)){
                $group_data['group_members'] = $group->nodes()->get([
                    'hardware_config.node_address AS label',
                    'hardware_config.id AS id'
                ])->toArray();

            // Sensors
            } else if(in_array($group_subsystem_name, $this->res_sensors)){
                $group_data['group_members'] = $group->sensors()->get([
                    'hardware_management.device_make AS label',
                    'hardware_management.id AS id'
                ])->toArray();

            // Cultivars
            } else if(in_array($group_subsystem_name, $this->res_cultivars)){
                $group_data['group_members'] = $group->cultivars()->get([
                    'cultivars_management.crop_name AS label',
                    'cultivars_management.id AS id'
                ])->toArray();

            // Cultivar Stages
            } else if(in_array($group_subsystem_name, $this->res_ct_stages)){
                $group_data['group_members'] = $group->cultivar_stages()->get([
                    'cultivars.stage_name AS label',
                    'cultivars.id AS id'
                ])->toArray();

            // Cultivar Templates
            } else if(in_array($group_subsystem_name, $this->res_ct_templates)){
                $group_data['group_members'] = $group->cultivar_templates()->get([
                    'cultivars_templates.name AS label',
                    'cultivars_templates.id AS id'
                ])->toArray();

            // Nutrient Templates
            } else if(in_array($group_subsystem_name, $this->res_nut_templates)){
                $group_data['group_members'] = $group->nutrient_templates()->get([
                    'nutrient_templates.name AS label',
                    'nutrient_templates.id AS id'
                ])->toArray();

            // Users
            } else if(in_array($group_subsystem_name, $this->res_users)){
                $members_users = $group->users()->get([
                    'users.email AS label',
                    'users.id AS id'
                ])->toArray();
                foreach($members_users as &$user){ unset($user['perms']); }
                $group_data['group_members'] = $members_users;

            // Roles
            } else if(in_array($group_subsystem_name, [ $this->res_roles ])){
                $group_data['group_members'] = $group->roles()->get([
                    'roles.role_name AS label',
                    'roles.id AS id'
                ])->toArray();

            // Companies / Entities
            } else if(in_array($group_subsystem_name, $this->res_companies)){
                $group_data['group_members'] = $group->companies()->get([
                    'companies.company_name AS label',
                    'companies.id AS id'
                ])->toArray();

            // Security Rules
            } else if(in_array($group_subsystem_name, $this->res_sec_rules)){
                $raw_rules = $group->security_rules()->toArray();
                $members = [];
                foreach($rules as $rule){
                    $perms = '(' . implode("", array_map(function($p){ return $p[0]; }, $rule->permissions()->pluck('permission_name')->toArray())) . ')';
                    $members[] = [
                        'label' => $rule->role->role_name . ' - ' . $rule->$rule->subsystem->subsystem_name . ' - ' . $perms,
                        'id' => $rule->id
                    ];
                }
                $group_data['group_members'] = $members;

            // Security Templates
            } else if(in_array($group_subsystem_name, $this->res_sec_templates)){
                $group_data['group_members'] = $group->security_templates()->get([
                    'security_templates.template_name AS label',
                    'security_templates.id AS id'
                ])->toArray();

            // Unknown
            } else {
                $group_data['group_members'] = [];
            }

            $groups_data[] = $group_data;
        }

        $recursiveFind = function($obj, $value) use (&$recursiveFind){
            if(is_array($obj)){
                foreach($obj as $v){
                    $found = $recursiveFind($v, $value);
                    if($found) return true;
                }
            } else {
                $found = stripos($obj, $value) !== false;
                if($found) return true;
            }
            return false;
        };

        if($filter){
            foreach($groups_data as $k => $v){
                $found = $recursiveFind($v, $filter);
                if(!$found){ unset($groups_data[$k]); }
            }
            $groups_data = array_values($groups_data);
        }

        /* Dropdown values / Resource Types */

        if($this->acc->is_admin){

            // Admins have unlimited access to all objects (of any group of any company)

            $nodes        = hardware_config::select('node_address AS label', 'node_type AS meta', 'hardware_config.id AS id', 'hardware_config.company_id')
                            ->join('fields', 'hardware_config.node_address', '=', 'fields.node_id') // prevent orphan nodes (without field rows)
                            ->orderBy('hardware_config.node_type', 'ASC')
                            ->orderBy('hardware_config.node_address', 'ASC')
                            ->get();

            $sensors      = hardware_management::select('device_make AS label', 'device_type AS meta', 'id', 'company_id')
                            ->orderBy('device_type', 'ASC')
                            ->orderBy('device_make', 'ASC')
                            ->get();

            $cultivars    = cultivars_management::select(DB::Raw('IFNULL( `cultivars_management`.`crop_name` , "Unnamed Cultivar" ) AS label'), 'id', 'company_id')
                            ->orderBy('crop_name', 'ASC')
                            ->get();

            $ct_stages    = cultivars::select('stage_name AS label', DB::Raw('IFNULL( `cultivars_management`.`crop_name` , "Unnamed Cultivar" ) AS meta'), 'cultivars.id', 'cultivars.company_id')
                            ->join('cultivars_management', 'cultivars.cultivars_management_id', '=', 'cultivars_management.id')
                            ->orderBy('cultivars.stage_name', 'ASC')
                            ->get();

            $ct_templates = cultivars_templates::select('name AS label', 'id', 'company_id')
                            ->orderBy('name', 'ASC')
                            ->get();

            $nut_templates = nutrient_templates::select('name AS label', 'id', 'company_id')
                             ->orderBy('name', 'ASC')
                             ->get();

            $users        = User::select('email AS label', 'companies.company_name AS meta', 'users.id AS id', 'company_id')
                            ->join('companies', 'users.company_id', '=', 'companies.id')
                            ->orderBy('companies.company_name', 'ASC')
                            ->orderBy('users.email', 'ASC')
                            ->get()
                            ->makeHidden(['perms','pivot']);

            $roles        = Role::select('role_name AS label', 'companies.company_name AS meta', 'roles.id AS id', 'company_id')
                            ->join('companies', 'roles.company_id', '=', 'companies.id')
                            ->orderBy('companies.company_name', 'ASC')
                            ->orderBy('roles.role_name', 'ASC')
                            ->get();

            $raw_rules    = SecurityRule::orderBy('id', 'ASC')->get();
            $sec_rules    = [];
            foreach($raw_rules as $rule){
                $label = $rule->role->role_name . ' - ' . $rule->subsystem->subsystem_name . ' - ';
                $label .= '(' . implode("", array_map(function($p){ return $p[0]; }, $rule->permissions()->pluck('permission_name')->toArray())) . ')';
                $sec_rules[] = ['label' => $label, 'meta' => $rule->company->company_name, 'id' => $rule->id, 'company_id' => $rule->company_id ];
            }

            $sec_templates = SecurityTemplate::select('security_templates.template_name AS label', 'security_templates.id AS id')
                             ->orderBy('security_templates.template_name', 'ASC')
                             ->get();

            $companies    = Company::select('company_name AS label', 'id')->orderBy('company_name')->get();

        } else {

            // Non-Admins are limited to all objects of a particular company (Should have N company access)

            $nodes        = hardware_config::select('node_address AS label', 'node_type AS meta', 'hardware_config.id AS id', 'hardware_config.company_id')
                            ->join('fields', 'hardware_config.node_address', '=', 'fields.node_id') // prevent orphan nodes (without field rows)
                            ->whereIn('hardware_config.company_id', $grants['Groups']['View']['C'])
                            ->orderBy('hardware_config.node_type', 'ASC')
                            ->orderBy('hardware_config.node_address', 'ASC')
                            ->get();

            $sensors      = hardware_management::select('device_make AS label', 'device_type AS meta', 'id', 'company_id')
                            ->whereIn('company_id', $grants['Groups']['View']['C'])
                            ->orderBy('device_type', 'ASC')
                            ->orderBy('device_make', 'ASC')
                            ->get();

            $cultivars    = cultivars_management::select(DB::Raw('IFNULL( `cultivars_management`.`crop_name` , "Unnamed Cultivar" ) AS label'), 'id', 'company_id')
                            ->whereIn('company_id', $grants['Groups']['View']['C'])
                            ->orderBy('crop_name', 'ASC')
                            ->get();

            $ct_stages    = cultivars::select('stage_name AS label', DB::Raw('IFNULL( `cultivars_management`.`crop_name` , "Unnamed Cultivar" ) AS meta'), 'cultivars.id', 'cultivars.company_id')
                            ->join('cultivars_management', 'cultivars.cultivars_management_id', '=', 'cultivars_management.id')
                            ->whereIn('cultivars.company_id', $grants['Groups']['View']['C'])
                            ->orderBy('cultivars.stage_name', 'ASC')
                            ->get();

            $ct_templates = cultivars_templates::select('name AS label', 'id', 'company_id')
                            ->whereIn('company_id', $grants['Groups']['View']['C'])
                            ->orderBy('name', 'ASC')
                            ->get();

            $nut_templates = nutrient_templates::select('name AS label', 'id', 'company_id')
                            ->whereIn('company_id', $grants['Groups']['View']['C'])
                            ->orderBy('name', 'ASC')
                            ->get();

            $users        = User::select('users.email AS label', 'companies.company_name AS meta', 'users.id AS id', 'company_id')
                            ->join('companies', 'users.company_id', '=', 'companies.id')
                            ->whereIn('users.company_id', $grants['Groups']['View']['C'])
                            ->orderBy('company_name', 'ASC')
                            ->orderBy('email', 'ASC')
                            ->get()
                            ->makeHidden(['perms','pivot']);

            $roles        = Role::select('role_name AS label', 'company_name AS meta', 'roles.id AS id', 'company_id')
                            ->join('companies', 'roles.company_id', '=', 'companies.id')
                            ->whereIn('roles.company_id', $grants['Groups']['View']['C'])
                            ->orderBy('company_name', 'ASC')
                            ->orderBy('role_name', 'ASC')
                            ->get();

            $raw_rules    = SecurityRule::whereIn('company_id', $grants['Groups']['View']['C'])->orderBy('id', 'ASC')->get();
            $sec_rules    = [];
            foreach($raw_rules as $rule){
                $label = $rule->role->role_name . ' - ' . $rule->subsystem->subsystem_name . ' - ';
                $label .= '(' . implode("", array_map(function($p){ return $p[0]; }, $rule->permissions()->pluck('permission_name')->toArray())) . ')';
                $sec_rules[] = ['label' => $label, 'meta' => $rule->company->company_name, 'id' => $rule->id, 'company_id' => $rule->company_id ];
            }

            $sec_templates = SecurityTemplate::select('security_templates.template_name AS label', 'security_templates.id AS id')
                             ->orderBy('security_templates.template_name', 'ASC')
                             ->get();

            $companies    = Company::select('company_name AS label', 'id')->whereIn('id', $grants['Groups']['View']['C'])->orderBy('company_name')->get();
        }

        $subsystems = Subsystem::select('subsystem_name', 'id');
        if(!$this->acc->is_admin){
            $subsystems->whereNotIn('subsystem_name', self::$restricted_subsystems);
        } else {
            // Don't allow creating Groups of Groups
            $subsystems->whereNotIn('subsystem_name', ['Groups']);
        }
        $subsystems = $subsystems->get();

        if($request->initial){
            $details = !empty($grants['Groups']['View']['O']) ?
                ('Group IDs: ' . implode(',', $grants['Groups']['View']['O'])) :
                ($this->acc->is_admin ? 'All Objects' : 'Access Denied');
            $this->acc->logActivity('View', 'Groups', $details);
        }

        if($companies){
            foreach($companies as $k => $cc){
                // IMPORTANT: The key needs to be a string or else the frontend sorting won't work. Trust me - F.
                $ccs_by_id['"'.$cc['id'].'"'] = $cc;
            }
        }

        $result = [
            'nodes'         => $nodes,
            'sensors'       => $sensors,
            'cultivars'     => $cultivars,
            'ct_stages'     => $ct_stages,
            'ct_templates'  => $ct_templates,
            'nut_templates' => $nut_templates,
            'users'         => $users,
            'roles'         => $roles,
            'sec_rules'     => $sec_rules,
            'sec_templates' => $sec_templates,
            'companies'     => $ccs_by_id,
            'subsystems'    => $subsystems,
            'groups_data'   => $groups_data,
            'total'         => $total
        ];

        if(!empty($grants)){ $result['grants'] = $grants; }

        return response()->json($result);
    }

    // GOOD Example of using permissions in context
    public function list(Request $request)
    {
        $request->validate([
            // chosen company
            'company_id' => 'required|exists:companies,id',
            'context' => 'required|array'
        ]);

        $groups = [];
        $subsystems = $this->acc->subsystems(); // backend 
        $subsystems_by_id = $this->acc->subsystems_by_id(); // frontend

        foreach($request->context as $rule){

            // Manual validation checks
            if(empty($rule['module']) || empty($rule['verb']) ){ continue; }

            // Security Context
            $verb    = $rule['verb'];
            $module  = $rule['module'];

            if($this->acc->is_admin){
                $g = Group::where('company_id', $request->company_id)
                ->where('subsystem_id', $subsystems[$module]['id'])
                ->get(['group_name', 'id', 'subsystem_id']) // we need these cols (therefor the Group:: query is needed)
                ->toArray();
                if(!empty($g) && is_array($g)){
                    $groups = array_merge($groups, $g);
                }
            } else {
                // in this case, rather than returning an 'access_denied' message, we return an empty list (if no permissions were granted)
                // permission check
                $grants  = $this->acc->requestAccess([ $module => [ 'p' => [$verb] ] ]);
                $filter_groups = !empty($grants[ $module ][ $verb ][ 'G' ]) ? $grants[ $module ][ $verb ][ 'G' ] : []; // group ids

                if(!empty($filter_groups)){
                    $g = Group::whereIn('id', $filter_groups)
                    ->where('company_id', $request->company_id)
                    ->get(['group_name', 'id', 'subsystem_id']) // we need these cols (therefor the Group:: query is needed)
                    ->toArray();

                    if(!empty($g) && is_array($g)){
                        $groups = array_merge($groups, $g);
                    }
                }
            }
        }

        //$groups = array_unique($groups);

        return response()->json([
            'groups' => $groups,
            'subsystems' => $subsystems_by_id
        ]);
    }

    // add new group (with optional members)
    public function add(Request $request)
    {
        $request->validate([
            'group_name'    => 'required|string',
            'company.id'    => 'required|integer',
            'subsystem.id'  => 'required|integer',
            'subsystem.subsystem_name' => 'required|string',
            'group_members' => 'array'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Groups' => ['p' => ['Add'] ] ]);
            if(empty($grants['Groups']['Add']['C'])){ return response()->json(['message' => 'access_denied'], 403); }
        }

        $company_id = $request->company['id'];
        $subsystem_id = $request->subsystem['id'];
        $subsystem_name = $request->subsystem['subsystem_name'];

        // subsystem validity check
        $registered_subsystems = $this->acc->subsystems();
        if(empty($registered_subsystems[$subsystem_name])){
            return response()->json([ 'errors' => [ 'id' => 'Unknown Subsystem' ] ]);
        }

        // unique check
        if( Group::where('group_name', $request->group_name)
            ->where('company_id', $company_id)
            ->where('subsystem_id', $subsystem_id)
            ->exists() 
        ){
            return response()->json([ 'errors' => [ 'group_name' => 'Group already exists' ] ]);
        }

        $group = new Group();

        $group->group_name   = $request->group_name;
        $group->company_id   = $company_id;
        $group->subsystem_id = $subsystem_id;

        $saved = $group->save();

        if(!$saved){
            return response()->json([ 'errors' => [ 'group_name' => 'Error saving group' ] ]);
        }

        if(!empty($request->group_members)){
            foreach($request->group_members as $item){
                DB::table($registered_subsystems[$subsystem_name]['group_table'])->insert([ 'group_id' => $group->id, 'object_id' => $item['id'] ]);
            }
        }

        // update permission cache
        Cache::forever(config('mab.instance')."_mab_perms_{$company_id}", SecurityRule::getCompanyRules($company_id));

        $company = Company::where('id', $company_id)->first();

        $this->acc->logActivity('Add', 'Groups', "{$company->company_name}:{$subsystem_name}:{$group->group_name}");

        return response()->json([ 'message' => 'group_added' ]);
    }

    // update existing group
    public function update(Request $request)
    {
        $request->validate([
            'id'            => 'required|integer',
            'group_name'    => 'required|string|max:50',
            'company.id'    => 'required|integer',
            'subsystem.id'  => 'required|integer',
            'subsystem.subsystem_name' => 'required|string',
            'group_members' => 'array'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Groups' => ['p' => ['Edit'], 'o' => $request->id, 't' => 'O' ] ]);
            if(empty($grants['Groups']['Edit']['O'])){ return response()->json(['message' => 'access_denied'], 403); }
        }

        $group_id       = $request->id;
        $company_id     = $request->company['id'];
        $subsystem_id   = $request->subsystem['id'];
        $subsystem_name = $request->subsystem['subsystem_name'];

        // subsystem validity check
        $registered_subsystems = $this->acc->subsystems();
        if(empty($registered_subsystems[$subsystem_name])){
            return response()->json([ 'errors' => [ 'id' => 'Unknown Subsystem' ] ]); 
        }

        // EXISTENCE CHECK: check if group still exists
        $group = Group::where('id', $group_id)->first();
        if(!$group){ return response()->json([ 'errors' => [ 'id' => 'Nonexistent group' ] ]); }

        // UNIQUE CHECK: check if group name changed, bail early if another group with name already exists
        $group_by_name = Group::where('group_name', $request->group_name)->first();
        if($group_by_name && $group_by_name->id != $group->id){
            // another group was found having the desired name, bail
            return response()->json([ 'errors' => [ 'id' => 'Group name already exists' ] ]); 
        }

        $old_subsystem_id   = $group->subsystem->id;
        $old_subsystem_name = $group->subsystem->subsystem_name;

        // subsystem changed? remove old grouping rows from particular resource table
        if($old_subsystem_id != $subsystem_id){
            DB::table($registered_subsystems[$subsystem_name]['group_table'])->where('group_id', $group_id)->delete();
        }

        if(in_array($old_subsystem_name, $this->res_nodes)){
            $old_members = $group->nodes()->get(['hardware_config.id AS id'])->toArray();
        } else
        if(in_array($old_subsystem_name, $this->res_sensors)) {
            $old_members = $group->sensors()->get(['hardware_management.id AS id'])->toArray();
        } else
        if(in_array($old_subsystem_name, $this->res_cultivars)) {
            $old_members = $group->cultivars()->get(['cultivars_management.id AS id'])->toArray();
        } else 
        if(in_array($old_subsystem_name, $this->res_ct_stages)) {
            $old_members = $group->cultivar_stages()->get(['cultivars.id AS id'])->toArray();
        } else 
        if(in_array($old_subsystem_name, $this->res_ct_templates)) {
            $old_members = $group->cultivar_templates()->get(['cultivars_templates.id AS id'])->toArray();
        } else
        if(in_array($old_subsystem_name, $this->res_nut_templates)) {
            $old_members = $group->nutrient_templates()->get(['nutrient_templates.id AS id'])->toArray();
        } else 
        if(in_array($old_subsystem_name, $this->res_users)){
            $old_members = $group->users()->get(['users.id AS id'])->toArray();
        } else 
        if(in_array($old_subsystem_name, $this->res_roles)) {
            $old_members = $group->roles()->get(['roles.id AS id'])->toArray();
        } else 
        if(in_array($old_subsystem_name, $this->res_companies)) {
            $old_members = $group->companies()->get(['companies.id AS id'])->toArray();
        } else 
        if(in_array($old_subsystem_name, $this->res_sec_rules)) {
            $old_members = $group->security_rules()->get(['security_rules.id AS id'])->toArray();
        } else 
        if(in_array($old_subsystem_name, $this->res_sec_templates)) {
            $old_members = $group->security_templates()->get(['security_templates.id AS id'])->toArray();
        }

        $new_members = $request->group_members;

        // calculate differences
        $members_removed = array_udiff($old_members, $new_members, function ($a, $b) { return $a['id'] - $b['id']; });
        $members_added   = array_udiff($new_members, $old_members, function ($a, $b) { return $a['id'] - $b['id']; });

        // update group name and company
        $group->group_name = $request->group_name;
        $group->subsystem_id = $subsystem_id;
        $group->company_id = $company_id;
        $group->save();

        // delete removed members from appropriate grouping table
        if($members_removed){
            foreach($members_removed as $member){
                DB::table($registered_subsystems[$old_subsystem_name]['group_table'])->where('group_id', $group_id)->where('object_id', $member['id'])->delete();
            }
        }

        // insert added members to appropriate grouping table
        if($members_added){
            foreach($members_added as $member){
                DB::table($registered_subsystems[$old_subsystem_name]['group_table'])->insert([ 'group_id' => $group_id, 'object_id' => $member['id'] ]);
            }
        }

        $company = Company::where('id', $company_id)->first();
        
        $this->acc->logActivity('Edit', 'Groups', "{$company->company_name}:{$subsystem_name}:{$group->group_name}");

        return response()->json([
            'message' => 'group_updated'
        ]);
    }

    // remove existing group
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Groups' => ['p' => ['Delete'], 'o' => $request->id, 't' => 'O'] ]);
            if(empty($grants['Groups']['Delete']['O'])){ return response()->json(['message' => 'access_denied'], 403); }
        }

        $group = Group::where('id', $request->id)->first();
        if(!$group){
            return response()->json([ 'errors' => [ 'id' => 'Nonexistent group' ] ]);
        }

        $company_id = $group->company_id;

        $company = Company::where('id', $company_id)->first();
        $subsystem = Subsystem::where('id', $group->subsystem_id)->first();

        $this->acc->logActivity('Delete', 'Groups', "{$company->company_name}:{$subsystem->subsystem_name}:{$group->group_name}");

        // gonners
        $group->delete();

        // update permission cache
        Cache::forever(config('mab.instance')."_mab_perms_{$company_id}", SecurityRule::getCompanyRules($company_id));

        return response()->json([
            'message' => 'group_removed'
        ]);
    }
}