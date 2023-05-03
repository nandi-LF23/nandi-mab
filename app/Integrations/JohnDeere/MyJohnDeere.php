<?php

namespace App\Integrations\JohnDeere;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use TorMorten\Eventy\Facades\Events as Eventy;
use MacsiDigital\OAuth2\Integration;

use App\Integrations\JohnDeere\API\Assets as AssetsAPI;
use App\Integrations\JohnDeere\API\Clients as ClientsAPI;
use App\Integrations\JohnDeere\API\Farms as FarmsAPI;
use App\Integrations\JohnDeere\API\Fields as FieldsAPI;
use App\Integrations\JohnDeere\API\Flags as FlagsAPI;
use App\Integrations\JohnDeere\API\FlagCategories as FlagCategoriesAPI;
use App\Integrations\JohnDeere\API\Boundaries as BoundariesAPI;
use App\Integrations\JohnDeere\API\EventSubscriptions as EventSubscriptionsAPI;
use App\Integrations\JohnDeere\AssetSyncer;
use App\Integrations\JohnDeere\FlagSyncer;

use App\Jobs\ProcessIntegrationJob;

use App\Models\Setting;
use App\Integrations\IntegrationBase;
use App\Calculations;
use App\Utils;

class MyJohnDeere extends IntegrationBase {

    public function __construct($options){
        parent::__construct($options);
    }

    public function setup($company_id)
    {
        $int_name     = "{$this->slug}-{$company_id}";
        $url_slug     = "{$this->slug}";
        $meta_key     = "{$url_slug}.oauth_meta";
        $conf_key     = "{$this->slug}.oauth_conf";
        $integration  = [];
        $refresh_conf = false;

        // GET AND STORE META INFORMATION VIA .WELL-KNOWN URL
        
        $meta = Cache::get(config('mab.instance')."_{$meta_key}", NULL);
        if(!$meta){
            $meta = Http::get('https://signin.johndeere.com/oauth2/aus78tnlaysMraFhC1t7/.well-known/oauth-authorization-server');
            if($meta->ok()){
                $refresh_conf = true;
                $meta = $meta->json();
                Cache::set(config('mab.instance')."_{$meta_key}", json_encode($meta), 3600 * 24); /* cache for 24 hours */
            }
        } else {
            $meta = json_decode($meta, true);
        }

        if($meta){
            // STORE CONFIG
            $config = Setting::get($conf_key, NULL);
            if(!$config || $refresh_conf){
                $config = [
                    'oauth2' => [
                        'clientId' => '0oa3csjh92qFwjFt35d7',
                        'clientSecret' => 'AZqogUFPitAFcAfqYdXkKANfcn9Z_Zg0aZcMoaba',
                        'urlAuthorize' => $meta['authorization_endpoint'],
                        'urlAccessToken' => $meta['token_endpoint'],
                        'urlResourceOwnerDetails' => 'not_used'
                    ],
                    'options' => [
                        'scope' => ['org2 eq2 ag3 files offline_access']
                    ],
                    'tokenProcessor'     => '\App\Integrations\AuthorisationProcessor',
                    'tokenModel'         => '\App\Integrations\TokenStorage', /* Problem */

                    'authorisedRedirect' => route('oauth2.token_success', [ 'integration' => $url_slug ]),
                    'failedRedirect'     => route('oauth2.token_failure', [ 'integration' => $url_slug ])
                ];
                Setting::set($conf_key, json_encode($config));
            }

            // get previously stored integration
            $int = Integration::where('name', $int_name)->first();

            $integration = [
                'name'          => $this->slug,
                'status_button' => $int && $int->accessToken ? 'Disconnect' : 'Connect',
                'status_text'   => $int && $int->accessToken ? 'Connected'  : 'Disconnected',
                'link'          => $int && $int->accessToken ?
                route('oauth2.token_revoke', [ 'integration' => $url_slug, 'company_id' => $company_id ]) : 
                route('oauth2.setup_auth',   [ 'integration' => $url_slug, 'company_id' => $company_id ])
            ];
        }

        return $integration;
    }

    public function options($subsystem)
    {
        // WARNING: Do NOT change the order of the option field keys below.
        // ALWAYS specify 'key' and 'value' after one another.
        // (MySQL Queries depend on this)

        // Options by subsystem
        $options = [
            'entities_manage' => [
                'org_id' => [
                    'key' => 'org_id',
                    'value' => '',
                    'label' => 'JD Organization ID',
                    'type'  => 'text',
                    'desc'  => 'The MyJohnDeere Organization ID',
                    'required' => true
                ]
            ],
            'hardware_config' => [
                'asset_sync' => [
                    'key'   => 'asset_sync',
                    'value' => '0',
                    'label' => 'Asset Sync',
                    'type'  => 'bool',
                    'desc'  => 'Sync this node with JDO Assets?'
                ],
                'flags_sync' => [
                    'key'   => 'flags_sync',
                    'value' => '0',
                    'label' => 'Flags Sync',
                    'type'  => 'bool',
                    'desc'  => 'Sync Node and Field Zones with JDO Flags?'
                ],
                'perimeter_sync' => [
                    'key'   => 'perimeter_sync',
                    'value' => '0',
                    'label' => 'Perimeter Sync',
                    'type'  => 'bool',
                    'desc'  => 'Perimeter outline population via JDO?'
                ]
            ]
        ];

        if(!array_key_exists($subsystem, $options)) return [];

        return $options[$subsystem];

    }

    public static function types()
    {
        // Node types supported by this integration
        //return ['Nutrients', 'Soil Moisture'];
        return ['Nutrients'];
    }

