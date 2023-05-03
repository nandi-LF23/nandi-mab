<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

use App\Models\hardware_config;
use App\Models\node_data;
use App\Models\node_data_meter;
use App\Calculations;
use App\User;

use App\Http\Controllers;

use App\Models\Setting;
use App\Models\Company;
use App\Models\cultivars_management;
use App\Integrations\JohnDeere\API\EventSubscriptions as EventSubscriptionsAPI;

Route::get('/fix_averages', function (Request $request) {

    set_time_limit(0);

    $nodes = [
        '60528-21471', '60528-21472', '60528-21473', '60528-21475',
        '60528-21485', '60528-21486', '60528-21487', '60528-21501',
        '60528-21502', '60528-21503', '60528-21505', '60528-21647',
        '62639-25931', '62639-25934', '62639-25946', '62639-25952',
        '0x000d0b27-0'
    ];

    $nr_of_updates = 0;

    foreach (node_data::whereIn('probe_id', $nodes)->cursor() as $row) {
        $count = 0;
        $count += $row->sm1 > 0 ? 1 : 0;
        $count += $row->sm2 > 0 ? 1 : 0;
        $count += $row->sm3 > 0 ? 1 : 0;
        $count += $row->sm4 > 0 ? 1 : 0;
        $count += $row->sm5 > 0 ? 1 : 0;
        $count += $row->sm6 > 0 ? 1 : 0;
        if ($count) {
            $avg = ($row->sm1 + $row->sm2 + $row->sm3 + $row->sm4 + $row->sm5 + $row->sm6) / $count;
            $row->update(['average' => $avg]);
            $nr_of_updates++;
        }
    }

    return response()->json(['nr_of_updates' => $nr_of_updates]);
});

Route::get('/testencrypt', function (Request $request) {
    $company_id = 20;
    $context = base64_encode(Crypt::encryptString(json_encode(['restricted_to' => $company_id])));
    return response()->json(['context' => $context]);
});

Route::get('/testdecrypt/{secret}', function (Request $request) {
    $result = '';
    if ($request->secret) {
        $result = Crypt::decryptString(base64_decode($request->secret));
    }
    return response()->json(['string' => $result]);
});

Route::get('/fix_missing_cm_records', function (Request $request) {

    $count = 0;

    $result = DB::table('hardware_config')
        ->join('fields', 'fields.node_id', '=', 'hardware_config.node_address')
        ->leftJoin('cultivars_management', 'cultivars_management.field_id', '=', 'fields.id')
        ->select(['fields.id AS field_id', 'fields.company_id AS field_cc_id', 'cultivars_management.id AS cm_id'])
        ->get()
        ->toArray();

    if ($result) {
        foreach ($result as $row) {
            if ($row->cm_id === NULL) {

                $cm = new cultivars_management();
                $cm->field_id = $row->field_id;
                $cm->company_id = $row->field_cc_id;
                $cm->NI = 1;
                $cm->NR = 1;
                $saved = $cm->save();
                if ($saved) {
                    $count++;
                }
            }
        }
    }

    return response()->json(['message' => 'DONE', 'inserts' => $count]);
});

Route::get('/test', function (Request $request) {
    $old_cc_sensors = DB::table('hardware_management')->where('company_id', 1)->pluck('id');
    return response()->json(['ids' => $old_cc_sensors]);
});

Route::get('/fix_dates', function (Request $request) {
    $nodes = hardware_config::all();
    foreach ($nodes as $node) {
        if ($node->node_type == 'Nutrients') {
            $nutr_data = nutrients_data::where('node_address', $node->node_address)->orderBy('id', 'desc')->first();
            if ($nutr_data) {
                $node->date_time = $nutr_data->date_sampled;
                $node->save();
            }
        } else if ($node->node_type == 'Soil Moisture') {
            $node_data = node_data::where('probe_id', $node->node_address)->orderBy('id', 'desc')->first();
            if ($node_data) {
                $node->date_time = $node_data->date_time;
                $node->save();
            }
        } else {
            $node_data = node_data_meter::where('node_id', $node->node_address)->orderBy('idwm', 'desc')->first();
            if ($node_data) {
                $node->date_time = $node_data->date_time;
                $node->save();
            }
        }
    }
    return response()->json(['message' => 'DONE']);
});

// Tested Working
// Route::get('/backfill_catm', function(Request $request){

