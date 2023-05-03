<?php

namespace App\Integrations\JohnDeere\API;

use Illuminate\Support\Facades\Log;
use App\Integrations\JohnDeere\API\ApiBase;

class Organizations extends ApiBase {

    public function __construct($base_url, $integration, $debug_mode = false)
    {
        parent::__construct($base_url, $integration, $debug_mode);
    }

    public function get_first()
    {
        $response = $this->request()->get('organizations');
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Organization fetch");
            }
            return false;
        }
        $data = $response->json();
        if($this->debug_mode){
            Log::debug($data);
        }
        
        if(empty($data['values'][0]['id'])){
            if($this->debug_mode){
                Log::debug("Error: No organizations registered");
            }
            return false;
        }
        return $data['values'][0]['id'];
    }

}
