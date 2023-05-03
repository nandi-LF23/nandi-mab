<?php

namespace App\Integrations\JohnDeere\API;

use Illuminate\Support\Facades\Log;
use App\Integrations\JohnDeere\API\ApiBase;

// NOT FINISHED

class Flags extends ApiBase {

    public function __construct(
        $base_url,
        $integration,
        $debug_mode = false
    ){
        parent::__construct($base_url, $integration, $debug_mode);
    }

    // get all flags (checked)
    // GET /organizations/{orgId}/flags
    public function get_all($org_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/flags");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Flags fetch failed");
                Log::debug($response);
            }
            return [];
        }

        $data = $response->json();
        if($this->debug_mode){
            Log::debug("Debug mode:");
            Log::debug($data);
        }

        return !empty($data['values']) ? $data['values'] : [];
    }

    // get single flag by flag_id (checked)
    // GET /organizations/{orgId}/flags/{flagId}
    public function get($org_id, $flag_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/flags/{$flag_id}");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Single Flag fetch failed: $flag_id");
                Log::debug($response);
            }
            return false;
        }
        return $response->json();
    }

    // get all flags by field
    // GET /organizations/{orgId}/fields/{fieldId}/flags
    public function get_by_field($org_id, $field_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/{$field_id}/flags");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Flags by field fetch failed");
                Log::debug($response);
            }
            return [];
        }

        $data = $response->json();
        
        if($this->debug_mode){
            Log::debug("Debug mode:");
            Log::debug($data);
        }

        return !empty($data['values']) ? $data['values'] : [];
    }

    // create a flag (checked)
    // POST /organizations/{orgId}/flags
    public function create_flag($org_id, $flag_cat_id, $field_id, $params)
    {
        $flag_id = false;

        // contrib link
        $contrib_link = [
            '@type' => "Link",
            'rel'   => "contributionDefinition",
            'uri'   => "{$this->base_url}contributionDefinitions/{$this->contrib_def_id}"
        ];
        
        // flag category link
        $flag_cat_link = [
            '@type' => "Link",
            'rel'   => "flagCategory",
            'uri'   => "{$this->base_url}flagCategory/{$flag_cat_id}"
        ];

        // field link
        $field_link = [
            '@type' => "Link",
            'rel'   => "field",
            'uri'   => "{$this->base_url}organizations/{$org_id}/fields/{$field_id}"
        ];

        // inject required links
        if(!empty($params['links'])){ $params['links'][] = $contrib_link; } else { $params['links'] = [ $contrib_link ]; }
        $params['links'][] = $flag_cat_link;
        $params['links'][] = $field_link;
        
        $response = $this->request()
        ->withBody(
            json_encode($params, JSON_UNESCAPED_SLASHES),
            'application/vnd.deere.axiom.v3+json'
        )->post("organizations/{$org_id}/flags");

        if($response->status() == 201){
            $headers = $response->headers();
            if(!empty($headers['Location'][0])){
                $flag_id = str_replace("{$this->base_url}organizations/{$org_id}/flags/", "", $headers['Location'][0]);
            }
        } else {
            if($this->debug_mode){
                Log::debug("Error: Flag creation failed");
                Log::debug($response);
            }
        }
        return $flag_id;
    }

    // update a flag (checked)
    // PUT /organizations/{orgId}/flags/{flagId}
    public function update_flag($org_id, $flag_cat_id, $field_id, $flag_id, $params)
    {
        // flag category link
        $flag_cat_link = [
            '@type' => "Link",
            'rel'   => "flagCategory",
            'uri'   => "{$this->base_url}flagCategory/{$flag_cat_id}"
        ];

        // field link
        $field_link = [
            '@type' => "Link",
            'rel'   => "field",
            'uri'   => "{$this->base_url}organizations/{$org_id}/fields/{$field_id}"
        ];

        // org link
        $org_link = [
            '@type' => "Link",
            'rel'   => "owningOrganization",
            'uri'   => "{$this->base_url}organizations/{$org_id}"
        ];

        // inject required links
        if(!empty($params['links'])){ $params['links'][] = $flag_cat_link; } else { $params['links'] = [ $flag_cat_link ]; }
        $params['links'][] = $field_link;
        $params['links'][] = $org_link;

        $response = $this->request()
        ->contentType('application/vnd.deere.axiom.v3+json')
        ->withBody(
            json_encode($params, JSON_UNESCAPED_SLASHES),
            'application/vnd.deere.axiom.v3+json'
        )->put("organizations/{$org_id}/flags/{$flag_id}");

        if($response->status() !== 204){
            if($this->debug_mode){
                Log::debug("Error: Flag update failed: $flag_id");
                Log::debug($response);
            }
            return false;
        }
        return true;
    }

    // delete an flag (checked)
    // DELETE /organizations/{orgId}/flags/{flagId}
    public function delete($org_id, $flag_id)
    {
        $response = $this->request()->delete("organizations/{$org_id}/flags/{$flag_id}");
        if($response->status() !== 204){
            if($this->debug_mode){
                Log::debug("Error: Flag deletion failed: $flag_id");
                Log::debug($response);
            }
            return false;
        }
        return true;
    }
}
