<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/*

Checklist:

// 1.) CREATE COMPANIES
// 2.) CREATE ROLES
// 3.) ADD ROLE SECURITY RULES
// 4.) ASSIGN USERS TO COMPANIES
// 5.) ASSIGN USERS TO COMPANY ROLES
// 6.) REMOVE OLD USERS
// 7.) ASSIGN NODES (+ANCILLARY ROWS) TO COMPANIES
// 8.) REMOVE OLD NODES
// 9.) CLONE LF SENSORS TO COMPANIES

*/

class PopulateCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        return; 
        
        $companies = [];
        $companies_by_id = [];

        // 1.) CREATE COMPANIES

        // Liquid Fibre already exists as Company 1 (From earlier Migration)

        $data = (array) DB::connection('mysql_root')->table('companies')->where('company_name', 'LF SA')->first();
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $data['id'];
        $companies_by_id[$data['id']] = $data;

        // -------------------------------------------------------------------- 1

        $data = [ 
            'company_name'                => 'Greenspan',
            'address_billing_country'     => 'US'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 2

        $data = [
            'company_name'                => 'AC USA',
            'address_billing_country'     => 'US'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 3

        $data = [
            'company_name'                => 'Small Inc.',
            'contact_name'                => 'Byron Small',
            'contact_phone'               => '573-888-7693',
            'contact_email'               => 'smallincfarms@gmail.com',
            'address_billing_line_1'      => '9810 State Hwy V',
            'address_billing_city'        => 'Senath',
            'address_billing_postalcode'  => '63876',
            'address_billing_country'     => 'US',
            'address_physical_line_1'     => '9810 State Hwy V',
            'address_physical_city'       => 'Senath',
            'address_physical_postalcode' => '63876',
            'address_physical_country'    => 'US'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 4

        $data = [
            'company_name'                => 'USAGN',
            'contact_name'                => 'Byron Small',
            'contact_phone'               => '573-888-7693',
            'contact_email'               => 'chris@usagnetwork.com',
            'address_billing_line_1'      => '9810 State Hwy V',
            'address_billing_city'        => 'Senath',
            'address_billing_postalcode'  => '63876',
            'address_billing_country'     => 'US',
            'address_physical_line_1'     => '9810 State Hwy V',
            'address_physical_city'       => 'Senath',
            'address_physical_postalcode' => '63876',
            'address_physical_country'    => 'US'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 5

        $data = [
            'company_name'                => 'Chandler Farms',
            'contact_name'                => 'Byron Small',
            'contact_phone'               => '573-888-7693',
            'contact_email'               => 'chandlerfarms@sbcglobal.net',
            'address_billing_line_1'      => '9810 State Hwy V',
            'address_billing_city'        => 'Senath',
            'address_billing_postalcode'  => '63876',
            'address_billing_country'     => 'US',
            'address_physical_line_1'     => '9810 State Hwy V',
            'address_physical_city'       => 'Senath',
            'address_physical_postalcode' => '63876',
            'address_physical_country'    => 'US'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 6

        $data = [
            'company_name'                => 'Tucor',
            'contact_name'                => 'Brad',
            'address_billing_country'     => 'US'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 7

        $data = [
            'company_name'                => 'Legacy Equipment',
            'contact_name'                => 'Byron Small',
            'contact_phone'               => '573-888-7693',
            'contact_email'               => 'wrcarter1967@hotmail.com',
            'address_billing_line_1'      => '9810 State Hwy V',
            'address_billing_city'        => 'Senath',
            'address_billing_postalcode'  => '63876',
            'address_billing_country'     => 'US',
            'address_physical_line_1'     => '9810 State Hwy V',
            'address_physical_city'       => 'Senath',
            'address_physical_postalcode' => '63876',
            'address_physical_country'    => 'US'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 8

        $data = [
            'company_name'                => 'Randy Carter Farms',
            'contact_name'                => 'Byron Small',
            'contact_phone'               => '573-888-7693',
            'contact_email'               => 'ryan.boozer@hotmail.com',
            'address_billing_line_1'      => '9810 State Hwy V',
            'address_billing_city'        => 'Senath',
            'address_billing_postalcode'  => '63876',
            'address_billing_country'     => 'US',
            'address_physical_line_1'     => '9810 State Hwy V',
            'address_physical_city'       => 'Senath',
            'address_physical_postalcode' => '63876',
            'address_physical_country'    => 'US'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 9

        $data = [
            'company_name'                => 'Distretti Farms',
            'contact_name'                => 'Byron Small',
            'contact_phone'               => '573-888-7693',
            'contact_email'               => 'scottiej516@yahoo.com',
            'address_billing_line_1'      => '9810 State Hwy V',
            'address_billing_city'        => 'Senath',
            'address_billing_postalcode'  => '63876',
            'address_billing_country'     => 'US',
            'address_physical_line_1'     => '9810 State Hwy V',
            'address_physical_city'       => 'Senath',
            'address_physical_postalcode' => '63876',
            'address_physical_country'    => 'US',
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 10

        $data = [
            'company_name'                => 'Jackson Farms',
            'contact_name'                => 'Byron Small',
            'contact_phone'               => '573-888-7693',
            'contact_email'               => 'jamersonfarms@gmail.com',
            'address_billing_line_1'      => '9810 State Hwy V',
            'address_billing_city'        => 'Senath',
            'address_billing_postalcode'  => '63876',
            'address_billing_country'     => 'US',
            'address_physical_line_1'     => '9810 State Hwy V',
            'address_physical_city'       => 'Senath',
            'address_physical_postalcode' => '63876',
            'address_physical_country'    => 'US'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 11

        $data = [
            'company_name'                => 'Droke Farms',
            'contact_name'                => 'Byron Small',
            'contact_phone'               => '573-888-7693',
            'contact_email'               => 'stevedroke@yahoo.com',
            'address_billing_line_1'      => '9810 State Hwy V',
            'address_billing_city'        => 'Senath',
            'address_billing_postalcode'  => '63876',
            'address_billing_country'     => 'US',
            'address_physical_line_1'     => '9810 State Hwy V',
            'address_physical_city'       => 'Senath',
            'address_physical_postalcode' => '63876',
            'address_physical_country'    => 'US'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 12

        $data = [
            'company_name'                => 'Jamerson Farms',
            'contact_name'                => 'Byron Small',
            'contact_phone'               => '573-888-7693',
            'contact_email'               => 'jamersonfarms@gmail.com',
            'address_billing_line_1'      => '9810 State Hwy V',
            'address_billing_city'        => 'Senath',
            'address_billing_postalcode'  => '63876',
            'address_billing_country'     => 'US',
            'address_physical_line_1'     => '9810 State Hwy V',
            'address_physical_city'       => 'Senath',
            'address_physical_postalcode' => '63876',
            'address_physical_country'    => 'US'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 13

        $data = [
            'company_name'                => 'Trey Irvin Farms',
            'contact_name'                => 'Byron Small',
            'contact_phone'               => '573-888-7693',
            'contact_email'               => 'byron@usagnetwork.com',
            'address_billing_line_1'      => '9810 State Hwy V',
            'address_billing_city'        => 'Senath',
            'address_billing_postalcode'  => '63876',
            'address_billing_country'     => 'US',
            'address_physical_line_1'     => '9810 State Hwy V',
            'address_physical_city'       => 'Senath',
            'address_physical_postalcode' => '63876',
            'address_physical_country'    => 'US'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 14

        $data = [
            'company_name'                => 'Lattech',
            'contact_name'                => 'Truhann',
            'address_billing_country'     => 'ZA'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 15

        $data = [
            'company_name'                => 'Disney Park',
            'address_billing_country'     => 'US'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 16

        $data = [
            'company_name'                => 'B Gander Agri'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 17

        $data = [
            'company_name'                => 'Case IH'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 18

        $data = [
            'company_name'                => 'Gray Farms'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 19

        $data = [
            'company_name'                => 'WVMGMT'
        ];
        $company_id = DB::connection('mysql_root')->table('companies')->insertGetId($data);
        $companies[$data['company_name']] = $data;
        $companies[$data['company_name']]['company_id'] = $company_id;
        $companies_by_id[$company_id] = $data;

        // -------------------------------------------------------------------- 20

        // 2.) CREATE ROLES

        $roles = [];

        foreach($companies as $company_name => $company){

            // Create <Company Users> Role
            $users_role_name = $company_name . ' Users';
            $users_role_data = [
                'role_name'  => $users_role_name,
                'company_id' => $company['company_id']
            ];
            $users_role_id = DB::connection('mysql_root')->table('roles')->insertGetId($users_role_data);
            $roles[$users_role_name] = $users_role_data;
            $roles[$users_role_name]['role_id'] = $users_role_id;

            // Create <Company Managers> Role
            $manage_role_name = $company_name . ' Managers';
            $manage_role_data = [
                'role_name'  => $manage_role_name,
                'company_id' => $company['company_id']
            ];
            $manage_role_id = DB::connection('mysql_root')->table('roles')->insertGetId($manage_role_data);
            $roles[$manage_role_name] = $manage_role_data;
            $roles[$manage_role_name]['role_id'] = $manage_role_id;

        }

        // 3.) ADD ROLE SECURITY RULES

        $sec_cc      = DB::connection('mysql_root')->table('companies')->where('company_name', 'LF SA')->first();
        $subsystems  = DB::connection('mysql_root')->table('subsystems')->get()->keyBy('subsystem_name')->toArray();
        $permissions = DB::connection('mysql_root')->table('permissions')->get()->keyBy('permission_name')->toArray();
        
        $standard_users_rules_json    = '[{"subsystem":"Map","permissions":["View"]},{"subsystem":"Dashboard","permissions":["View"]},{"subsystem":"Soil Moisture","permissions":["View","Graph","Edit"]},{"subsystem":"Cultivars","permissions":["View","Edit"]},{"subsystem":"Cultivar Stages","permissions":["View","Add","Edit","Delete"]},{"subsystem":"Well Controls","permissions":["View","Edit","Graph"]},{"subsystem":"Meters","permissions":["View","Edit","Graph"]},{"subsystem":"Node Config","permissions":["View"]},{"subsystem":"Sensor Types","permissions":["View"]}]';
        
        $standard_managers_rules_json = '[{"subsystem":"Map","permissions":["View"]},{"subsystem":"Dashboard","permissions":["View"]},{"subsystem":"Field Management","permissions":["View","Edit"]},{"subsystem":"Soil Moisture","permissions":["View","Add","Edit","Delete","Graph","Export"]},{"subsystem":"Cultivars","permissions":["View","Add","Edit","Delete"]},{"subsystem":"Cultivar Stages","permissions":["View","Add","Edit","Delete"]},{"subsystem":"Cultivar Templates","permissions":["View","Add","Edit","Delete","Import","Export"]},{"subsystem":"Well Controls","permissions":["View","Add","Edit","Delete","Graph","Export","Toggle"]},{"subsystem":"Meters","permissions":["View","Add","Edit","Delete","Graph","Export"]},{"subsystem":"Node Config","permissions":["View","Add","Edit","Delete","Import","Export","Reboot","Flash"]},{"subsystem":"Sensor Types","permissions":["View","Add","Edit","Delete"]},{"subsystem":"Users","permissions":["View","Add","Edit","Delete","Reset Password"]},{"subsystem":"Roles","permissions":["View","Add","Edit","Delete","Import","Export"]},{"subsystem":"Groups","permissions":["View","Add","Edit","Delete"]},{"subsystem":"Security Rules","permissions":["View","Add","Edit","Delete"]},{"subsystem":"Entities","permissions":["View","Add","Edit"]}]';

        // Insert Security Templates

        DB::connection('mysql_root')->table('security_templates')->insert([
            'template_name' => 'Standard Users',
            'template_data' => $standard_users_rules_json,
            'company_id'    => $sec_cc->id
        ]);

        DB::connection('mysql_root')->table('security_templates')->insert([
            'template_name' => 'Standard Managers',
            'template_data' => $standard_managers_rules_json,
            'company_id'    => $sec_cc->id
        ]);

        $standard_users_rules = json_decode($standard_users_rules_json, true);
        $standard_managers_rules = json_decode($standard_managers_rules_json, true);

        // Apply Rules to each Role
        
        foreach ($roles as $role_name => $role_data){

            if(strpos($role_name, 'Users') !== false){
                $rules = $standard_users_rules;
            } else {
                $rules = $standard_managers_rules;
            }

            foreach($rules as $rule){

                // Insert Base Security Rule
                $sec_rule_id = DB::connection('mysql_root')->table('security_rules')->insertGetId([
                    'company_id'   => $role_data['company_id'],
                    'role_id'      => $role_data['role_id'],
                    'subsystem_id' => $subsystems[ $rule['subsystem'] ]->id,
                ]);

                // Insert Security Rule Permissions
                foreach($rule['permissions'] as $perm){
                    DB::connection('mysql_root')->table('security_rules_permissions')->insert([
                        'security_rule_id' => $sec_rule_id,
                        'permission_id'    => $permissions[ $perm ]->id
                    ]);
                }

                // Insert Security Rule Company
                DB::connection('mysql_root')->table('security_rules_companies')->insert([
                    'security_rule_id' => $sec_rule_id,
                    'company_id'       => $role_data['company_id'],
                ]);
            }
        }

        // 4.) ASSIGN USERS TO COMPANIES (27)

        $users_companies = [
            'Dave Mayers'       => 'LF SA',               /* dave@liquidfibre.com */
            'Chris Pillow'      => 'LF SA',               /* chris@usagnetwork.com */
            'Fritz Bester'      => 'LF SA',               /* fritzbester@gmail.com */
            'Byron Small'       => 'LF SA',               /* smallincfarms@gmail.com */
            'Francois D'        => 'LF SA',               /* francoisd@ait.global */
            'Fritz'             => 'LF SA',               /* fritz@liquidfibre.com */
            'dmtimport'         => 'LF SA',               /* dmt@liquidfibre.com */
            'Truhann'           => 'LF SA',               /* truhann.vanderpoel@lattech.co.za */
            'Brad Rathje'       => 'AC USA',               /* brad@liquidfibre.com */
            'US AG NETWORK'     => 'USAGN',               /* byron@usagnetwork.com */
            'Chris'             => 'USAGN',               /* chris@usarpma.com */
            'Larry Sarver'      => 'Tucor',               /* lsarver@Tucor.com */
            'Warren Moik'       => 'Tucor',               /* wmoik@tucor.com */
            'Ben Gander'        => 'B Gander Agri',       /* bganderagri@gmail.com */
            'Ryan Boozer'       => 'Distretti Farms',     /* ryan.boozer@hotmail.com */
            'Randy Carter'      => 'Randy Carter Farms',  /* wrcarter1967@hotmail.com */
            'Del Massey'        => 'Case IH',             /* del.massey@caseih.com */
            'Randy Gray'        => 'Gray Farms',          /* randy@gray-farms.com */
            'Brad Gray'         => 'Gray Farms',          /* brad@gray-farms.com */
            'Benjamin Leachman' => 'WVMGMT',              /* benjaminl@wvmgmt.com */
            'Alaina Casazza'    => 'Greenspan',           /* acasazza@wvmgmt.com */
            'Jason Chandler'    => 'Chandler Farms',      /* chandlerfarms@sbcglobal.net */
            'Legacy Equipment'  => 'Legacy Equipment',    /* browland@legacyequipment.com */
            'Steve Droke'       => 'Droke Farms',         /* stevedroke@yahoo.com */
            'Trey Irvin'        => 'Trey Irvin Farms',    /* jdirvin3@yahoo.com */
            'Scottie Jackson'   => 'Jackson Farms',       /* scottiej516@yahoo.com */
            'Tim Jamerson'      => 'Jamerson Farms'       /* jamersonfarms@gmail.com */
        ];

        // Set User-Company Membership via company_id
        foreach($users_companies as $user_name => $company_name){
            if(!empty($companies[$company_name])){
                DB::connection('mysql_root')->table('users')->where('name', $user_name)
                ->update(['company_id' => $companies[ $company_name ]['company_id'] ]);
            }
        }

        // 5.) ASSIGN USERS TO COMPANY ROLES
        foreach($users_companies as $user_name => $company_name){
            if(!empty($companies[$company_name])){

                // Get User
                $user = DB::connection('mysql_root')->table('users')->where('name', $user_name)->first();

                // Determine New Role Name
                if($user->role == 'User'){
                    $role_name = $company_name . ' Users';

                } else if($user->role == 'Dealer' || $user->role == 'Admin'){
                    $role_name = $company_name . ' Managers';
                }

                // Get Role
                $role = DB::connection('mysql_root')->table('roles')->where('role_name', $role_name)->first();

                // Assign User to Role
                DB::connection('mysql_root')->table('users')->where('name', $user_name)->update([ 'role_id' => $role->id ]);
            }
        }

        // 6.) REMOVE OLD USERS (4)

        $remove_users = [
            'Demo',
            'demo',
            'Andy Body',
            'Smith Farms'
        ];

        foreach($remove_users as $user_name){
            DB::connection('mysql_root')->table('users')->where('name', $user_name)->delete();
        }

        // 7.) ASSIGN NODES (+ANCILLARY ROWS) TO COMPANIES

        $nodes = [
            'Greenspan' => [
                '20808-0',
                '20824-0',
                '7952-0',
                '8270-0'
            ],
            'AC USA' => [
                '0x000d1208-0',
                '0x000d12c0-0',
                '250874-0',
                '354679096315711-0',
                '354679096315711-1'
            ],
            'Small Inc.' => [
                '0x000d0e72-0',
                '356441115180248-0',
                'M000002561-0',
                'M000002630-0'
            ],
            'USAGN' => [
                '0x000d0308-0',
                '356441115173466-0',
                '356441115234839-0',
                '356441115263614-0',
                '356441115263614-1',
                '356441115313906-0',
                '356441115316396-0',
                '0x000869f7-0',
                '0x000d1241-0',
                '0x000d129c-0',
                '0x000D17bf-0',
                '0x000d17ee-0',
                '0x000d1c87-0'
            ],
            'LF SA' => [
                '354679096314086-1'
            ],
            'Chandler Farms' => [
                'M000002704-0',
                'M000002705-0',
                'M000002706-0',
                'M000002707-0',
                'M000002708-0',
                'M000002709-0',
                'M000002710-0',
                'M000002711-0',
                'M000002712-0',
                'M000002713-0',
                'M000002714-0',
                'M000002715-0',
                'M000002716-0',
                'M000002717-0',
                'M000002718-0',
                'M000002719-0',
                'M000002720-0',
                'M000002721-0',
                'M000002722-0',
                'M000002723-0',
                'M000002724-0',
                'M000002725-0'
            ],
            'Tucor' => [
                '201557-0',
                'cr1000x2095p10-0',
                'cr1000X2095p12-0',
                'crx10002095p09-0',
                'crx10020959p11-0'
            ],
            'Legacy Equipment' => [
                '0x000d1c83-0',
                '356441115171833-0',
                '356441115172633-0',
                '356441115181022-0',
                '356441115186898-0',
                '356441115228500-0',
                '356441115233484-1',
                '356441115313872-0'
            ],
            'Randy Carter Farms' => [
                'M000001113-0',
                'M000002055-0',
                'M000002128-0',
                'M000002702-0',
                'M000002703-0',
                'M000002728-0',
                'M000002731-0'
            ],
            'Distretti Farms' => [
                '354444114238886-0',
                '356441115168235-0',
                '356441115246429-0',
                '356441115313856-0'
            ],
            'Jackson Farms' => [
                'M000001110-0',
                'M000001211-0',
                'M000001212-0',
                'M000001214-0',
                'M000001251-0',
                'M000001252-0',
                'M000002048-0',
                'M000002515-0'
            ],
            'Droke Farms' => [
                'M000002726-0',
                'M000002727-0'
            ],
            'Jamerson Farms' => [
                'M000001205-0',
                'M000001206-0',
                'M000001213-0',
                'M000001216-0',
                'M000001223-0',
                'M000001224-0',
                'M000001225-0',
                'M000001228-0',
                'M000001235-0',
                'M000001240-0',
                'M000001249-0',
                'M000001253-0',
                'M000002059-0',
                'M000002072-0',
                'M000002085-0',
                'M000002116-0',
                'M000002510-0',
                'M000002729-0',
                'M000002730-0'
            ],
            'Trey Irvin Farms' => [
                'M000001234-0',
                'M000001482-0',
                'M000002514-0'
            ],
            'Lattech' => [
                '354679096314151-0'
            ],
            'Disney Park' => [
                '02c92571e032-1001', /* * */
                '02c92571e032-1013',
                '02c92571e032-1025',
                '02c92571e032-1037',
                '02c92571e032-1049',
                '02c92571e032-1061',
                '02c92571e032-1073',
                '02c92571e032-1085',
                '02c92571e032-1097',
                '02c92571e032-1109',
                '02c92571e032-1121',
                '201529-0',
                '202822-0',
                '356441115174092-3',
                '461e8d27e611-1001',
                '461e8d27e611-1013',
                '461e8d27e611-1025',
                '461e8d27e611-1037',
                '79bc0df5df0c-1001',
                '79bc0df5df0c-1037',
                '94d4f77e847-1037',  /* e94d4f77e847 */
                '9d8c95f913bf-1001',
                '9d8c95f913bf-1013',
                '9d8c95f913bf-1025',
                '9d8c95f913bf-1037',
                '9d8c95f913bf-1049',
                '9d8c95f913bf-1061',
                '9d8c95f913bf-1073',
                '9d8c95f913bf-1085',
                '9d8c95f913bf-1097',
                '9d8c95f913bf-1109',
                '9d8c95f913bf-1121',
                '9d8c95f913bf-1133',
                '9d8c95f913bf-1145',
                'abf1f342ad68-1001',
                'abf1f342ad68-1013',
                'abf1f342ad68-1025',
                'abf1f342ad68-1049',
                'abf1f342ad68-1061',
                'abf1f342ad68-1073',
                'abf1f342ad68-1085',
                'e94d4f77e847-1001',
                'e94d4f77e847-1013',
                'e94d4f77e847-1025',
                'e94d4f77e847-1037',
                'e94d4f77e847-1061',
                'e94d4f77e847-1073'
            ]
        ];

        // Set Node-Company Membership via company_id (along with associated records)
        foreach($nodes as $company_name => $node_addresses){
            foreach($node_addresses as $node_address){
                if(DB::connection('mysql_root')->table('hardware_config')->where('node_address', $node_address)->exists()){
                    $company_id = $companies[$company_name]['company_id'];

                    // Update Node Company
                    DB::connection('mysql_root')->table('hardware_config')->where('node_address', $node_address)
                    ->update([ 'company_id' => $company_id ]);

                    // Get associated Field Record
                    $field = DB::connection('mysql_root')->table('fields')->where('node_id', $node_address)->first();

                    // ensure field exists
                    if(!empty($field) && !empty($field->id)){

                        // Update Associated Field Record
                        DB::connection('mysql_root')->table('fields')->where('node_id', $node_address)
                        ->update([ 'company_id' => $company_id ]);

                        // Get assocaited cultivars_management (cm) record
                        $cm = DB::connection('mysql_root')->table('cultivars_management')->where('field_id', $field->id)->first();

                        // ensure cm record exists
                        if(!empty($cm) && !empty($cm->id)){

                            // Update cm Record
                            DB::connection('mysql_root')->table('cultivars_management')->where('field_id', $field->id)
                            ->update([ 'company_id' => $company_id ]);

                            // Update Cultivar Stages via cm record
                            DB::connection('mysql_root')->table('cultivars')->where('cultivars_management_id', $cm->id)
                            ->update([ 'company_id' => $company_id ]);
                        }
                    }
                }
            }
        }

        // 8.) REMOVE OLD NODES

        // Nodes to remove (Requested via Spreadsheet)
        $remove_nodes = [
            //'b034b45d179d-1001',
            '0x000d16bf-0',
            '0x12345678-0'
        ];

        // Remove Node and associated records
        foreach($remove_nodes as $node_address){

            // get field
            $field = DB::connection('mysql_root')->table('fields')->where('node_id', $node_address)->first();

            // ensure field exists
            if(!empty($field) && !empty($field->id)){

                // get cm record
                $cm = DB::connection('mysql_root')->table('cultivars_management')->where('field_id', $field->id)->first();

                // ensure cm record exists
                if(!empty($cm) && !empty($cm->id)){

                    // delete cultivar stages via cm record
                    DB::connection('mysql_root')->table('cultivars')->where('cultivars_management_id', $cm->id)->delete();

                    // delete cm record
                    DB::connection('mysql_root')->table('cultivars_management')->where('field_id', $field->id)->delete();
                }
            }
            // delete field record
            DB::connection('mysql_root')->table('fields')->where('node_id', $node_address)->delete();

            // delete node record
            DB::connection('mysql_root')->table('hardware_config')->where('node_address', $node_address)->delete();
        }

        // 9.) CLONE LF SENSORS TO COMPANIES

        foreach($companies as $company){

            // Skip Liquid Fibre (Sensors already exist)
            if($company['company_name'] == 'LF SA'){ continue; }

            $copies = [];
            $rows = DB::table('hardware_management')->where('company_id', 1)->get();

            foreach($rows as $row){
                $copies[] = $row;
            }

            foreach($copies as &$row){
                $row->id = NULL;
                $row->company_id = $company['company_id'];
                DB::table('hardware_management')->insert((array) $row);
            }
        }

        // 10.) ASSIGN CLONED SENSORS TO CORRECT NODES
        $nodes = DB::connection('mysql_root')->table('hardware_config')->get();
        foreach($nodes as $node){
            if(!empty($node->probe_make)){
                // fetch existing sensor that node points to
                $old_sensor = DB::connection('mysql_root')->table('hardware_management')
                ->where('id', $node->probe_make)->first();
                // ensure sensor still exists
                if($old_sensor){
                    // get the new sensor that was cloned for this node's company
                    $new_sensor = DB::connection('mysql_root')->table('hardware_management')
                    ->where('device_make', $old_sensor->device_make)
                    ->where('company_id', $node->company_id)
                    ->first();
                    // update node to point to the correct sensor for it's company
                    DB::connection('mysql_root')->table('hardware_config')
                    ->where('node_address', $node->node_address)
                    ->update([ 'probe_make' => $new_sensor->id ]);
                } else {
                    // node is pointing to a non-existant sensor, make NULL?
                    Log::debug("Migration: Node {$node->node_address}'s probe_make field points to non-existant sensor: {$node->probe_make}");
                }
            } else {
                Log::debug("Migration: Node {$node->node_address}'s probe_make field is NULL");
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
