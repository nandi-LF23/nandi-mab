<?php

namespace App\Integrations\JohnDeere\API;

use MacsiDigital\OAuth2\Support\Token\DB as DBToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiBase {

    protected $base_url;
    protected $token;
    protected $http;
    protected $debug_mode;

    public function __construct(
        $base_url,
        $integration, /* slug-company_id */
        $debug_mode = false,
        $contrib_prod_id = 'e804b2b1-8c4b-4682-b66e-0058b3ea6ca4', // These need to be stored as settings (create during setup)
        $contrib_def_id = '92d6e274-1696-4002-9c13-bdc861f46253'   // These need to be stored as settings (create during setup)
    ){
        $this->base_url = $base_url;
        $this->token = new DBToken($integration); // slug-company_id
        $this->http = $this->build_base_query();
        $this->debug_mode = $debug_mode;
        $this->contrib_prod_id = $contrib_prod_id;
        $this->contrib_def_id  = $contrib_def_id;
    }

    // override for each api
    public function get_all($org_id){ return []; }

    // get contribution product id
    public function get_contribution_product_id()
    {
        return $this->contrib_prod_id;
    }

    // get contribution definition id
    public function get_contribution_definition_id()
    {
        return $this->contrib_def_id;
    }

    public function request($headers = [], $merge = false)
    {
        if($this->token->hasExpired()){
            $this->token->renewToken();
        }
        $this->http = $this->build_base_query($headers, $merge);
        return $this->http;
    }

    public function build_base_query($customHeaders = [], $merge = false)
    {
        $defaultHeaders = [
            'Authorization' => 'Bearer ' . $this->token->accessToken(),
            'Cache-Control' => 'no-cache',
            'Accept'        => 'application/vnd.deere.axiom.v3+json'
        ];

        $defaultHeaders = (!empty($customHeaders) && $merge == false) ? $customHeaders : array_merge($defaultHeaders, $customHeaders);

        return Http::withoutVerifying()
        ->retry(3, 750)
        ->withHeaders($defaultHeaders)
        ->baseUrl($this->base_url)
        ->withOptions(["verify" => false]);
    }

    // utility function to fetch any field by matching key-value pair
    public function get_field_by_kv($org_id, $field, $key, $value)
    {
        $data = $this->get_all($org_id);
        if($data){
            foreach($data as $row){
                if(!empty($row[$key]) && $row[$key] == $value){
                    return $row[$field];
                }
            }
        }
        return false;
    }
}