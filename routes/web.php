<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers;

Route::get('/', function () {
    return view('front'); 
})->name('root');

Route::get('/reset-pass/{token}/{email}', function($token, $email){ return view('reset', ['token' => $token, 'email' => $email]); })->name('password.reset');

Route::get('/mabdevlog', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');

// Fetch a report file from a signed link
Route::get('/mabreports/{report}', function(Request $request){
    
    if (! $request->hasValidSignature()) {
        abort(401);
    }

    return response()->file(storage_path("reports/{$request->report}"))->deleteFileAfterSend(false);

})->name('mabreports')->middleware('signed');


// ALWAYS ENSURE THIS IS THE LAST ROUTE

Route::fallback(function (Request $request) {

    $params = [
        'redirect' => $request->path(),
        'context' => !empty($request->context) ? $request->context : ''
    ];

    return view('front', $params);
});

