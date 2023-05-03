<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\HasApiTokens;
use App\Models\SecurityRule;
use App\Models\Subsystem;
use App\Models\Group;
use App\Models\Company;
use App\Models\Permission;
use App\Utils;
use DB;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';
    public $timestamps = true;
    public $primaryKey = 'id';

    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile',
        'address',
        'unit_of_measure',
        'timezone',
        'role_id',
        'company_id',
        'is_admin',
        'is_distributor',
        'is_active',
        'restricted_to'
    ];

    protected $hidden = [
        'password', 'created_at', 'updated_at', 'email_verified_at', 'remember_token'
    ];

    protected $appends = [ 'perms' ];

    /* accessors */

    // access for 'perms' virtual property (naming of method is important: get<property>Attribute )
    // this is used on the front-end
    public function getPermsAttribute()
    {
        return $this->security_rules();
    }

    /* Queries */

    public function isAdmin()
    {
        return $this->is_admin;
    }

    public function isDistributor()
    {
        return $this->is_distributor;
    }

    public function isRestricted()
    {
        return $this->restricted_to;
    }

    // TODO: Change this to drill down (to do a check for a sub-sub-sub-company, etc)
    public function isDistributorOf($company_id)
    {
        // NEW: Also include the parent company in the check (the user's own company) (EXPERIMENTAL)
        return DB::table('distributors_companies')
        ->where('user_id', $this->id)
        ->where(
            function($query) use ($company_id) {
                $query->orWhere('company_id', $company_id);
                $query->orWhere('parent_company_id', $company_id);
            }
        )->exists();
    }

    /* Relationships */

    public function the_role() // was called the_role due to a 'role' legacy property still existing on the model
    {
        return $this->belongsTo('App\Models\Role', 'role_id');
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Models\Group', 'groups_users', 'object_id', 'group_id');
    }
    
    /**
     * requestAccess  grants access, resolves and merges Company and Group Objects (for Non-Entities) and returns ACLs
     *
     * @param array   $subperms An associative array of permissions keyed by Subsystem name
     * @param integer $object_id
     * @return mixed
     */

    public function requestAccess($sub_perms)
    {
        if(!is_array($sub_perms) || empty($sub_perms)){ return false; }

        $perms_meta = $this->security_rules(); /* cached */ /* $perms_meta[$c][$r][$s][$v]['C'] = []; */
        $all_subsystems = $this->subsystems(); /* cached */
        $all_subsystems_perms = $this->subsystems_permissions(); /* cached */
        $all_admin_user_ids = $this->get_admin_user_ids();
        $grants = [];

        // Distributor Permission Injection
        if($this->isDistributor()){
            // Inject Distributor Rules into Existing User Permissions
            $dist_rules = $this->distributor_rules(); /* cached */
            $perms_meta = Utils::array_merge_rec($perms_meta, $dist_rules); 
            //Log::debug($perms_meta);
        }

        // Subsystems
        foreach($sub_perms as $subsystem => $object)
        {
            $permissions = !empty($object['p']) ? $object['p'] : null;
            $object_id   = !empty($object['o']) ? $object['o'] : null;
            $object_type = !empty($object['t']) ? $object['t'] : null;

            // This shouldn't ever happen, but handle it just in case.
            if(empty($permissions)){ continue; }

            // replace 'All' placeholder: get all permission verbs assigned to the selected subsystem
            if(stripos($permissions[0], 'All') !== false){
                $permissions = $all_subsystems_perms[$subsystem];
            }

            // Permissions
            foreach($permissions as $perm) // Add, Edit, Delete, etc
            {
                $grants[$subsystem][$perm] = $object_id ? false : [ 'C' => [], 'G' => [], 'O' => [] ];
                $accumulated_object_ids = [];

                // COMPANIES/ENTITIES

                if(
                    !empty($perms_meta[$this->company_id][$this->role_id][$subsystem][$perm]['C']) ||
                    !empty($perms_meta[$this->company_id][$this->role_id][$subsystem][$perm]['CI'])
                ){
                    if(!empty($perms_meta[$this->company_id][$this->role_id][$subsystem][$perm]['C'])){
                        $manual_company_ids = $perms_meta[$this->company_id][$this->role_id][$subsystem][$perm]['C'];
                    } else { $manual_company_ids = []; }

                    if(!empty($perms_meta[$this->company_id][$this->role_id][$subsystem][$perm]['CI'])){
                        $inferred_company_ids = $perms_meta[$this->company_id][$this->role_id][$subsystem][$perm]['CI'];
                    } else { $inferred_company_ids = []; }

                    // Restricted mode: Limit to specific Entities (Overrides everything else)
                    if($this->isRestricted()){
                        $manual_company_ids = strpos($this->restricted_to, ',') !== false ?
                            array_map(function($i){ return (int) $i; }, explode(',', $this->restricted_to)) :
                            array((int) $this->restricted_to);
                    }

                    // Resolve Company IDs to Object IDs: merge all chosen companies' object ids
                    if($perm !== 'Add' && $subsystem !== 'Entities'){
                        foreach($manual_company_ids as $company_id){
                            $company_resolved_object_ids = DB::table($all_subsystems[$subsystem]['resource_table'])
                            ->where('company_id', $company_id)->pluck('id')->toArray();
                            $accumulated_object_ids = array_merge($accumulated_object_ids, $company_resolved_object_ids);
                        }
                    }

                    // [[ SINGLE COMPANY CHECK - RETURN BOOLEAN ]]
                    if($object_id && $object_type == 'C'){
                        /* ONLY COMPARE WITH MANUALLY ASSIGNED COMPANIES */
                        /* Just because user can X a group of company Y doesn't imply full access to X on company Y (for all permissions) */
                        $grants[$subsystem][$perm]['C'] = $this->is_admin || in_array($object_id, $manual_company_ids);

                    // [[ GENERAL COMPANY CHECK - RETURN RANGE ]]
                    } else {
                        /* We merge in inferred companies (for the backend) (inferred companies are needed here because of dependant dropdowns) */
                        $grants[$subsystem][$perm]['C'] = array_unique(array_merge($manual_company_ids, $inferred_company_ids));
                        /* We merge in inferred companies (for the frontend) */
                        $perms_meta[$this->company_id][$this->role_id][$subsystem][$perm]['C'] = $grants[$subsystem][$perm]['C'];
                    }
                }

                // GROUPS

                if (
                    !empty($perms_meta[$this->company_id][$this->role_id][$subsystem][$perm]['G']) ||
                    !empty($perms_meta[$this->company_id][$this->role_id][$subsystem][$perm]['GI'])
                ){
                    if(!empty($perms_meta[$this->company_id][$this->role_id][$subsystem][$perm]['G'])){
                        $manual_group_ids = $perms_meta[$this->company_id][$this->role_id][$subsystem][$perm]['G'];
                    } else { $manual_group_ids = []; }

                    if(!empty($perms_meta[$this->company_id][$this->role_id][$subsystem][$perm]['GI'])){
                        $inferred_group_ids = $perms_meta[$this->company_id][$this->role_id][$subsystem][$perm]['GI'];
                    } else { $inferred_group_ids = []; }

                    // Resolve Group IDs to Object IDs: merge all chosen groups' object ids
                    if($perm !== 'Add'){
                        foreach($manual_group_ids as $group_id){
                            $group_resolved_object_ids = DB::table($all_subsystems[$subsystem]['group_table'])
                            ->where('group_id', $group_id)->pluck('object_id')->toArray();
                            $accumulated_object_ids = array_merge($accumulated_object_ids, $group_resolved_object_ids);
                        }
                    }

                    // [[ SINGLE GROUP CHECK - RETURN BOOLEAN ]]
                    if($object_id && $object_type == 'G'){
                        /* COMPARE WITH BOTH MANUAL + INFERRED GROUPS */
                        /* If a user can access Company X, then he also has access to all Company X's groups (even groups that weren't manually assigned) */
                        $grants[$subsystem][$perm]['G'] = $this->is_admin || in_array($object_id, array_unique(array_merge($manual_group_ids, $inferred_group_ids)));

                    // [[ GENERAL GROUP CHECK - RETURN RANGE ]]
                    } else {
                        /* We merge in inferred groups (for the backend) */
                        $grants[$subsystem][$perm]['G'] = array_unique(array_merge($manual_group_ids, $inferred_group_ids));
                        /* We merge in inferred groups (for the frontend) */
                        $perms_meta[$this->company_id][$this->role_id][$subsystem][$perm]['G'] = $grants[$subsystem][$perm]['G'];
                    }
                }

                // OBJECTS

                // Final object id assignment (post company post group accumulation)
                $accumulated_object_ids = array_unique($accumulated_object_ids);

                if($subsystem == 'Users'){ 
                    // ADMINS SECURITY - Remove all Admin Users from the Permission stream (Untouchable accounts)
                    $accumulated_object_ids = array_values(
                        array_filter(
                            $accumulated_object_ids,
                            function($id) use($all_admin_user_ids) {
                                return !in_array($id, $all_admin_user_ids);
                            }
                        )
                    );
                    // EXCEPTION - Inject Current user into permission stream (to allow user to edit own account object)
                    $accumulated_object_ids[] = $this->id;
                }

                $perms_meta[$this->company_id][$this->role_id][$subsystem][$perm]['O'] = $accumulated_object_ids;

                // [[ SINGLE OBJECT CHECK - RETURN BOOLEAN ]]
                if($object_id && $object_type == 'O'){
                    $grants[$subsystem][$perm]['O'] = $this->is_admin || in_array($object_id, $accumulated_object_ids);
                
                // [[ GENERAL OBJECT CHECK - RETURN RANGE ]]
                } else {
                    $grants[$subsystem][$perm]['O'] = $accumulated_object_ids;
                }
            }
        }

        // pass along a copy of the grant-populated permissions for the front-end
        $grants['__perms_meta__'] = $perms_meta;

        return $grants;
    }

    public function logActivity($operation, $subsystem, $details)
    {
        $subsystems  = $this->subsystems();
        $permissions = $this->permissions();

        if(empty($permissions[$operation]) || empty($subsystems[$subsystem]) || empty($details)){
            Log::debug("Unknown Subsystem/Operation or empty details: {$subsystem} / {$operation} / {$details}");
            return false;
        }

        return DB::table('activity_log')->insert([
            'user_name'    => $this->name,
            'operation_id' => $permissions[$operation]['id'],
            'subsystem_id' => $subsystems[$subsystem]['id'],
            'details'      => $details,
            'company_name' => Company::where('id', $this->company_id)->pluck('company_name')->first(),
            'occurred'     => date("Y-m-d H:i:s")
        ]);
    }

    // Basically allows only admins to change company ids of objects when updating
    public function companyCheckFails($company_id)
    {
        if($this->is_admin){
            return false;
        } else if(!$company_id || $this->company_id != $company_id){
            return true;
        }
        return false;
    }

    /* Distributor Related Methods */

    // Get managed companies for current level only
    public function getManagedCompanyIds()
    {
        return DB::table('distributors_companies')->where('user_id', $this->id)->pluck('company_id')->toArray();
    }

    public function setManagedCompanies()
    {
        $this->managed_company_ids = DB::table('distributors_companies')->where('user_id', $this->id)->pluck('company_id')->toArray();
    }

    // get cached distributor rules (for use by $user->requestAccess) (merged with security_rules)
    public function distributor_rules()
    {
        $user = $this;
        return Cache::rememberForever(
            config('mab.instance')."_mab_dist_perms_{$user->id}",
            function () use ($user){
                return SecurityRule::getDistributorRules($user);
            }
        );
    }

    public function updateDistributorCache()
    {
        // Update Distributor Cache
        $key = config('mab.instance')."_mab_dist_perms_{$this->id}";
        Cache::forget($key);
        Cache::forever($key, SecurityRule::getDistributorRules($this));
    }

    public function removeDistributorCache()
    {
        Cache::forget(config('mab.instance')."_mab_dist_perms_{$this->id}");
    }

    public static function updateAllDistributorCaches()
    {
        $dist_users = User::where('is_distributor', 1)->get();
        foreach($dist_users as $d_user){
            $d_user->updateDistributorCache();
        }
    }

    /* Security Related Methods */

    // get cached security rules (for use by $user->requestAccess)

    public function security_rules()
    {
        $company_id = $this->company_id;
        return Cache::rememberForever(
            config('mab.instance')."_mab_perms_{$company_id}",
            function () use ($company_id){
                return SecurityRule::getCompanyRules($company_id);
            }
        );
    }

    public function subsystems()
    {
        return Cache::rememberForever(config('mab.instance')."_mab_subsystems", function () {
            $subsystems = Subsystem::all();
            $output = [];
            foreach($subsystems as $s){
                $output[$s->subsystem_name] = [
                    'id' => $s->id,
                    'group_table' => $s->group_table,
                    'resource_table' => $s->resource_table,
                    'route' => $s->route
                ];
            }
            return $output;
        });
    }

    public function subsystems_by_id()
    {
        return Cache::rememberForever(config('mab.instance')."_mab_subsystems_by_id", function () {
            return Subsystem::get(['id','subsystem_name'])->keyBy('id')->toArray();
        });
    }

    public function permissions()
    {
        return Cache::rememberForever(config('mab.instance')."_mab_permissions", function () {
            return Permission::get(['id','permission_name'])->keyBy('permission_name')->toArray();
        });
    }

    public function permissions_by_id()
    {
        return Cache::rememberForever(config('mab.instance')."_mab_permissions_by_id", function () {
            return Permission::get(['id','permission_name'])->keyBy('id')->toArray();
        });
    }

    public function subsystems_permissions()
    {
        return Cache::rememberForever(config('mab.instance')."_mab_subsystems_permissions", function(){

            $subsystems = Subsystem::all();
            $output = [];

            foreach($subsystems as $s){
                $output[$s->subsystem_name] = DB::table('subsystem_permissions')
                ->join('permissions', 'subsystem_permissions.permission_id','=','permissions.id')
                ->join('subsystems',  'subsystem_permissions.subsystem_id', '=','subsystems.id')
                ->where('subsystems.subsystem_name', $s->subsystem_name)
                ->get()->pluck('permission_name');
            }

            return $output;
        });
    }

    public function get_admin_user_ids()
    {
        return DB::table('users')->where('is_admin', 1)->pluck('id')->toArray();
    }
}
