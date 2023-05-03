<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

use App\Models\SecurityRule;
use App\Models\Company;
use App\User;
use App\Utils;

class ApiAuthController extends Controller
{
    public function login (Request $request) {

        $credentials = $request->validate([
            'email'      => 'required',
            'password'   => 'required',
            'context'    => 'nullable',
            'singlemode' => 'nullable'
        ]);

        // see beforehand if user exists
        $acc = User::where('email', $request->email)->first();
        if(!$acc){
            sleep(3);
            return response()->json([ "message" => "Access denied" ], 422);
        }

        // prevent login for all users of a company when is_locked is set
        $cc = Company::where('id', $acc->company_id)->first();
        if($cc->is_locked){
            sleep(3);
            return response()->json([ "message" => "Entity Locked" ], 422);
        }

        // prevent login for locked user account (when is_active == 0)
        if(!$acc->is_active){
            sleep(3);
            return response()->json([ "message" => "User Account Locked" ], 422);
        }

        // Permit only the following users on Dev
        if( strpos($_SERVER['HTTP_HOST'], 'dev.myagbuddy.com') !== false && !in_array($acc->email, [
            'dave@liquidfibre.com',
            'fritz@liquidfibre.com',
            'nandi@liquidfibre.com',
            'fritzbester@gmail.com',
            /*'brad@liquidfibre.com',*/ /* Bad boy, down boy */
            'lsarver@Tucor.com',
            'lsarver@tucor.com',
            'wmoik@tucor.com',
            'wmoik@Tucor.com'
        ])){
            sleep(3);
            return response()->json([ "message" => "User Unauthorized" ], 422);
        }

        // Critical
        if (!Hash::check($request->password, $acc->password)) {
            sleep(3);
            $acc->logActivity('Auth', 'Users', "Login Failed {$acc->email} ({$request->ip()}) (Access Denied)");
            return response()->json([ "message" => "Access denied" ], 422);
        }

        $token = $acc->createToken('Laravel Password Grant Client')->accessToken;
        unset($acc->password);

        $redirect = '';
        $singlemode = !empty($request->singlemode) ? $request->singlemode : false;

        if($acc->isDistributor()){
            // for sending to frontend
            $acc->setManagedCompanies();
        }

        if($acc->is_admin){
            $redirect = 'map';
        } else {

            $subsystems = $acc->subsystems();

            $restricted_subsystems = [
                'Cultivars',
                'Cultivar Stages',
                'Cultivar Templates',
                'Security Rules',
                'Security Templates',
                'Nutrient Templates'
            ];

            foreach($subsystems as $sname => $obj){
                
                if(in_array($sname, $restricted_subsystems)){ continue; }

                $grants = $acc->requestAccess([ $sname => ['p' => ['View'] ] ]);
                
                if(!empty($grants[$sname]['View']['C'])){
                    $redirect = $subsystems[$sname]['route'];
                    break;
                }
            }
        }

        if(empty($redirect)){
            $acc->logActivity('Auth', 'Users', "Login Failed {$acc->email} ({$request->ip()}) (Can't Redirect)");
            // can later maybe change logo to 'company' instead (minus some sensitive fields)
            return response()->json([ "message" => 'Insufficient Permissions'], 422);
        }

        $acc->logActivity('Auth', 'Users', "Login Success {$acc->email} ({$request->ip()})");

        $logo = Company::where('id', $acc->company_id)->pluck('company_logo')->first();
        $logo = $logo ? url($logo) : $logo;

        $response = [
            'message'    => "OK",
            'token'      => $token,
            'user'       => $acc,
            'logo'       => $logo,
            'redirect'   => $redirect,
            'singlemode' => $singlemode
        ];

        // If Single (Restricted Mode) was chosen
        if($singlemode){

            $companies = DB::table('companies')->select(['company_name AS label', 'id']);

            if(!$acc->is_admin){

                // If context was set (via JD and/or other integrations)
                if(!empty($request->context) && $request->context){

                    try {
                        $context = Utils::decryptDecode($request->context);
                        if(!$context || !is_array($context) || !array_key_exists('restricted_to', $context)){
                            throw new \Exception('Missing/Invalid context data');
                        }

                        $restricted_to = $context['restricted_to'];
                        $restricted_to = strpos(',', $restricted_to) !== false ? explode(',', $restricted_to) : [ (int) $restricted_to ];

                        // ensure user has access to context companies (restricted_to)
                        $grants = $acc->requestAccess(['Entities' => ['p' => ['All'] ] ]);
                        if(empty($grants['Entities']['View']['C'])){
                            return response()->json(['message' => 'Access Denied'], 422);
                        }
                        foreach($restricted_to as $cc_id){
                            if(!in_array($cc_id, $grants['Entities']['View']['C'])){
                                return response()->json(['message' => 'Access Denied'], 422);
                            }
                        }

                        $companies->whereIn('id', $restricted_to);

                    } catch (DecryptException $de){
                        // Blacklist user? Email management? Log for now..
                        Log::debug("Auth token compromised: User: {$acc->email}, IP: {$request->ip()}");
                        return response()->json([ "message" => "An error occured" ], 422);
                    } catch (\Exception $e){
                        Log::debug("Context Error: User: {$acc->email}, IP: {$request->ip()}, Error: {$e->getMessage()}");
                        return response()->json([ "message" => "An error occured" ], 422);
                    }

                } else {
                    $grants = $acc->requestAccess([ 'Entities' => ['p' => ['View'] ] ]);
                    if(!empty($grants['Entities']['View']['C'])){
                        $companies->whereIn('id', $grants['Entities']['View']['C']);
                    }
                }
            }

            $companies = $companies->get()->toArray();
            $response['companies'] = $companies;
        }

        return response()->json($response);

    }