//     return; // REMOVE SAFETY BEFORE USE

//     $slice = DB::table('raw_data_catm')
//     ->where('created_at', '>', '2021-09-30 08:00:00')
//     ->where('created_at', '<', '2021-09-30 16:00:00')
//     ->get();

//     if($slice){
//         foreach($slice as $row){

//             $req = new Request(
//                 ['imei' => $row->device_id, 'no_raw_save' => 1, 'update_existing' => 1],
//                 ['imei' => $row->device_id, 'no_raw_save' => 1, 'update_existing' => 1],
//                 [],[],[],[],
//                 $row->device_data
//             );

//             $di = new App\Http\Controllers\DataImportController();
//             $di->catmImport($req, $row->device_id);

//         }
//     }

//     return response()->json(['message' => 'done']);
// });

// Tested Working
// Route::get('/backfill_dmt', function(Request $request){

//     return; // REMOVE SAFETY BEFORE USE

//     set_time_limit(0);

//     $slice = DB::table('raw_data_dmt')
//     ->where('created_at', '>', '2021-09-15 00:00:00')
//     ->where('created_at', '<', '2021-10-01 00:00:00')
//     ->get();

//     if($slice){
//         $di = new App\Http\Controllers\DataImportController();
//         foreach($slice as $row){
//             $params = json_decode($row->device_data, true);
//             if($params){
//                 $req = new Request(
//                     ['imei' => $row->device_id, 'no_raw_save' => 1, 'update_existing' => 1] + $params,
//                     [],
//                     [],[],[],[],
//                     $row->device_data
//                 );
//                 $di->dmtImport($req, $row->device_id);
//             }
//         }
//     }

//     return response()->json(['message' => 'done']);
// });

// Route::get('/backfill_fieldwise', function(Request $request){

//     // return; // REMOVE SAFETY BEFORE USE

//     set_time_limit(0);

//     $slice = DB::table('raw_data_fieldwise')
//     ->where('created_at', '>', '2021-07-15 00:00:00')
//     ->where('created_at', '<', '2021-08-01 00:00:00')
//     ->get();

//     if($slice){
//         $di = new App\Http\Controllers\DataImportController();
//         foreach($slice as $row){
//             $req = new Request(
//                 ['OpCode' => 'probedata', 'no_raw_save' => 1, 'update_existing' => 1, 'DataJson' => $row->device_data],
//                 [],
//                 [],[],[],[],
//                 ''
//             );
//             $di->fieldwiseImport($req);
//         }
//     }

//     return response()->json(['message' => 'done']);
// });

Route::get('/mab_flush', function (Request $request) {
    Artisan::call('cache:clear');
    Artisan::call('optimize:clear');
    Artisan::call('config:cache');
    Cache::clear();
    return response()->json(['message' => 'done']);
});

// Route::get('/backfill_banner', function(Request $request){

//     $slice = DB::table('raw_data_banner')
//     ->where('created_at', '>', '2021-06-01 00:00:00')
//     ->where('created_at', '<', '2021-10-06 09:00:00')
//     ->where('device_id', '')
//     ->get();

// TODO

//     return response()->json(['message' => 'done']);
// });

