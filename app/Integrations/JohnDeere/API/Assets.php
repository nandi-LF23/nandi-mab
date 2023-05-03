<?php

namespace App\Integrations\JohnDeere\API;

use Illuminate\Support\Facades\Log;
use App\Integrations\JohnDeere\API\ApiBase;

class Assets extends ApiBase {

    // These are required to be able to create new Assets
    // (See: https://developer-portal.deere.com/#/myjohndeere/contributions/activation-overview)
    
    protected $contrib_prod_id; // Contribution Product ID
    protected $contrib_def_id;  // Contribution Definition ID

    public function __construct(
        $base_url,
        $integration,
        $debug_mode = false
    ){
        parent::__construct($base_url, $integration, $debug_mode);
    }

    // get asset catalog
    public function get_catalog()
    {
        $response = $this->request()->get("assetCatalog");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Assets catalog fetch failed"); 
                Log::debug($response);
            }
            return false;
        }
        
        $data = $response->json();
        if($this->debug_mode){
            Log::debug($data);
        }

        return !empty($data['values']) ? $data['values'] : [];
    }

    // get all assets
    public function get_all($org_id)
    {
        $response = $this->request([ 'No_Paging' => 'true' ], true)->get("organizations/{$org_id}/assets");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Assets fetch failed"); 
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

    // get single asset by asset_id
    public function get($asset_id)
    {
        $response = $this->request()->get("assets/{$asset_id}");
        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Single Asset fetch failed: $asset_id");
                Log::debug($response);
            }
            return false;
        }
        return $response->json();
    }

    // create an asset
    public function create_asset($org_id, $params)
    {
        $asset_id = false;

        // create link
        $link_obj = [
            '@type' => "Link",
            'rel'   => "contributionDefinition",
            'uri'   => "{$this->base_url}contributionDefinitions/{$this->contrib_def_id}"
        ];
        
        // inject required link
        if(!empty($params['links'])){ $params['links'][] = $link_obj; } else { $params['links'] = [ $link_obj ]; }
        
        $response = $this->request()
        ->withBody(
            json_encode($params, JSON_UNESCAPED_SLASHES),
            'application/vnd.deere.axiom.v3+json'
        )
        ->post("organizations/{$org_id}/assets");

        if($response->status() == 201){
            $headers = $response->headers();
            if(!empty($headers['Location'][0])){
                $asset_id = str_replace("{$this->base_url}assets/", "", $headers['Location'][0]);
            }
        } else {
            if($this->debug_mode){
                Log::debug("Error: Asset creation failed");
                Log::debug($response);
            }
        }
        return $asset_id;
    }

    // create location (send measurements)
    public function create_location($asset_id, $params)
    {
        $result = false;
        try {
            $response = $this->request()
            ->withBody($params,'application/vnd.deere.axiom.v3+json')
            ->post("assets/{$asset_id}/locations");
            if($response->status() !== 201){
                if($this->debug_mode){
                    Log::debug("Error: Asset location creation failed: $asset_id");
                    Log::debug($response);
                }
                $result = false;
            }
            $result = $response->json();
        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug("Error: Asset location update failed");
            Log::debug($ex->response);
        }
        return $result;
    }

    // update an asset (only title)
    public function update($asset_id, $params)
    {
        // create link
        $link_obj = [
            '@type' => "Link",
            'rel'   => "contributionDefinition",
            'uri'   => "{$this->base_url}contributionDefinitions/{$this->contrib_def_id}"
        ];

        // inject required link
        if(!empty($params['links'])){ $params['links'][] = $link_obj; } else { $params['links'] = [ $link_obj ]; }

        $response = $this->request()
        ->contentType('application/vnd.deere.axiom.v3+json')
        ->put("assets/{$asset_id}", $params);
        if($response->status() !== 204){
            if($this->debug_mode){
                Log::debug("Error: Asset update failed: $asset_id");
                Log::debug($response);
            }
            return false;
        }
        return true;
    }

    // delete an asset
    public function delete($asset_id)
    {
        $response = $this->request()->delete("assets/{$asset_id}");
        if($response->status() !== 204){
            if($this->debug_mode){
                Log::debug("Error: Asset deletion failed: $asset_id");
                Log::debug($response);
            }
            return false;
        }
        return true;
    }
}
