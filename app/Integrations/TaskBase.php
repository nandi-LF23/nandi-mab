<?php

namespace App\Integrations;

use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class TaskBase {

    /*
        Why do we need to override the integration configuration file?

        * Some fields are dynamic (Such as the openid endpoints, that could change at a whim)

    */

    public function override_config($slug, $config_key, $company_id = null)
    {
        $config = Setting::get($config_key);

        if(!$config){ Log::debug("Task Base Empty config: $config_key"); return false; }

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
}