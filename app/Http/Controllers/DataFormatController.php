<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DataFormat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\ParserGenerators\JSONParserGenerator;

class DataFormatController extends Controller
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
            'format'   => 'nullable',
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
        $format = !empty($request->format) ? $request->format : '';

        $total = DataFormat::count();
        $dataformats = DataFormat::when($format, function($query, $format) {
            // filter by type (optional)
            $query->where('format', $format);
        })
        ->skip($offset)
        ->take($limit)
        ->get();

        $targets = [
            "IMEI",
            "SM Reading",
            "Temp Reading",
            "SDI M Reading",
            "Reading Date",
            "Battery Voltage",
            "Battery Percentage",
            "Latitude",
            "Longitude",
            "Ambient Temp",
            "Logic Field"
        ];

        return response()->json([
            'formats' => $dataformats,
            'total'   => $total,
            'targets' => $targets
        ]);
    }

    public function list(Request $request)
    {
        $formats = [];
        // permission check
        if(!$this->acc->is_admin){
            return response()->json(['message' => 'access_denied'], 403);
        }
        $formats = DB::table('dataformats')->select(['id', 'name'])->get();
        return response()->json(['formats' => $formats]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'name'      => 'required|max:128',
            'format'    => 'required|max:64',
            'spec'      => 'required'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            return response()->json(['message' => 'access_denied'], 403);
        }

        $resp = ['message' => 'fail'];

        $df = new DataFormat();

        $df->name = $request->name;
        $df->format = $request->format;
        $df->spec = json_encode($request->spec);

        $gen = new JSONParserGenerator($df->spec);
        $code = $gen->generate();

        if(!empty($code)){
            Log::debug($code);
            //$df->parser = $code;
        }

        $df->save();

        if($df){ $resp = ['message' => 'df_created']; }

        return response()->json($resp);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id'        => 'required',
            'name'      => 'required|max:128',
            'format'    => 'required|max:64',
            'spec'      => 'required'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            return response()->json(['message' => 'access_denied'], 403);
        }

        $df = DataFormat::where('id', $request->id)->first();
        if(!$df){ return response()->json(['message' => 'nonexistent']); }

        $df->name   = $request->name;
        $df->format = $request->format;

        $prev_hash = hash('md5', $df->spec);
        $df->spec  = json_encode($request->spec);
        $curr_hash = hash('md5', $df->spec);

        if($prev_hash != $curr_hash){

            $gen = new JSONParserGenerator($df->spec);
            $code = $gen->generate();

            if(!empty($code)){
                Log::debug($code);
                //$df->parser = $code;
            }
        }

        $df->save();

        return response()->json(['message' => 'df_updated']);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        $df = DataFormat::where('id', $request->id)->first();
        if(!$df){ return response()->json(['message' => 'nonexistent']); }

        $df->delete();

        return response()->json(['message' => 'df_removed']);
    }
}