    public function logout (Request $request) {
        $acc = Auth::user();
        $acc->logActivity('Auth', 'Users', "Logout {$acc->email} ({$request->ip()})");
        $acc->restricted_to = null;
        $acc->save();
        $acc->token()->revoke();
        $acc->token()->delete();
        Cache::forget(config('mab.instance')."_mab_dist_perms_{$acc->id}");
        return response(['status' => 'logged_out'], 200);
    }

    public function forgot(Request $request) {
        $input = $request->all();

        $message = '';
        $error = false;
        
        $validator = Validator::make($input, ['email' => "required|email", 'url' => "required"] );
        if ($validator->fails()) {
            $message = $validator->errors()->first();
            $error = true;
        } else {
            $resetUrl = $input['url'];
            $resetEmail = $input['email'];
            try {
                ResetPassword::createUrlUsing(function ($notifiable, $token) use ($resetUrl, $resetEmail) {
                    return $resetUrl . '?token=' . $token . '&email=' . $resetEmail;
                });

                $response = Password::sendResetLink($request->only('email'));

                switch ($response) {
                    case Password::RESET_LINK_SENT:
                        $message = trans($response);
                    break;
                    case Password::INVALID_USER:
                        $message = trans($response);
                        $error = true;
                    break;
                }
            } catch (\Swift_TransportException $ex) {
                $message = $ex->getMessage();
                $error = true;
            } catch (\Exception $ex) {
                $message = $ex->getMessage();
                $error = true;
            }
        }
        return response()->json(['message' => $message], $error ? 422 : 200);
    }

    public function reset(Request $request) {

        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();
        if(!$user){
            return response()->json([ "message" => "User doesn't exist" ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password){
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );
    
        if($status == Password::PASSWORD_RESET){
            if($user){ $user->logActivity('Auth', 'Users', "Password Reset Success {$request->email} ({$request->ip()})"); }
            return response()->json( [ "message" => 'Your password was reset! Please login' ], 200 );
        } else {
            if($user){ $user->logActivity('Auth', 'Users', "Password Reset Access Denied {$request->email} ({$request->ip()})"); }
            return response()->json( [ "message" => 'Password reset failed' ], 422 );
        }
    }

    public function restrict(Request $request) {

        $request->validate([
            'restrict_to' => 'required'
        ]);

        $acc = Auth::user();

        $restricted_to = $request->restrict_to;
        $restricted_to = strpos(',', $restricted_to) !== false ? explode(',', $restricted_to) : [ (int) $restricted_to ];

        // permission check
        if(!$acc->is_admin){
            $grants = $acc->requestAccess(['Entities' => ['p' => ['All'] ] ]);
            if(empty($grants['Entities']['View']['C'])){
                return response()->json(['message' => 'Access Denied'], 422);
            }
            foreach($restricted_to as $cc_id){
                if(!in_array($cc_id, $grants['Entities']['View']['C'])){
                    return response()->json(['message' => 'Access Denied'], 422);
                }
            }
            $restricted_to = implode(',', $restricted_to);
            $acc->update([ 'restricted_to' => $restricted_to ]);
        }

        return response()->json(['message' => 'success']);
    }

}
