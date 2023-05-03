<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Models\Subsystem;
use App\Models\Company;
use DB;

class SecurityRule extends Model
{
    protected $table = 'security_rules';
    protected $primaryKey = 'id';
    protected $fillable = [ 'company_id', 'role_id', 'subsystem_id' ];
    protected $hidden = ['pivot'];
    public $timestamps = false;
    
    /* Relationships */

    public function company()
    {
        return $this->belongsTo('App\Models\Company');
    }

    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }

    public function subsystem()
    {
        return $this->belongsTo('App\Models\Subsystem');
    }

    public function permissions()
    {
        return $this->belongsToMany('App\Models\Permission', 'security_rules_permissions', 'security_rule_id', 'permission_id');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Models\Group', 'security_rules_groups', 'security_rule_id', 'group_id');
    }

    public function companies()
    {
        return $this->belongsToMany('App\Models\Company', 'security_rules_companies', 'security_rule_id', 'company_id');
    }

    public static function getDistributorPermissions()
    {
        // Distributor Specific Security Rules
        // NOTE: Please clear the MAB cache when changing this via /api/mab_flush
        return [
            "Node Config"        => [ "View", "Add", "Edit", "Delete", "Import", "Export", "Reboot", "Flash" ],
            "Sensor Types"       => [ "View", "Clone" ],
            "Users"              => [ "View", "Add", "Edit", "Delete", "Lock", "Reset Password" ],
            "Roles"              => [ "View", "Add", "Edit", "Delete", "Import", "Export" ],
            "Groups"             => [ "View", "Add", "Edit", "Delete" ],
            "Security Rules"     => [ "View", "Add", "Edit", "Delete" ],
            "Entities"           => [ "View", "Add", "Edit", "Delete", "Integrate", "Lock", "Report" ]
        ];
    }

    // generate distributor rules (for injection into company rules) (this gets cached)
    public static function getDistributorRules($user)
    {
        $permissions = [];

        $dist_rules = self::getDistributorPermissions();     // (static) assoc [ subsystem => [ ..verbs...] ]
        $subsidiary_ids = Company::get_subsidiary_ids($user->company_id, true); // (cached) [1,3,5,6] etc

        //Log::debug($subsidiary_ids);

        $c = $user->company_id;
        $r = $user->role_id;

        foreach($dist_rules as $s => $perm_verbs){
            foreach($perm_verbs as $v){
                $permissions[$c][$r][$s][$v]['C'] = $subsidiary_ids;
            }
        }

        return $permissions;
    }

    // generate company rules (this gets cached)
    public static function getCompanyRules($company_id)
    {
        if(!$company_id) return [];

        $permissions = [];
        $subsystems = Subsystem::all();
        $company = Company::where('id', $company_id)->first();
        $roles = $company->roles()->get();

        $c = $company_id;

        foreach($roles as $role){
            $r = $role->id;

            foreach($subsystems as $subsystem){
                $s = $subsystem->subsystem_name;

                $rules = SecurityRule::where ( 'company_id', (int) $company_id )
                                     ->where ( 'role_id', (int) $role->id )
                                     ->where ( 'subsystem_id', (int) $subsystem->id )->get();

                foreach($rules as $rule){
                    $perm_verbs = $rule->permissions()->pluck( 'permission_name' );

                    //Log::debug("perm_verbs" . var_export($perm_verbs, true));

                    foreach($perm_verbs as $v){

                        /* 0: Initialize */
                        if(empty($permissions[$c][$r][$s][$v]['C'])){
                            $permissions[$c][$r][$s][$v]['C'] = [];
                        }
                        if(empty($permissions[$c][$r][$s][$v]['G'])){
                            $permissions[$c][$r][$s][$v]['G'] = [];
                        }
                        if(empty($permissions[$c][$r][$s][$v]['CI'])){
                            $permissions[$c][$r][$s][$v]['CI'] = [];
                        }
                        if(empty($permissions[$c][$r][$s][$v]['GI'])){
                            $permissions[$c][$r][$s][$v]['GI'] = [];
                        }

                        /* 1: get the manually chosen companies of the security rule and merge with existing rules' companies (of the same verb) */
                        $manual_companies = $rule->companies()->get(['company_id AS id'])->pluck('id')->toArray();

                        /* 2: get the manually chosen groups of the security rule */
                        $manual_groups = $rule->groups()->get(['group_id AS id'])->pluck('id')->toArray();

                        /* 3: Get the companies inferred from groups of the security rule (confirmed working) */
                        $inferred_companies = Group::whereIn('id', $manual_groups)->pluck('company_id')->toArray();

                        /* 4: get the groups inferred from companies of the security rule (limited to current subsystem) (confirmed working) */
                        $inferred_groups = Group::whereIn('company_id', $manual_companies)->where('subsystem_id', $subsystem->id)->pluck('id')->toArray();

                        /* 5: build the manual company rules */
                        $permissions[$c][$r][$s][$v]['C'] = array_unique(
                            array_merge($permissions[$c][$r][$s][$v]['C'],  $manual_companies)
                        );

                        /* 6 build the inferred company rules */
                        $permissions[$c][$r][$s][$v]['CI'] = array_unique(
                            array_merge($permissions[$c][$r][$s][$v]['CI'], $inferred_companies)
                        );

                        /* 7: build the manual group rules */
                        $permissions[$c][$r][$s][$v]['G'] = array_unique(
                            array_merge($permissions[$c][$r][$s][$v]['G'],  $manual_groups)
                        );

                        /* 8: build the inferred group rules */
                        $permissions[$c][$r][$s][$v]['GI'] = array_unique(
                            array_merge($permissions[$c][$r][$s][$v]['GI'], $inferred_groups)
                        );
                    }
                }
            }
        }

        // Log::debug("SecurityRule::getCompanyRules: " . var_export($permissions, true));

        return $permissions;
    }

    public static function generateFromSelectedSubsystems(
        $selected_subsystems,
        $company_id,
        $role_id,
        $type
    ){
        // Generate Security Rules by Selected Subsystems/Modules
        $subsystems  = DB::table('subsystems')->get()->keyBy('subsystem_name')->toArray();
        $permissions = DB::table('permissions')->get()->keyBy('permission_name')->toArray();

        $rules = [];

        // Users Rules
        if($type == 'Users'){

            $base_rules = [
                [ 'subsystem' => 'Map',                'permissions' => ['View'] ],
                [ 'subsystem' => 'Dashboard',          'permissions' => ['View'] ],
                [ 'subsystem' => 'Field Management',   'permissions' => ['View'] ],
                [ 'subsystem' => 'Node Config',        'permissions' => ['View'] ],
                [ 'subsystem' => 'Sensor Types',       'permissions' => ['View'] ]
            ];

            $soil_moisture_rules = [
                [ 'subsystem' => 'Soil Moisture',      'permissions' => ['View', 'Graph', 'Edit'] ],
                [ 'subsystem' => 'Cultivars',          'permissions' => ['View', 'Edit'] ],
                [ 'subsystem' => 'Cultivar Stages',    'permissions' => ['View','Add','Edit','Delete'] ],
            ];

            $nutrients_rules = [
                [ 'subsystem' => 'Nutrients',          'permissions' => ['View', 'Graph', 'Edit'] ],
            ];

            $well_controls_rules = [
                [ 'subsystem' => 'Well Controls',      'permissions' => ['View', 'Graph', 'Edit'] ]
            ];

            $meters_rules = [
                [ 'subsystem' => 'Meters',             'permissions' => ['View', 'Graph', 'Edit'] ]
            ];

        // Manager Rules
        } else if($type == 'Managers'){

            $base_rules = [
                [ 'subsystem' => 'Map',                'permissions' => ['View'] ],
                [ 'subsystem' => 'Dashboard',          'permissions' => ['View'] ],
                [ 'subsystem' => 'Field Management',   'permissions' => ['View'] ],
                [ 'subsystem' => 'Node Config',        'permissions' => ['View', 'Add', 'Edit', 'Delete', 'Import', 'Export', 'Reboot', 'Flash'] ],
                [ 'subsystem' => 'Sensor Types',       'permissions' => ['View', 'Add', 'Edit', 'Delete'] ],

                [ 'subsystem' => 'Users',              'permissions' => ['View', 'Add', 'Edit', 'Delete', 'Reset Password'] ],
                [ 'subsystem' => 'Roles',              'permissions' => ['View'] ],
                [ 'subsystem' => 'Entities',           'permissions' => ['View', 'Edit', 'Report', 'Integrate'] ]
            ];

            $soil_moisture_rules = [
                [ 'subsystem' => 'Soil Moisture',      'permissions' => ['View', 'Add', 'Edit', 'Delete', 'Graph', 'Export'] ],
                [ 'subsystem' => 'Cultivars',          'permissions' => ['View', 'Add', 'Edit', 'Delete'] ],
                [ 'subsystem' => 'Cultivar Stages',    'permissions' => ['View', 'Add', 'Edit', 'Delete'] ],
                [ 'subsystem' => 'Cultivar Templates', 'permissions' => ['View', 'Add', 'Edit', 'Delete', 'Import', 'Export'] ],
            ];

            $nutrients_rules = [
                [ 'subsystem' => 'Nutrients',          'permissions' => ['View', 'Graph', 'Edit'] ],
                [ 'subsystem' => 'Nutrient Templates', 'permissions' => ['View', 'Edit'] ]
            ];

            $well_controls_rules = [
                [ 'subsystem' => 'Well Controls',      'permissions' => ['View', 'Add', 'Edit', 'Delete', 'Graph', 'Export', 'Toggle'] ]
            ];

            $meters_rules = [
                [ 'subsystem' => 'Meters',             'permissions' => ['View', 'Add', 'Edit', 'Delete', 'Graph', 'Export'] ]
            ];

        }

        $rules = array_merge($rules, $base_rules);

        if(in_array('Soil Moisture', $selected_subsystems)){
            $rules = array_merge($rules, $soil_moisture_rules);
        }

        if(in_array('Nutrients', $selected_subsystems)){
            $rules = array_merge($rules, $nutrients_rules);
        }

        if(in_array('Well Controls', $selected_subsystems)){
            $rules = array_merge($rules, $well_controls_rules);
        }

        if(in_array('Meters', $selected_subsystems)){
            $rules = array_merge($rules, $meters_rules);
        }

        foreach($rules as $rule){

            // Insert Base Security Rule
            $sec_rule_id = DB::table('security_rules')->insertGetId([
                'company_id'   => $company_id,
                'role_id'      => $role_id,
                'subsystem_id' => $subsystems[ $rule['subsystem'] ]->id,
            ]);

            // Insert Security Rule Permissions
            foreach($rule['permissions'] as $perm){
                DB::table('security_rules_permissions')->insert([
                    'security_rule_id' => $sec_rule_id,
                    'permission_id'    => $permissions[ $perm ]->id
                ]);
            }

            // Insert Security Rule Company
            DB::table('security_rules_companies')->insert([
                'security_rule_id' => $sec_rule_id,
                'company_id'       => $company_id,
            ]);
        }

    }
}
