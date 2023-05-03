<?php
    
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

use TorMorten\Eventy\Facades\Events as Eventy;

use App\Models\Company;
use App\Models\Setting;
use MacsiDigital\OAuth2\Integration;

class IntegrationController extends Controller
{
    public function __construct()
    {
        $this->middleware('cors');
    }

    // Setup Token Authorization
    public function setup_auth(Request $request, $integration, $company_id)
    {
        // store company_id in session
        session(['company_id' => $company_id]);

        $url = route('oauth2.authorise', [ 'integration' => $integration ]);

        return redirect()->away($url);
    }

    // Generic OAuth2 Token Success Code
    public function token_success(Request $request, $integration)
    {
        $company_id = session('company_id');
        $slug       = $integration;
        $int_name   = "{$integration}-{$company_id}";

        $int = Integration::where('name', $int_name)->first();

        Eventy::action("oauth2.token_success", $request, $int);
        Eventy::action("oauth2.{$slug}.token_success", $request, $int);

        // remove company_id from user's session
        $request->session()->forget('company_id');

        return view('oauth2/token_success');
    }

    // Generic OAuth2 Token Failure Code
    public function token_failure(Request $request)
    {
        // remove company_id from user's session
        $request->session()->forget('company_id');

        return view('oauth2/token_failure');
    }

    // Generic OAuth2 Token Revocation Code
    public function token_revoke(Request $request, $integration, $company_id)
    {
        $result = 'failure';

        $slug = $integration;
        $int_name = "{$slug}-{$company_id}";
        $conf_key = "{$slug}.oauth_conf";
        $meta_key = "{$slug}.oauth_meta";

        // GET AND STORE META INFORMATION VIA .WELL-KNOWN URL
        $meta = Cache::get(config('mab.instance')."_{$meta_key}", NULL);
        if(!$meta){
            $meta = Http::get('https://signin.johndeere.com/oauth2/aus78tnlaysMraFhC1t7/.well-known/oauth-authorization-server');
            if($meta->ok()){
                $meta = $meta->json();
                Cache::set(config('mab.instance')."_{$meta_key}", json_encode($meta), 3600 * 24); /* cache for 24 hours */
            }
        } else {
            $meta = json_decode($meta, true);
        }

        $conf = json_decode(Setting::get($conf_key), true);
        $int = Integration::where('name', $int_name)->first();
        
        $clientId = !empty($conf['oauth2']['clientId']) ? $conf['oauth2']['clientId'] : null;
        $clientSecret = !empty($conf['oauth2']['clientSecret']) ? $conf['oauth2']['clientSecret'] : null;

        $accessToken = $int ? $int->accessToken : '';
        $refreshToken = $int ? $int->refreshToken : '';

        if($meta && $clientId && $clientSecret){

            $revoked = false;

            Eventy::action("oauth2.token_before_revoke", $request, $int);
            Eventy::action("oauth2.{$slug}.token_before_revoke", $request, $int);

            // Revoke Access Token
            if($accessToken){
                $resp = Http::withBasicAuth($clientId, $clientSecret)->asForm()->post($meta['revocation_endpoint'], [
                    'token' => $accessToken,
                    'token_type_hint' => 'access_token'
                ]);
                if(!$resp->ok()){
                    Log::debug($resp);
                }
                $revoked = true;
            }

            // Revoke Refresh Token
            if($refreshToken){
                $resp = Http::withBasicAuth($clientId, $clientSecret)->asForm()->post($meta['revocation_endpoint'], [
                    'token' => $refreshToken,
                    'token_type_hint' => 'refresh_token'
                ]);
                if($resp->ok()){
                    Integration::where('name', $int_name)->delete();
                    $result = 'token_revoked';
                    $revoked = true;
                } else {
                    Log::debug($resp);
                }
            }

            if($revoked){
                Eventy::action("oauth2.token_revoked", $request, $int);
                Eventy::action("oauth2.{$slug}.token_revoked", $request, $int);
            }
        }

        // remove company_id from user's session
        $request->session()->forget('company_id');

        return response()->json(['result' => $result]);
    }
}