    public function events()
    {
        // A new nutrient node was created
        Eventy::addAction('node_config.new', function($node, $field)
        {
            if(!in_array($node->node_type, self::types()) || !$this->is_active($node->company_id)) return;

            // Can add a new Asset or Flag, but we currently use an opt-in approach (Checkboxes on Hardware Config)

        }, 10, 2);

        // An existing nutrient node was updated
        Eventy::addAction('node_config.save', function($node, $field, $info)
        {
            if(!in_array($node->node_type, self::types()) || !$this->is_active($node->company_id)) return;

            // asset_sync
            if( !empty($info['integrations'][$this->slug]['hardware_config']['asset_sync']['value']) &&
                       $info['integrations'][$this->slug]['hardware_config']['asset_sync']['value'] == '1'
            ){
                if($info['company_changed'] || $info['node_type_changed']){
                    $this->maybe_delete_jdo_asset($node);
                } else {
                    $created = $this->maybe_create_jdo_node_asset($node);
                    $this->maybe_create_jdo_field($field);

                    if(!$created && $info['field_name_changed']){
                        $this->update_jdo_field_name($field, $info['field_name_new']);
                    }
                    // manual coordinates change via UI
                    if(!$created && $info['coordinates_changed']){
                        $this->maybe_update_jdo_node_asset($node, $field);
                    }
                }
            }

            // flags_sync
            if( !empty($info['integrations'][$this->slug]['hardware_config']['flags_sync']['value']) &&
                       $info['integrations'][$this->slug]['hardware_config']['flags_sync']['value'] == '1'
            ){
                if($info['company_changed'] || $info['node_type_changed']){
                    $this->maybe_delete_jdo_flag($node);
                } else {
                    $created = $this->maybe_create_jdo_node_flag($node, $field);

                    // manual coordinates change via UI
                    if(!$created && $info['coordinates_changed']){
                        // Node Flag updates also happen in SyncFlagsTask periodically
                        $this->maybe_update_jdo_node_flag($node, $field);
                    }
                }
            }

            // perimeter_sync
            if( !empty($info['integrations'][$this->slug]['hardware_config']['perimeter_sync']['value']) &&
                       $info['integrations'][$this->slug]['hardware_config']['perimeter_sync']['value'] == '1'
            ){
                if($info['perimeter_changed']){
                    if($this->maybe_delete_jdo_field_boundary($field)){
                        $this->upsert_jdo_field_boundary($field, $info['perimeter_new']);
                    }
                }
            }

        }, 10, 3);

        // A new Zone file was imported
        Eventy::addAction('node_config.zones.import', function($node, $field, $info)
        {
            if(!in_array($node->node_type, self::types()) || !$this->is_active($node->company_id)) return;

            if( !empty($info['integrations'][$this->slug]['hardware_config']['flags_sync']['value']) &&
                       $info['integrations'][$this->slug]['hardware_config']['flags_sync']['value'] == '1'
            ){
                if($this->maybe_delete_jdo_field_zone_boundaries($field)){
                    $this->upsert_jdo_field_zone_boundaries($field);
                }
            }
        }, 10, 3);

        // A Field's Zones is about to be cleared/removed
        Eventy::addAction('fields.zones.before_clear', function($node, $field, $info)
        {
            if(!in_array($node->node_type, self::types()) || !$this->is_active($node->company_id)) return;

            if( !empty($info['integrations'][$this->slug]['hardware_config']['flags_sync']['value']) &&
                       $info['integrations'][$this->slug]['hardware_config']['flags_sync']['value'] == '1'
            ){
                $this->maybe_delete_jdo_field_zone_boundaries($field);
            }
        }, 10, 3);

        // A Field's Zones were cleared/removed
        Eventy::addAction('fields.perimeter.before_clear', function($node, $field, $info)
        {
            if(!in_array($node->node_type, self::types()) || !$this->is_active($node->company_id)) return;

            if( !empty($info['integrations'][$this->slug]['hardware_config']['flags_sync']['value']) &&
                       $info['integrations'][$this->slug]['hardware_config']['flags_sync']['value'] == '1'
            ){
                $this->maybe_delete_jdo_field_boundary($field);
            }

        }, 10, 3);

        // An existing nutrient node's address was changed
        Eventy::addAction('node_config.update_address', function($node, $info)
        {
            if(!in_array($node->node_type, self::types()) || !$this->is_active($node->company_id)) return;

            if( !empty($info['integrations'][$this->slug]['hardware_config']['asset_sync']['value']) &&
                       $info['integrations'][$this->slug]['hardware_config']['asset_sync']['value'] == '1'
            ){
                $this->update_jdo_asset_title($node, $info['new_address'], $info['old_address']);
            }
        }, 10, 2);

        // A nutrient node was deleted
        Eventy::addAction('node_config.delete', function($node, $info)
        {
            if(!in_array($node->node_type, self::types()) || !$this->is_active($node->company_id)) return;

            if( !empty($info['integrations'][$this->slug]['hardware_config']['asset_sync']['value']) &&
                       $info['integrations'][$this->slug]['hardware_config']['asset_sync']['value'] == '1'
            ){
                $this->maybe_delete_jdo_asset($node);
            }

        }, 10, 2);

        // An existing company was updated
        Eventy::addAction('company.save', function($company, $info)
        {
            if(!$this->is_active($company->id)) return;

            // check for company name change (update jdo farm)
            if($info['company_name_changed']){
                $this->update_jdo_farm($company, $info['company_name_new']);
            }

            // check for contact name change (update jdo client)
            if($info['contact_name_changed']){
                $this->update_jdo_client($company, $info['contact_name_new']);
            }

            // Save manually set org_id
            if(!empty($info['integrations'][$this->slug]['entities_manage']['org_id']['value'])){
                $org_id = $info['integrations'][$this->slug]['entities_manage']['org_id']['value'];
                if(is_numeric($org_id)){
                    $int_name = "{$this->slug}-{$company->id}";
                    Setting::set("{$int_name}.org_id", $org_id);
                }
            }
        }, 10, 2);

        // An existing company was deleted
        Eventy::addAction('company.delete', function($company)
        {
            if(!$this->is_active($company->id)) return;

            // disable integration
            route('oauth2.token_revoke', ['integration' => $this->slug, 'company_id' => $company->id])->send();

        }, 10, 1);

        // Process Integration Job Queue
        Eventy::addAction("jobs.{$this->slug}.process", function($meta)
        {
            if(in_array($meta['action'], [/*'CREATED', */'MODIFIED']))
            {
                $int_name = $meta['int_name'];
                $conf_key = "{$this->slug}.oauth_conf";
                list($slug, $company_id) = explode('-', $int_name);

                if(!$this->override_config($this->slug, $conf_key, $company_id)){
                    Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
                }

                // Instantiate API
                $boundary_api = new BoundariesAPI($this->base_url, $int_name, $this->debug_mode);

                try {

                    // Get Changed Boundary
                    $data = $boundary_api->get($meta['orgId'], $meta['fieldId'], $meta['boundaryId']);
                    if(empty($data['multipolygons'])){ Log::debug("JOB: No coordinates to update from"); return; }

                    // Log::debug("Webook before conversion:");
                    // Log::debug($data);
                    // Log::debug("Webook after conversion:");

                    // Convert Geometry from Boundary to GeoJSON
                    $perimeter_feat_geojson = $this->convert_boundary_to_geojson($data['multipolygons'], 'Feature');
                    if(empty($perimeter_feat_geojson)){ Log::debug("Unable to convert boundary JSON to GeoJSON"); return; }

                    // Log::debug($perimeter_feat_geojson);

                    $key = DB::table('settings')->where('value', $meta['fieldId'])->value('key');
                    if(!$key){ Log::debug("JOB: Unable to get field id from key {$meta['fieldId']}"); return; }

                    // Get Field Object
                    $field_id = str_replace("{$int_name}.field_", '', $key);
                    $field = DB::table('fields')->where('id', $field_id)->first();
                    if(!$field){ Log::debug("JOB: Field doesn't exist: $field_id"); return; }

                    // Get Possibly Existing Perimeter
                    $perimeter = $field->perimeter;

                    $boundary_key = DB::table('settings')->where('value', $meta['boundaryId'])->value('key');

                    // PERIMETER UPDATE/CREATE
                    if($boundary_key){

                        if(strpos($boundary_key, '.boundary_zone') !== false){

                            // Don't allow updating Zone Boundaries via JDO -> MAB for now.

                        } else if(strpos($boundary_key, '.boundary') !== false){

                            // Allow updating Perimeter Boundaries via JDO -> MAB.

                            // Empty Perimeter: Create New Perimeter

                            if(empty($perimeter)){

                                $node = DB::table('hardware_config')->where('node_address', $field->node_id)->first();
                                if(!$node){ Log::debug("JOB: Node doesn't exist anymore: {$field->node_id}"); return; }

                                Log::debug("JOB: Creating new perimeter for field {$field_id}");

                                // adding 'id' maybe  breaks GeoJSON standards compliance
                                $perimeter_feat_geojson['id'] = $field->node_id; 

                                // Assign properties
                                $feature_properties = [];

                                // Set according to Node Type Support
                                if(in_array($node->node_type, ['Nutrients', 'Soil Moisture'])){ $feature_properties['Soil Moisture'] = true; }
                                if($node->node_type == 'Nutrients'){ $feature_properties['Nutrients'] = true; }
                                
                                // Mark as Field Boundary
                                $feature_properties['Field'] = true;

                                $perimeter_feat_geojson['properties'] = $feature_properties;

                                // wrap it in a FeatureCollection
                                $perimeter = [ "type" => "FeatureCollection", "features" => [ $perimeter_feat_geojson ] ];

                                // Save it
                                DB::table('fields')->where('id', $field_id)->update([ 'perimeter' => $perimeter ]);

                            // Non-Empty Existing Perimeter: Only update coordinates
                            } else {
                                Log::debug("JOB: Updating existing perimeter for field {$field_id}");
                                $perimeter = json_decode($perimeter, true);

                                if(!empty($perimeter['features'][0]['geometry'])){

                                    // Only update coordinates
                                    $perimeter['features'][0]['geometry'] = $perimeter_feat_geojson['geometry'];

                                    DB::table('fields')->where('id', $field_id)->update([ 'perimeter' => $perimeter ]);
                                }

                            }

                        }
                    }
                } catch (\Illuminate\Http\Client\RequestException $ex) {
                    Log::debug("Exception (mjd)");
                    Log::debug($ex->response);
                    return false;
                }

            } else if($meta['action'] == 'DELETED'){

            }

        }, 10, 1);

        // Post Authentication
        // @param $integration Integration object ($integration->name == <slug>-<company_id>)
        Eventy::addAction("oauth2.{$this->slug}.token_success", function($request, $integration)
        {
            // Maybe Register the Webhook
            $this->maybe_create_event_subscription($integration);

            // Maybe redirect to Organizational Connections/Access Screen
            $this->maybe_redirect_to_org_connections($integration);

        }, 10, 2);

        // Yes, this event was registed to 'token_revoke' previously (facepalm)
        Eventy::addAction("oauth2.{$this->slug}.token_before_revoke", function($request, $integration)
        {
            $this->maybe_remove_event_subscription($integration);

        }, 10, 2);

    }

