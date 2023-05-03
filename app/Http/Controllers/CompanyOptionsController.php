<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Company;

class CompanyOptionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('cors');
        $this->middleware(function ($request, $next) { $this->acc = Auth::user(); return $next($request); });
    }

    // Fetch all Per-Company Options
    public function get_company_opts(Request $request)
    {
        // Ensure Parameters are present
        if(empty($request->id)){
            return response()->json(['message' => 'missing_param']);
        }

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(
                ['Entities' => ['p' => ['All'], 'o' => $request->id, 't' => 'C'] ]
            );
            if(empty($grants['Entities']['View']['C'])){
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // Fetch options, group by group_name
        $options = DB::table('company_options')
        ->select([
            'options_groups.name AS group_name',
            'options_specs.label',
            'options_specs.desc',
            'company_options.slug',
            'options_specs.type',
            'company_options.value'
        ])
        ->join('options_specs', 'company_options.slug', '=', 'options_specs.slug')
        ->join('options_groups', 'options_specs.group_id', '=', 'options_groups.id')
        ->where('company_id', $request->id)
        ->get()
        ->mapToGroups(function ($item, $key) {
            return [ $item->group_name => $item ];
        });

        // In case options are empty, return defaults
        if($options->isEmpty()){
            $options = Company::get_default_options();
        }

        return response()->json([ 'options' => $options ]);
    }

    // Update all Per-Company Options (Bulk Update)
    public function update_company_opts(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'options'    => 'required|array'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Entities' => ['p' => ['All'], 'o' => $request->company_id, 't' => 'C'] ]);
            if(empty($grants['Entities']['Edit']['C'])){
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // update options
        foreach($request->options as $group => $options){
            foreach($options as $option){
                DB::table('company_options')->updateOrInsert(
                    [
                        'company_id' => $request->company_id,
                        'slug'       => $option['slug']
                    ],
                    [ 
                        'value'      => $option['value'] 
                    ]
                );
            }
        }

        return response()->json(['message' => 'options_updated']);
    }

}