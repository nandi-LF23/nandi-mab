<?php

namespace App\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MacsiDigital\OAuth2\Integration;

// Methods are called from various places in MAB

class IntegrationManager {

    // per-integration setup (called from CompanyController@get)
    public static function setup($company_id)
    {
        $integration_classes = config('integrations')['classes'];
        $integrations = [];

        foreach($integration_classes as $class => $classOpts){
            try {
                $i = new $class($classOpts);
                $integrations[$i->slug()] = $i->setup($company_id, true);
            } catch (\Exception $e){
                Log::debug($e->getMessage());
            }
        }
        return $integrations;
    }

    // per-integration event handlers registration (called from IntegrationServiceProvider's boot)
    public static function events()
    {
        $integration_classes = config('integrations')['classes'];

        foreach($integration_classes as $class => $classOpts){
            try {
                $i = new $class($classOpts);
                $i->events();
            } catch (\Exception $e){
                Log::debug($e->getMessage());
            }
        }
    }

    // per-integration routes registration (called from IntegrationServiceProvider's boot)
    public static function routes()
    {
        $integration_classes = config('integrations')['classes'];

        foreach($integration_classes as $class => $classOpts){
            try {
                $i = new $class($classOpts);
                $i->routes();
            } catch (\Exception $e){
                Log::debug($e->getMessage());
            }
        }
    }

    // per-integration default options registrations (called from HardwareConfigController@get)
    public static function options($company_id, $subsystem, $node_type = '')
    {
        $default_options = [];

        $active_integrations = Integration::where('name', 'like', "%-{$company_id}%")->get();

        if($active_integrations->count()){

            $integration_classes = config('integrations')['classes'];

            foreach($integration_classes as $class => $classOpts){
                try {
                    $i = new $class($classOpts);
                    if( 
                        $active_integrations->contains(function($v, $k) use ($i, $company_id) {
                            return ($i->slug().'-'.$company_id) == $v->name; 
                        })
                    ){
                        $opts = null;

                        // always filter options by subsystem
                        // optionally filter by node_type

                        if($node_type){
                            $opts = in_array($node_type, $i->types()) ? $i->options($subsystem) : null;
                        } else {
                            $opts = $i->options($subsystem);
                        }

                        // e.g: $default_options['MyJohnDeere']['hardware_config'] = [ .. slice of subsystem options .. ]
                        if($opts){
                            $default_options[$i->slug()][$subsystem] = $opts;
                        }
                    }
                } catch (\Exception $e){
                    Log::debug($e->getMessage());
                }
            }
        }
        return $default_options;
    }

}