Route::group(['middleware' => ['cors', 'auth:api']], function () {

    // Map
    Route::post('/map_data', 'MapController@index')->name('map_data');

    // Field Map (Field Management)
    Route::post('/fieldmap_data', 'FieldMapController@index')->name('fieldmap_data');

    // Dashboard
    Route::post('/dashboard_table', 'DashboardController@index')->name('dashboard_table');

    // Node Config
    // -----------

    // Node Config Table
    Route::post('/hardwareconfigtable', 'HardwareConfigController@NodeConfigTable')->name('hardwareconfig');
    // Node Config Form
    Route::get('/hardwareconfig/{node_address}', 'HardwareConfigController@get')->name('hardwareconfigsingle');
    // Node Config Form Save
    Route::post('/hardwareconfigsave', 'HardwareConfigController@save')->name('hardwareconfigsave'); // validated
    // Node Config New Record
    Route::post('/hardwareconfignew', 'HardwareConfigController@new')->name('hardwareconfignew'); // validated
    // Node Config Get Latest Coords
    Route::get('/hardwareconfiglatestcoords/{node_address}', 'HardwareConfigController@get_latest_coords')->name('hardwareconfiglatestcoords');
    // Node Config Node Exists
    Route::get('/hardwareconfigexists/{node_address}', 'HardwareConfigController@exists')->name('hardwareconfigexists');
    // Node Config Delete Record
    Route::post('/hardwareconfigdestroy', 'HardwareConfigController@destroy')->name('hardwareconfigdestroy'); // validated
    // Node Address Change
    Route::post('/hardwareconfigaddrchange', 'HardwareConfigController@update_address')->name('hardwareconfigaddrchange');
    // Node Reboot Endpoint
    Route::get('/hardwareconfigreboot/{node_address}', 'HardwareConfigController@reboot')->name('hardwareconfigreboot'); // validated
    // Wells Pump Toggle
    Route::get('/hardwareconfigtoggle/{node_address}', 'HardwareConfigController@toggle_wm')->name('toggleWM'); // validated
    // Node Firmware Flash (TODO: Implement)
    Route::get('/hardwareconfigflash/{node_address}', 'HardwareConfigController@flash')->name('hardwareconfigflash');
    // Field Clear Zones
    Route::post('/clearfieldzones', 'FieldsController@clear_field_zones')->name('clearfieldzones');
    // Field Clear Perimeter
    Route::post('/clearfieldperimeter', 'FieldsController@clear_field_perimeter')->name('clearfieldperimeter');

    // Soil Moisture (for Fields)
    // --------------------------

    // Soil Moisture Table
    Route::post('/SMTable', 'HardwareConfigController@SoilMoistureTable')->name('SMTable');
    // Soil Moisture Form Populate (Table Manage Button Destination)
    Route::get('/ManageSM/{node_address}', 'FieldsController@manage_sm')->name('FieldsManage');
    // Soil Moisture Graph (Table Graph Button Destination)
    Route::post('/Graph', 'GraphController@sm_graph')->name('Graph');
    // Soil Moisture Form Update/Save
    Route::post('/ManageSave', 'FieldsController@update_sm')->name('FieldsUpdate'); // validated

    // Nutrients
    // ---------

    // Nutrients Table
    Route::post('/NutrientsTable', 'HardwareConfigController@NutrientsTable')->name('NutrientsTable');
    // Nutrients Form Populate
    Route::get('/ManageNutrients/{node_address}', 'FieldsController@manage_n')->name('ManageNutrients');
    // Nutrients Form Save
    Route::post('/SaveNutrients', 'FieldsController@update_n')->name('SaveNutrients');
    // Load Nutrient Templates
    Route::get('/loadNutrientTemplates/{company_id}', 'NutrientTemplateController@load_templates')->name('LoadNutrientTemplates');
    // Save Nutrient Template
    Route::post('/saveNutrientTemplate', 'NutrientTemplateController@save_template')->name('SaveNutrientTemplate');
    // Remove Nutrient Template
    Route::post('/removeNutrientTemplate', 'NutrientTemplateController@remove_template')->name('RemoveNutrientTemplate');
    // Apply Nutrient Template
    Route::post('/applyNutrientTemplate', 'NutrientTemplateController@apply_template')->name('ApplyNutrientTemplate');
    //save the sensor types
    Route::post('/saveNutriTemplateData', 'NutrientTemplateController@saveNutriTemplateData')->name('saveNutriTemplateData');

    Route::get('/loadNutriTemplateData/{nutriprobe}', 'NutrientTemplateController@loadNutriTemplateData')->name('loadNutriTemplateData');
    Route::get('/loadnodedata/{node_address}', 'NutrientTemplateController@loadnodedata')->name('loadnodedata');
    Route::post('/saveNutriTemplateDataGroup', 'NutrientTemplateController@saveNutriTemplateDataGroup')->name('saveNutriTemplateDataGroup');
    Route::get('/loadNutrientTemplate/{id}', 'NutrientTemplateController@loadNutrientTemplate')->name('loadNutrientTemplate');
    Route::get('/loadNurtrientGroups/{nutriprobe}', 'NutrientTemplateController@loadNutriTemplateDataGroup')->name('loadNutriTemplateDataGroup');

    // Wells
    // -----

    // Wells Table
    Route::post('/WMTable', 'HardwareConfigController@WellsTable')->name('WMTable');
    // Wells Graph
    Route::post('/GraphWM', 'GraphController@wells_graph')->name('GraphWM');
    // Wells Form
    Route::get('/ManageWM/{node_address}', 'FieldsController@manage_wells')->name('FieldsManageWM');

    // Meters (for Fields)
    // -------------------

    // Meters Table
    Route::post('/WMTableV1', 'HardwareConfigController@MetersTable')->name('WMTableV1');
    // Meters Graph
    Route::post('/GraphWMV1', 'GraphController@meters_graph')->name('GraphWMV1');
    // Meters Form
    Route::get('/ManageWMV1/{node_address}', 'FieldsController@manage_meters')->name('FieldsManageWMV1');
    // For Updating both Wells and Meters
    Route::post('/update_wm/{node_address}', 'FieldsController@update_wm')->name('UpdateWMs'); // validated

    // Sensor Types
    // ------------

    // Sensor Types Table
    Route::post('/hardwaremanagementtable', 'HardwareManagementController@SensorTypesTable')->name('hardwaremangementtable');
    // Sensor Types List (By Company)
    Route::post('/hardwaremanagementlist', 'HardwareManagementController@SensorTypesList')->name('hardwaremangementlist');
    // Sensor Types Form
    Route::get('/hardwaremanagementform/{id}', 'HardwareManagementController@SensorTypesForm')->name('hardwaremangementform');
    // Sensor Types Form Update
    Route::post('/hardwaremanagementsave', 'HardwareManagementController@save')->name('hardwaremangementsave'); // validated
    // Sensor Types New Record
    Route::post('/hardwaremanagementnew', 'HardwareManagementController@new')->name('hardwaremangementnew'); // validated
    // Sensor Types Delete Record
    Route::post('/hardwaremanagementdestroy', 'HardwareManagementController@destroy')->name('hardwaremangementdestroy'); // validated
    // Sensor Types Clone
    Route::post('/hardwaremanagementclone', 'HardwareManagementController@clone')->name('hardwaremanagementclone'); // validated

    // Cultivar Management Records
    // ---------------------------

    // Manage Cultivar Form
    Route::get('/ManageCultivars/{fid}', 'CultivarController@cultivar_manage')->name('CultivarManage');
    // Manage Cultivar Form Save
    Route::post('/CultivarSave', 'CultivarController@update')->name('CultivarUpdate'); // validated

    // Cultivar Growth Stages
    // -----------------------

    // Manage Cultivar 'Add Stage' Button
    Route::post('/stageAdd', 'CultivarController@add_stage')->name('stageAdd'); // validated
    // Manage Cultivar 'Update' Row
    Route::post('/stagesUpdate', 'CultivarController@update_stages')->name('stagesUpdate'); // validated
    // Manage Cultivar 'Delete Last Stage' Button
    Route::post('/stageDeleteLast', 'CultivarController@delete_last_stage')->name('stageDeleteLast'); // validated
    // Manage Cultivar 'Visual Editor' Apply Button (Update or Create)
    Route::post('/stagesSet', 'CultivarController@set_stages')->name('stagesSet'); // validated

    // Cultivar Templates 
    // ------------------

    // Load Cultivar Templates
    Route::get('/loadCultiTemplates/{company_id}', 'CultivarController@load_templates')->name('LoadCultiTemplates');
    // Save Cultivar Template
    Route::post('/saveCultiTemplate', 'CultivarController@save_template')->name('SaveCultiTemplate');
    // Remove Cultivar Template
    Route::post('/removeCultiTemplate', 'CultivarController@remove_template')->name('RemoveCultiTemplate');

    // Activity Log
    // ------------

    Route::post('/activity_logs/{company_id?}', 'ActivityLogController@index')->name('activity_logs');

    // User Management
    // ---------------

    // Users Table
    Route::post('/users', 'UserController@index')->name('users');
    // Users Table (NOT USED)
    Route::get('/usersbyrole/{role}', 'UserController@getUsersByRole')->name('usersbyrole');
    // Promote User to admin (admin only)
    Route::post('/promote', 'UserController@promote')->name('promote');

    // Lock User Account (Prevent Login)
    Route::post('/user_lock', 'UserController@lock')->name('user_lock');
    // Unlock User Account (Allow Login)
    Route::post('/user_unlock', 'UserController@unlock')->name('user_unlock');

    // Get users by Company (NOT USED)
    Route::get('/usersbycc', 'UserController@getUsersByCompanyId')->name('usersbycc'); // change to users_list
    // User Delete (Table Button)
    Route::post('/userdestroy', 'UserController@destroy')->name('userdestroy');
    // User Create New (Modal)
    Route::post('/usernew', 'UserController@new')->name('usernew'); // validated
    // User Form (Edit) (By Email)
    Route::get('/user/{email}', 'UserController@getUser')->name('getUser');
    // User Get by ID (NOT USEd)
    Route::get('/user_id/{id}', 'UserController@getUserById')->name('getUserById');
    // User Exists
    Route::get('/userexists/{email}', 'UserController@exists')->name('userexists');
    // User Form Update Password (Modal)
    Route::post('/updatePW', 'UserController@updatePW')->name('updatePW'); // validated
    // Timezone Dropdown
    Route::get('/getTimezones', 'UserController@getTimezones')->name('getTimezones');
    // Users Dropdown (NOT USED)
    Route::get('/user_search', 'UserController@list')->name('user_search');
    // Restrict User (To a specific Entity)
    Route::post('/restrict', 'Auth\ApiAuthController@restrict')->name('restrict');
    // Logout
    Route::post('/logout', 'Auth\ApiAuthController@logout')->name('logout');

    // Companies
    // ---------

    /* table */
    Route::post('/companies',       'CompanyController@index')->name('companies');
    Route::post('/companiesAll',       'CompanyController@indexAll')->name('companiesAll');

    /* light */
    Route::post('/companies_list',  'CompanyController@list')->name('companies_list'); // with Context

    /* distributors */
    Route::post('/companies_dist_list', 'CompanyController@dist_list')->name('companies_dist_list');

    Route::get('/company/{id}',    'CompanyController@get')->name('company')->where('id', '[0-9]+');
    Route::get('/company_objects/{id}', 'CompanyController@objects')->name('company_objects')->where('id', '[0-9]+');
    Route::post('/company_report',  'CompanyController@report')->name('company_report')->where('id', '[0-9]+');
    Route::post('/company_add',     'CompanyController@add')->name('company_add');
    Route::post('/company_update',  'CompanyController@update')->name('company_upd');
    Route::post('/company_destroy', 'CompanyController@destroy')->name('company_dest');
    Route::post('/company_lock',    'CompanyController@lock')->name('company_lock');
    Route::post('/company_unlock',  'CompanyController@unlock')->name('company_unlock');
    Route::post('/company_move',    'CompanyController@move')->name('company_move');
    Route::get('/company_move_om/{id}', 'CompanyController@move_meta_old')->name('company_move_om');
    Route::get('/company_move_nm/{id}', 'CompanyController@move_meta_new')->name('company_move_nm');

    // Company Options
    // ---------------

    Route::get('/get_company_opts/{id}', 'CompanyOptionsController@get_company_opts')->name('get_company_opts');
    Route::post('/upd_company_opts', 'CompanyOptionsController@update_company_opts')->name('upd_company_opts');

    // Roles
    // -----

    /* table */
    Route::post('/roles',        'RoleController@index')->name('roles');
    // Add roles_list (with Context)
    Route::get('/role/{id}',    'RoleController@get')->name('role')->where('id', '[0-9]+');
    Route::post('/roles_by_cc',  'RoleController@getRolesByCompanyId')->name('getRolesByCompanyId');
    Route::post('/role_add',     'RoleController@add')->name('roles_add');
    Route::post('/role_update',  'RoleController@update')->name('roles_upd');
    Route::post('/role_destroy', 'RoleController@destroy')->name('roles_dest');

    // Roles Security Rules
    // --------------------

    Route::get('/role_rules_get/{role_id}', 'SecurityRuleController@get')->name('role_rules_get')->where('role_id', '[0-9]+');
    // Add role_rules_list (with Context)
    Route::post('/role_rule_add', 'SecurityRuleController@add')->name('role_rule_add');
    Route::post('/role_rule_update', 'SecurityRuleController@update')->name('role_rule_edit');
    Route::post('/role_rule_destroy', 'SecurityRuleController@destroy')->name('role_rule_destroy');

    // Security Templates
    // ------------------

    Route::get('/sec_tpl_get', 'SecurityTemplateController@get')->name('sec_tpl_get');
    Route::post('/sec_tpl_save', 'SecurityTemplateController@save')->name('sec_tpl_save');
    Route::post('/sec_tpl_apply', 'SecurityTemplateController@apply')->name('sec_tpl_apply');
    Route::post('/sec_tpl_destroy', 'SecurityTemplateController@destroy')->name('sec_tpl_destroy');

    // Subsystems Utility
    Route::get('/subsystems', 'SecurityRuleController@getSubsystems')->name('subsystems');

    // Groups
    // ------

    // get groups with members (and other meta data) (heavy) (table)
    Route::post('/groups', 'GroupController@index')->name('groups');
    // get groups list (without members) (lite) (ALL Groups)
    Route::post('/groups_list', 'GroupController@list')->name('groups_list'); // groups_list (with Context)
    // add groups (with optional members)
    Route::post('/group_add', 'GroupController@add')->name('group_add');
    // update group (replace members)
    Route::post('/group_update', 'GroupController@update')->name('group_update');
    // remove group
    Route::post('/group_destroy', 'GroupController@destroy')->name('group_destroy');

    // Connections
    // -----------

    Route::post('/connections',            'ConnectionController@index')->name('connections');
    Route::post('/connections_add',        'ConnectionController@add')->name('connections_add');
    Route::post('/connections_update',     'ConnectionController@update')->name('connections_update');
    Route::post('/connections_destroy',    'ConnectionController@destroy')->name('connections_destroy');
    Route::post('/connections_connect',    'ConnectionController@connect')->name('connections_connect');
    Route::post('/connections_disconnect', 'ConnectionController@disconnect')->name('connections_disconnect');

    // Data Formats
    // -----------

    Route::post('/dataformats',            'DataFormatController@index')->name('dataformats');
    Route::get('/dataformats_list',        'DataFormatController@list')->name('dataformats_list');
    Route::post('/dataformats_add',        'DataFormatController@add')->name('dataformats_add');
    Route::post('/dataformats_update',     'DataFormatController@update')->name('dataformats_update');
    Route::post('/dataformats_destroy',    'DataFormatController@destroy')->name('dataformats_destroy');

    // Data Imports
    // ------------

    // CSV Import
    Route::post('/dataImport', 'FileImportController@import')->name('dataImport');

    // Helpdesk
    // --------

    Route::post('/logticket', 'HelpdeskController@logTicket')->name('logTicket');

    // Integration Manager
    // -------------------

    // Disabled for now
    // Route::get("/oauth2/{integration}/revoke", 'IntegrationController@token_revoke')->name('oauth2.token_revoke');

    //GRAPH EXPORTS CSV

    //export nutri data
    Route::post('/export_csv_nutri', 'DataExportController@export_csv_nutri')->name('export_csv_nutri');
});

