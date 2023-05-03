<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

use App\Models\Role;
use App\Models\Group;
use App\Models\Company;
use App\Models\SecurityRule;
use App\Models\nutrient_templates;
use App\Models\hardware_config;
use App\Models\Setting;

use TorMorten\Eventy\Facades\Events as Eventy;
use Barryvdh\DomPDF\Facade\Pdf;

use MacsiDigital\OAuth2\Integration;
use App\Integrations\IntegrationManager;
use App\Utils;
use App\User;
use App\Reporting\DevicePlacementReport;

class CompanyController extends Controller
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
            'sort_by'  => 'required',
            'sort_dir' => 'required'
        ]);

        $limit  = $request->per_page;
        $offset = ($request->cur_page-1) * $limit;

        $sortBy  = $request->sort_by;
        $sortDir = $request->sort_dir;
        
        // optional filter param
        $filter = !empty($request->filter) ? $request->filter : '';
        
        $companies = [];
        $grants = [];

        // currently, for entities, only used for sorting
        $columns = [
            'company_name',
            'parent_entity'
        ];

        $companies = DB::table('companies as cc')
        ->select([DB::raw('distinct cc.company_name'), 'pc.company_name AS parent_entity', 'cc.id'])
        ->leftJoin('distributors_companies as dc', 'cc.id', '=', 'dc.company_id')
        ->leftJoin('companies as pc', 'pc.id', '=', 'dc.parent_company_id')
        ->when($filter, function($query, $filter){
            $query->where('cc.company_name', 'like', "%$filter%");
        });

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Entities' => ['p' => ['All'] ] ]);
            if(!empty($grants['Entities']['View']['C'])){
                $companies->whereIn('cc.id', $grants['Entities']['View']['C']);
            } else {
                $companies = [];
            }
        }

        $totals = [
            'user_count'   => 0,
            'role_count'   => 0,
            'node_count'   => 0,
            'group_count'  => 0
        ];

        if($companies){
            $total = $companies->count(DB::raw('distinct cc.company_name'));
            if($total){
                $sortBy = Utils::findColumnAlias(Utils::findFromPartial($columns, $sortBy, 'company_name'));
                $companies = $companies->orderBy($sortBy, $sortDir)->skip($offset)->take($limit)->get();
            }
            if($this->acc->is_admin){
                $company_ids = DB::table('companies')->pluck('id');
            } else {
                $company_ids = $grants['Entities']['View']['C'];
            }
            // Column Totals
            $totals['user_count']   = DB::table('users')->select(DB::raw('COUNT(*) as uc'))->whereIn('company_id', $company_ids)->value('uc');
            $totals['role_count']   = DB::table('roles')->select(DB::raw('COUNT(*) as rc'))->whereIn('company_id', $company_ids)->value('rc');
            $totals['node_count']   = DB::table('hardware_config')->select(DB::raw('COUNT(*) as nc'))->whereIn('company_id', $company_ids)->value('nc');
            $totals['group_count']  = DB::table('groups')->select(DB::raw('COUNT(*) as gc'))->whereIn('company_id', $company_ids)->value('gc');
        }

        if($companies->count()){
            foreach($companies as &$company){

                $company->user_count   = DB::table('users')->select(DB::raw('COUNT(*) as uc'))->where('company_id', $company->id)->value('uc');
                $company->role_count   = DB::table('roles')->select(DB::raw('COUNT(*) as rc'))->where('company_id', $company->id)->value('rc');
                $company->node_count   = DB::table('hardware_config')->select(DB::raw('COUNT(*) as nc'))->where('company_id', $company->id)->value('nc');
                $company->group_count  = DB::table('groups')->select(DB::raw('COUNT(*) as gc'))->where('company_id', $company->id)->value('gc');

                if($this->acc->is_admin){
                    $company->is_distributor = DB::table('users')->where('company_id', $company->id)->where('is_distributor', '1')->exists() ? 1 : 0;
                }
            }
        }

        if($request->initial){
            $details = implode(',', 
                !empty($grants['Entities']['View']['C']) ? $grants['Entities']['View']['C'] : 
                ($this->acc->is_admin ? ['All Objects'] : ['Access Denied']));
            $this->acc->logActivity('View', 'Entities', $details);
        }

        return response()->json([
            'rows'   => $companies,
            'total'  => $total,
            'totals' => $totals,
            'grants' => $grants
        ]);
    }

    // /companies_list
    public function list(Request $request)
    {
        $request->validate([
            'context' => 'required|array',
        ]);

        $ccs_by_id = [];
        $companies = Company::select('company_name', 'id');

        if(!$this->acc->is_admin){
            foreach($request->context as $rule){

                if(empty($rule['module']) || empty($rule['verb'])){ continue; }

                // Security Context
                $verb    = $rule['verb'];
                $module  = $rule['module'];

                // permission check
                $grants = $this->acc->requestAccess([ $module => ['p' => [ $verb ] ] ]);
                if(!empty($grants[ $module ][ $verb ][ 'C' ])){
                    $companies->whereIn('id', $grants[ $module ][ $verb ][ 'C' ]);
                } else {
                    // no explicit edit company perms, so just load the user's own company
                    $companies->where('id', $this->acc->company_id);
                }
            }
        }

        $companies = $companies->orderBy('company_name')->get()->toArray();

        if($companies){
            // >> key by id + merge <<
            // NOTE: Do NOT remove the quotes from the below keying expression
            foreach($companies as $k => $cc){
                $ccs_by_id["\"{$cc['id']}\""] = $cc;
            }
        }

        return response()->json([
            'companies' => $ccs_by_id
        ]);
    }

    // List of manageable companies
    // 1. When 'Add New User' Dialog is opened
    // 2. When 'Add New User' Company selection changes

    // Admins: Be able to assign any Entities they wish to Distributor User
    // Distributors: Can only assign what they own.

    public function dist_list(Request $request)
    {
        $request->validate([
            /* Company ID of new or existing user */
            'company_id' => 'required|exists:companies,id'
        ]);

        // Ensure that only Admins and Distributors can see the 'Managed Companies' dropdown values.
        if(!$this->acc->is_admin && !$this->acc->is_distributor){
            return response()->json([ 'companies' => [] ]);
        }

        // ADMIN: All company access
        if($this->acc->is_admin){
            $companies = Company::select('company_name', 'id')
            ->where('id', '!=', $this->acc->company_id)
            ->where('id', '!=', $request->company_id)
            ->orderBy('company_name')
            ->get()
            ->toArray();
        // DISTRIBUTOR: Get all direct subsidiaries of the current company
        } else {
            $companies = Company::select('company_name', 'id')
            ->whereIn('id', Company::get_subsidiary_ids($request->company_id))
            ->orderBy('company_name')
            ->get()
            ->toArray();
        }

        $ccs_by_id = [];

        if($companies){
            foreach($companies as $k => $cc){
                $ccs_by_id['"'.$cc['id'].'"'] = $cc;
            }
        }

        return response()->json([
            'companies' => $ccs_by_id
        ]);
    }

    // get company objects
    public function objects(Request $request)
    {
        $objects = [];
        $grants = [];

        if(empty($request->id)){
            return response()->json(['message' => 'invalid_company']);
        }

        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess([
                'Entities'     => ['p' => ['All'], 'o' => $request->id, 't' => 'C'],
                'Users'        => ['p' => ['All'] ],
                'Roles'        => ['p' => ['All'] ],
                'Node Config'  => ['p' => ['All'] ],
                'Groups'       => ['p' => ['All'] ],
                'Sensor Types' => ['p' => ['All'] ]
            ]);
            if(empty($grants['Entities']['View']['C'])){
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        $cc = Company::where('id', $request->id)->first();
        if($cc){
            $objects['users']   = $cc->users()->get(['email AS label', 'id'])->toArray();
            $objects['roles']   = $cc->roles()->get(['role_name AS label', 'id'])->toArray();
            $objects['nodes']   = $cc->nodes()->get(['node_address AS label', 'node_type AS type', 'id'])->toArray();
            $objects['groups']  = $cc->groups()->get(['group_name AS label', 'id'])->toArray();
            $objects['sensors'] = $cc->sensors()->get(['device_make AS label', 'id'])->toArray();

            if($objects['nodes']){
                foreach($objects['nodes'] as &$node){
                    $translate_links = [
                        'Soil Moisture' => '/soil_moisture/edit',
                        'Nutrients' => '/nutrients/edit',
                        'Wells' => '/well_controls/edit',
                        'Water Meter' => '/meters/edit'
                    ];

                    $translate_subsystems = [
                        'Soil Moisture' => 'Soil Moisture',
                        'Nutrients' => 'Nutrients',
                        'Wells' => 'Well Controls',
                        'Water Meter' => 'Meters',
                    ];

                    $url = $translate_links[$node['type']];
                    $id  = $node['label'];

                    $node['link'] = "{$url}/{$id}"; // label is node_address
                    $node['subsystem'] = $translate_subsystems[$node['type']];
                }
            }
        }

        $return = [ 'objects' => $objects ];
        if($grants){
            $return['grants'] = $grants;
        }

        return response()->json($return);
    }

    // Device Placement Report
    public function report(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'company_ids' => 'nullable|array', /* optional list of selected distributor(s) / array with one subsidiary */
            'year' => 'nullable',
            'detailed' => 'required|boolean',
            'mode' => 'required|in:dist,sub',
            'initial' => 'required|boolean'
        ]);

        $da_report = new DevicePlacementReport();

        // no selectable companies by default (non-admins/non-distributor users)
        $cc_ids = [];
        $companies = [];
        // The user's chosen companies
        $company_ids = !empty($request->company_ids) ? $request->company_ids : [];

        if($request->mode == 'dist'){

            if($this->acc->is_admin){
                // admins gets all companies
                $cc_ids = DB::table('companies')->pluck('id')->toArray();
            
            } else if($this->acc->is_distributor){
                // distributors get a list of subsidiary companies they can manage
                $cc_ids = Company::get_subsidiary_ids($this->acc->company_id, true);
                // EXPERIMENTAL: Include parent company in list
                array_unshift($cc_ids, $this->acc->company_id);

            } else {
                return response()->json([ 'message' => 'access_denied' ], 403);
            }

        } else if($request->mode == 'sub'){

            // exactly one subsidiary company id required
            if(empty($company_ids) || !is_array($company_ids) || count($company_ids) !== 1){
                return response()->json([ 'message' => 'missing_params' ]);
            }

            // permission check (for one element array (subsidiary))
            if(!$this->acc->is_admin && !$this->acc->isDistributorOf($company_ids[0])){
                $grants = $this->acc->requestAccess(['Entities' => ['p' => ['Report'], 'o' => $company_ids[0], 't' => 'C']]);
                if(empty($grants['Entities']['Report']['C'])){
                    return response()->json([ 'message' => 'access_denied' ], 403);
                }
            }
        }

        if($cc_ids){
            // a list of distributor companies (admins) / subsidiary companies (distributors) to choose from
            $companies = DB::table('companies')
            ->select(['id', 'company_name'])
            ->whereIn('id', $cc_ids)
            ->orderBy('company_name')
            ->get()
            ->toArray();

            // assign full list on initial call
            if($request->initial){
                $company_ids = $cc_ids;
            } else {
                // respect user's choices but detect illegal company id injections
                $illegals = array_diff($company_ids, $cc_ids);
                if($illegals){
                    return response()->json([ 'message' => 'access_denied' ], 403);
                }
            }
        }

        switch($request->type){
            case 'json':

                $summary_data = $da_report->summary(
                    $this->acc,
                    $request->year,
                    $company_ids, // once again, the user's filtered company choices (made on front-end via multi-select)(or all allowed on initial call)
                    $request->mode
                );

                if(!empty($summary_data['message']) && $summary_data['message'] == 'access_denied'){
                    return response()->json($summary_data, 403);
                }

                //Log::debug($summary_data);

                $detail_data = [];

                if($request->detailed){
                    $detail_data = $da_report->details(
                        $this->acc,
                        $request->year,
                        $company_ids,
                        $request->mode
                    );

                    if(!empty($detail_data['message']) && $detail_data['message'] == 'access_denied'){
                        return response()->json($detail_data, 403);
                    }

                }

                // Limit by year
                $years = DB::table('hardware_config')
                ->select(DB::raw('YEAR(commissioning_date) AS year'))
                ->groupBy('year')
                ->orderBy('year', 'desc')
                ->pluck('year')
                ->toArray();

                array_unshift($years, 'Total');

                return response()->json([
                    'summary'   => $summary_data['data'],
                    'totals'    => $summary_data['totals'],
                    'details'   => $detail_data,
                    'years'     => $years,        // (List of years for user to choose from on frontend) 
                    'companies' => $companies,    // the user's company choices (made on front-end via multi-select)
                    'cc_ids'    => $cc_ids
                ]);

            break;
            case 'pdf':

                $html = $da_report->html(
                    $this->acc,
                    $request->year,
                    $company_ids,
                    $request->mode,
                    $request->detailed,
                );

                $filename = 'placement_report_' . uniqid() . '.pdf';
                
                $pdf = Pdf::loadHTML($html)
                ->setPaper('A4', 'landscape')
                ->save(
                    storage_path("reports/{$filename}")
                );

                $url = URL::signedRoute('mabreports', [ 'report' => $filename ]);

                return response()->json(['url' => $url]);

            break;
        }

        return response()->json(['message' => 'invalid_type']);
    }

    // get single
    public function get(Request $request)
    {
        $cc = [];
        $company = [];
        $grants = [];
        $table = [];

        if(empty($request->id)){ return response()->json(['message' => 'missing id parameter']); }

        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Entities' => ['p' => ['All'], 'o' => $request->id, 't' => 'C'] ]);
            if(empty($grants['Entities']['View']['C'])){
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        $cc = Company::where('id', $request->id)->first();
        if(!$cc){ return response()->json(['message' => 'company missing']); }

        $company = [
            'id' => $cc->id,
            'company_name' => $cc->company_name,
            'company_logo' => $cc->company_logo ? url($cc->company_logo) : '',

            'contact_name'  => $cc->contact_name,
            'contact_email' => $cc->contact_email,
            'contact_phone' => $cc->contact_phone,

            'physical_address' => [
                'line_1'     => $cc->address_physical_line_1,
                'line_2'     => $cc->address_physical_line_2,
                'city'       => $cc->address_physical_city,
                'postalcode' => $cc->address_physical_postalcode,
                'country'    => $cc->address_physical_country,
            ],
            'billing_address' => [
                'line_1'     => $cc->address_billing_line_1,
                'line_2'     => $cc->address_billing_line_2,
                'city'       => $cc->address_billing_city,
                'postalcode' => $cc->address_billing_postalcode,
                'country'    => $cc->address_billing_country,
            ],
            'integrations' => [],
            'is_locked' => $cc->is_locked,

            'is_distributor' => DB::table('users')->where('company_id', $cc->id)->where('is_distributor', '1')->exists() ? 1 : 0,

            'created_at' => $cc->created_at,
            'updated_at' => $cc->updated_at
        ];

        // Load integrations for this Company (includes possible default options for entities_manage)
        $integrations = IntegrationManager::setup($cc->id);

        
        // integration options  (TODO: Filter by permissions)
        $defaults = IntegrationManager::options($cc->id, 'entities_manage');

        // Ensure Defaults only returned for Active Integrations 
        if(!empty($defaults)){
            $saved = json_decode($cc->integrations, true);
            $company['integrations'] = !empty($saved) ? Utils::array_merge_rec($defaults, $saved) : $defaults;
        }

        $details = !empty($company['company_name']) ? $company['company_name'] : 'Access Denied';
        $this->acc->logActivity('View', 'Entities', $details);

        return response()->json([
            'company' => $company,
            'integrations' => $integrations
        ]);
    }

    // add new company
    public function add(Request $request)
    {
        $request->validate([

            'company_name'                => 'string|required',
            'company_logo'                => 'string|nullable',

            'contact_name'                => 'string|required',
            'contact_email'               => 'string|nullable',
            'contact_phone'               => 'string|nullable',

            /* Physical Address used as Billing Address (Not actual DB Field) */
            'p_as_b'                      => 'string|required',

            'physical_address'            => 'array',
            'physical_address.line_1'     => 'string|nullable',
            'physical_address.line_2'     => 'string|nullable',
            'physical_address.city'       => 'string|nullable',
            'physical_address.postalcode' => 'string|nullable',
            'physical_address.country'    => 'string|nullable',

            'billing_address'             => 'array',
            'billing_address.line_1'      => 'string|nullable',
            'billing_address.line_2'      => 'string|nullable',
            'billing_address.city'        => 'string|nullable',
            'billing_address.postalcode'  => 'string|nullable',
            'billing_address.country'     => 'string|nullable',

            'add_roles'                   => 'array',
            'add_groups'                  => 'array',

            'modules'                     => 'array|required'

        ]);

        $grants = [];

        // permission check
        if(!$this->acc->is_admin && !$this->acc->is_distributor){
            $grants = $this->acc->requestAccess(['Entities' => ['p' => ['All'] ] ]);
            if(empty($grants['Entities']['Add']['C'])){
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // company name unique check
        if(Company::where('company_name', $request->company_name)->exists()){
            return response()->json(['message' => 'entity_exists']);
        }

        // Create new Company Record
        $cc = new Company();

        $cc->company_name = $request->company_name;
        $cc->company_logo = '';
        $logo_filename    = Utils::sanitize_filename($request->company_name);

        // Company Logo Upload
        if($request->company_logo){
            $url = Utils::upload_base64_file($logo_filename, $request->company_logo);
            if($url){
                $cc->company_logo = $url;
            }
        }

        // Contact Details
        $cc->contact_name  = $request->contact_name;
        $cc->contact_email = $request->contact_email;
        $cc->contact_phone = $request->contact_phone;

        // Company Address fields
        $cc->address_physical_line_1     = $request->physical_address['line_1'];
        $cc->address_physical_line_2     = $request->physical_address['line_2'];
        $cc->address_physical_city       = $request->physical_address['city'];
        $cc->address_physical_postalcode = $request->physical_address['postalcode'];
        $cc->address_physical_country    = $request->physical_address['country'];

        if($request->p_as_b == 'yes'){
            $cc->address_billing_line_1      = $cc->address_physical_line_1;
            $cc->address_billing_line_2      = $cc->address_physical_line_2;
            $cc->address_billing_city        = $cc->address_physical_city;
            $cc->address_billing_postalcode  = $cc->address_physical_postalcode;
            $cc->address_billing_country     = $cc->address_physical_country;
        } else {
            $cc->address_billing_line_1      = $request->billing_address['line_1'];
            $cc->address_billing_line_2      = $request->billing_address['line_2'];
            $cc->address_billing_city        = $request->billing_address['city'];
            $cc->address_billing_postalcode  = $request->billing_address['postalcode'];
            $cc->address_billing_country     = $request->billing_address['country'];
        }

        // Save new Company to DB
        $cc->save();

        // Generate '<Company Name> Users' Role
        $users_role_name = "{$cc->company_name} Users";
        $users_role_id = DB::table('roles')->insertGetId([
            'role_name'  => $users_role_name,
            'company_id' => $cc->id
        ]);

        // Generate '<Company Name> Managers' Role
        $managers_role_name = "{$cc->company_name} Managers";
        $managers_role_id = DB::table('roles')->insertGetId([
            'role_name'  => $managers_role_name,
            'company_id' => $cc->id
        ]);

        // Generate Security Rules from Selected Subsystems
        SecurityRule::generateFromSelectedSubsystems($request->modules, $cc->id, $users_role_id, 'Users');
        SecurityRule::generateFromSelectedSubsystems($request->modules, $cc->id, $managers_role_id, 'Managers');

        // Company optional roles
        if(!empty($request->add_roles))
        {
            $grants = $this->acc->requestAccess(['Roles' => ['p' => ['All'] ] ]);
            if(!empty($grants['Roles']['Add']['C'])){
                foreach($request->add_roles as $role_name)
                {
                    if(!in_array($role_name, [$users_role_name, $managers_role_name])){
                        $role = new Role();
                        $role->role_name = $role_name;
                        $role->company_id = $cc->id;
                        $role->save();
                    }
                }
            }
        }

        // Company optional groups
        if(!empty($request->add_groups))
        {
            $grants = $this->acc->requestAccess(['Groups' => ['p' => ['All'] ] ]);
            if(!empty($grants['Groups']['Add']['C'])){
                foreach($request->add_groups as $g)
                {
                    $group = new Group();
                    $group->group_name = $g['group_name'];
                    $group->subsystem_id = $g['subsystem_id'];
                    $group->company_id = $cc->id;
                    $group->save();
                }
            }
        }

        // Add Default Options for Company
        Company::set_default_options($cc->id);

        // Add Default Nutrient Template for Company
        $saved = nutrient_templates::create([
            'name'      => 'Default Template',
            'template'  => json_encode([
                'poly1' => 1,
                'poly2' => 1,
                'lower_limit' => -50,
                'upper_limit' => 50,
                'metric' => 'ppm',
                'soil_type' => 1, // Sand
                'crop_name' => 'Crop Name'
            ]),
            'company_id' => $cc->id,
            'user_id'    => (int) $this->acc->id
        ]);

        // If a distributor added a new entity, add in the distributor link for the new entity
        // (When a Distributor adds a new Entity, it becomes a Child Entity of the Distributor User's Entity)
        // (When an Admin adds an Entity, it would be added as a top-level entity)
        // (The admin can then go assign it to some distributor)

        //if($this->acc->is_distributor){
            DB::table('distributors_companies')->insert([
                'user_id'           => $this->acc->id,         /* The Distributor User */
                'parent_company_id' => $this->acc->company_id, /* The Distributor User's Entity */
                'company_id'        => $cc->id                 /* The Newly created Entity becomes a Sub-Entity */
            ]);

            // Forces a cache bust on all distributors (Inefficient, will optimize later)
            User::updateAllDistributorCaches();
        //}

        $this->acc->logActivity('Add', 'Entities', "{$cc->company_name}");

        Eventy::action('company.new', $cc);

        return response()->json([
            'message' => 'company_added',
            'grants'  => $grants
        ]);
    }

    // update existing company
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required',

            'company_name'                => 'required|string',
            'company_logo'                => 'string|nullable',

            'contact_name'                => 'string|required',
            'contact_email'               => 'string|nullable',
            'contact_phone'               => 'string|nullable',

            'physical_address'            => 'array',
            'physical_address.line_1'     => 'string|nullable',
            'physical_address.line_2'     => 'string|nullable',
            'physical_address.city'       => 'string|nullable',
            'physical_address.postalcode' => 'string|nullable',
            'physical_address.country'    => 'string|nullable',

            'billing_address'             => 'array',
            'billing_address.line_1'      => 'string|nullable',
            'billing_address.line_2'      => 'string|nullable',
            'billing_address.city'        => 'string|nullable',
            'billing_address.postalcode'  => 'string|nullable',
            'billing_address.country'     => 'string|nullable',

            'integrations'                => 'array|nullable',
        ]);

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Entities' => ['p' => ['Edit', 'Lock'] ] ]);
            if(empty($grants['Entities']['Edit']['C'])){
                return response()->json([ 'message' => 'access_denied' ], 403);
            }
        }

        // unique check
        $company = Company::where('company_name', $request->company_name)->first();

        // if a different company exists with the desired name, stop

        if($company && $company->id != $request->id){
            return response()->json([ 'errors' => [ 'company_name' => 'Company already exists' ] ]);
        }

        // if it was our company afterall, prevent fetching it again unnecessarily (optimization)
        if(!$company){
            $company = Company::where('id', $request->id)->first();
            if(!$company){
                // it got deleted while user was busy editing it
                return response()->json([ 'message' => 'nonexistent' ]);
            }
        }

        $old_company_name = $company->company_name;
        $old_contact_name = $company->contact_name;

        // user wishes to remove logo, delete it.
        if(empty($request->company_logo)){
            // remove logo (if it was present) (WORKING)
            if(Storage::disk('public')->exists(basename($company->company_logo))){
                Storage::disk('public')->delete(basename($company->company_logo));
            }
        // user wants to update logo, overwrite it
        } else if($request->company_logo && $request->company_logo !== $company->company_logo){
            $logo_filename = Utils::sanitize_filename($request->company_name);
            $url = Utils::upload_base64_file($logo_filename, $request->company_logo);
            if($url){
                $request->company_logo = $url;
            }
        }

        $integrations_json = null;

        // INTEGRATIONS FIELDS SANITY CHECKS + STRINGIFY
        if(!empty($request->integrations)){
            $integrations_json = json_encode($request->integrations, JSON_FORCE_OBJECT );
            if ($integrations_json === NULL && json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([ 'message' => 'malformed_json', 'context' => 'O' ], 403);
            }
        }

        // update company
        Company::where('id', $request->id)->update([
            'company_name'                => $request->company_name,
            'company_logo'                => $request->company_logo,

            'contact_name'                => $request->contact_name,
            'contact_email'               => $request->contact_email,
            'contact_phone'               => $request->contact_phone,

            'address_physical_line_1'     => $request->physical_address['line_1'],
            'address_physical_line_2'     => $request->physical_address['line_2'],
            'address_physical_city'       => $request->physical_address['city'],
            'address_physical_postalcode' => $request->physical_address['postalcode'],
            'address_physical_country'    => $request->physical_address['country'],

            'address_billing_line_1'      => $request->billing_address['line_1'],
            'address_billing_line_2'      => $request->billing_address['line_2'],
            'address_billing_city'        => $request->billing_address['city'],
            'address_billing_postalcode'  => $request->billing_address['postalcode'],
            'address_billing_country'     => $request->billing_address['country'],

            'integrations'                => $integrations_json,
        ]);

        $this->acc->logActivity('Edit', 'Entities', "{$request->company_name} ($request->id)");

        $info = [
            'company_name_changed' => $request->company_name != $old_company_name,
            'company_name_new' => $request->company_name,
            'company_name_old' => $company->company_name,

            'contact_name_changed' => $request->contact_name != $old_contact_name,
            'contact_name_new' => $request->contact_name,
            'contact_name_old' => $company->contact_name,
            'integrations' => !empty($request->integrations) ? $request->integrations : null
        ];

        Eventy::action('company.save', $company, $info);

        return response()->json([ 'message' => 'company_updated' ]);
    }

    // Get Old (Source) Company Metadata associated with Ownership Transfer
    public function move_meta_old(Request $request, $company_id)
    {
        if(empty($company_id) || !DB::table('companies')->where('id', $company_id)->exists()){
            return response()->json(['message' => 'nonexistent']);
        }

        $old_cc_id = $company_id;
        $allowed_cc_ids = [];
        $allowed_ccs_by_id = [];

        $metadata = [
            'sensor_mappings' => [],
            'role_mappings'   => []
        ];

        // permission check
        if(!$this->acc->is_admin){

            // If this is a distributor or a normal user, 
            // the list of allowed companies to delete would equal the list of companies to move objects over to.

            $grants = $this->acc->requestAccess(['Entities' => ['p' => ['All'] ] ]);
            $allowed_cc_ids = !empty($grants['Entities']['Delete']['C']) ? $grants['Entities']['Delete']['C'] : [];
            if( empty($allowed_cc_ids) || !in_array($old_cc_id, $allowed_cc_ids) ){
                return response()->json([ 'message' => 'Access Denied' ], 403);
            }
        }

        // DEVICE MAPPINGS (Gathered from usage by Nodes)

        $old_sensors = DB::table('hardware_config')
        ->select([DB::raw('distinct hardware_config.hardware_management_id'),'hm.device_make', 'hm.device_type'])
        ->join('hardware_management as hm', 'hm.id', '=', 'hardware_config.hardware_management_id')
        ->where('hardware_config.company_id', $company_id)
        ->get()
        ->toArray();



        foreach($old_sensors as $sensor){
            $metadata['sensor_mappings'][] = [
                'old_sensor_id'   => $sensor->hardware_management_id,
                'old_sensor_name' => $sensor->device_make,
                'old_sensor_type' => $sensor->device_type,
                'new_sensor_id'   => null // to be selected by user (or will be moved over as-is)
            ];
        }

        // ROLE MAPPINGS (Gathered by usage from users)

        $old_roles = DB::table('users')
        ->select([ DB::raw('distinct users.role_id'), 'rl.role_name' ])
        ->join('roles as rl', 'rl.id', '=', 'users.role_id')
        ->where('users.company_id', $company_id)
        ->get()
        ->toArray();

        foreach($old_roles as $role){
            $metadata['role_mappings'][] = [
                'old_role_id'   => $role->role_id,
                'old_role_name' => $role->role_name,
                'new_role_id'   => null // to be selected by user
            ];
        }

        // Remember to exclude old company id from target company choices
        if(!empty($allowed_cc_ids)){
            // Non-Admin
            $allowed_ccs = DB::table('companies')
            ->select(['id', 'company_name'])
            ->where('id', '!=', $old_cc_id)
            ->whereIn('id', $allowed_cc_ids)
            ->get()
            ->toArray();

        } else {
            // Admin
            $allowed_ccs = DB::table('companies')
            ->select(['id', 'company_name'])
            ->where('id', '!=', $old_cc_id)
            ->get()
            ->toArray();
        }

        if($allowed_ccs){
            foreach($allowed_ccs as $k => $cc){
                $allowed_ccs_by_id['"'.$cc['id'].'"'] = $cc;
            }
        }

        // TARGET COMPANIES
        $metadata['target_companies'] = $allowed_ccs_by_id;

        return response()->json($metadata);
    }

    // Get New (Target) Entity's Sensors and Roles
    public function move_meta_new(Request $request, $company_id)
    {
        if(empty($company_id) || !DB::table('companies')->where('id', $company_id)->exists()){
            return response()->json(['message' => 'nonexistent']);
        }

        $new_cc_id = $company_id;
        $metadata  = [];

        // NEW SENSORS
        $new_sensors = DB::table('hardware_management')
        ->where('company_id', $new_cc_id)
        ->select(['id', 'device_make', 'device_type'])
        ->get()
        ->toArray();
        
        $metadata['new_sensors'] = $new_sensors;

        // GET SENSOR COUNTS BY TYPE
        $metadata['counts'] = [
            'Nutrients'     => DB::table('hardware_management')->where('company_id', $new_cc_id)->where('device_type', 'Nutrients')->count(),
            'Soil Moisture' => DB::table('hardware_management')->where('company_id', $new_cc_id)->where('device_type', 'Soil Moisture')->count(),
            'Wells'         => DB::table('hardware_management')->where('company_id', $new_cc_id)->where('device_type', 'Wells')->count(),
            'Water Meter'   => DB::table('hardware_management')->where('company_id', $new_cc_id)->where('device_type', 'Water Meter')->count()
        ];

        // NEW ROLES
        $new_roles = DB::table('roles')
        ->where('company_id', $new_cc_id)
        ->select(['id', 'role_name'])
        ->get()
        ->toArray();

        $metadata['new_roles'] = $new_roles;

        return response()->json($metadata);
    }

    // Transfer Company Objects Ownership
    public function move(Request $request)
    {
        $request->validate([
            'old_company_id'  => 'required|exists:companies,id',
            'new_company_id'  => 'required|exists:companies,id',
            'sensor_mappings' => 'nullable|array', // [old_sensor_id, new_sensor_id] (hardware_management_id)
            'role_mappings'   => 'nullable|array', // [old_role_id, new_role_id]
            'move_types'      => 'required|array', // what to move [ nodes / users ] (or both)
            'new_sensors'     => 'nullable|array',
            'new_roles'       => 'nullable|array'
        ]);

        // ensure new and old differs
        if($request->old_company_id == $request->new_company_id){
            return response()->json(['message' => 'same_company']);
        }

        // permission check
        if(!$this->acc->is_admin){

            // If this is a distributor or a normal user, 
            // the list of allowed companies to delete would equal the list of companies to move objects over to.

            $grants = $this->acc->requestAccess(['Entities' => ['p' => ['All'] ] ]);
            if(
                empty($grants['Entities']['Delete']['C']) ||
                !in_array($request->new_company_id, $grants['Entities']['Delete']['C']) ||
                !in_array($request->old_company_id, $grants['Entities']['Delete']['C'])
            ){
                return response()->json([ 'message' => 'Access Denied' ], 403);
            }
        }

        $old_cc_id = $request->old_company_id;
        $new_cc_id = $request->new_company_id;
        $sensor_mappings = $request->sensor_mappings;
        $role_mappings = $request->role_mappings;
        $move_types = $request->move_types;

        foreach($move_types as $type){

            // Nodes (Sensor Types, Fields, Cultivar Management, Cultivars (Growth Stages), Cultivar Templates)
            if($type == 'nodes'){

                $old_cc_sensors = DB::table('hardware_management')->where('company_id', $old_cc_id)->pluck('id')->toArray();
                $new_cc_sensors = DB::table('hardware_management')->where('company_id', $new_cc_id)->pluck('id')->toArray();

                // Do Optional Sensor Changeover
                if(!empty($sensor_mappings)){
                    foreach($sensor_mappings as $map){
                        // Sanity Check (Ensure Old Sensor is member of Old Sensors)
                        if(in_array($map['old_sensor_id'], $old_cc_sensors)){
                            if($map['new_sensor_id']){
                                // Manually chosen user mapping
                                // Sanity Check (Ensure New Sensor is member of New Sensors)
                                if(in_array($map['new_sensor_id'], $new_cc_sensors)){
                                    // Update hardware_config (Node) record to point to new sensor (For all nodes)
                                    DB::table('hardware_config')
                                    ->where('hardware_management_id', $map['old_sensor_id'])
                                    ->update([ 'hardware_management_id' => $map['new_sensor_id'] ]);
                                }
                            } else {
                                // Move over Sensor as-is to new company
                                DB::table('hardware_management')
                                ->where('id', $map['old_sensor_id'])
                                ->update([ 'company_id' => $new_cc_id ]);
                            }
                        }
                    }
                }

                if(empty($new_cc_sensors)){
                    // If no Target Sensors exist, move over the old sensors
                    DB::table('hardware_management')->where('company_id', $old_cc_id)->update([ 'company_id' => $new_cc_id ]);
                } else {
                    // Else delete Old Sensors
                    DB::table('hardware_management')->where('company_id', $old_cc_id)->delete();
                }

                // Update Nodes
                DB::table('hardware_config')->where('company_id', $old_cc_id)->update([ 'company_id' => $new_cc_id ]);
                // Update Fields
                DB::table('fields')->where('company_id', $old_cc_id)->update([ 'company_id' => $new_cc_id ]);
                // Update Cultivar Management Tables
                DB::table('cultivars')->where('company_id', $old_cc_id)->update([ 'company_id' => $new_cc_id ]);
                DB::table('cultivars_management')->where('company_id', $old_cc_id)->update([ 'company_id' => $new_cc_id ]);
                DB::table('cultivars_templates')->where('company_id', $old_cc_id)->update([ 'company_id' => $new_cc_id ]);
                // Update Nutrient Templates
                $template_names = DB::table('nutrient_templates')->where('company_id', $new_cc_id)->pluck('name')->toArray();
                DB::table('nutrient_templates')->where('company_id', $old_cc_id)->whereNotIn('name', $template_names)->update([ 'company_id' => $new_cc_id]);

            // Users (Roles, Security Rules, Security Templates)
            } else if($type == 'users'){

                $old_cc_roles = DB::table('roles')->where('company_id', $old_cc_id)->pluck('id')->toArray();
                $new_cc_roles = DB::table('roles')->where('company_id', $new_cc_id)->pluck('id')->toArray();

                // Do Optional Role Changeover
                if(!empty($role_mappings)){
                    foreach($role_mappings as $map){
                        // Security+Sanity Check
                        if(in_array($map['old_role_id'], $old_cc_roles) && in_array($map['new_role_id'], $new_cc_roles)){
                            DB::table('users')->where('company_id', $old_cc_id)->where('role_id', $map['old_role_id'])->update([
                                'role_id' => $map['new_role_id']
                            ]);
                        }
                    }
                }

                if(empty($new_cc_roles)){
                    // If no Target Roles exist, move over the old roles (+security rules)
                    DB::table('roles')->where('company_id', $old_cc_id)->update([ 'company_id' => $new_cc_id ]);
                    // Update Old Security Rules
                    DB::table('security_rules_companies')->where('company_id', $old_cc_id)->update([ 'company_id' => $new_cc_id ]);
                    DB::table('security_rules')->where('company_id', $old_cc_id)->update([ 'company_id' => $new_cc_id ]);
                } else {
                    // Else delete Old Roles
                    DB::table('roles')->where('company_id', $old_cc_id)->delete();
                    // Remove Old Security Rules
                    DB::table('security_rules_companies')->where('company_id', $old_cc_id)->delete();
                    DB::table('security_rules')->where('company_id', $old_cc_id)->delete();
                }

                // Update Users
                DB::table('users')->where('company_id', $old_cc_id)->update([ 'company_id' => $new_cc_id ]);
                // Update Groups
                DB::table('groups')->where('company_id', $old_cc_id)->update([ 'company_id' => $new_cc_id ]);

                // Remove Security Templates (New Company has their own)
                DB::table('security_templates')->where('company_id', $old_cc_id)->delete();
            }
        }

        $new_cc_name = DB::table('companies')->where('id', $new_cc_id)->value('company_name');
        $old_cc_name = DB::table('companies')->where('id', $old_cc_id)->value('company_name');
        $this->acc->logActivity('Delete', 'Entities', "(Moved Objects) $old_cc_name ($old_cc_id) -> $new_cc_name ($new_cc_id)");

        return response()->json(['message' => 'company_moved']);

    }

    // remove existing company (if not referenced)
    public function destroy(Request $request)
    {
        $request->validate([ 'id' => 'required' ]);

        $grants = [];
        $company_id = $request->id;

        // Check if company still exists
        $company = Company::where('id', $company_id)->first();
        if(!$company){
            return response()->json([ 'errors' => [ 'message' => 'company_missing' ] ]);
        }

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Entities' => ['p' => ['Delete'], 'o' => $company_id, 't' => 'C' ] ]);
            if(empty($grants['Entities']['Delete']['C'])){
                return response()->json([ 'message' => 'Access Denied' ], 403);
            }
        }

        // remove logo (if present) (WORKING)
        if(Storage::disk('public')->exists(basename($company->company_logo))){
            Storage::disk('public')->delete(basename($company->company_logo));
        }

        // For tying in with various integrations
        Eventy::action('company.delete', $company);

        // Remove Nutrient Templates first (As they Reference both the Company + User)
        DB::table('nutrient_templates')->where('company_id', $company_id)->delete();

        // Remove Users of the Company First (Prevents FK Constraint Violation)
        DB::table('users')->where('company_id', $company_id)->delete();

        // Remove the company
        Company::destroy($company_id);
        
        // Clear company cache
        Cache::forget(config('mab.instance')."_mab_perms_{$company_id}");

        // Forces a cache bust on all distributors (Yes it's really necessary)
        User::updateAllDistributorCaches();

        // Log the activity
        $this->acc->logActivity('Delete', 'Entities', "{$company->company_name} ($company_id)");

        // Return the result
        return response()->json([
            'message' => 'company_removed',
            'grants' => $grants
        ]);
    }

    // TODO: Change the lock/unlock endpoints to a singular toggle_lock endpoint
    public function lock(Request $request)
    {
        $request->validate([
            'company_id' => 'required'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Entities' => ['p' => ['Lock'], 'o' => $request->company_id, 't' => 'C'] ]);
            if(empty($grants['Entities']['Lock']['C'])){
                return response()->json([ 'message' => 'Access Denied' ], 403);
            }
        }

        Company::where('id', $request->company_id)->update([
            'is_locked' => 1
        ]);

        return response()->json([
            'message' => 'company_locked'
        ]);
    }

    public function unlock(Request $request)
    {
        $request->validate([
            'company_id' => 'required'
        ]);

        // permission check
        if(!$this->acc->is_admin){
            $grants = $this->acc->requestAccess(['Entities' => ['p' => ['Lock'], 'o' => $request->company_id, 't' => 'C'] ]);
            if(empty($grants['Entities']['Lock']['C'])){
                return response()->json([ 'message' => 'Access Denied' ], 403);
            }
        }

        Company::where('id', $request->company_id)->update([
            'is_locked' => 0
        ]);

        return response()->json([
            'message' => 'company_unlocked'
        ]);
    }
}