<?php
    
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

use App\Models\Subsystem;
use App\Models\SecurityRule;
use App\Models\Role;
use App\Models\Company;
use App\Models\Group;
use App\Models\hardware_config;
use App\User;
use App\Utils;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('cors');
        $this->middleware(function ($request, $next) { $this->acc = Auth::user(); return $next($request); });
        $this->timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
    }

    // table
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

        $limit   = $request->per_page;
        $offset  = ($request->cur_page-1) * $limit;

        $sortBy  = $request->sort_by;
        $sortDir = $request->sort_dir;
        
        // optional filter param
        $filter = !empty($request->filter) ? $request->filter : '';
        // optional filter param
        $entity = !empty($request->entity) ? $request->entity : '';

        $users  = [];
        $grants = [];
        $ccs    = [];

        $columns = [
            'users.id',
            'companies.company_name',
            'users.name',
            'users.email',
            'roles.role_name',
        ];

        if($this->acc->is_admin){
            $columns[] = 'is_admin';
        }
        if($this->acc->is_admin || $this->acc->is_distributor){
            $columns[] = 'is_active';
            $columns[] = 'is_distributor';
        }

        $columns[] = 'unit_of_measure';
        $columns[] = 'timezone';

        $sortBy = Utils::findColumnAlias(Utils::findFromPartial($columns, $sortBy, 'users.name'));

        $users = User::select($columns)
        ->leftJoin('companies', 'users.company_id', '=', 'companies.id')
        ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
        ->when($entity, function($query, $entity)  {
            // filter by entity (optional)
            $query->where('companies.id', $entity);
        })
        ->where(function($query) use ($filter) {
            $query->when($filter, function($query, $filter){
                // filter by user name
                $query->where('role_name', 'like', "%$filter%")
                ->orWhere('companies.company_name', 'like', "%$filter%")
                ->orWhere('users.name', 'like', "%$filter%")
                ->orWhere('users.email', 'like', "%$filter%");
            });
        });

        if(!$this->acc->is_admin){
            // permission check
            $grants = $this->acc->requestAccess(['Users' => ['p' => ['All'] ] ]);
            if(!empty($grants['Users']['View']['O'])){
                $users->whereIn('users.id', $grants['Users']['View']['O']);
                $ccs = $grants['Users']['View']['C'];
                $ccs = Company::whereIn('id', $ccs)->get()->toArray();
            } else {
                $users = [];
            }
        } else {
            $ccs = Company::select(['id', 'company_name'])->orderBy('company_name')->get()->toArray();
        }

        if($users){
            $total = $users->count();
            if($total){
                if(!empty($sortBy) && !empty($sortDir)){
                    $users = $users->orderBy($sortBy, $sortDir);
                } else {
                    $users = $users->orderBy('created_at', 'desc');
                }
                $users = $users->skip($offset)->take($limit)->get();
            }
        }

        if($users){
            foreach($users as &$row){
                $row->timezone = $this->timezones[$row->timezone];
                $row->makeHidden('perms');
            }
        }

        if($request->initial){
            $details = !empty($grants['Users']['View']['C']) ?
                ('Company IDs: ' . implode(',', $grants['Users']['View']['C'])) :
                ($this->acc->is_admin ? 'All Objects' : 'Access Denied');
            $this->acc->logActivity('View', 'Users', $details);
        }

        return response()->json([
            'users'    => $users,
            'total'    => $total,
            'grants'   => $grants,
            'entities' => $ccs
        ]);
    }

    // ACTIVE - get users by role name
    public function getUsersByRole(Request $request)
    {
        $users = [];

        if(!empty($request->role)){
            // permission check
            $grants = $this->acc->requestAccess(['Users' => ['p' => ['View'] ] ]);
            if(!empty($grants['Users']['View']['O'])){
                $users = User::whereIn('role_id', function($q) use($request){
                    $q->select('id')->from('roles')->where('role_name', $request->role);
                })->whereIn('id', $grants['Users']['View']['O'])->orderBy('id', 'DESC')->get();

                foreach($users as &$row){
                    $row->timezone = $this->timezones[$row->timezone];
                    $row->company_name = $row->company->company_name;
                }
            }
        }

        return response()->json([
            'users' => $users
        ]);
    }

    // ACTIVE - get all users keyed by company id
    public function getUsersByCompanyId(Request $request)
    {
        $users = [];
        $users_by_cc = [];

        if($this->acc->is_admin){
            $users = User::all();
        } else {
            // permission check
            $grants = $this->acc->requestAccess(['Users' => ['p' => ['View'] ] ]);
            if(!empty($grants['Users']['View']['O'])){
                $users = User::whereIn('id', $grants['Users']['View']['O'])->get();
            }
        }

        if($users){
            foreach($users as $user){ $users_by_cc[$user->company_id][] = $user; }
        }

        return response()->json([
            'users' => $users_by_cc
        ]);
    }

    // ACTIVE - get user BY EMAIL
    public function getUser(Request $request)
    {
        if(empty($request->email)){ return response()->json(['message' => 'email_missing']); }

        $user = User::where('email', $request->email)->first();
        if(!$user){ return response()->json(['message' => 'nonexistent']); }

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Users' => ['p' => ['View'], 'o' => $user->id, 't' => 'O'] ]);
            if(empty($grants['Users']['View']['O'])){ return response()->json(['message' => 'access_denied'], 403); }
        }

        $user->role_name = $user->the_role->role_name;
        $user->company_name = $user->company->company_name;

        unset($user->password);

        $this->acc->logActivity('View', 'Users', "User: {$user->email} ({$user->id})");

        // get top level distributor company ids
        $user->managed_company_ids = DB::table('distributors_companies')->where('user_id', $user->id)->pluck('company_id')->toArray();

        return response()->json([
            'message' => 'user_loaded',
            'user' => $user
        ]);
    }

    // NOT USED - get user by id
    public function getUserById(Request $request)
    {
        $user = [];

        if(empty($request->id)){
            return response()->json(['message' => 'nonexistent']);
        }

        $user = User::where('id', $request->id)->first();

        // permission check
        if($this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Users' => ['p' => ['View'], 'o' => $user->id, 't' => 'O'] ]);
            if(empty($grants['Users']['View']['O'])){ return response()->json(['message' => 'access_denied'], 403); }
        }

        $user->role_name = $user->the_role->role_name;
        $user->company_name = $user->company->company_name;
        unset($user->password);

        $this->acc->logActivity('View', 'Users', "User: {$user->email} ({$user->id})");

        $cc = Company::where('id', $user->company_id)->first();

        return response()->json([
            'user' => $user
        ]);
    }

    // NOT USED - Get all users for dropdown (Wrongly labeled search)
    public function list(Request $request)
    {
        // list functions should always return something (even if it's an empty array)

        $users = [];

        if($this->acc->is_admin){
            $users = User::all();
        } else {
            // permission check
            $grants = $this->acc->requestAccess(['Users' => ['p' => ['View'] ] ]);
            if(!empty($grants['Users']['View']['O'])){
                $users = User::whereIn('id', $grants['Users']['View']['O'])->get()->toArray();
            }
        }

        return response()->json([
            'results' =>  $users
        ]);
    }

    // ACTIVE - New User Create (/usernew)
    public function new(Request $request)
    {
        $request->validate([
            'name'                  => 'required',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|same:password_confirmation',
            'password_confirmation' => 'required',
            'unit_of_measure'       => 'required',
            'timezone'              => 'required',
            'role_id'               => 'required|integer',
            'company_id'            => 'required|integer',
            'group_id'              => 'integer|nullable',
            'is_distributor'        => 'integer|nullable',
            'managed_company_ids'   => 'array|nullable'
        ]);

        $grants = [];

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Users' => ['p' => ['All'] ] ]);
            if(empty($grants['Users']['Add']['C']) || !in_array($request->company_id, $grants['Users']['Add']['C'])){
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        $input = $request->all();

        $input['password'] = Hash::make($input['password']);
        $input['role']     = Role::where('id', $request->role_id)->pluck('role_name')->first();
        $input['is_admin'] = 0; // users need to get "promoted to admin" explicitly (via Promote button on user's profile)
        $input['is_distributor'] = 0;

        // Assign distributorship
        if($this->acc->is_admin || $this->acc->is_distributor){
            $input['is_distributor'] = !empty($request->is_distributor) ? 1 : 0;
        }

        // Create the new User (Needed for next step (User ID))
        $user = User::create($input);

        // Save Distributor Company IDs (Only Admins+Distributors Allowed)
        if($user->is_distributor){
            // (Optional)
            if(!empty($request->managed_company_ids) && is_array($request->managed_company_ids)){
                foreach($request->managed_company_ids as $cc_id){
                    DB::table('distributors_companies')->insert([
                        'user_id'           => $user->id,
                        'parent_company_id' => $user->company_id,
                        'company_id'        => $cc_id
                    ]);
                }
                // Generate Distributor's Security Rules + Cache them
                $user->updateDistributorCache();
            }
        }

        // add user to group
        if(Group::where('id', $request->group_id)->exists()){
            DB::table('groups_users')->insert(['group_id' => $request->group_id, 'object_id' => $user->id]);
        }

        $this->acc->logActivity('Add', 'Users', "User: {$user->email} ({$user->id})");

        return response()->json([
            'message' => 'user_added',
            'grants'  => $grants
        ]);
    }

    // ACTIVE - Update user / Update Password
    public function updatePW(Request $request)
    {
        $input = $request->all();

        if(empty($request->id)){
            return response()->json(['message' => 'missing_id']);
        }

        // user password update
        if(!empty($input['password'])){

            $input = Arr::only($input, ['password']);

            $request->validate([
                'id'   => 'required|exists:users,id',
                'password' => 'required|string|same:password_confirmation',
                'password_confirmation' => 'required|string'
            ]);

            $input['password'] = Hash::make($input['password']);

            $message = 'pw_updated';

        // user fields update
        } else {

            $input = Arr::except($input, ['password']);

            $request->validate([
                'id'   => 'required|exists:users,id',
                'name' => 'required|string',
                'email' => 'required|email|unique:users,email,' . $request->id,
                'unit_of_measure' => 'required',
                'timezone' => 'required',
                'role_id' => 'required|exists:roles,id',
                'company_id' => 'required|exists:companies,id',
                'is_distributor' => 'integer|nullable',
                'managed_company_ids' => 'array|nullable'
            ]);

            $message = 'user_updated';
        }

        // ensure user still exists
        $user = User::find($request->id);
        if(!$user){ return response()->json(['message' => 'nonexistent']); }

        if($message == 'pw_updated'){
            // permission check
            if(!$this->acc->is_admin){
                $grants = $this->acc->requestAccess(['Users' => ['p' => ['Reset Password'], 'o' => $request->id, 't' => 'O'] ]);
                if(empty($grants['Users']['Reset Password']['O'])){
                    return response()->json(['message' => 'access_denied'], 403);
                }
            }
        }

        if($message == 'user_updated'){

            // permission check
            if(!$this->acc->is_admin){
                $grants = $this->acc->requestAccess(['Users' => ['p' => ['Edit'], 'o' => $request->id, 't' => 'O'] ]);
                if(empty($grants['Users']['Edit']['O'])){
                    return response()->json(['message' => 'access_denied'], 403);
                }
            }

            // USER ENTITY CHANGE
            if($user->company_id != $request->company_id){

                $subsystems = $this->acc->subsystems();
                $groups = Group::where('company_id', $user->company_id)->where('subsystem_id', $subsystems['Users']['id'])->get();

                // REMOVE USER FROM OLD ENTITIES' USER GROUPS
                if($groups){
                    foreach($groups as $g){
                        DB::table($subsystems['Users']['group_table'])->where('group_id', $g->id)->where('object_id', $request->id)->delete();
                    }
                }

                // REMOVE USER'S DISTRIBUTOR RIGHTS ON ENTITY CHANGE
                DB::table('distributors_companies')->where('user_id', $user->id)->delete();
                $user->is_distributor = 0;
                $user->updateDistributorCache();

                // LOG ACTIVITY
                $new_company = Company::where('id', $request->company_id)->pluck('company_name')->first();
                $old_company = Company::where('id', $user->company_id)->pluck('company_name')->first();
                $this->acc->logActivity('Edit', 'Users', "User: {$user->email} ({$user->id}) Changed Entity: $new_company");
                $this->acc->logActivity('Edit', 'Users', "User: {$user->email} ({$user->id}) Distributorship Removed for: $old_company");

            } else {

                if($this->acc->is_admin || $this->acc->is_distributor){

                    // UPDATE DISTRIBUTOR STATUS
                    $input['is_distributor'] = !empty($request->is_distributor) ? 1 : 0;

                    // UPDATE DISTRIBUTOR COMPANY IDS 
                    if($request->has('managed_company_ids')){

                        $existing_ccs = DB::table('distributors_companies')->where('user_id', $request->id)->pluck('company_id')->toArray();
                        $new_ccs = array_diff($request->managed_company_ids, $existing_ccs);
                        $del_ccs = array_diff($existing_ccs, $request->managed_company_ids);

                        // IF THERE WERE CHANGES
                        if($new_ccs || $del_ccs){

                            // DONT ALLOW A DISTRIBUTOR TO CHANGE IT'S OWN MANAGED COMPANIES
                            if(!$this->acc->is_admin && $this->acc->id == $request->id){
                                return response()->json(['message' => 'access_denied'], 403);
                            }

                            // ADD NEWLY ADDED COMPANIES (ADDED IN DROPDOWN)
                            foreach($new_ccs as $cc_id){
                                DB::table('distributors_companies')->insert([
                                    'user_id' => $user->id,
                                    'parent_company_id' => $user->company_id,
                                    'company_id' => $cc_id
                                ]);
                            }

                            // REMOVE REMOVED COMPANIES (REMOVED FROM DROPDOWN)
                            foreach($del_ccs as $cc_id){
                                DB::table('distributors_companies')->where('user_id', $request->id)->where('company_id', $cc_id)->delete();
                            }

                            $user->updateDistributorCache();

                        }
                    }
                }
            }
        }

        $user->update($input);

        $verb = $message == 'pw_updated' ? 'Reset Password' : 'Edit';
        $this->acc->logActivity($verb, 'Users', "User: {$user->email} ({$user->id})");

        $user->setHidden(['password']);

        return response()->json([
            'message' =>  $message,
            'user' => $this->acc->id == $user->id ? $user->toArray() : null
        ]);
    }

    public function exists(Request $request, $email)
    {
        return response()->json(['user_exists' => DB::table('users')->where('email', $email)->exists()]);
    }

    // ACTIVE - Delete a user
    public function destroy(Request $request)
    {
        // user to be deleted
        $user = User::where('id', $request->id)->first();

        // ensure user to delete still exists (sanity check)
        if(!$user){ return response()->json(['message' => 'nonexistent']); }

        // permission check
        $grants = $this->acc->requestAccess(['Users' => ['p' => ['All'], 'o' => $user->id, 't' => 'O' ] ]);
        if($this->acc->is_admin || !empty($grants['Users']['Delete']['O'])){

            // policy: last line of defence (nobody deletes the original accounts)
            if(in_array($user->email, ['dave@liquidfibre.com', 'fritz@liquidfibre.com', 'fritzbester@gmail.com', 'brad@liquidfibre.com'])){
                return response()->json(['message' => 'access_denied'], 403);
            }

            // Optionally remove distributor cache
            if($user->isDistributor()){
                $user->removeDistributorCache();
            }

            $user->delete();

            $this->acc->logActivity('Delete', 'Users', "{$user->email} ({$user->id})");

            return response()->json([
                'message' => 'user_removed'
            ]);
        }

        return response()->json(['message' => 'access_denied'], 403);
    }

    // ACTIVE - Promote a user to Admin (Admins only)
    public function promote(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id'
        ]);

        // policy: ensure only admins can promote users
        if(!$this->acc->is_admin){ return response()->json(['message' => 'access_denied'], 403); }

        $user = User::where('id', $request->id)->first();
        $user->is_admin = true;
        $user->save();

        $this->acc->logActivity('Edit', 'Users', "Promoted User: {$user->email} ({$user->id})");

        return response()->json(['message' => 'user_promoted']);
    }

    // Lock a User's Account (Prevent Login)
    public function lock(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Users' => ['p' => ['Lock'], 'o' => $request->id, 't' => 'O'] ]);
            if(empty($grants['Users']['Lock']['O'])){
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // PREVENT ADMINS FROM BEING LOCKED OUT
        $user = User::where('id', $request->id)->first();
        if($user->is_admin){
            return response()->json(['message' => 'access_denied'], 403);
        }

        $user->is_active = 0;
        $user->save();

        $this->acc->logActivity('Lock', 'Users', "Locked User: {$user->email} ({$user->id})");

        return response()->json(['message' => 'user_locked']);
    }

    // Unlock a User's Account (Allow Login)
    public function unlock(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Users' => ['p' => ['Lock'], 'o' => $request->id, 't' => 'O'] ]);
            if(empty($grants['Users']['Lock']['O'])){
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        $user = User::where('id', $request->id)->first();
        $user->is_active = 1;
        $user->save();

        $this->acc->logActivity('Lock', 'Users', "Unlocked User: {$user->email} ({$user->id})");

        return response()->json(['message' => 'user_unlocked']);
    }

    public function getTimezones()
    {
        return response()->json([
            'timezones' =>  $this->timezones,
            'offsets' => Utils::getTimeZoneOffsets($this->timezones)
        ]);
    }
}