// User Login
Route::post('/login', 'Auth\ApiAuthController@login')->middleware(['cors', 'throttle:limitlogins'])->name('login');

// User Registration (Disabled for Now)
// Route::post('/register','Auth\ApiAuthController@register')->name('register.api');

// Forgot Password - Send Reset Link to User via Email
Route::post('/forgot', 'Auth\ApiAuthController@forgot');
// Reset Password
Route::post('/reset', 'Auth\ApiAuthController@reset')->name('password.update');

// DMT Import
Route::post('/dmtimport/{imei}', 'DataImportController@dmtImport')->name('dmtImport')->middleware('auth.basic.once');
// CatM Import
Route::post('/catmimport/{imei}', 'DataImportController@catmImport')->name('catmImport');
// Fieldwise Import
Route::post('/fwimport', 'DataImportController@fieldwiseImport')->name('fwImport');
// Banner Import
Route::match(['get', 'post'], '/bannerimport', 'DataImportController@bannerImport')->name('bannerImport');
// Campbell Import
Route::post('/campbell_import', 'DataImportController@campbellImport')->name('campbellImport');

// Data Views

// DMT Recent Raw Data (Debugging Interface)
Route::get('/dmt_recent/{imei?}', 'DataViewController@dmtRecent')->name('dmtRecent');
// CatM Recent
Route::get('/catm_recent/{imei?}', 'DataViewController@catmRecent')->name('catmRecent');
// Banner Recent Raw Data (Debugging Interface)
Route::get('/banner_recent/{device_id?}', 'DataViewController@bannerRecent')->name('bannerRecent');
// Fieldwise Recent Raw Data (Debugging Interface)
Route::get('/fieldwise_recent/{device_id?}', 'DataViewController@fieldwiseRecent')->name('fieldwiseRecent');
