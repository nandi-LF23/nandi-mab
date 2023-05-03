<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Company;
use App\Models\Role;
use App\Models\SecurityRule;
use App\User;
use App\Utils;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('cors');
        $this->middleware(function ($request, $next) { $this->acc = Auth::user(); return $next($request); });
    }

    // get all (table)
    public function index(Request $request)
    {
        $request->validate([
            'cur_page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:5',
            'initial'  => 'required',
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

        $roles  = [];
        $grants = [];

        $columns = [
            'roles.id AS id',
            'company_name',
            'company_id',
            'role_name'
        ];

        $sortBy = Utils::findColumnAlias(Utils::findFromPartial($columns, $sortBy, 'role_name'));

        $roles = Role::select($columns)
        ->join('companies', 'roles.company_id', '=', 'companies.id')
        ->when($entity, function($query, $entity)  {
            // filter by entity (optional)
            $query->where('companies.id', $entity);
        })
        ->when($filter, function($query, $filter){
            // filter by role name or company name
            $query->where('role_name', 'like', "%$filter%")
            ->orWhere('company_name', 'like', "%$filter%");
        });

        if(!$this->acc->is_admin){
            // permission check
            $grants = $this->acc->requestAccess(['Roles' => ['p' => ['All'] ] ]);
            if(!empty($grants['Roles']['View']['O'])){
                $roles->whereIn('roles.id', $grants['Roles']['View']['O']);
                $ccs = $grants['Roles']['View']['C'];
                $ccs = Company::whereIn('id', $ccs)->orderBy('company_name')->get()->toArray();
            } else {
                $roles = [];
            }
        } else {
            $ccs = Company::select(['id', 'company_name'])->orderBy('company_name')->get()->toArray();
        }

        if($roles){
            $total = $roles->count();
            if($roles){
                $roles->orderBy($sortBy, $sortDir);
                $roles = $roles->skip($offset)->take($limit)->get();
            }
        }

        if($roles){
            foreach($roles as &$role){
                $role['user_count'] = User::where('role_id', $role['id'])->count() ?: 0;
                $role['rule_count'] = SecurityRule::where('role_id', $role['id'])->count() ?: 0;
            }
        }

        if($request->initial){
            $details = !empty($grants['Roles']['View']['C']) ?
                ('Company IDs: ' . implode(',', $grants['Roles']['View']['C'])) :
                ($this->acc->is_admin ? 'All Objects' : 'Access Denied');
            $this->acc->logActivity('View', 'Roles', $details);
        }

        return response()->json([
            'rows'   => $roles,
            'total'  => $total,
            'grants' => $grants,
            'entities' => $ccs
        ]);
    }

    // get all roles keyed by company id
    public function getRolesByCompanyId(Request $request)
    {
        if(empty($request->company_id)){ return response()->json([ 'roles' => [] ]); }

        $roles = [];

        // admin gets all roles by company
        if($this->acc->is_admin){
            $roles = Role::where('company_id', $request->company_id)->get()->toArray();
        } else {
            $grants = $this->acc->requestAccess(['Roles' => ['p' => ['All'] ] ]);
            // non-admin gets filtered roles by company
            if(!empty($grants['Roles']['View']['O'])){
                $roles = Role::whereIn('id', $grants['Roles']['View']['O'])
                ->where('company_id', $request->company_id)
                ->get()->toArray();
            // user with no perms gets his own role by company
            } else {
                $roles = [ Role::where('id', $this->acc->role_id)->first()->toArray() ];
            }
        }
        
        return response()->json([
            'roles' => $roles
        ]);
    }

    // get single
    public function get(Request $request)
    {
        $role = [];

        if(!$request->id){
            return response()->json(['message' => 'missing_id']);
        }

        if(!$this->acc->is_admin){
            // permission check
            $grants = $this->acc->requestAccess([
                'Roles' => ['p' => ['All'], 'o' => $request->id, 't' => 'O']
            ]);
            if(empty($grants['Roles']['View']['O'])){ 
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        $role = Role::select('roles.id', 'role_name', 'company_id', 'company_name')
        ->join('companies', 'companies.id', '=', 'roles.company_id')
        ->where('roles.id', $request->id)
        ->first();

        $members = $role->users()->get(['email AS label', 'id']);

        $this->acc->logActivity('View', 'Roles', "{$role['role_name']} ({$role['id']})");

        return response()->json([
            'role' => $role->toArray(),
            'members' => $members->toArray()
        ]);
    }
    
    // add new role
    public function add(Request $request)
    {
        $grants = [];

        $request->validate([
            'role_name' => 'required|string',
            'company_id' => 'required|integer'
        ]);

        if(!$this->acc->is_admin){
            // permission check
            $grants = $this->acc->requestAccess(['Roles' => ['p' => ['All'] ] ]);
            if(empty($grants['Roles']['Add']['C'])){
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // check if company still exists
        $company = Company::where('id', $request->company_id)->first();
        if(!$company){
            // return error in format usable by Vee-Validate
            return response()->json([ 'errors' => [ 'company_id' => 'Invalid company' ] ]);
        }

        // unique check (role name must be unique to particular company)
        if(Role::where('role_name', $request->role_name)->where('company_id', $request->company_id)->exists()){
            return response()->json([ 'errors' => [ 'role_name' => 'Role already exists' ] ]);
        }

        $role = new Role();
        $role->company_id = $request->company_id;
        $role->role_name = $request->role_name;
        $role->save();

        $role->company_name = $company->company_name;
        $role->user_count = 0;
        $role->rule_count = 0;

        $this->acc->logActivity('Add', 'Roles', "{$role->role_name} ({$role->id})");

        return response()->json([
            'message' => 'role_added',
            'role' => $role,
            'grants' => $grants
        ]);
    }

    // update existing role
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'role_name' => 'required',
            'company_id' => 'required'
        ]);

        if(!$this->acc->is_admin){
            // permission check
            $grants = $this->acc->requestAccess(['Roles' => ['p' => ['Edit'], 'o' => $request->id, 't' => 'O'] ]);
            if(empty($grants['Roles']['Edit']['O'])){
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // UNIQUE CHECK: ensure unique roll names company-wide
        $role = Role::where('role_name', $request->role_name)
            ->where('company_id', $request->company_id)
            ->first();
            
        if($role && $role->id != $request->id){
            return response()->json([ 'errors' => [ 'role_name' => 'Role already exists' ] ]);
        }
        // UNIQUE CHECK

        if(!$role){
            $role = Role::where('id', $request->id)->first();
        }

        $role->update([
            'role_name' => $request->role_name
        ]);

        $this->acc->logActivity('Edit', 'Roles', "{$role->role_name} ({$role->id})");

        return response()->json([ 'message' => 'role_updated' ]);
    }

    // remove existing role (if not referenced)
    public function destroy(Request $request)
    {
        $request->validate([ 'id' => 'required' ]);

        $grants = [];

        if(!$this->acc->is_admin){
            // permission check
            $grants = $this->acc->requestAccess(['Roles' => ['p' => ['All'], 'o' => $request->id, 't' => 'O'] ]);
            if(empty($grants['Roles']['Delete']['O'])){
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        $role = Role::where('id', $request->id)->first();
        if(!$role){
            return response()->json(['message' => 'nonexistent']);
        }

        $user_count = User::where('role_id', $role->id)->count();
        if($user_count){
            return response()->json(['message' => 'role_in_use', 'object_type' => 'User(s)', 'object_count' => $user_count ]);
        }

        $rule_count = SecurityRule::where('role_id', $role->id)->count();
        if($rule_count){
            return response()->json(['message' => 'role_in_use', 'object_type' => 'Rule(s)', 'object_count' => $rule_count ]);
        }
        
        $this->acc->logActivity('Delete', 'Roles', "{$role->role_name} ({$role->id})");
        $role->delete();

        $result = [
            'message' => 'role_removed',
        ];

        if($grants){ $result['grants'] = $grants; }

        return response()->json($result);
    }
}