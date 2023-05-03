<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Connection;
use App\Utils;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process as Process;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ConnectionController extends Controller
{
    public function __construct()
    {
        $this->middleware('cors');
        $this->middleware(function ($request, $next) { $this->acc = Auth::user(); return $next($request); });
    }

    public function index(Request $request)
    {
        $request->validate([
            'cur_page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:5',
            'initial'  => 'required',
            'filter'   => 'nullable',
            'type'     => 'nullable',
            'sort_by'  => 'required',
            'sort_dir' => 'required'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            return response()->json(['message' => 'access_denied'], 403);
        }

        $limit   = $request->per_page;
        $offset  = ($request->cur_page-1) * $limit;

        $sortBy  = $request->sort_by;
        $sortDir = $request->sort_dir;

        $total   = 0;

        // optional filter param
        $filter = !empty($request->filter) ? $request->filter : '';
        // optional filter param
        $type = !empty($request->type) ? $request->type : '';

        $total = DB::table('connections')->count();
        $connections = DB::table('connections')
        ->select(['dataformats.name AS format_name','connections.*'])
        ->leftJoin('dataformats', 'dataformats.id', '=', 'connections.dataformat_id')
        ->when($type, function($query, $type)  {
            // filter by type (optional)
            $query->where('type', $type);
        })
        ->skip($offset)
        ->take($limit)
        ->get();

        if($connections->count()){
            foreach($connections as $conn){

                // check if process is running
                if($conn->pid && file_exists("/proc/{$conn->pid}")){
                    // check if it should still be running
                    if($conn->status == 'Connected'){
                        $duration = Utils::time_duration_display((new \DateTime($conn->started))->format('Y-m-d H:i:s'));
                        $conn->status = $conn->status . " ($duration)";
                    }
                } else {
                    // should have been running but is not
                    if($conn->status == 'Connected'){
                        DB::table('connections')->where('id', $conn->id)->update(['status' => 'Disconnected']);
                    }
                }
            }
        }

        return response()->json([
            'conn_data' => $connections,
            'total'     => $total
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'name' => 'required|max:128',
            'type' => 'required|max:64',
            'dataformat_id' => 'required|exists:dataformats,id',
            'config' => 'required'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            return response()->json(['message' => 'access_denied'], 403);
        }

        $resp = ['message' => 'fail'];

        $conn = new Connection();

        $conn->name = $request->name;
        $conn->type = $request->type;
        $conn->config = json_encode($request->config);
        $conn->dataformat_id = $request->dataformat_id;
        $conn->save();

        if($conn){ $resp = ['message' => 'conn_created']; }

        return response()->json($resp);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'name' => 'required|max:128',
            'type' => 'required',
            'dataformat_id' => 'required|exists:dataformats,id',
            'config' => 'required'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            return response()->json(['message' => 'access_denied'], 403);
        }

        $conn = Connection::where('id', $request->id)->first();
        if(!$conn){ return response()->json(['message' => 'nonexistent']); }

        // cannot update an active connection
        if($conn->status == 'Connected'){
            return response()->json(['message' => 'conn_active']);
        }

        $conn->name = $request->name;
        $conn->type = $request->type;
        $conn->dataformat_id = $request->dataformat_id;
        $conn->config = json_encode($request->config);
        $conn->save();

        return response()->json(['message' => 'conn_updated']);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        $conn = Connection::where('id', $request->id)->first();
        if(!$conn){ return response()->json(['message' => 'nonexistent']); }

        // cannot delete an active connection, stop it first
        if($conn->status == 'Connected'){
            return response()->json(['message' => 'conn_active']);
        }

        $conn->delete();

        return response()->json(['message' => 'conn_removed']);
    }

    public function connect(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            return response()->json(['message' => 'access_denied'], 403);
        }

        $conn = Connection::where('id', $request->id)->first();
        if(!$conn){ return response()->json(['message' => 'nonexistent']); }

        if($conn->status == 'Connected'){
            // already connected
            return response()->json(['message' => 'already']);
        }

        try {

            $process = new Process(["php", base_path('artisan'), "bgworker", "{$conn->id}"]);
            $process->setOptions(['create_new_console' => true]);
            $process->start();

            $pid = $process->getPid();
            $conn->pid = $pid; // Save Process ID
            $conn->save();

        } catch(\Exception $e){
            Log::debug('Error while attempt to start worker process');
            Log::debug($e->getMessage());
            return response()->json(['message' => 'failed']);
        }

        return response()->json(['message' => 'initiated']);
    }

    public function disconnect(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            return response()->json(['message' => 'access_denied'], 403);
        }

        $conn = Connection::where('id', $request->id)->first();
        if(!$conn){ return response()->json(['message' => 'nonexistent']); }

        // cannot disconnect an already disconnected connection
        if($conn->status == 'Disconnected'){
            return response()->json(['message' => 'already']);
        }

        // attempt disconnection
        $pid = $conn->pid;
         
        // check if process is still running, then attempt to kill it
        if($pid && file_exists("/proc/$pid")){

            posix_kill($pid, 2);

            $conn->status = 'Disconnected';
            $conn->pid = null;
            $conn->started = null;
            $conn->save();
        }

        return response()->json(['message' => 'initiated']);
    }
}
