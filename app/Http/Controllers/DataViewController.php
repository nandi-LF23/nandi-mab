<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\User;
use App\Models\node_data;
use App\Models\node_data_meter;
use App\Models\nutri_data;
use App\Models\hardware_config;
use App\Models\raw_data_dmt;
use App\Models\raw_data_catm;
use App\Models\raw_data_fieldwise;
use App\Models\raw_data_banner;
use App\Mail\Message;
use Mail;

class DataViewController extends Controller
{
    public function catmRecent(Request $request, $imei = '')
    {
        if(!empty($imei)){
            $data = raw_data_catm::orderBy('id', 'desc')
            ->where('device_id', 'like', '%'.$imei.'%')
            ->take(48)->get();
        } else {
            $data = raw_data_catm::orderBy('id', 'desc')
            ->take(48)->get();
        }
        $output = "<!DOCTYPE><html><body>";

        if($data){
            $output .= "<strong>Raw CatM Data (Most Recent 48 Rows)</strong><br><br><ul>";
            foreach($data as $row){
                $output .= "<li>id: " . $row->id . "</li>";
                $output .= "<li>device_id: " . $row->device_id . "</li>";
                $output .= "<li>created_at: " . $row->created_at . "</li>";
                $obj = json_decode($row->device_data);
                if($obj === NULL){
                    $output .= "<li>device_data: Invalid JSON</li>";
                    $output .= "<li>invalid_json: <pre>{$row->device_data}</pre></li>";
                } else {
                    $json = json_encode($obj, JSON_PRETTY_PRINT);
                    $output .= "<li>device_data: <pre>" . $json . "</pre></li>";
                }
                $output .= "</ul>";
            }
        } else { $output .= "<p>No data yet</p>"; };

        $output .= "</body></html>";

        return response($output, 200)->header('Content-Type', 'text/html');
    }

    public function dmtRecent(Request $request, $imei = '')
    {
        if(!empty($imei)){
            $data = raw_data_dmt::orderBy('id', 'desc')
            ->where('device_id', 'like', '%'.$imei.'%')
            ->take(100)->get();
        } else {
            $data = raw_data_dmt::orderBy('id', 'desc')
            ->take(100)->get();
        }
        $output = "<!DOCTYPE><html><body>";

        if($data){
            $output .= "<strong>Raw DMT Data (Most Recent 100 Rows)</strong><br><br><ul>";
            foreach($data as $row)
            {
                $output .= "<li>id: " . $row->id . "</li>";
                $output .= "<li>device_id: " . $row->device_id . "</li>";
                $output .= "<li>created_at: " . $row->created_at . "</li>";
                $obj = json_decode($row->device_data);
                if($obj === NULL){
                    $output .= "<li>device_data: Invalid JSON</li>";
                    $output .= "<li>invalid_json: <pre>{$row->device_data}</pre></li>";
                } else {
                    $json = json_encode($obj, JSON_PRETTY_PRINT);
                    $output .= "<li>device_data: <pre>" . $json . "</pre></li>";
                }
                $output .= "</ul>";
            }
        } else { $output .= "<p>No data yet</p>"; };

        $output .= "</body></html>";

        return response($output, 200)->header('Content-Type', 'text/html');
    }

    public function bannerRecent(Request $request)
    {
        if(empty($request->device_id)){
            $data = raw_data_banner::orderBy('id', 'desc')->take(100)->get();
        } else {
            $data = raw_data_banner::orderBy('id', 'desc')->where('device_id', $request->device_id)->take(100)->get();
        }

        $output = "<!DOCTYPE><html><body>";

        if($data){
            $output .= "<strong>Raw Banner Data (Most Recent 100 Rows)</strong><br><br><ul>";
            foreach($data as $row){
                $output .= "<li>id: " . $row->id . "</li>";
                $output .= "<li>device_id: " . $row->device_id . "</li>";
                $output .= "<li>created_at: " . $row->created_at . "</li>";
                $output .= "<li>device_data: <pre>{$row->device_data}</pre></li>";
            }
            $output .= "</ul>";
        } else { $output .= "<p>No data yet</p>"; };

        $output .= "</body></html>";

        return response($output, 200)->header('Content-Type', 'text/html');
    }

    public function fieldwiseRecent(Request $request)
    {
        if(empty($request->device_id)){
            $data = raw_data_fieldwise::orderBy('id', 'desc')->take(48)->get();
        } else {
            $data = raw_data_fieldwise::orderBy('id', 'desc')->where('device_id', $request->device_id)->take(48)->get();
        }

        $output = "<!DOCTYPE><html><body>";

        if($data){
            $output .= "<strong>Raw Fieldwise Data (Most Recent 48 Rows)</strong><br><br><ul>";
            foreach($data as $row){
                $output .= "<li>id: " . $row->id . "</li>";
                $output .= "<li>device_id: " . $row->device_id . "</li>";
                $output .= "<li>created_at: " . $row->created_at . "</li>";
                $obj = json_decode($row->device_data);
                if($obj === NULL){
                    $output .= "<li>device_data: Invalid JSON</li>";
                    $output .= "<li>invalid_json: <pre>{$row->device_data}</pre></li>";
                } else {
                    $json = json_encode($obj, JSON_PRETTY_PRINT);
                    $output .= "<li>device_data: <pre>" . $json . "</pre></li>";
                }
            }
            $output .= "</ul>";
        } else { $output .= "<p>No data yet</p>"; };

        $output .= "</body></html>";

        return response($output, 200)->header('Content-Type', 'text/html');
    }
}