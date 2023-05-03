<?php

namespace App\Integrations\JohnDeere\API;

use Illuminate\Support\Facades\Log;
use App\Integrations\JohnDeere\API\ApiBase;

class FlagCategories extends ApiBase {

    public function __construct(
        $base_url,
        $integration,
        $debug_mode = false
    ){
        parent::__construct($base_url, $integration, $debug_mode);
    }

    // get all flag categories (checked)
    // GET /organizations/{orgId}/flagCategories
    public function get_all($org_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/flagCategories");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Flag Categories fetch failed");
                Log::debug($response);
            }
            return [];
        }

        $data = $response->json();
        if($this->debug_mode){
            Log::debug($data);
        }

        return !empty($data['values']) ? $data['values'] : [];
    }

    // get single flag by flag_id (checked)
    // GET /organizations/{orgId}/flagCategories/{categoryId}
    public function get($org_id, $flag_cat_id)
    {
        $response = $this->request()->get("organizations/{$org_id}/flagCategories/{$flag_cat_id}");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Single Flag Category fetch failed: $flag_cat_id");
                Log::debug($response);
            }
            return false;
        }
        return $response->json();
    }

    // create a flag category (checked)
    // POST /organizations/{orgId}/flagCategories
    public function create_flag_category($org_id, $category_name)
    {
        $flag_cat_id = false;

        // create link
        $link_obj = [
            '@type' => "Link",
            'rel'   => "contributionDefinition",
            'uri'   => "{$this->base_url}contributionDefinitions/{$this->contrib_def_id}"
        ];

        $params = [
            "@type"         => "FlagCategory",
            "sourceNode"    => $this->contrib_def_id, // "sourceNode"
            "categoryTitle" => $category_name,
            "preferred"     => true,
            "archived"      => false,
            "links" => [
                $link_obj
            ]
        ];

        $response = $this->request()
        ->withBody(
            json_encode($params, JSON_UNESCAPED_SLASHES),
            'application/vnd.deere.axiom.v3+json'
        )->post("organizations/{$org_id}/flagCategories");

        if($response->status() == 201){
            $headers = $response->headers();
            if(!empty($headers['Location'][0])){
                $flag_cat_id = str_replace("{$this->base_url}organizations/{$org_id}/flagCategories/", "", $headers['Location'][0]);
            }
        } else if($this->debug_mode){
            Log::debug("Error: Flag category creation failed");
            Log::debug($response);
        }

        return $flag_cat_id;
    }

    // update a flag category (checked)
    // PUT /organizations/{orgId}/flagCategories/{categoryId}
    public function update($org_id, $flag_cat_id, $new_category_name)
    {
        $response = $this->request()
        ->contentType('application/vnd.deere.axiom.v3+json')
        ->withBody(
            json_encode([
                'categoryTitle' => $new_category_name,
                'preferred' => 'true',
                'archived' => 'false'
            ], JSON_UNESCAPED_SLASHES),
            'application/vnd.deere.axiom.v3+json'
        )->put("organizations/{$org_id}/flagCategories/{$flag_cat_id}");

        if($response->status() !== 204){
            if($this->debug_mode){
                Log::debug("Error: Flag category update failed: $flag_cat_id");
                Log::debug($response);
            }
            return false;
        }
        return true;
    }

    // delete a flag category (checked)
    // DELETE /organizations/{orgId}/flagCategories/{categoryId}
    public function delete($org_id, $flag_cat_id)
    {
        $response = $this->request()->delete("organizations/{$org_id}/flagCategories/{$flag_cat_id}");
        if($response->status() !== 204){
            if($this->debug_mode){
                Log::debug("Error: Flag category deletion failed: $flag_cat_id");
                Log::debug($response);
            }
            return false;
        }
        return true;
    }
}
