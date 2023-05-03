<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class OverrideConfig
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // MyJohnDeere Integration
        if ($request->routeIs('oauth2.*') && $request->integration) { 
            /* $request->integration is singular here (Just a slug, e.g: MyJohnDeere) */
            $config = Setting::get("{$request->integration}.oauth_conf");
            if($config){
                $config = json_decode($config, true);
                config([$request->integration => $config]);
            }
        }

        return $next($request);
    }
}