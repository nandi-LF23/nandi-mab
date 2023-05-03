<?php

namespace App\Integrations\JohnDeere\API;

use Illuminate\Support\Facades\Log;
use App\Integrations\JohnDeere\API\ApiBase;

class Clients extends ApiBase {

    public function __construct($base_url, $integration, $debug_mode = false)
    {
        parent::__construct($base_url, $integration, $debug_mode);
    }

    // get first client
    public function get_first($org_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/clients");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Clients fetch first failed");
            }
            return false;
        }
        $data = $response->json();
        if($this->debug_mode){
            Log::debug($data);
        }
        
        if(empty($data['values'][0]['id'])){
            if($this->debug_mode){
                Log::debug("Error: No clients registered");
            }
            return false;
        }
        return $data['values'][0]['id'];
    }

    // get all clients
    public function get_all($org_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/clients");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Clients fetch failed"); 
                Log::debug($response);
            }
            return false;
        }
        $data = $response->json();
        if(empty($data['values'])){
            if($this->debug_mode){
                Log::debug("Error: No clients registered");
            }
            return [];
        }
        return $data['values'];
    }

    // get single client by client_id
    public function get($org_id, $client_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/clients/{$client_id}");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Single client fetch failed: $client_id");
                Log::debug($response);
            }
            return false;
        }
        return $response->json();
    }

    // get client by field_id
    public function get_client_by_field($org_id, $field_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/fields/{$field_id}/clients");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Get client by field_id fetch failed: $field_id");
                Log::debug($response);
            }
            return false;
        }
        return $response->json();
    }

    // get client by farm_id
    public function get_client_by_farm($org_id, $farm_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/farms/{$farm_id}/clients");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Get client by farm_id fetch failed: $farm_id");
                Log::debug($response);
            }
            return false;
        }
        return $response->json();
    }

    // create a new client
    public function create_client($org_id, $params)
    {
        $client_id = false;

        $response = $this->request()
        ->contentType('application/vnd.deere.axiom.v3+json')
        ->post("organizations/{$org_id}/clients", $params);

        if($response->status() == 201){
            $headers = $response->headers();
            if(!empty($headers['Location'][0])){
                Log::debug("Client creation succeeded!");
                $client_id = str_replace("{$this->base_url}organizations/{$org_id}/clients/", "", $headers['Location'][0]);
            }
        } else {
            $headers = $response->headers();
            Log::debug("Error: Client creation failed");
            Log::debug($response);
            Log::debug($headers);
        }

        return $client_id;
    }

    // update a client
    public function update($org_id, $client_id, $params)
    {
        $response = $this->request()
        ->contentType('application/vnd.deere.axiom.v3+json')
        ->put("organizations/{$org_id}/clients/{$client_id}", $params);
        if($response->status() !== 204){
            if($this->debug_mode){
                Log::debug("Error: Client update failed: $client_id");
                Log::debug($response);
            }
            return false;
        }
        return true;
    }

    // delete a client
    public function delete($org_id, $client_id)
    {
        $response = $this->request()->delete("organizations/{$org_id}/clients/{$client_id}");
        if($response->status() !== 204){
            if($this->debug_mode){
                Log::debug("Error: Client deletion failed: $client_id");
                Log::debug($response);
            }
            return false;
        }
        return true;
    }
}
