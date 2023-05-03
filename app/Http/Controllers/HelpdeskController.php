<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class HelpdeskController extends Controller {

    public function __construct()
    {
        $this->middleware('cors');
        $this->middleware(function ($request, $next) { $this->acc = Auth::user(); return $next($request); });
    }

    public function logTicket(Request $request)
    {
        $request->validate([
            'message'    => 'required',
            'email'      => 'required',
            'name'       => 'required',
            'screen'     => 'required',
            'attachment' => 'nullable'
        ]);

        $token = "1RAKHQW30E3LSN0KCNFBL6YSABG9NAVRVFCKHNSLD2OFID5RCZ3XD1IZFWWVPBIK";
        $url = 'https://helpdesk.liquidfibre.com/api/v1/ticket';


        $result = [ 'status' => 'OK' ];

        $postData = [
            'message' => $request->message,
            'actAsType' => 'customer',
            'name'  => $request->name,
            'subject' => "MyAgBuddy Support - {$request->email} - {$request->screen}",
            'from'  => $request->email,
            'type'  => 1, /* Open Ticket */
            'tags'  => 'MAB'
        ];

        if($request->hasFile('attachment')){
            $file = $request->file('attachment');
            // UPLOAD ERROR
            if(!$file->isValid()){
                $result['status'] = 'Fail';
                $result['message'] = 'Invalid file';
                return response()->json($result);
            }
            $fh = fopen($file->getRealPath(), 'r');
            // Send with Attachment
            $response = Http::withToken($token)->attach('attachments', $fh, $file->getClientOriginalName())->post($url, $postData);
        } else {
            // Send without Attachment
            $response = Http::withToken($token)->post($url, $postData);
        }

        // success
        $code = $response->status();
        if($code == 200){
            $result = [
                'status' => 'OK',
                'code' => $code,
                'ticketId' => $response['ticketId'],
                'message' => $response['message']
            ];
        } else {
            $result = [
                'status' => 'Fail',
                'code' => $code
            ];
        }

        return response()->json($result);
    }
}