    public function routes()
    {
        // MyJohnDeere Events WebHook
        Route::post('receivingEvents', function(Request $request){

            $events = json_decode($request->getContent(), true);

            if(empty($events)){ return response()->noContent(); }

            //Log::debug("Webhook");
            //Log::debug($events);

            foreach($events as $data){
                // sanity checks
                if(!empty($data['eventTypeId']) && $data['eventTypeId'] == 'boundary' && !empty($data['metadata'])){

                    $metadata = [];

                    foreach($data['metadata'] as $record){ $metadata[$record['key']] = $record['value']; }
                    $key = DB::table('settings')->where('value', $metadata['orgId'])->value('key');

                    if($key){
                        $metadata['int_name'] = str_replace('.org_id', '', $key);
                        list($slug, $company_id) = explode('-', $metadata['int_name']);

                        if(!$this->is_active($company_id)){ return; }

                        ProcessIntegrationJob::dispatch($metadata);
                    }
                }
            }

            return response()->noContent();

        })->name('receiving_events');
    }

    /* ====== */
    /* ------ */
    /* FIELDS */
    /* ------ */
    /* ====== */

    // Creates a JDO Field from a MAB Field
    protected function maybe_create_jdo_field($field)
    {
        $company_id   = $field->company_id;
        $int_name     = "{$this->slug}-{$company_id}";
        $conf_key     = "{$this->slug}.oauth_conf";
        $org_key      = "{$int_name}.org_id";
        $client_key   = "{$int_name}.client_id";
        $farm_key     = "{$int_name}.farm_id";
        $field_key    = "{$int_name}.field_{$field->id}";

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
        }

        // Get Company
        $cc = DB::table('companies')->where('id', $company_id)->first();

        try {
            // Get JDO Organization ID
            $org_id = Setting::get($org_key);
            if(!$org_id){ Log::debug('Missing organization id for company ' . $company_id ); return false; }

            // Format Names
            //$client_name = str_replace(" ", "", $cc->contact_name); // no spaces allowed apparently.
            $client_name = $cc->contact_name;
            $farm_name   = $cc->company_name; // as is
            $field_name  = $field->field_name; // as is 

            // Get Client ID (Or Create)
            $client_id = Setting::get($client_key);
            if(!$client_id){

                // Instantiate API
                $clients_api = new ClientsAPI($this->base_url, $int_name, $this->debug_mode);

                // see if it exists on JDO before trying to create it
                Log::debug("Checking if client '$client_name' exists");
                $client_id = $clients_api->get_field_by_kv($org_id, 'id', 'name', $client_name);
                if(!$client_id){
                    Log::debug("Client '$client_name' doesn't exist, attempting to create");
                    // Create it
                    $client_id = $clients_api->create_client($org_id, [
                        'name' => $client_name,
                        'archived' => 'false'
                    ]);
                    if(!$client_id){ Log::debug("Failed to create client for: {$cc->contact_name}"); return false; }
                    Log::debug("Created client: {$cc->contact_name} -> {$client_id}");
                }
                Setting::set($client_key, $client_id);
            }

            // Get Farm ID (Or Create)
            $farm_id = Setting::get($farm_key);
            if(!$farm_id){

                // Instantiate API
                $farms_api = new FarmsAPI($this->base_url, $int_name, $this->debug_mode);

                // see if it exists on JDO before trying to create it
                $farm_id = $farms_api->get_field_by_kv($org_id, 'id', 'name', $farm_name);
                if(!$farm_id){
                    // Create it
                    $farm_id = $farms_api->create_farm($org_id, $client_id, [
                        'name' => $farm_name,
                        'archived' => 'false'
                    ]);
                    if(!$farm_id){ Log::debug("Failed to create farm for: {$cc->company_name}"); return false; }
                    Log::debug("Created farm: {$cc->company_name} -> {$farm_id}");
                }
                Setting::set($farm_key, $farm_id);
            }

            // Get Field ID (Or Create)
            $field_id = Setting::get($field_key);
            if(!$field_id){

                // Instantiate API
                $fields_api = new FieldsAPI($this->base_url, $int_name, $this->debug_mode);

                // see if it exists on JDO before trying to create it
                $field_id = $fields_api->get_field_by_kv($org_id, 'id', 'name', $field_name);
                if(!$field_id){
                    // Create it
                    $field_id = $fields_api->create_field($org_id, [
                        'name' => $field_name,
                        'archived' => 'false',
                        'farms'   => [ 'farms'   => [ [ 'name' => $farm_name,   'id' => $farm_id   ] ] ],
                        'clients' => [ 'clients' => [ [ 'name' => $client_name, 'id' => $client_id ] ] ]
                    ]);
                    if(!$field_id){ Log::debug("Failed to create field for: {$field_name}"); return false; }
                    Log::debug("Created new Field: field_{$field->id} -> {$field_id}"); 
                }
                Setting::set($field_key, $field_id);
            }

        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug('Exception @ ' . __FUNCTION__);
            Log::debug($ex->response);
            return false;
        }

