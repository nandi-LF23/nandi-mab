<?php

namespace App\Integrations\JohnDeere;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use MacsiDigital\OAuth2\Integration;
use App\Integrations\TaskBase;
use App\Integrations\JohnDeere\API\Assets as AssetsAPI;
use App\Integrations\JohnDeere\API\Organizations as OrganizationsAPI;
use App\Integrations\JohnDeere\AssetSyncer;
use App\Integrations\JohnDeere\MyJohnDeere;
use App\Models\Setting;

// This task only updates existing JDO assets from nodes who have their 'Asset Sync' option enabled.
// New Assets are created when new nodes are created on MAB.** (Still considering this)
// New Assets are also conditionally created when an existing node's 'Asset Sync' option is toggled.

class SyncAssetsTask extends TaskBase {

    protected $assets_api;
    protected $orgs_api;

    // Setup
    public function __invoke()
    {
        $this->setup();
    }

    public function setup()
    {
        //Log::debug("SyncAssetsTask::setup");
        $options = config('integrations')['classes']['\App\Integrations\JohnDeere\MyJohnDeere'];
        $debug_mode = $options['debug_mode'];
        $base_url = $options['base_url'];
        $slug = $options['slug'];
        $conf_key = "{$slug}.oauth_conf";

        // only run when active integrations exist
        $integrations = Integration::where('name', 'like', '%'.$slug.'%')->get();
        if($integrations->count() == 0){ return; }

        set_time_limit(0);

        $node_types = MyJohnDeere::types();

        foreach($integrations as $int){
            list($not_used, $company_id) = explode('-', $int->name);

            if(!$this->override_config($slug, $conf_key, $company_id)){ continue; }

            $this->assets_api = new AssetsAPI($base_url, $int->name, $debug_mode);
            $this->orgs_api   = new OrganizationsAPI($base_url, $int->name, $debug_mode);

            $this->run_task($base_url, $slug, $company_id, $node_types);
        }
    }

    // Asset Sync Logic
    public function run_task($base_url, $slug, $company_id, $node_types)
    {
        //Log::debug("SyncAssetsTask");
        //return;

        $int_name = "{$slug}-{$company_id}";
        $org_key  = "{$int_name}.org_id";

        // ORGANIZATION
        $org_id = Setting::get($org_key);
        if(!$org_id){
            Log::debug("SyncAssetsTask: org_id not set");
            //$org_id = $this->orgs_api->get_first();
            if(!$org_id){ return; }
            //Setting::set($org_key, $org_id, 600);
        }

        $nodes  = $this->get_syncable_nodes($company_id, $node_types);
        $assets = $this->assets_api->get_all($org_id);

        // Start Sync
        foreach($nodes as $node){

            $asset_exists = false;
            $asset_ref = null;

            // Confirm asset still exists on JDO
            foreach($assets as $asset){
                if($node->node_address == $asset['title']){ $asset_exists = true; $asset_ref = $asset; break; }
            }

            // Update Existing JDO Asset with Syncable Node
            if($asset_exists){

                $asset_key  = "{$int_name}.{$node->node_address}";
                $asset_id   = Setting::get($asset_key);
                $asset_sync = null;

                $options = json_decode($node->integration_opts, true);
                if($options){
                    $asset_sync = !empty($options[$slug]['hardware_config']['asset_sync']['value']) ? 
                        $options[$slug]['hardware_config']['asset_sync']['value'] : null;
                }

                if($asset_id && $asset_sync == '1'){

                    $syncer = new AssetSyncer($this->assets_api);
                    $syncer->sync($node, $asset_id, $int_name);

                }
            }
        }
    }

    public function get_syncable_nodes($company_id, $node_types)
    {
        $nodes = DB::table('hardware_config')
        ->select([
            'node_address',
            'field_name',
            'fields.id as field_id',
            'nutrient_template_id',
            'node_type',
            'date_time',
            'integration_opts',
            'latt',
            'lng',
            'zone',
            'hardware_config.company_id'
        ])
        ->where('hardware_config.company_id', $company_id)
        ->whereIn('node_type', $node_types)
        ->where('integration_opts', 'like', '%"key":"asset_sync","value":"1"%')
        ->join('fields', 'hardware_config.node_address', 'fields.node_id')
        ->get();

        if($nodes->count() == 0){
            //Log::debug('SyncAssetsTask: Company has no syncable nutrient probes');
            return [];
        }

        return $nodes->toArray();
    }

}