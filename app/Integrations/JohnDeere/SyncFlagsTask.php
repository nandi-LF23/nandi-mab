<?php

namespace App\Integrations\JohnDeere;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use MacsiDigital\OAuth2\Integration;
use App\Integrations\TaskBase;
use App\Integrations\JohnDeere\API\Flags as FlagsAPI;
use App\Integrations\JohnDeere\API\FlagCategories as FlagCategoriesAPI;
use App\Integrations\JohnDeere\API\Organizations as OrganizationsAPI;
use App\Integrations\JohnDeere\FlagSyncer;
use App\Integrations\JohnDeere\MyJohnDeere;
use App\Models\Setting;

// This task only updates existing JDO flags from nodes who have their 'Flags Sync' option enabled.
// New Flags are created when new nodes are created on MAB.** (Still considering this)
// New Flags are also conditionally created when an existing node's 'Flags Sync' option is toggled.

class SyncFlagsTask extends TaskBase {

    protected $flags_api;
    protected $flags_cat_api;
    protected $orgs_api;

    // Setup
    public function __invoke()
    {
        $this->setup();
    }

    public function setup()
    {
        //Log::debug("SyncFlagsTask::setup");
        $options = config('integrations')['classes']['\App\Integrations\JohnDeere\MyJohnDeere'];
        $debug_mode = $options['debug_mode'];
        $base_url = $options['base_url'];
        $slug = $options['slug'];

        // only run when active integrations exist
        $integrations = Integration::where('name', 'like', '%'.$slug.'%')->get();
        if($integrations->count() == 0){ return; }

        set_time_limit(0);

        $node_types = MyJohnDeere::types();

        foreach($integrations as $int){
            list($not_used, $company_id) = explode('-', $int->name);

            if(!$this->override_config($slug, "{$slug}.oauth_conf", $company_id)){ continue; }

            $this->flags_api     = new FlagsAPI($base_url, $int->name, $debug_mode);
            $this->flags_cat_api = new FlagCategoriesAPI($base_url, $int->name, $debug_mode);
            $this->orgs_api      = new OrganizationsAPI($base_url, $int->name, $debug_mode);

            $this->run_task($base_url, $slug, $company_id, $node_types);
        }
    }

    // Flag Sync Logic
    public function run_task($base_url, $slug, $company_id, $node_types)
    {
        //Log::debug("SyncFlagsTask");
        //return; 

        $int_name = "{$slug}-{$company_id}";
        $org_key  = "{$int_name}.org_id";

        // ORGANIZATION
        $org_id = Setting::get($org_key);
        if(!$org_id){
            Log::debug("SyncFlagsTask: org_id not set");
            //$org_id = $this->orgs_api->get_first();
            if(!$org_id){ return; }
            //Setting::set($org_key, $org_id, 600);
        }

        $nodes = $this->get_syncable_nodes($company_id, $node_types);
        $flags = $this->flags_api->get_all($org_id);

        // Start Sync
        foreach($nodes as $node){

            $flag_exists = false;
            $flag_ref    = null;

            // Confirm flag still exists on JDO
            if($flags){
                foreach($flags as $flag){
                    if(!empty($flag['notes']) && $node->node_address == $flag['notes']){ $flag_exists = true; $flag_ref = $flag; break; }
                }
            }

            // Update Existing JDO Flag with Syncable Node
            if($flag_exists){

                $flag_key   = "{$int_name}.{$node->node_address}.flag_id";
                $flag_id    = Setting::get($flag_key);
                $flags_sync = null;

                $options = json_decode($node->integration_opts, true);
                if($options){
                    $flags_sync = !empty($options[$slug]['hardware_config']['flags_sync']['value']) ? 
                        $options[$slug]['hardware_config']['flags_sync']['value'] : null;
                }

                if($flag_id && $flags_sync == '1'){

                    $syncer = new FlagSyncer($this->flags_api, $this->flags_cat_api);
                    $syncer->sync($node, $flag_id, $int_name, $org_id);

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
        ->where('integration_opts', 'like', '%"key":"flags_sync","value":"1"%')
        ->join('fields', 'hardware_config.node_address', 'fields.node_id')
        ->get();

        if($nodes->count() == 0){
            //Log::debug('SyncFlagsTask: Company has no syncable nutrient probes');
            return [];
        }

        return $nodes->toArray();
    }
}