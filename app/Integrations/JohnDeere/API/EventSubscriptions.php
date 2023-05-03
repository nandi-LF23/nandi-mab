<?php

namespace App\Integrations\JohnDeere\API;

use Illuminate\Support\Facades\Log;
use App\Integrations\JohnDeere\API\ApiBase;

class EventSubscriptions extends ApiBase {

    public function __construct($base_url, $integration, $debug_mode = false)
    {
        parent::__construct($base_url, $integration, $debug_mode);
    }

    // get all event subscriptions
    public function get_all($org_id)
    {
        $response = $this->request()->get("eventSubscriptions");

        if(!$response->ok()){
            if($this->debug_mode){
                Log::debug("Error: Event Subscriptions fetch failed"); 
                Log::debug($response);
            }
            return false;
        }
        $data = $response->json();
        return !empty($data['values']) ? $data['values'] : [];
    }

    public function create_event_subscription(
        $event_type_id,
        $filters,
        $route_name,
        $display_name
    ){
        $event_sub_id = null;

        $params = [
            "eventTypeId" => $event_type_id,
            "filters" => $filters,
            "targetEndpoint" => [ "targetType" => "https", "uri" => route($route_name) ],
            "status" => "Active",
            "displayName" => $display_name,
            "token" => "MyS00p3rS3cr3t2022!"
        ];

        try {
            $response = $this->request()
            ->withBody(
                json_encode($params, JSON_UNESCAPED_SLASHES),
                'application/vnd.deere.axiom.v3+json'
            )
            ->post("eventSubscriptions");

            if($response->status() == 201){
                $data = $response->json();
                $event_sub_id = $data['id'];
            } else {
                if($this->debug_mode){
                    Log::debug("Error: Event Subscriptions creation failed"); 
                    Log::debug($response);
                }
            }
        } catch (\Illuminate\Http\Client\RequestException $ex) {
            Log::debug("ERROR EXCEPTION");
            Log::debug($ex->response);
        }
        return $event_sub_id;
    }

    public function cancel_event_subscription($event_sub_id, $params){

        $response = $this->request([ 'Content-Type' => 'application/vnd.deere.axiom.v3+json' ], true)
        ->withBody(
            json_encode($params, JSON_UNESCAPED_SLASHES),
            'application/vnd.deere.axiom.v3+json'
        )
        ->put("eventSubscriptions/{$event_sub_id}");

        if($response->status() != 204){
            if($this->debug_mode){
                Log::debug("Error: Event Subscriptions cancellation failed"); 
                Log::debug($response);
            }
            return false;
        }
        return true;
    }

}
