<?php

namespace App\Integrations\JohnDeere\API;

use Illuminate\Support\Facades\Log;
use App\Integrations\JohnDeere\API\ApiBase;

class Boundaries extends ApiBase {

    public function __construct($base_url, $integration, $debug_mode = false)
    {
        parent::__construct($base_url, $integration, $debug_mode);
    }

    // get all boundaries by field
    public function get_all_by_field($org_id, $field_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/fields/{$field_id}/boundaries");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Boundary fetch by field ($field_id) failed"); 
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

    public function get($org_id, $field_id, $boundary_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/fields/{$field_id}/boundaries/{$boundary_id}");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error during boundary fetch (organizations/{$org_id}/fields/{$field_id}/boundaries/{$boundary_id})");
                Log::debug($response);
                return false;
            }
        }
        return $response->json();
    }

    // create a new boundary, returns a boundary_id on success
    public function create_boundary($org_id, $field_id, $params)
    {
        $boundary_id = false;

        // create link
        $link_obj = [
            '@type' => "Link",
            'rel'   => "contributionDefinition",
            'uri'   => "{$this->base_url}contributionDefinitions/{$this->contrib_def_id}"
        ];
        
        // inject required link
        if(!empty($params['links'])){ $params['links'][] = $link_obj; } else { $params['links'] = [ $link_obj ]; }

        try {
            $response = $this->request()
            ->contentType('application/vnd.deere.axiom.v3+json')
            ->post("organizations/{$org_id}/fields/{$field_id}/boundaries", $params);

            if($response->status() == 201){
                $headers = $response->headers();
                if(!empty($headers['Location'][0])){
                    if($this->debug_mode){
                        Log::debug("Boundary creation succeeded!");
                    }
                    $boundary_id = str_replace("{$this->base_url}organizations/{$org_id}/fields/{$field_id}/boundaries/", "", $headers['Location'][0]);
                }
            } else {
                if($this->debug_mode){
                    Log::debug("Error: Field boundary creation failed");
                    Log::debug($response);
                }
            }
        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug("Error: Field boundary creation failed");
            Log::debug($ex->response);
        }
        return $boundary_id;
    }

    // update an existing boundary (requires a boundary_id)
    public function update_boundary($org_id, $field_id, $boundary_id, $params)
    {
        $result = false;

        try {
            $response = $this->request()
            ->contentType('application/vnd.deere.axiom.v3+json')
            ->put("organizations/{$org_id}/fields/{$field_id}/boundaries/{$boundary_id}", $params);

            if($response->status() == 204){
                if($this->debug_mode){ Log::debug("Field boundary update success"); }
                $result = true;
            } else {
                if($this->debug_mode){
                    Log::debug("Error: Field boundary update failed");
                    Log::debug($response);
                }
            }
        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug("Error: Field boundary update failed");
            Log::debug($ex->response);
        }

        return $result;
    }

    public function delete_boundary($org_id, $field_id, $boundary_id)
    {
        $response = $this->request()
        ->contentType('application/vnd.deere.axiom.v3+json')
        ->delete("organizations/{$org_id}/fields/{$field_id}/boundaries/{$boundary_id}");
        return $response->status() == 204 ? true : false;
    }

    // generate a field boundary
    public function generate()
    {

    }
}
