<?php

namespace App\Integrations;

use Illuminate\Support\Facades\Log;
use MacsiDigital\OAuth2\Integration;
use App\Models\Setting;

class IntegrationBase {

    protected $slug;
    protected $base_url;
    protected $debug_mode;

    public function __construct($options)
    {
        $this->slug = $options['slug'];
        $this->base_url = $options['base_url'];
        $this->debug_mode = $options['debug_mode'];
    }

    public function slug()
    {
        return $this->slug;
    }

    public function base_url()
    {
        return $this->base_url;
    }
 
    public function is_active($company_id)
    {
        return Integration::where('name', 'like', '%'. $this->slug . '-' . $company_id . '%')->count() > 0;
    }

    /*
        Why do we need to override the integration configuration file?

        * Some fields are dynamic (Such as the openid endpoints, that could change at a whim)

    */

    public function override_config($slug, $config_key, $company_id = null)
    {
        $config = Setting::get($config_key);

        if(!$config){ Log::debug("Integration Base Empty config: $config_key"); return false; }

        $config = json_decode($config, true);

        if($config){ 
            // override base config
            foreach($config as $key => $val){ $k = "{$slug}.{$key}"; config([ $k => $val ]); } 

            // optionally override per-company config (virtual)
            if($company_id){
                foreach($config as $key => $val){ $k = "{$slug}-{$company_id}.{$key}"; config([ $k => $val ]); } 
            }
        }


        return $config;
    }

    public function setup($company_id){}

    public function events(){}

    public function routes(){}

    public function options($subsystem){}

}