        return true;
    }

    // Update a JDO Field name from a MAB Field Name
    protected function update_jdo_field_name($field, $new_field_name)
    {
        $company_id = $field->company_id;
        $int_name   = "{$this->slug}-{$company_id}";
        $conf_key   = "{$this->slug}.oauth_conf";
        $org_key    = "{$int_name}.org_id";
        $client_key = "{$int_name}.client_id";
        $farm_key   = "{$int_name}.farm_id";

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
        }

        $field_id = Setting::get("{$int_name}.field_{$field->id}");
        if($field_id){

            // Get Company
            $cc = DB::table('companies')->where('id', $company_id)->first();

            // Get Organization ID
            $org_id = Setting::get($org_key);
            if(!$org_id){ Log::debug('Missing organization id for company ' . $company_id ); return false; }

            // Get Client ID
            $client_id = Setting::get($client_key);
            if(!$client_id){ Log::debug('Missing client id for company ' . $company_id ); return false; }

            // Get Farm ID
            $farm_id = Setting::get($farm_key);
            if(!$farm_id){ Log::debug('Missing farm id for company ' . $company_id ); return false; }

            // Instantiate API
            $fields_api  = new FieldsAPI($this->base_url, $int_name, $this->debug_mode);

            $farm_name   = $cc->company_name;
            //$client_name = str_replace(" ", "", $cc->contact_name); // no spaces allowed
            $client_name = $cc->contact_name;

            try {
                $field_id = $fields_api->update($org_id, $field_id, [
                    'name'     => $new_field_name,
                    'archived' => 'false',
                    'farms'    => [ 'farms'   => [ [ 'name' => $farm_name,   'id' => $farm_id   ] ] ],
                    'clients'  => [ 'clients' => [ [ 'name' => $client_name, 'id' => $client_id ] ] ]
                ]);
            } catch (\Illuminate\Http\Client\RequestException $ex) {
                Log::debug('Exception @ ' . __FUNCTION__);
                Log::debug($ex->response);
                return false;
            }
            return true;
        }

        return false;
    }

    /* ===== */
    /* ----- */
    /* FARMS */
    /* ----- */
    /* ===== */

    protected function update_jdo_farm($company, $new_farm_name)
    {
        $company_id = $company->id;
        $int_name   = "{$this->slug}-{$company_id}";
        $conf_key   = "{$this->slug}.oauth_conf";
        $org_key    = "{$int_name}.org_id";
        $farm_key   = "{$int_name}.farm_id";
        $client_key = "{$int_name}.client_id";

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
        }

        // Get Organization ID
        $org_id = Setting::get($org_key);
        if(!$org_id){ Log::debug('Missing organization id for company ' . $company_id ); return false; }

        // Get Farm ID
        $farm_id = Setting::get($farm_key);
        if(!$farm_id){ Log::debug('Missing farm id for company ' . $company_id ); return false; }

        // Get Client ID
        $client_id = Setting::get($client_key);
        if(!$client_id){ Log::debug('Missing client id for company ' . $company_id ); return false; }

        // Instantiate API
        $farms_api = new FarmsAPI($this->base_url, $int_name, $this->debug_mode);

        try {
            $farms_api->update($org_id, $farm_id, $client_id, [
                'name' => $new_farm_name,
                'archived' => 'false'
            ]);
            return true;
        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug('Exception @ ' . __FUNCTION__);
            Log::debug($ex->response);
            return false;
        }
    }

    /* ======= */
    /* ------- */
    /* CLIENTS */
    /* ------- */
    /* ======= */

    protected function update_jdo_client($company, $new_contact_name)
    {
        $company_id = $company->id;
        $int_name   = "{$this->slug}-{$company_id}";
        $conf_key   = "{$this->slug}.oauth_conf";
        $org_key    = "{$int_name}.org_id";
        $client_key = "{$int_name}.client_id";

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
        }

        // Get Organization ID
        $org_id = Setting::get($org_key);
        if(!$org_id){ Log::debug('Missing organization id for company ' . $company_id ); return false; }

        // Get Client ID
        $client_id = Setting::get($client_key);
        if(!$client_id){ Log::debug('Missing client id for company ' . $company_id ); return false; }

        // Format Client Name
        //$new_client_name = str_replace(" ", "", $new_contact_name); // no spaces allowed
        $new_client_name = $new_contact_name;

        // Instantiate API
        $clients_api = new ClientsAPI($this->base_url, $int_name, $this->debug_mode);

        try {
            $clients_api->update($org_id, $client_id, [
                'name' => $new_client_name,
                'archived' => 'false'
            ]);
            return true;
        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug('Exception @ ' . __FUNCTION__);
            Log::debug($ex->response);
            return false;
        }
    }

    /* ====== */
    /* ------ */
    /* ASSETS */
    /* ------ */
    /* ====== */

    // Creates a JDO Asset from a MAB Node
    protected function maybe_create_jdo_node_asset($node)
    {
        $result       = false;
        $company_id   = $node->company_id;
        $int_name     = "{$this->slug}-{$company_id}";
        $conf_key     = "{$this->slug}.oauth_conf";
        $node_address = $node->node_address;
        $org_key      = "{$int_name}.org_id";
        $asset_key    = "{$int_name}.{$node_address}";

        // Short-Circuit if Asset ID already exists.
        $asset_id = Setting::get($asset_key, NULL);
        if($asset_id){ return $result; }

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return $result;
        }

        try {
            // Get JDO Organization ID
            $org_id = Setting::get($org_key);
            if(!$org_id){ Log::debug('Missing organization id for company ' . $company_id ); return $result; }

            // Instantiate API
            $assets_api = new AssetsAPI($this->base_url, $int_name, $this->debug_mode);

            // see if it exists on JDO before trying to create it
            $asset_id = $assets_api->get_field_by_kv($org_id, 'id', 'title', $node_address);
            if(!$asset_id){

                // Create it
                $asset_id = $assets_api->create_asset($org_id, [
                    'title'         => $node_address,
                    'assetCategory' => 'DEVICE',
                    'assetType'     => 'SENSOR',
                    'assetSubType'  => 'OTHER'
                ]);

                if(!$asset_id){ Log::debug("Failed to create asset for: {$node_address}"); return $result; }
                Log::debug("Created new Asset: {$node_address} -> {$asset_id}");
                $result = true;
            }
            // create/existed? set it
            Setting::set($asset_key, $asset_id);

        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug('Exception @ ' . __FUNCTION__);
            Log::debug($ex->response);
        }

        return $result;
    }

    protected function maybe_update_jdo_node_asset($node, $field)
    {
        $conf_key   = "{$this->slug}.oauth_conf";
        $company_id = $node->company_id;
        $int_name   = "{$this->slug}-{$company_id}";
        $asset_key  = "{$int_name}.{$node->node_address}";

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
        }

        // Get Asset ID
        $asset_id = Setting::get($asset_key, NULL);
        if(!$asset_id){ Log::debug("asset_id doesn't exist, cannot update asset"); return false; }

        // Instantiate API
        $assets_api = new AssetsAPI($this->base_url, $int_name, $this->debug_mode);

        $syncer = new AssetSyncer($assets_api);
        $result = $syncer->sync($node, $asset_id, $int_name, $field);

        return $result;
    }

    // Update JDO Asset Title (When a MAB Node Address Changed)
    protected function update_jdo_asset_title($node, $new_address, $old_address)
    {
        $company_id   = $node->company_id;
        $int_name     = "{$this->slug}-{$company_id}";
        $conf_key     = "{$this->slug}.oauth_conf";

        // Get Asset ID
        $asset_id = Setting::get("{$int_name}.{$old_address}", NULL);
        if(!$asset_id){ return false; }

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
        }

        // Instantiate API
        $assets_api = new AssetsAPI($this->base_url, $int_name, $this->debug_mode);

        try {

            if($assets_api->update($asset_id, [
                'title'         => $new_address,
                'assetCategory' => 'DEVICE',
                'assetType'     => 'SENSOR',
                'assetSubType'  => 'OTHER'
            ])){
                // Re-Key
                Setting::set("{$int_name}.{$new_address}", $asset_id);
                Setting::del("{$int_name}.{$old_address}");
            }

        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug('Exception @ ' . __FUNCTION__);
            Log::debug($ex->response);
            return false;
        }

        return true;
    }

    // Delete a JDO Asset when a MAB Node is Deleted
    protected function maybe_delete_jdo_asset($node)
    {
        return false; // as per Dave's orders

        $company_id   = $node->company_id;
        $int_name     = "{$this->slug}-{$company_id}";
        $conf_key     = "{$this->slug}.oauth_conf";
        $asset_key    = "{$int_name}.{$node->node_address}";

        $asset_id = Setting::get($asset_key);
        if($asset_id){

            // OVERRIDE CONFIG
            if(!$this->override_config($this->slug, $conf_key, $company_id)){
                Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
            }

            // Instantiate API
            $assets_api = new AssetsAPI($this->base_url, $int_name, $this->debug_mode);

            try {
                if($assets_api->delete($asset_id)){
                    // Ensure Setting is also deleted
                    Setting::del($asset_key);
                    return true;
                }
            } catch (\Illuminate\Http\Client\RequestException $ex) {
                Log::debug('Exception @ ' . __FUNCTION__);
                Log::debug($ex->response);
                return false;
            }
        }

        return false;
    }

    /* ===== */
    /* ----- */
    /* FLAGS */
    /* ----- */
    /* ===== */

    // Creates a JDO Flag from a MAB Node
    protected function maybe_create_jdo_node_flag($node, $field)
    {
        $result     = false;
        $company_id = $node->company_id;
        $int_name   = "{$this->slug}-{$company_id}";
        $conf_key   = "{$this->slug}.oauth_conf";
        $org_key    = "{$int_name}.org_id";
        $field_key  = "{$int_name}.field_{$field->id}";
        $n_cat_key  = "{$int_name}.{$node->node_address}.flag_cat_id";
        $flag_key   = "{$int_name}.{$node->node_address}.flag_id";

        // Format Names
        $flag_name     = $node->node_address; // as is
        $node_cat_name = "Probe - {$node->node_type}";

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return $result;
        }

        try {
            // Get JDO Organization ID
            $org_id = Setting::get($org_key);
            if(!$org_id){ Log::debug("Missing organization id for company $company_id"); return $result; }

            // Get JDO Field ID
            $field_id = Setting::get($field_key);
            if(!$field_id){ Log::debug('Missing field id for field ' . $field->field_name ); return $result; }

            // Get Node Flag Category ID (Or Create)
            $n_cat_id = Setting::get($n_cat_key, NULL); // TODO: SET TO NULL WHEN CHANGING NODE'S TYPE TO FORCE REFETCH
            if(!$n_cat_id){

                // Instantiate API
                $flags_cat_api = new FlagCategoriesAPI($this->base_url, $int_name, $this->debug_mode);

                // see if it exists on JDO before trying to create it
                $n_cat_id = $flags_cat_api->get_field_by_kv($org_id, 'id', 'categoryTitle', $node_cat_name);

                if(!$n_cat_id){
                    // Create it
                    $n_cat_id = $flags_cat_api->create_flag_category($org_id, $node_cat_name);
                    if(!$n_cat_id){ Log::debug('Could not create flag category: ' . $node_cat_name ); return $result; }
                    Log::debug("Created flag category: {$node_cat_name}");
                }
                Setting::set($n_cat_key, $n_cat_id);
            }

            // Instantiate API
            $flags_api = new FlagsAPI($this->base_url, $int_name, $this->debug_mode);
            
            // Maybe Create Flag
            $flag_id = Setting::get($flag_key, NULL);
            if($flag_id){ return $result; }

            // see if it exists on JDO before trying to create it
            $flag_id = $flags_api->get_field_by_kv($org_id, 'id', "notes", $flag_name);
            if($flag_id){ return $result; }

            // Create it
            $flag_id = $flags_api->create_flag($org_id, $n_cat_id, $field_id, [
                '@type' => 'Flag',
                'geometry' => [ 'type' => 'Point', 'coordinates' => [ $node->lng, $node->latt ] ],
                'notes' => $flag_name,
                'archived' => 'false',
                'proximityAlertEnabled' => 'false',
                'metadata' => $this->get_node_flag_metadata($node)
            ]);

            if(!$flag_id){ Log::debug('Could not create flag : ' . $flag_name ); return $result; }
            Log::debug("Created flag: {$flag_name} -> {$flag_id}");
            $result = true;
            Setting::set($flag_key, $flag_id);

        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug('Exception @ ' . __FUNCTION__);
            Log::debug($ex->response);
        }

        return $result;
    }

    protected function maybe_update_jdo_node_flag($node, $field)
    {
        $conf_key   = "{$this->slug}.oauth_conf";
        $company_id = $node->company_id;
        $int_name   = "{$this->slug}-{$company_id}";
        $org_key    = "{$int_name}.org_id";
        $flag_key   = "{$int_name}.{$node->node_address}.flag_id";

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
        }

        // Get JDO Organization ID
        $org_id = Setting::get($org_key);
        if(!$org_id){ Log::debug('Missing organization id for company ' . $company_id ); return false; }

        // Get Flag ID
        $flag_id = Setting::get($flag_key, NULL);
        if(!$flag_id){ return false; }

        // Instantiate API
        $flags_api = new FlagsAPI($this->base_url, $int_name, $this->debug_mode);
        $flags_cat_api = new FlagCategoriesAPI($this->base_url, $int_name, $this->debug_mode);

        $syncer = new FlagSyncer($flags_api, $flags_cat_api);
        $result = $syncer->sync($node, $flag_id, $int_name, $org_id, $field);

        return $result;
    }

    // Delete a JDO Flag when a MAB Node is Deleted
    protected function maybe_delete_jdo_flag($node)
    {
        return; // Disabled: Requested by Dave

        $response   = false;
        $conf_key   = "{$this->slug}.oauth_conf";
        $company_id = $node->company_id;
        $int_name   = "{$this->slug}-{$company_id}";
        $org_key    = "{$int_name}.org_id";
        $flag_key   = "{$int_name}.{$node->node_address}.flag_id";

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return $response;
        }

        // Get JDO Organization ID
        $org_id = Setting::get($org_key);
        if(!$org_id){ Log::debug('Missing organization id for company ' . $company_id ); return $response; }

        // Get Flag ID
        $flag_id = Setting::get($flag_key, NULL);
        if(!$flag_id){ return $response; }

        // Instantiate API
        $flags_api = new FlagsAPI($this->base_url, $int_name, $this->debug_mode);

        try {
            $flags_api->delete($org_id, $flag_id);
            Log::debug("Deleted Flag ($flag_id) from JDO");
            $response = true;
        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug('Exception @ ' . __FUNCTION__);
            Log::debug($ex->response);
        }

        return $response;
    }

    // (Either create or update) JDO Flags from MAB Zones (NOT USED)
    protected function upsert_jdo_field_zone_flags($field)
    {
        $zones = json_decode($field->zones, true);
        if(empty($zones)){ return; }

        $company_id   = $field->company_id;
        $mab_field_id = $field->id;
        $int_name     = "{$this->slug}-{$company_id}";
        $conf_key     = "{$this->slug}.oauth_conf";
        $org_key      = "{$int_name}.org_id";
        $field_key    = "{$int_name}.field_{$mab_field_id}";
        $z_cat_key    = "{$int_name}.field_{$mab_field_id}.flag_cat_id"; // for now, 'Field Zones' (same for all)

        $zone_cat_name = 'Field Zones';
        $flag_key_base = "{$int_name}.field_{$mab_field_id}.flag_id_zone";

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
        }

        try {
            // Get JDO Organization ID
            $org_id = Setting::get($org_key);
            if(!$org_id){ Log::debug('Missing organization id for company ' . $company_id ); return false; }

            // Get JDO Field ID
            $jdo_field_id = Setting::get($field_key);
            if(!$jdo_field_id){ Log::debug('upsert_jdo_field_zone_flags: Missing field id for field ' . $field->field_name ); return false; }

            // Get Zone Flag Category ID (Or Create)
            $z_cat_id = Setting::get($z_cat_key, NULL); // TODO: Delete when deleting field
            if(!$z_cat_id){

                // Instantiate API
                $flags_cat_api = new FlagCategoriesAPI($this->base_url, $int_name, $this->debug_mode);

                // see if it exists on JDO before trying to create it
                $z_cat_id = $flags_cat_api->get_field_by_kv($org_id, 'id', 'categoryTitle', $zone_cat_name);

                if(!$z_cat_id){
                    // Create it
                    $z_cat_id = $flags_cat_api->create_flag_category($org_id, $zone_cat_name);
                    if(!$z_cat_id){ Log::debug('Could not create flag category: ' . $zone_cat_name ); return false; }
                    Log::debug("Created flag category: {$zone_cat_name}");
                }
                Setting::set($z_cat_key, $z_cat_id);
            }

            // Instantiate API
            $flags_api = new FlagsAPI($this->base_url, $int_name, $this->debug_mode);

            // Create/Update a Flag for each Zone
            foreach($zones as $zone){

                $zone_id = !empty($zone['data']['ZONE_ID']) ? $zone['data']['ZONE_ID'] : null;
                if(!$zone_id){ Log::debug("Missing Zone ID, Skipping Flag Sync for Zones. Field: " . $field->field_name ); return false; }
                $zone_id = Utils::slugify($zone_id);

                $flag_key  = "{$flag_key_base}_{$zone_id}";
                $flag_name = "{$field->field_name} - Zone {$zone_id}";

                $geometry = json_decode($zone['geom'], true);

                $flag_id = Setting::get($flag_key, NULL);

                if($flag_id){
                    // update
                    if($flags_api->update_flag($org_id, $z_cat_id, $jdo_field_id, $flag_id, [
                        '@type' => 'Flag',
                        'id' => $flag_id,
                        'geometry' => $geometry,
                        'notes' => $flag_name,
                        'archived' => 'false',
                        'proximityAlertEnabled' => 'false',
                        'metadata' => $this->get_zone_flag_metadata($field, $zone)
                    ])){
                        Log::debug("Updated zone flag: field_{$mab_field_id}.flag_id_zone_{$zone_id} -> {$flag_id}");
                    } else {
                        Log::debug("Field zone update failed: field_{$mab_field_id}.flag_id_zone_{$zone_id} -> {$flag_id}");
                        return false;
                    }
                } else {
                    // see if it exists on JDO before trying to create it
                    $flag_id = $flags_api->get_field_by_kv($org_id, 'id', "notes", $flag_name); // TODO: Find a safer key to compare with

                    if(!$flag_id){
                        // Create it
                        $flag_id = $flags_api->create_flag($org_id, $z_cat_id, $jdo_field_id, [
                            '@type' => 'Flag',
                            'geometry' => $geometry,
                            'notes' => $flag_name,
                            'archived' => 'false',
                            'proximityAlertEnabled' => 'false',
                            'metadata' => $this->get_zone_flag_metadata($field, $zone)
                        ]);
                        if(!$flag_id){ Log::debug('Could not create flag : ' . $flag_name ); return false; }
                        Log::debug("Created flag: {$flag_name} -> {$flag_id}");
                    }
                    Setting::set($flag_key, $flag_id);
                }
            }

        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug('Exception @ ' . __FUNCTION__);
            Log::debug($ex->response);
            return false;
        }
        return true;
    }

    // Needed when creating a Node Flag
    protected function get_node_flag_metadata($node)
    {
        $metadata = [];
        if(!empty($node) && !empty($node->node_address)){

            if($node->node_type == 'Nutrients' && !empty($node->nutrient_template_id)){
                // PPM Average
                $results = Calculations::calcNutrientAverageGaugeValues($node->node_address, $node->nutrient_template_id);

                $metadata[] = [
                    "name"  => "PPM_AVG",
                    "value" => "{$results['nutrient_avg']}",
                ];
            }

            $temp = Calculations::getLatestNodeAvgTemp($node);

            // Temperature
            $metadata[] = [
                "name"  => "TEMPERATURE",
                "value" => "{$temp}",
            ];
            $sm = Calculations::getLatestNodeAvgSM($node);

            // Soil Moisture
            $metadata[] = [
                "name"  => "SOIL_MOISTURE",
                "value" => "{$sm}",
            ];

            if($node->node_type == 'Soil Moisture'){
                // TODO: Add SM Status
            }

        }
        return $metadata;
    }

    // Needed when creating a JDO Zone Flag (NOT USED)
    protected function get_zone_flag_metadata($field, $zone)
    {
        $metadata = [];
        if(!empty($field) && !empty($zone)){

            $zone_id = !empty($zone['data']['ZONE_ID']) ? $zone['data']['ZONE_ID'] : ''; // Display it Raw

            if($zone_id){
                // Zone ID
                $metadata[] = [
                    "name"  => "ZONE_ID",
                    "value" => $zone_id,
                ];
            }

            // Field ID
            $metadata[] = [
                "name"  => "MAB_FIELD_ID",
                "value" => $field->id,
            ];

            // Node Address
            $metadata[] = [
                "name"  => "NODE_ADDRESS",
                "value" => $field->node_id,
            ];

        }
        return $metadata;
    }

    /* ========== */
    /* ---------- */
    /* BOUNDARIES */
    /* ---------- */
    /* ========== */

    // (Either create or update) a JDO Boundary from a MAB Field Perimeter
    protected function upsert_jdo_field_boundary($field, $new_perimeter)
    {
        $company_id   = $field->company_id;
        $mab_field_id = $field->id;
        $int_name     = "{$this->slug}-{$company_id}";
        $conf_key     = "{$this->slug}.oauth_conf";
        $org_key      = "{$int_name}.org_id";
        $field_key    = "{$int_name}.field_{$mab_field_id}";
        $boundary_key = "{$int_name}.field_{$mab_field_id}.boundary";

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
        }

        // Get Organization ID
        $org_id = Setting::get($org_key);
        if(!$org_id){ Log::debug("Missing organization id for company " . $company_id ); return false; }

        // Get JDO Field ID
        $jdo_field_id = Setting::get($field_key);
        if(!$jdo_field_id){ Log::debug('Missing field id for mab field ' . $mab_field_id ); return false; }

        // Convert Perimeter Geometry to Boundary Geometry
        $boundary_geom = $this->convert_geojson_to_boundary($new_perimeter); // HAND-DRAWN PERIMETER (FeatureCollection)

        if(!$boundary_geom){ Log::debug('Failed to convert perimeter to boundary for field ' . $mab_field_id); return false; }

        //Log::debug($boundary_geom);

        // Apparently, like the Client name, The Boundary Name should also not contain any spaces. (*** Not sure about the space thing anymore **)
        $boundary_name  = str_replace(" ", "", $field->field_name) . 'Boundary';

        // Instantiate API
        $boundary_api = new BoundariesAPI($this->base_url, $int_name, $this->debug_mode);

        try{
            $boundary_id = Setting::get($boundary_key);
            if($boundary_id){
                // update
                if($boundary_api->update_boundary($org_id, $jdo_field_id, $boundary_id, [
                    'name' => $boundary_name,
                    'active' => true,
                    'archived' => false,
                    'irrigated' => false,
                    'sourceType' => 'External',
                    'multipolygons' => $boundary_geom
                ])){
                    Log::debug("Updated field boundary: field_{$mab_field_id}.boundary -> {$boundary_id}");
                } else {
                    Log::debug("Field boundary update failed: field_{$mab_field_id}.boundary -> {$boundary_id}");
                    return false;
                }

            } else {
                // see if it exists on JDO before trying to create it
                $boundary_id = $boundary_api->get_field_by_kv($org_id, 'id', 'name', $boundary_name);
                if(!$boundary_id){
                    // Create it

                    $params = [
                        '@type' => 'Boundary',
                        'name' => $boundary_name,
                        'active' => true,
                        'archived' => false,
                        'irrigated' => false,
                        'sourceType' => 'External',
                        'multipolygons' => $boundary_geom
                    ];

                    //Log::debug($params);

                    $boundary_id = $boundary_api->create_boundary($org_id, $jdo_field_id, $params);
                    if(!$boundary_id){ Log::debug("Failed to create field boundary for: {$mab_field_id}"); return false; }
                    Log::debug("Created new field boundary: field_{$mab_field_id}.boundary -> {$boundary_id}");
                }
                Setting::set($boundary_key, $boundary_id);
            }
        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug('Exception @ ' . __FUNCTION__);
            Log::debug($ex->response);
            return false;
        }

        return true;
    }

    // Delete JDO Field Boundary (Perimeter)
    protected function maybe_delete_jdo_field_boundary($field)
    {
        $company_id   = $field->company_id;
        $mab_field_id = $field->id;
        $int_name     = "{$this->slug}-{$company_id}";
        $conf_key     = "{$this->slug}.oauth_conf";
        $org_key      = "{$int_name}.org_id";
        $field_key    = "{$int_name}.field_{$mab_field_id}";
        $boundary_key = "{$int_name}.field_{$mab_field_id}.boundary";

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
        }

        try {
            // Get JDO Organization ID
            $org_id = Setting::get($org_key);
            if(!$org_id){ Log::debug('Missing organization id for company ' . $company_id ); return false; }

            // Get JDO Field ID
            $jdo_field_id = Setting::get($field_key);
            if(!$jdo_field_id){ Log::debug('Missing field id for mab field ' . $mab_field_id ); return false; }

            // Get JDO Boundary ID
            $boundary_id = Setting::get($boundary_key);
            if($boundary_id){

                // Instantiate API
                $boundary_api = new BoundariesAPI($this->base_url, $int_name, $this->debug_mode);
                
                if($boundary_api->delete_boundary($org_id, $jdo_field_id, $boundary_id)){
                    Log::debug("deleted field boundary: $boundary_id");
                    // Ensure Setting is also deleted
                    Setting::del($boundary_key);
                    return true;
                } else {
                    Log::debug("BOUNDARY DELETE FAILED: ($boundary_key) -> ($boundary_id)");
                }
            } else {
                Log::debug("no boundary_id to delete boundary");
                return true;
            }
        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug('Exception @ ' . __FUNCTION__);
            Log::debug($ex->response);
            return false;
        }

        return false;
    }

    // (Either create or update) JDO Boundaries from MAB Zones
    protected function upsert_jdo_field_zone_boundaries($field)
    {
        $zones = json_decode($field->zones, true);
        if(empty($zones)){ return; }

        $company_id     = $field->company_id;
        $mab_field_id   = $field->id;
        $int_name       = "{$this->slug}-{$company_id}";
        $conf_key       = "{$this->slug}.oauth_conf";
        $org_key        = "{$int_name}.org_id";
        $field_key      = "{$int_name}.field_{$mab_field_id}";
        $bzone_key_base = "{$int_name}.field_{$mab_field_id}.boundary_zone";

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
        }

        try {
            // Get JDO Organization ID
            $org_id = Setting::get($org_key);
            if(!$org_id){ Log::debug('Missing organization id for company ' . $company_id ); return false; }

            // Get JDO Field ID
            $jdo_field_id = Setting::get($field_key);
            if(!$jdo_field_id){ Log::debug('upsert_jdo_field_zone_boundaries: Missing field id for field ' . $field->field_name ); return false; }

            // Instantiate API
            $boundary_api = new BoundariesAPI($this->base_url, $int_name, $this->debug_mode);

            // Create/Update a Flag for each Zone
            foreach($zones as $zone){

                $zone_id = !empty($zone['data']['ZONE_ID']) ? $zone['data']['ZONE_ID'] : null;
                if(!$zone_id){ Log::debug("Missing Zone ID, Skipping Boundary Sync for Zones. Field: " . $field->field_name ); return false; }
                $zone_id = Utils::slugify($zone_id);

                $boundary_key  = "{$bzone_key_base}_{$zone_id}";
                $boundary_name = "{$field->field_name} - Zone {$zone_id}";

                $zone_geojson = json_decode($zone['geom'], true);

                // ===========================================================================
                // HACK: IF Polygon, convert it to MultiPolygon to appease the snotty JDO devs
                // ===========================================================================
                //
                if($zone_geojson['type'] == 'Polygon'){
                    $zone_geojson['type'] = 'MultiPolygon';
                    $zone_geojson['coordinates'] = [ $zone_geojson['coordinates'] ];
                }

                $zone_boundary = $this->convert_geojson_to_boundary($zone_geojson);

                $boundary_id = Setting::get($boundary_key, NULL);
                
                if($boundary_id){
                    Log::debug("Attempting to UPDATE Field Zone Boundary: $boundary_id");
                    // update
                    if($boundary_api->update_boundary($org_id, $jdo_field_id, $boundary_id, [
                        'name' => $boundary_name,
                        'active' => false,
                        'archived' => false,
                        'irrigated' => false,
                        'sourceType' => 'External',
                        'multipolygons' => $zone_boundary
                    ])){
                        Log::debug("Updated field zone boundary: field_{$mab_field_id}.boundary_zone_{$zone_id} -> {$boundary_id}");
                    } else {
                        Log::debug("Field zone boundary update failed: field_{$mab_field_id}.boundary_zone_{$zone_id} -> {$boundary_id}");
                        return false;
                    }
        
                } else {
                    // see if it exists on JDO before trying to create it
                    $boundary_id = $boundary_api->get_field_by_kv($org_id, 'id', 'name', $boundary_name);
                    if(!$boundary_id){
                        Log::debug("Attempting to CREATE Field Zone Boundary: $boundary_id");
                        // Create it
                        $params = [
                            '@type' => 'Boundary',
                            'name' => $boundary_name,
                            'active' => true,
                            'archived' => false,
                            'irrigated' => false,
                            'sourceType' => 'External',
                            'multipolygons' => $zone_boundary
                        ];
                        //Log::debug($params);
                        $boundary_id = $boundary_api->create_boundary($org_id, $jdo_field_id, $params);
                        if(!$boundary_id){ Log::debug("Failed to create field zone boundary for: {$mab_field_id}"); return false; }
                        Log::debug("Created new field zone boundary: field_{$mab_field_id}.boundary_zone_{$zone_id} -> {$boundary_id}");
                    }
                    Setting::set($boundary_key, $boundary_id);
                }
            }

        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug('Exception @ ' . __FUNCTION__);
            Log::debug($ex->response);
            return false;
        }
        return true;
    }

    // Delete JDO Zone Boundaries
    protected function maybe_delete_jdo_field_zone_boundaries($field)
    {
        Log::debug('maybe_delete_jdo_field_zone_boundaries');
        $zones = json_decode($field->zones, true);
        if(!$zones){
            Log::debug("no field zone boundaries to delete, empty zones");
            // if new zones were imported incorrectly and field->zones is empty, bail
            return false;
        }

        // otherwise, if we're redoing the shapefile import, try and delete the old boundaries

        $company_id     = $field->company_id;
        $mab_field_id   = $field->id;
        $int_name       = "{$this->slug}-{$company_id}";
        $conf_key       = "{$this->slug}.oauth_conf";
        $org_key        = "{$int_name}.org_id";
        $field_key      = "{$int_name}.field_{$mab_field_id}";
        $bzone_key_base = "{$int_name}.field_{$mab_field_id}.boundary_zone";
        $zone_ids       = [];

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
        }

        // Get Organization ID
        $org_id = Setting::get($org_key);
        if(!$org_id){ Log::debug('Missing organization id for company ' . $company_id ); return false; }

        // Get JDO Field ID
        $jdo_field_id = Setting::get($field_key);
        if(!$jdo_field_id){ Log::debug('Missing field id for mab field ' . $mab_field_id ); return false; }

        // Gather Zone IDS
        foreach($zones as $zone){
            if(!empty($zone['data']['ZONE_ID'])){ $zone_ids[] = Utils::slugify($zone['data']['ZONE_ID']); } 
        }

        if($zone_ids){

            // Instantiate API
            $boundary_api = new BoundariesAPI($this->base_url, $int_name, $this->debug_mode);

            try {
                // Delete each Flag
                foreach($zone_ids as $zone_id){
                    $boundary_key = "{$bzone_key_base}_{$zone_id}";
                    $boundary_id = Setting::get($boundary_key, NULL);
                    if($boundary_id){
                        Log::debug("Attempting to delete zone boundary: ($boundary_key) -> ($boundary_id)");
                        if($boundary_api->delete_boundary($org_id, $jdo_field_id, $boundary_id)){
                            Log::debug('zone_boundary deleted: ' . $boundary_id);
                            // Ensure Setting is also deleted
                            Setting::del($boundary_key);
                        } else {
                            Log::debug('zone_boundary NOT deleted: ' . $boundary_id);
                        }
                    }
                }
            } catch (\Illuminate\Http\Client\RequestException $ex) {
                Log::debug('Exception @ ' . __FUNCTION__);
                Log::debug($ex->response);
                return false;
            }
        }

        return true;
    }

    // Converts any kind of GeoJSON to JD Boundary/Boundaries Objects (Tested and Working)
    protected function convert_geojson_to_boundary($geojson)
    {
        $output = null;

        if(!empty($geojson['type'])){
            if($geojson['type'] == 'Point'){
                if(!empty($geojson['coordinates']) && is_array($geojson['coordinates']) && count($geojson['coordinates']) == 1){
                    $output = [
                        "@type" => "Point",
                        "lon" => $geojson['coordinates'][0], // Longitudes range from -180 to 180.
                        "lat" => $geojson['coordinates'][1]  // Latitudes range from -90 to 90
                    ];
                }
            } else if($geojson['type'] == 'LineString'){
                if(!empty($geojson['coordinates']) && is_array($geojson['coordinates']) && count($geojson['coordinates']) > 1 ){
                    $output = [];
                    foreach($geojson['coordinates'] as $pair){
                        $output[] = [
                            "@type" => "Point",
                            "lon" => $pair[0], // Longitudes range from -180 to 180.
                            "lat" => $pair[1]  // Latitudes range from -90 to 90
                        ];
                    }
                }
            } else if($geojson['type'] == 'Polygon'){
                if(!empty($geojson['coordinates']) && is_array($geojson['coordinates']) && count($geojson['coordinates']) >= 1 ){
                    $output = [ "@type" => "Polygon", "rings" => [] ];
                    $i = 0;
                    foreach($geojson['coordinates'] as $rings){
                        $ring = [
                            "@type" => "Ring",
                            'points' => [],
                            'type' => $i == 0 ? 'exterior' : 'interior',
                            'passable' => true
                        ];
                        if(!empty($rings) && is_array($rings) && count($rings) > 1){
                            foreach($rings as $pair){
                                $ring['points'][] = [
                                    "@type" => "Point",
                                    "lon" => $pair[0], // Longitudes range from -180 to 180.
                                    "lat" => $pair[1]  // Latitudes range from -90 to 90
                                ];
                            }
                        }
                        if(!empty($ring['points'])){ $output['rings'][] = $ring; }
                        $i++;
                    }
                }
            } else if($geojson['type'] == 'MultiPolygon'){
                // >= to allow for the odd case of a Wrapped Polygon as a MultiPolygon of length 1 (Fucking Idiotic JD API)
                if(!empty($geojson['coordinates']) && is_array($geojson['coordinates']) && count($geojson['coordinates']) >= 1 ){
                    $output = []; // multipolygon array
                    foreach($geojson['coordinates'] as $polygon){
                        $poly = [ "@type" => "Polygon", "rings" => [] ];
                        if(!empty($polygon) && is_array($polygon) && count($polygon) >= 1 ){
                            $i = 0;
                            foreach($polygon as $rings){
                                $ring = [
                                    "@type" => "Ring",
                                    'points' => [],
                                    'type' => $i == 0 ? 'exterior' : 'interior',
                                    'passable' => true
                                ];
                                if(!empty($rings) && is_array($rings) && count($rings) > 1){
                                    foreach($rings as $pair){
                                        $ring['points'][] = [
                                            "@type" => "Point",
                                            "lon" => $pair[0], // Longitudes range from -180 to 180.
                                            "lat" => $pair[1]  // Latitudes range from -90 to 90
                                        ];
                                    }
                                }
                                if(!empty($ring['points'])){ $poly['rings'][] = $ring; }
                                $i++;
                            }
                        }
                        if(!empty($poly['rings'])){ $output[] = $poly; }
                    }
                }

            } else if($geojson['type'] == 'MultiPoint'){
                if(!empty($geojson['coordinates']) && is_array($geojson['coordinates']) && count($geojson['coordinates']) > 1 ){
                    $output = [];
                    foreach($geojson['coordinates'] as $pair){
                        $output[] = [
                            "@type" => "Point",
                            "lon" => $pair[0], // Longitudes range from -180 to 180.
                            "lat" => $pair[1]  // Latitudes range from -90 to 90
                        ];
                    }
                }
            } else if($geojson['type'] == 'MultiLineString'){
                if(!empty($geojson['coordinates']) && is_array($geojson['coordinates']) && count($geojson['coordinates']) > 1 ){
                    $output = [ "@type" => "Polygon", "rings" => [] ];
                    $i = 0;
                    foreach($geojson['coordinates'] as $pairs){
                        $ring = [
                            "@type" => "Ring",
                            'points' => [],
                            'type' => $i == 0 ? 'exterior' : 'interior',
                            'passable' => true
                        ];
                        if(!empty($pairs) && is_array($pairs) && count($pairs) > 1){
                            foreach($pairs as $pair){
                                $ring['points'][] = [
                                    "@type" => "Point",
                                    "lon" => $pair[0], // Longitudes range from -180 to 180.
                                    "lat" => $pair[1]  // Latitudes range from -90 to 90
                                ];
                            }
                        }
                        if(!empty($ring['points'])){ $output['rings'][] = $ring; }
                        $i++;
                    }
                }
            // Is like a FeatureCollection except it only contains geometry object(s)
            } else if($geojson['type'] == 'GeometryCollection'){
                if(!empty($geojson['geometries']) && is_array($geojson['geometries']) && count($geojson['geometries']) >= 1 ){
                    foreach($geojson['geometries'] as $geometry){
                        $out = $this->convert_geojson_to_boundary($geometry);
                        if($out){ $output[] = $out; }
                    }
                }
            // Contains a geometry object as well as a property object / We skip the property object and only extract the geometry object
            } else if($geojson['type'] == 'Feature'){
                if(!empty($geojson['geometry'])){
                    $out = $this->convert_geojson_to_boundary($geojson['geometry']);
                    if($out){ $output = $out; }
                }
            // Contains a geometry objects as well as a property objects / We skip the property objects and only extract the geometry objects
            } else if($geojson['type'] == 'FeatureCollection'){
                if(!empty($geojson['features']) && is_array($geojson['features']) && count($geojson['features']) >= 1){
                    foreach($geojson['features'] as $feature){
                        $out = $this->convert_geojson_to_boundary($feature);
                        if($out){ $output[] = $out; }
                    }
                    if(count($output) == 1){ $output = $output[0]; }
                }
            }
        }
        return $output;
    }

    // Converts JDO Boundary JSON to GeoJSON
    protected function convert_boundary_to_geojson($multipolygons, $output_type = 'MultiPolygon')
    {
        $output = null;

        if(!empty($multipolygons) && is_array($multipolygons) && count($multipolygons) >= 1){

            $output = [
                'type' => 'MultiPolygon',
                'coordinates' => []
            ];

            // iterate through polygons
            foreach($multipolygons as $polygon){

                if(!empty($polygon['@type']) && $polygon['@type'] == 'Polygon'){
                    if(!empty($polygon['rings']) && is_array($polygon['rings'])){

                        $poly = [];

                        // iterate through polygon's rings
                        foreach($polygon['rings'] as $ring){
                            if(!empty($ring['@type']) && $ring['@type'] == 'Ring'){
                                if(!empty($ring['points']) && is_array($ring['points'])){

                                    $ring_pairs = [];

                                    // iterate through ring pairs
                                    foreach($ring['points'] as $pair){
                                        if(!empty($pair['lon']) && !empty($pair['lat'])){
                                            $ring_pairs[] = [ $pair['lon'], $pair['lat'] ];
                                        }
                                    }

                                    if(!empty($ring)){ $poly[] = $ring_pairs; }
                                }
                            }
                        }

                        if(!empty($poly)){ $output['coordinates'][] = $poly; }
                    }
                }
            }
        }

        if(!empty($output) && $output_type == 'Feature'){
            return [ 'type' => 'Feature', 'properties' => [], 'geometry' => $output ];
        }

        return $output;
    }

    /* =========== */
    /* ----------- */
    /* CONNECTIONS */
    /* ----------- */
    /* =========== */

    protected function maybe_redirect_to_org_connections($integration)
    {
        $connUrl = null;
        
        $result = Http::withToken($integration->accessToken)
        ->accept('application/vnd.deere.axiom.v3+json')
        ->get("{$this->base_url}organizations");

        if($result->ok()){
            $data = $result->json();
            if(!empty($data['values'])){
                foreach($data['values'] as $entry){
                    if(!empty($entry['links'])){
                        foreach($entry['links'] as $link){
                            if($link['rel'] == 'connections'){
                                $connUrl = $link['uri'];
                                break 2;
                            }
                        }
                    }
                }
            }
            if($connUrl){
                Redirect::to(url($connUrl))->send();
            }
        }
    }

    /* ============= */
    /* ------------- */
    /* SUBSCRIPTIONS */
    /* ------------- */
    /* ============= */

    protected function maybe_create_event_subscription($integration)
    {
        $int_name = $integration->name;
        $conf_key = "{$this->slug}.oauth_conf";
        $org_key  = "{$int_name}.org_id";
        list($slug, $company_id) = explode('-', $int_name);
        $sub_already_exists = false;

        // Get Organization ID
        $org_id = Setting::get($org_key);
        if(!$org_id){ Log::debug('Missing organization id for company ' . $company_id ); return false; }

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
        }

        // Instantiate API
        $events_api = new EventSubscriptionsAPI($this->base_url, $int_name, $this->debug_mode);

        try {
            // Get Registered Event Subscriptions
            $events = $events_api->get_all($org_id);

            if($events){
                foreach($events as $event){
                    if(!empty($event['filters'][0]['values'][0]) && 
                        $event['filters'][0]['values'][0] == $org_id &&
                        $event['status'] != 'Terminated')
                    {
                        $sub_already_exists = true;
                        break;
                    }
                }
            }

            // No Active Subs for this Org, Register Event Subscription
            if(!$sub_already_exists){

                // Get Company
                $cc = DB::table('companies')->where('id', $company_id)->first();

                // Create Webhook Subscription
                $event_sub_id = $events_api->create_event_subscription(
                    'boundary',
                    [
                        [ "key" => "orgId",  "values" => [ "{$org_id}" ] ],
                        [ "key" => "action", "values" => ['CREATED', 'DELETED', 'MODIFIED'] ],
                    ],
                    'receiving_events', // route name
                    "{$cc->company_name} Boundary Events"
                );
                if(!$event_sub_id){ Log::debug("Failed to create event subscription for: {$org_id}"); return false; }
                Log::debug("Created new event subscription: {$org_id} -> {$event_sub_id}");
                Setting::set("{$int_name}.event_sub_id", $event_sub_id);

            } else {
                Log::debug("Event Subscription Already Exists, Skipping...");
            }

        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug("Error while trying to register subscription");
            Log::debug($ex->response);
            return false;
        }
    }

    protected function maybe_remove_event_subscription($integration)
    {
        $int_name  = $integration->name;
        $conf_key  = "{$this->slug}.oauth_conf";
        $event_key = "{$int_name}.event_sub_id";
        $org_key   = "{$int_name}.org_id";
        list($slug, $company_id) = explode('-', $int_name);

        // Get Organization ID
        $org_id = Setting::get($org_key);
        if(!$org_id){ Log::debug('Missing organization id for company ' . $company_id ); return false; }

        // OVERRIDE CONFIG
        if(!$this->override_config($this->slug, $conf_key, $company_id)){
            Log::debug('Failed to override config @ ' . __FUNCTION__); return false;
        }

        // Get Event Subscription ID
        $event_sub_id = Setting::get($event_key);
        if($event_sub_id){

            // Instantiate API
            $events_api = new EventSubscriptionsAPI($this->base_url, $int_name, $this->debug_mode);

            try {
                // Get Registered Event Subscriptions
                $events = $events_api->get_all($org_id);

                // Match by ID
                if(!$events){ Log::debug("Nothing to unsubscribe"); return false; }

                foreach($events as $event){
                    if($event['id'] == $event_sub_id && $event['status'] != 'Terminated'){
                        $my_event = $event;
                        $my_event['status'] = "Terminated";
                        $events_api->cancel_event_subscription($event_sub_id, $my_event);
                        Setting::del($event_key);
                    }
                }

            } catch (\Illuminate\Http\Client\RequestException $ex) {
                Log::debug('Exception @ ' . __FUNCTION__);
                Log::debug($ex->response);
                return false;
            }
        }
        return false;
    }
}