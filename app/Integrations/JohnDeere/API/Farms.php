<?php

namespace App\Integrations\JohnDeere\API;

use Illuminate\Support\Facades\Log;
use App\Integrations\JohnDeere\API\ApiBase;

class Farms extends ApiBase {

    public function __construct($base_url, $integration, $debug_mode = false)
    {
        parent::__construct($base_url, $integration, $debug_mode);
    }

    // get first farm
    public function get_first($org_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/farms");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Farms fetch first failed");
            }
            return false;
        }
        $data = $response->json();
        if($this->debug_mode){
            Log::debug($data);
        }
        
        if(empty($data['values'][0]['id'])){
            if($this->debug_mode){
                Log::debug("Error: No farms registered");
            }
            return false;
        }
        return $data['values'][0]['id'];
    }

    // get all farms
    public function get_all($org_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/farms");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Farms fetch failed"); 
                Log::debug($response);
            }
            return false;
        }
        $data = $response->json();
        if(empty($data['values'])){
            if($this->debug_mode){
                Log::debug("Error: No farms registered");
            }
            return [];
        }
        return $data['values'];
    }

    // get all farms by client ID
    public function get_all_by_client($org_id, $client_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/clients/{$client_id}/farms");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Farms fetch by client ($client_id) failed"); 
                Log::debug($response);
            }
            return false;
        }
        $data = $response->json();
        if(empty($data['values'])){
            if($this->debug_mode){
                Log::debug("Error: No farms registered");
            }
            return [];
        }
        return $data['values'];
    }

    // get farm by field (get field's parent farm)
    public function get_farm_by_field($org_id, $field_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/fields/{$field_id}/farms");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Farms fetch by field ($field_id) failed"); 
                Log::debug($response);
            }
            return false;
        }
        $data = $response->json();
        if(empty($data['values'])){
            if($this->debug_mode){
                Log::debug("Error: Field has no parent");
            }
            return [];
        }
        return $data['values'];
    }

    // get single farm by farm_id
    public function get($org_id, $farm_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/farms/{$farm_id}");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Single Farm fetch failed: $farm_id");
                Log::debug($response);
            }
            return false;
        }
        return $response->json();
    }

    // create a new farm
    public function create_farm($org_id, $client_id, $params)
    {
        // create link
        $link_obj = [
            '@type' => "Link",
            'rel'   => "client",
            'uri'   => "{$this->base_url}organizations/{$org_id}/clients/{$client_id}"
        ];
        $farm_id = false;

        // inject required link
        if(!empty($params['links'])){ $params['links'][] = $link_obj; } else { $params['links'] = [ $link_obj ]; }

        $response = $this->request()
        ->withBody(
            json_encode($params, JSON_UNESCAPED_SLASHES),
            'application/vnd.deere.axiom.v3+json'
        )
        ->post("organizations/{$org_id}/farms", $params);

        if($response->status() == 201){
            $headers = $response->headers();
            if(!empty($headers['Location'][0])){
                $farm_id = str_replace("{$this->base_url}organizations/{$org_id}/farms/", "", $headers['Location'][0]);
            }
        } else {
            if($this->debug_mode){
                Log::debug("Error: Farm creation failed");
                Log::debug($response);
            }
        }
        return $farm_id;

    }

    // update a farm
    public function update($org_id, $farm_id, $client_id, $params)
    {
        // create clientLink
        $link_obj = [
            '@type' => "Link",
            'rel'   => "client",
            'uri'   => "{$this->base_url}organizations/{$org_id}/clients/{$client_id}"
        ];

        // inject required link
        if(!empty($params['clientLink'])){ $params['clientLink'][] = $link_obj; } else { $params['clientLink'] = [ $link_obj ]; }

        $response = $this->request()
        ->contentType('application/vnd.deere.axiom.v3+json')
        ->put("organizations/{$org_id}/farms/{$farm_id}", $params);
        if($response->status() !== 204){
            if($this->debug_mode){
                Log::debug("Error: Farm update failed: $farm_id");
                Log::debug($response);
            }
            return false;
        }
        return true;
    }

    // delete a farm
    public function delete($org_id, $farm_id)
    {
        $response = $this->request()->delete("organizations/{$org_id}/farms/{$farm_id}");
        if($response->status() !== 204){
            if($this->debug_mode){
                Log::debug("Error: Farm deletion failed: $farm_id");
                Log::debug($response);
            }
            return false;
        }
        return true;
    }
}
