<?php

namespace App\Integrations\JohnDeere\API;

use Illuminate\Support\Facades\Log;
use App\Integrations\JohnDeere\API\ApiBase;

class Fields extends ApiBase {

    public function __construct($base_url, $integration, $debug_mode = false)
    {
        parent::__construct($base_url, $integration, $debug_mode);
    }

    // get all fields
    public function get_all($org_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/fields");

        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Fields fetch failed"); 
                Log::debug($response);
            }
            return false;
        }
        $data = $response->json();
        if(empty($data['values'])){
            if($this->debug_mode){
                Log::debug("Error: No fields registered");
            }
            return [];
        }
        return $data['values'];
    }

    // get all fields by client ID
    public function get_all_by_client($org_id, $client_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/clients/{$client_id}/fields");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Fields fetch by client ($client_id) failed"); 
                Log::debug($response);
            }
            return false;
        }
        $data = $response->json();
        if(empty($data['values'])){
            if($this->debug_mode){
                Log::debug("Error: No fields registered");
            }
            return [];
        }
        return $data['values'];
    }

    // get all fields by client ID
    public function get_all_by_farm($org_id, $farm_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/clients/{$farm_id}/fields");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Fields fetch by farm ($farm_id) failed"); 
                Log::debug($response);
            }
            return false;
        }
        $data = $response->json();
        if(empty($data['values'])){
            if($this->debug_mode){
                Log::debug("Error: No fields registered");
            }
            return [];
        }
        return $data['values'];
    }

    // get single field by field_id
    public function get($org_id, $field_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/fields/{$field_id}");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Single Field fetch failed: $field_id");
                Log::debug($response);
            }
            return false;
        }
        return $response->json();
    }

    // create a new field
    public function create_field($org_id, $params)
    {
        $field_id = false;

        try {
            $response = $this->request()
            ->contentType('application/vnd.deere.axiom.v3+json')
            ->post("organizations/{$org_id}/fields", $params);

            if($response->status() == 201){
                $headers = $response->headers();
                if(!empty($headers['Location'][0])){
                    Log::debug("Field creation succeeded!");
                    $field_id = str_replace("{$this->base_url}organizations/{$org_id}/fields/", "", $headers['Location'][0]);
                }
            } else {
                if($this->debug_mode){
                    Log::debug("Error: Field creation failed");
                    Log::debug($response);
                }
            }
        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug("Error: Field creation failed");
            Log::debug($ex->response);
        }

        return $field_id;
    }

    // update a field
    public function update($org_id, $field_id, $params)
    {
        $response = $this->request()
        ->contentType('application/vnd.deere.axiom.v3+json')
        ->put("organizations/{$org_id}/fields/{$field_id}", $params);
        if($response->status() !== 204){
            if($this->debug_mode){
                Log::debug("Error: Field update failed: $field_id");
                Log::debug($response);
            }
            return false;
        }
        return true;
    }

    // delete a field
    public function delete($org_id, $field_id)
    {
        $response = $this->request()->delete("organizations/{$org_id}/fields/{$field_id}");
        if($response->status() !== 204){
            if($this->debug_mode){
                Log::debug("Error: Field deletion failed: $asset_id");
                Log::debug($response);
            }
            return false;
        }
        return true;
    }
}
