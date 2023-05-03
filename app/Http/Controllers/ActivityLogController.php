<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Utils;
use DB;  

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('cors');
        $this->middleware(function ($request, $next) { $this->acc = Auth::user(); return $next($request); });
        //$this->timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
    }

    // get all (table)
    public function index(Request $request)
    {
        // $this->tz = $this->timezones[$this->acc->timezone];
        // if(!$this->tz){ $this->tz = 'UTC'; }
        // $tzObj = new \DateTimeZone($this->tz);

        $request->validate([
            'cur_page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:5',
            'filter'   => 'nullable',
            'entity'   => 'nullable',
            'sort_by'  => 'required',
            'sort_dir' => 'required'
        ]);
        
        $limit  = $request->per_page;
        $offset = ($request->cur_page-1) * $limit;

        $sortBy  = $request->sort_by;
        $sortDir = $request->sort_dir;
        
        // optional filter param
        $filter = !empty($request->filter) ? $request->filter : '';
        // optional filter param
        $entity = !empty($request->entity) ? $request->entity : '';

        $rows = [];
        
        $total = 0;

        $columns = [
            'activity_log.user_name AS user',
            'subsystems.subsystem_name AS subsystem',
            'permissions.permission_name AS operation',
            'activity_log.details',
            'activity_log.occurred',
            'activity_log.company_name AS company'
        ];

        $sortBy = Utils::findColumnAlias(Utils::findFromPartial($columns, $sortBy, 'activity_log.occurred'));

        if($this->acc->is_admin){

            $rows = DB::table('activity_log')->select($columns)
            ->join('subsystems', 'subsystems.id', '=', 'activity_log.subsystem_id')
            ->join('permissions', 'permissions.id', '=', 'activity_log.operation_id')
            ->when($entity, function($query, $entity)  {
                // filter by entity (optional)
                $query->where('activity_log.company_name', $entity); // company_name and not ID (Only one in the system)
            })
            ->when($filter, function($query, $filter){
                // filter by user name
                $query->where(function($query) use ($filter){
                    $query->where('activity_log.user_name', 'like', "%$filter%")
                    ->orWhere('subsystems.subsystem_name', 'like', "%$filter%")
                    ->orWhere('permissions.permission_name', 'like', "%$filter%")
                    ->orWhere('activity_log.details', 'like', "%$filter%");
                });
            });

            if($rows){
                $total = $rows->count();
                if($total){
                    $rows->orderBy($sortBy, $sortDir);
                    $rows = $rows->skip($offset)->take($limit)->get();
                }
            }

            $ccs = DB::table('companies')->select(['id', 'company_name'])->get()->toArray();

            // APPLY TIMEZONE
            // if($rows){
            //     foreach($rows as &$row){
            //         $dt = new \DateTime($row->occurred);
            //         $dt->setTimezone($tzObj);
            //         $row->occurred = $dt->format('Y-m-d H:i:s');
            //     }
            // }

        }

        return response()->json([
            'rows'  => $rows,
            'total' => $total,
            'entities' => $ccs
        ]);
    }
}