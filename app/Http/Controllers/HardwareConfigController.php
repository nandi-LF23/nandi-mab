<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

use App\Models\hardware_config;
use App\Models\fields;
use App\Models\cultivars_management;
use App\Models\cultivars;
use App\Models\node_data;
use App\Models\node_data_meter;
use App\Models\nutri_data;
use App\Models\nutrient_templates;
use App\Models\Group;
use App\Models\Company;

use MacsiDigital\OAuth2\Integration;
use App\Integrations\IntegrationManager;

use App\User;
use App\Utils;
use DB;

use TorMorten\Eventy\Facades\Events as Eventy;

class HardwareConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('cors');
        $this->middleware(function ($request, $next) {
            $this->acc = Auth::user();
            return $next($request);
        });
        $this->timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
    }

    // Node Config Table (Admin)
    public function NodeConfigTable(Request $request)
    {
        $request->validate([
            'cur_page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:5',
            'initial'  => 'required',
            'filter'   => 'nullable',
            'entity'   => 'nullable'
        ]);

        $this->tz = $this->timezones[$this->acc->timezone];
        if (!$this->tz) {
            $this->tz = 'UTC';
        }
        $tzObj = new \DateTimeZone($this->tz);

        $limit  = $request->per_page;
        $offset = ($request->cur_page - 1) * $limit;

        $sortBy  = !empty($request->sort_by) ? $request->sort_by : 'id';
        $sortDir = !empty($request->sort_dir) ? $request->sort_dir : 'desc';

        // optional filter param
        $filter = !empty($request->filter) ? $request->filter : '';
        // optional entity param
        $entity = !empty($request->entity) ? $request->entity : '';

        $nodes = [];
        $grants = [];
        $ccs    = [];
        $ccs_by_id = [];
        $total  = 0;

        $columns = [
            'hardware_config.id',
            'hardware_config.node_address',
            'hardware_config.latt',
            'hardware_config.lng',
            'hardware_config.commissioning_date',
            'fields.field_name',
            'hardware_config.node_type',
            'hardware_config.node_make',
            'companies.company_name',
            'hardware_config.date_time',
            'hardware_config.integration_opts'
        ];

        $sortBy = Utils::findColumnAlias(Utils::findFromPartial($columns, $sortBy, 'date_time'));

        $nodes = hardware_config::select($columns)
            ->join('fields', 'hardware_config.node_address', '=', 'fields.node_id')
            ->join('companies', 'hardware_config.company_id', '=', 'companies.id') /* Entity Column */
            ->when($entity, function ($query, $entity) {
                // filter by entity (optional)
                $query->where('companies.id', $entity);
            })
            ->where(function ($query) use ($filter) {
                $query->when($filter, function ($query, $filter) {
                    // filter by node_address, node_type, field_name, latt, lng or company_name (optional)
                    $query->where('hardware_config.node_address', 'like', "%$filter%")
                        ->orWhere('hardware_config.node_type', 'like', "%$filter%")
                        ->orWhere('fields.field_name', 'like', "%$filter%")
                        ->orWhere('hardware_config.latt', 'like', "%$filter%")
                        ->orWhere('hardware_config.lng', 'like', "%$filter%")
                        ->orWhere('companies.company_name', 'like', "%$filter%");
                });
            });

        if (!$this->acc->is_admin) {
            // permission check
            $grants = $this->acc->requestAccess([
                'Node Config'   => ['p' => ['All']],
                'Soil Moisture' => ['p' => ['All']],
                'Nutrients'     => ['p' => ['All']],
                'Well Controls' => ['p' => ['All']],
                'Meters'        => ['p' => ['All']]
            ]);

            if (!empty($grants['Node Config']['View']['O'])) {
                $nodes->whereIn('hardware_config.id', $grants['Node Config']['View']['O']);
                // Companies are fetched to populate the 'Filter by Entity' dropdown..
                $ccs = $grants['Node Config']['View']['C'];
                $ccs = Company::whereIn('id', $ccs)->orderBy('company_name')->get()->toArray();
            } else {
                $nodes = [];
            }
        } else {
            $ccs = Company::select(['id', 'company_name'])->orderBy('company_name')->get();
        }

        if ($nodes) {
            $total = $nodes->count();
            if ($total) {
                // sorting & pagination
                $nodes->orderBy($sortBy, $sortDir);
                $nodes = $nodes->skip($offset)->take($limit)->get();
            }
        }

        if ($nodes) {
            foreach ($nodes as &$row) {
                if ($row->date_time) {
                    $lr = new \DateTime($row->date_time);
                    $lr->setTimezone($tzObj);
                    $now = new \DateTime('now');
                    $now->setTimezone($tzObj);
                    $row->date_time = $lr->format('Y-m-d H:i:s');
                    $row->date_diff = $now->diff($lr);
                } else {
                    $row->date_time = '1970-01-01 00:00:00';
                    $row->date_diff = '';
                }
                if ($row->integration_opts) {
                    $data = json_decode($row->integration_opts, true);
                    if ($data) {
                        $values = array_unique(array_keys($data));

                        // Replacements
                        $idx = array_search('MyJohnDeere', $values);
                        if ($idx !== false) {
                            $values[$idx] = 'JD';
                        }

                        $row->integrations = implode(',', $values);
                        // We don't need the rest
                        unset($row->integration_opts);
                    }
                }
            }
        }

        if ($ccs) {
            foreach ($ccs as $k => $cc) {
                // IMPORTANT: The key needs to be a string or else the frontend sorting won't work. Trust me - F.
                $ccs_by_id['"' . $cc['id'] . '"'] = $cc;
            }
        }

        if ($request->initial) {
            $details = !empty($grants['Node Config']['View']['C']) ?
                ('Company IDs: ' . implode(',', $grants['Node Config']['View']['C'])) : ($this->acc->is_admin ? 'All Objects' : 'Access Denied');
            $this->acc->logActivity('View', 'Node Config', $details);
        }

        $result = [
            'rows'     => $nodes,
            'total'    => $total,
            'entities' => $ccs_by_id
        ];

        if (!empty($grants)) {
            $result['grants'] = $grants;
        }

        return response()->json($result);
    }

    // Soil Moisture / SMTable
    public function SoilMoistureTable(Request $request)
    {
        $this->tz = $this->timezones[$this->acc->timezone];
        if (!$this->tz) {
            $this->tz = 'UTC';
        }
        $tzObj = new \DateTimeZone($this->tz);

        $request->validate([
            'cur_page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:5',
            'initial'  => 'required',
            'filter'   => 'nullable',
            'entity'   => 'nullable',
        ]);

        $limit  = $request->per_page;
        $offset = ($request->cur_page - 1) * $limit;

        $sortBy  = !empty($request->sort_by) ? $request->sort_by : 'id';
        $sortDir = !empty($request->sort_dir) ? $request->sort_dir : 'desc';

        // optional filter param
        $filter = !empty($request->filter) ? $request->filter : '';
        // optional entity param
        $entity  = !empty($request->entity) ? $request->entity : '';

        $nodes  = [];
        $grants = [];
        $ccs    = [];
        $ccs_by_id = [];
        $total  = 0;

        $columns = [
            'companies.company_name',
            'cultivars_management.id as cm_id',
            'hardware_config.id',
            'hardware_config.node_address',
            'hardware_config.commissioning_date',
            'hardware_config.date_time',
            'fields.field_name',
            'fields.graph_type',
            'fields.graph_start_date'
        ];

        $sortBy = Utils::findColumnAlias(Utils::findFromPartial($columns, $sortBy, 'date_time'));

        $nodes = hardware_config::select($columns)
            ->join('fields', 'hardware_config.node_address', '=', 'fields.node_id')
            ->join('companies', 'hardware_config.company_id', '=', 'companies.id')
            ->join('cultivars_management', 'cultivars_management.field_id', '=', 'fields.id')
            ->leftJoin('node_data', function ($join) {
                $join
                    ->on('hardware_config.node_address', 'node_data.probe_id')
                    ->on('hardware_config.date_time', 'node_data.date_time');
            })
            ->where('node_type', 'Soil Moisture')
            ->where(function ($query) use ($filter, $entity) {
                // filter by entity/company
                $query->when($entity, function ($query, $entity) {
                    $query->where('companies.id', $entity);
                });
                // filter by node_address, field_name or company_name
                $query->when($filter, function ($query, $filter) {
                    $query->where('node_address', 'like', "%$filter%")
                        ->orWhere('field_name', 'like', "%$filter%")
                        ->orWhere('company_name', 'like', "%$filter%");
                });
            });

            

        if (!$this->acc->is_admin) {
            // permission check
            $grants = $this->acc->requestAccess(['Soil Moisture' => ['p' => ['All']]]);
            if (!empty($grants['Soil Moisture']['View']['O'])) {
                $nodes->whereIn('hardware_config.id', $grants['Soil Moisture']['View']['O']);
                $ccs = $grants['Soil Moisture']['View']['C'];
                $ccs = Company::whereIn('id', $ccs)->orderBy('company_name')->get()->toArray();
            } else {
                $nodes = [];
            }
        } else {
            $ccs = Company::select(['id', 'company_name'])->orderBy('company_name')->get()->toArray();
        }

        if ($nodes) {
            $total = $nodes->count();
            if ($total) {
                $nodes->orderBy($sortBy, $sortDir);
                $nodes = $nodes->skip($offset)->take($limit)->get();
            }
        }

        if ($nodes) {
            foreach ($nodes as &$row) {
                if ($row->date_time) {
                    $dt = new \DateTime($row->date_time);
                    $dt->setTimezone($tzObj);
                    $now = new \DateTime('now');
                    $now->setTimezone($tzObj);
                    $row->date_time = $dt->format('Y-m-d H:i:s');
                    $row->date_diff = $now->diff($dt);
                } else {
                    $row->date_time = '1970-01-01 00:00:00';
                    $row->date_diff = '';
                }
            }
        }

        if ($ccs) {
            foreach ($ccs as $k => $cc) {
                // IMPORTANT: The key needs to be a string or else the frontend sorting won't work. Trust me - F.
                $ccs_by_id['"' . $cc['id'] . '"'] = $cc;
            }
        }

        if ($request->initial) {
            $details = !empty($grants['Soil Moisture']['View']['C']) ?
                ('Company IDs: ' . implode(',', $grants['Soil Moisture']['View']['C'])) : ($this->acc->is_admin ? 'All Objects' : 'Access Denied');

            $this->acc->logActivity('View', 'Soil Moisture', $details);
        }

        $result = [
            'rows'     => $nodes,
            'total'    => $total,
            'entities' => $ccs
        ];

        if ($grants) {
            $result['grants'] = $grants;
        }

        return response()->json($result);
    }

    // Nutrients / NutrientsTable
    public function NutrientsTable(Request $request)
    {

        $this->tz = $this->timezones[$this->acc->timezone];
        if (!$this->tz) {
            $this->tz = 'UTC';
        }
        $tzObj = new \DateTimeZone($this->tz);

        $request->validate([
            'cur_page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:5',
            'initial'  => 'required',
            'filter'   => 'nullable',
            'entity'   => 'nullable'
        ]);

        $limit  = $request->per_page;
        $offset = ($request->cur_page - 1) * $limit;

        $sortBy  = !empty($request->sort_by) ? $request->sort_by : 'id';
        $sortDir = !empty($request->sort_dir) ? $request->sort_dir : 'desc';

        // optional filter param
        $filter = !empty($request->filter) ? $request->filter : '';
        // optional entity param
        $entity  = !empty($request->entity) ? $request->entity : '';

        $nodes  = [];
        $grants = [];
        $ccs    = [];
        $ccs_by_id = [];
        $total  = 0;

        $columns = [
            'companies.company_name',
            'hardware_config.id',
            'hardware_config.node_address',
            'hardware_config.commissioning_date',
            'hardware_config.date_time',
            'fields.field_name',
            'fields.id as field_id',
            'fields.graph_type',
            'fields.graph_start_date'
        ];

        $sortBy = Utils::findColumnAlias(Utils::findFromPartial($columns, $sortBy, 'date_time'));

        $nodes = hardware_config::select($columns)
            ->join('fields', 'hardware_config.node_address', '=', 'fields.node_id')
            ->join('companies', 'hardware_config.company_id', '=', 'companies.id')
            ->leftJoin('nutri_data', function ($join) {
                $join
                    ->on('hardware_config.node_address', 'nutri_data.node_address')
                    ->on('hardware_config.date_time', 'nutri_data.date_reported');
            })
            ->where('node_type', 'Nutrients')
            ->where(function ($query) use ($filter, $entity) {
                // filter by entity
                $query->when($entity, function ($query, $entity) {
                    $query->where('companies.id', $entity);
                });
                // filter by node_address, field_name or company_name
                $query->when($filter, function ($query, $filter) {
                    $query->where('node_address', 'like', "%$filter%")
                        ->orWhere('field_name', 'like', "%$filter%")
                        ->orWhere('company_name', 'like', "%$filter%");
                });
            });

        if (!$this->acc->is_admin) {
            // permission check
            $grants = $this->acc->requestAccess(['Nutrients' => ['p' => ['All']]]);
            if (!empty($grants['Nutrients']['View']['O'])) {
                $nodes->whereIn('hardware_config.id', $grants['Nutrients']['View']['O']);
                $ccs = $grants['Nutrients']['View']['C'];
                $ccs = Company::whereIn('id', $ccs)->orderBy('company_name')->get()->toArray();
            } else {
                $nodes = [];
            }
        } else {
            $ccs = Company::select(['id', 'company_name'])->orderBy('company_name')->get()->toArray();
        }

        if ($nodes) {
            $total = $nodes->count();
            if ($total) {
                $nodes->orderBy($sortBy, $sortDir);
                $nodes = $nodes->skip($offset)->take($limit)->get();
            }
        }

        if ($nodes) {
            foreach ($nodes as &$row) {
                if ($row->date_time) {
                    $dt = new \DateTime($row->date_time);
                    $dt->setTimezone($tzObj);
                    $now = new \DateTime('now');
                    $now->setTimezone($tzObj);
                    $row->date_time = $dt->format('Y-m-d H:i:s');
                    $row->date_diff = $now->diff($dt);
                } else {
                    $row->date_time = '1970-01-01 00:00:00';
                    $row->date_diff = '';
                }
            }
        }

        if ($ccs) {
            foreach ($ccs as $k => $cc) {
                // IMPORTANT: The key needs to be a string or else the frontend sorting won't work. Trust me - F.
                $ccs_by_id['"' . $cc['id'] . '"'] = $cc;
            }
        }

        if ($request->initial) {
            $details = !empty($grants['Nutrients']['View']['C']) ?
                ('Company IDs: ' . implode(',', $grants['Nutrients']['View']['C'])) : ($this->acc->is_admin ? 'All Objects' : 'Access Denied');

            $this->acc->logActivity('View', 'Nutrients', $details);
        }

        $result = [
            'rows'     => $nodes,
            'total'    => $total,
            'entities' => $ccs
        ];

        if ($grants) {
            $result['grants'] = $grants;
        }

        return response()->json($result);
    }

    // Wells Table
    public function WellsTable(Request $request)
    {
        $this->tz = $this->timezones[$this->acc->timezone];
        if (!$this->tz) {
            $this->tz = 'UTC';
        }
        $tzObj = new \DateTimeZone($this->tz);

        $request->validate([
            'cur_page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:5',
            'initial'  => 'required',
            'filter'   => 'nullable',
            'entity'   => 'nullable'
        ]);

        $limit  = $request->per_page;
        $offset = ($request->cur_page - 1) * $limit;

        $sortBy  = !empty($request->sort_by) ? $request->sort_by : 'id';
        $sortDir = !empty($request->sort_dir) ? $request->sort_dir : 'desc';

        // optional filter param
        $filter = !empty($request->filter) ? $request->filter : '';
        // optional filter param
        $entity = !empty($request->entity) ? $request->entity : '';

        $nodes  = [];
        $grants = [];
        $ccs    = [];
        $ccs_by_id = [];
        $total  = 0;

        $columns = [
            'companies.company_name',
            'fields.field_name',
            'hardware_config.id',
            'hardware_config.node_address',
            'hardware_config.commissioning_date',
            'hardware_config.date_time',
            'hardware_management.measurement_type'
        ];

        $sortBy = Utils::findColumnAlias(Utils::findFromPartial($columns, $sortBy, 'date_time'));

        $nodes = hardware_config::select($columns)
            ->join('fields', 'hardware_config.node_address', '=', 'fields.node_id')
            ->join('companies', 'hardware_config.company_id', '=', 'companies.id')
            ->join('hardware_management', 'hardware_config.hardware_management_id', '=', 'hardware_management.id')
            ->leftJoin('node_data_meters', function ($join) {
                $join
                    ->on('hardware_config.node_address', '=', 'node_data_meters.node_id')
                    ->on('hardware_config.date_time', '=', 'node_data_meters.date_time');
            })
            ->where('node_type', 'Wells')
            ->when($entity, function ($query, $entity) {
                // filter by entity (optional)
                $query->where('companies.id', $entity);
            })
            ->where(function ($query) use ($filter) {
                $query->when($filter, function ($query, $filter) {
                    // filter by node_address, field_name or company_name
                    $query->where('node_address', 'like', "%$filter%")
                        ->orWhere('field_name', 'like', "%$filter%")
                        ->orWhere('company_name', 'like', "%$filter%");
                });
            });

        if (!$this->acc->is_admin) {
            $grants = $this->acc->requestAccess(['Well Controls' => ['p' => ['All']]]);
            if (!empty($grants['Well Controls']['View']['O'])) {
                $nodes->whereIn('hardware_config.id', $grants['Well Controls']['View']['O']);
                $ccs = $grants['Well Controls']['View']['C'];
                $ccs = Company::whereIn('id', $ccs)->orderBy('company_name')->get()->toArray();
            } else {
                $nodes = [];
            }
        } else {
            $ccs = Company::select(['id', 'company_name'])->orderBy('company_name')->get()->toArray();
        }

        if ($nodes) {
            $total = $nodes->count();
            if ($total) {
                $nodes->orderBy($sortBy, $sortDir);
                $nodes = $nodes->skip($offset)->take($limit)->get();
            }
        }

        if ($nodes) {
            foreach ($nodes as &$row) {
                if ($row->date_time) {
                    $dt = new \DateTime($row->date_time);
                    $dt->setTimezone($tzObj);
                    $now = new \DateTime('now');
                    $now->setTimezone($tzObj);
                    $row->date_time = $dt->format('Y-m-d H:i:s');
                    $row->date_diff = $now->diff($dt);
                } else {
                    $row->date_time = '1970-01-01 00:00:00';
                    $row->date_diff = '';
                }
            }
        }

        if ($ccs) {
            foreach ($ccs as $k => $cc) {
                // IMPORTANT: The key needs to be a string or else the frontend sorting won't work. Trust me - F.
                $ccs_by_id['"' . $cc['id'] . '"'] = $cc;
            }
        }

        if ($request->initial) {
            $details = !empty($grants['Well Controls']['View']['C']) ?
                ('Company IDs: ' . implode(',', $grants['Well Controls']['View']['C'])) : ($this->acc->is_admin ? 'All Objects' : 'Access Denied');
            $this->acc->logActivity('View', 'Well Controls', $details);
        }

        $result = [
            'rows'     => $nodes,
            'total'    => $total,
            'entities' => $ccs
        ];

        if ($grants) {
            $result['grants'] = $grants;
        }

        return response()->json($result);
    }

    // Meters Table
    //changed to node_data table because the water meter is attached to a soil moisture probe and thus stores in the node_data table.
    public function MetersTable(Request $request)
    {

        $this->tz = $this->timezones[$this->acc->timezone];
        if (!$this->tz) {
            $this->tz = 'UTC';
        }
        $tzObj = new \DateTimeZone($this->tz);

        $request->validate([
            'cur_page' => 'required|integer|min:1',
            'per_page' => 'required|integer|min:5',
            'initial'  => 'required',
            'filter'   => 'nullable',
            'entity'   => 'nullable'
        ]);

        $limit  = $request->per_page;
        $offset = ($request->cur_page - 1) * $limit;

        $sortBy  = !empty($request->sort_by) ? $request->sort_by : 'id';
        $sortDir = !empty($request->sort_dir) ? $request->sort_dir : 'desc';

        // optional filter param
        $filter = !empty($request->filter) ? $request->filter : '';
        // optional entity param
        $entity = !empty($request->entity) ? $request->entity : '';

        $nodes  = [];
        $grants = [];
        $ccs    = [];
        $ccs_by_id = [];
        $total  = 0;

        $columns = [
            'companies.company_name',
            'fields.field_name',
            'hardware_config.id',
            'hardware_config.node_address',
            'hardware_config.commissioning_date',
            'hardware_config.date_time',
            'hardware_management.measurement_type'
            // $user_uom
        ];

// dd($columns[6] = $this->acc->unit_of_measure == 1 ? 'Cubes' : 'Gallons');
        $sortBy = Utils::findColumnAlias(Utils::findFromPartial($columns, $sortBy, 'date_time'));

        $nodes = hardware_config::select($columns)
            ->where('node_type', 'Water Meter')
            ->join('fields', 'hardware_config.node_address', '=', 'fields.node_id')
            ->join('companies', 'hardware_config.company_id', '=', 'companies.id')
            ->join('hardware_management', 'hardware_config.hardware_management_id', '=', 'hardware_management.id')
            // ->leftJoin('node_data_meters', function ($join) {
            //     $join
            //         ->on('hardware_config.node_address', '=', 'node_data_meters.node_id')
            //         ->on('hardware_config.date_time', '=', 'node_data_meters.date_time');
            // })
            ->leftJoin('node_data', function ($join) {
                $join
                    ->on('hardware_config.node_address', '=', 'node_data.probe_id')
                    ->on('hardware_config.date_time', '=', 'node_data.date_time');
            })
            ->when($entity, function ($query) use ($entity) {
                $query->where('companies.id', $entity);
            })
            ->where(function ($query) use ($filter) {
                $query->when($filter, function ($query, $filter) {
                    // filter by node_address, field_name or company_name
                    $query->where('node_address', 'like', "%$filter%")
                        ->orWhere('field_name', 'like', "%$filter%")
                        ->orWhere('company_name', 'like', "%$filter%");
                });
            });

        if (!$this->acc->is_admin) {
            $grants = $this->acc->requestAccess(['Meters' => ['p' => ['All']]]);
            if (!empty($grants['Meters']['View']['O'])) {
                $nodes->whereIn('hardware_config.id', $grants['Meters']['View']['O']);
                $ccs = $grants['Meters']['View']['C'];
                $ccs = Company::whereIn('id', $ccs)->orderBy('company_name')->get()->toArray();
            } else {
                $nodes = [];
            }
        } else {
            $ccs = Company::select(['id', 'company_name'])->orderBy('company_name')->get()->toArray();
        }

        if ($nodes) {
            $total = $nodes->count();
            if ($total) {
                $nodes->orderBy($sortBy, $sortDir);
                $nodes = $nodes->skip($offset)->take($limit)->get();
            }
        }

        if ($nodes) {
            foreach ($nodes as &$row) {
                $row->measurement_type = $this->acc->unit_of_measure == 1 ? 'Cubes' : 'Gallons';
                if ($row->date_time) {
                    $dt = new \DateTime($row->date_time);
                    $dt->setTimezone($tzObj);
                    $now = new \DateTime('now');
                    $now->setTimezone($tzObj);
                    $row->date_time = $dt->format('Y-m-d H:i:s');
                    $row->date_diff = $now->diff($dt);
                } else {
                    $row->date_time = '1970-01-01 00:00:00';
                    $row->date_diff = '';
                }
            }
        }

        if ($ccs) {
            foreach ($ccs as $k => $cc) {
                // IMPORTANT: The key needs to be a string or else the frontend sorting won't work. Trust me - F.
                $ccs_by_id['"' . $cc['id'] . '"'] = $cc;
            }
        }

        if ($request->initial) {
            $details = !empty($grants['Meters']['View']['C']) ?
                ('Company IDs: ' . implode(',', $grants['Meters']['View']['C'])) : ($this->acc->is_admin ? 'All Objects' : 'Access Denied');
            $this->acc->logActivity('View', 'Meters', $details);
        }

        $result = [
            'rows'     => $nodes,
            'total'    => $total,
            'entities' => $ccs,
        ];

        if ($grants) {
            $result['grants'] = $grants;
        }

        return response()->json($result);
    }

    // hardwareconfig/{node_address} - get for editing / form population
    public function get(Request $request)
    {
        $item = [];
        $grants = [];

        if (empty($request->node_address)) {
            return response()->json(['message' => 'missing_node_address']);
        }

        // check if node still exists
        $hw_config = hardware_config::where("node_address", $request->node_address)->first();
        if (!$hw_config) {
            return response()->json(['message' => 'nonexistent', 'obj' => 'node']);
        }

        // check if field still exists
        $field = fields::where('node_id', $request->node_address)->first();
        if (!$field) {
            return response()->json(['message' => 'nonexistent', 'obj' => 'field']);
        }

        // check if node type is correct
        $subsystem = Utils::convertNodeTypeToSubsystem($hw_config->node_type);
        if (!$subsystem) {
            return response()->json(['message' => 'invalid_type']);
        }

        if (!$this->acc->is_admin) {

            // permission check
            $grants = $this->acc->requestAccess([
                'Node Config'      => ['p' => ['All'], 'o' => $hw_config->id, 't' => 'O'],
                $subsystem         => ['p' => ['All'], 'o' => $hw_config->id, 't' => 'O']
            ]);

            if (
                empty($grants['Node Config']['View']['O']) &&
                empty($grants[$subsystem]['View']['O'])
            ) {
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // exclude returning these fields to the frontend
        $hw_config->makeHidden(['created_at', 'updated_at']);
        $field->makeHidden(['created_at', 'updated_at']);

        $item = $hw_config;
        $item->field_name = $field->field_name;
        $item->wl_station_name = $field->wl_station_name;
        $item->perimeter = $field->perimeter; // JSON Encoded FeatureCollection, return as is

        $additional_layers = null;

        // (Optional) Additional Layer (Zones) for MapPolyDrawer
        if (!empty($field->zones)) {
            $zones = json_decode($field->zones, true);

            $zone_colors = [
                "1" => "#00FF00", // green,
                "2" => "#DAFF00", // lightgreen,
                "3" => "#FFDA00", // yellow
                "4" => "#FE0000"  // red
            ];

            foreach ($zones as $idx => &$info) {

                $zone_id = (int) Utils::get_first_number($info['data']['ZONE_ID']);
                $base_id = "{$hw_config->node_address}_{$field->id}_{$zone_id}";

                $zones[$idx]['source'] = [
                    'type' => 'geojson',
                    'data' => [
                        'type' => 'Feature',
                        'geometry' => json_decode($info['geom'], true),
                        'properties' => [
                            'Zone' => true
                        ]
                    ]
                ];

                $zones[$idx]['layer'] = [
                    'id' => $base_id,
                    'source' => $base_id,
                    'minzoom' => 0,
                    'maxzoom' => 24,
                    'type' => 'fill',
                    'filter' => ['==', ['get', 'Zone'], true],
                    'paint' => [
                        'fill-color' => array_key_exists($zone_id, $zone_colors) ? $zone_colors[$zone_id] : "#555555",
                        'fill-opacity' => 0.2,
                        'fill-outline-color' => 'rgba(0,0,0,1.0)',
                        'fill-antialias' => true
                    ]
                ];
                unset($info['geom']);
                unset($info['data']);
            }
            $additional_layers = json_encode($zones);
        }

        // integration options
        if ($this->acc->is_admin || !empty($grants['Node Config']['Integrate']['O'])) {
            $defaults = IntegrationManager::options($hw_config->company_id, 'hardware_config', $hw_config->node_type);
            // Ensure Defaults only returned for Active Integrations 
            if (!empty($defaults)) {
                $saved = json_decode($item->integration_opts, true);
                $item->integration_opts = !empty($saved) ? Utils::array_merge_rec($defaults, $saved) : $defaults;
            } else {
                $item->integration_opts = null;
            }
        }

        $this->acc->logActivity('View', 'Node Config', $item->node_address);

        return response()->json([
            'message'  => 'hardware_loaded',
            'hardware' => $item,
            'grants'   => $grants,
            'field_id' => $field->id,
            'additional_layers' => $additional_layers
        ]);
    }

    // Add new Node
    public function new(Request $request)
    {
        $this->validateNodeConfig($request, true);

        $subsystem = Utils::convertNodeTypeToSubsystem($request->node_type);
        if (empty($subsystem)) {
            return response()->json(['message' => 'access_denied', 'context' => 'Invalid Type'], 403);
        }

        // permission check (Confirm user can add node to chosen company)
        if (!$this->acc->is_admin) {
            $grants = $this->acc->requestAccess([
                $subsystem    => ['p' => ['Add'], 'o' => $request->company_id, 't' => 'C'],
                'Node Config' => ['p' => ['Add'], 'o' => $request->company_id, 't' => 'C']
            ]);
            if (empty($grants['Node Config']['Add']['C']) && empty($grants[$subsystem]['Add']['C'])) {
                return response()->json(['message' => 'access_denied', 'context' => 'C'], 403);
            }
        }

        // optional permission check (Confirm user can add node to chosen group)
        if (!empty($request->group_id)) {
            if (!$this->acc->is_admin) {
                $grants = $this->acc->requestAccess([
                    $subsystem    => ['p' => ['Add'], 'o' => $request->group_id, 't' => 'G'],
                    'Node Config' => ['p' => ['Add'], 'o' => $request->group_id, 't' => 'G']
                ]);
                if (empty($grants['Node Config']['Add']['G']) && empty($grants[$subsystem]['Add']['G'])) {
                    return response()->json(['message' => 'access_denied', 'context' => 'G'], 403);
                }
            }
        }

        $node_address = $request->node_address . '-' . $request->probe_address;

        // manual uniqueness check
        if (hardware_config::where('node_address', $node_address)->exists()) {
            return response()->json(['message' => "alreadyexists"]);
        }

        $hwconfig = new hardware_config();
        $hwconfig->node_type = $request->node_type;
        $hwconfig->node_address = $node_address;
        $hwconfig->probe_address = $request->probe_address;
        $hwconfig->latt = $request->latt;
        $hwconfig->lng = $request->lng;
        $hwconfig->zone = !empty($request->zone) ? $request->zone : '';
        $hwconfig->coords_locked = !empty($request->coords_locked) ? $request->coords_locked : 0;
        $hwconfig->node_make = $request->node_make;
        $hwconfig->node_serial_number = $request->node_serial_number;
        $hwconfig->device_serial_number = $request->device_serial_number;
        $hwconfig->commissioning_date = date('Y-m-d H:i:s');
        $hwconfig->created_at = date('Y-m-d H:i:s');
        $hwconfig->company_id = $request->company_id;

        // populate date_time field
        if ($request->node_type == 'Soil Moisture') {
            $nd = node_data::where('probe_id', $node_address)->orderBy('id', 'desc')->first();
            if ($nd) {
                $hwconfig->date_time = $nd->date_time;
            }
        } else if ($request->node_type == 'Nutrients') {
            $nd = nutri_data::where('node_address', $node_address)->orderBy('id', 'desc')->first();
            if ($nd) {
                $hwconfig->date_time = $nd->date_sampled;
            }
        } else if (in_array($request->node_type, ['Wells', 'Water Meter'])) {
            $ndm = node_data_meter::where('node_id', $node_address)->orderBy('idwm', 'desc')->first();
            if ($ndm) {
                $hwconfig->date_time = $ndm->date_time;
            }
        }

        // All Node Types have an Associated Field + CM Record (Created beforehand)
        // ------------------------------------------------------------------------

        // see if existing field exists, use that
        $field = fields::where('node_id', $node_address)->first();
        if (!$field) {
            // otherwise, create new field (per node)
            $field = new fields();
        }
        $field->company_id = $request->company_id;
        $field->field_name = $request->field_name;
        $field->node_id = $node_address;
        $field->eto = 0;

        if ($request->has('wl_station_name')) {
            $field->wl_station_name = $request->wl_station_name;
        }

        // SM has default graph_type of ave (set in DB)
        if ($request->node_type == 'Nutrients') {
            $field->graph_type = 'nutrient_ppm_avg';
        } else if (in_array($request->node_type, ['Wells', 'Water Meter'])) {
            $field->graph_type = 'pulse';
        }

        // save node associated field record
        $field->save();

        // see if existing cultivars_management record exists, use that
        $cm = cultivars_management::where('field_id', $field->id)->first();
        if (!$cm) {
            // otherwise, create new cultivars_management (per node)
            $cm = new cultivars_management();
        }
        $cm->field_id = $field->id;
        $cm->company_id = $field->company_id;
        $cm->NI = 1;
        $cm->NR = 1;
        $cm->save();

        // Node Type
        if ($request->node_type == 'Nutrients') {
            // Assign default nutrient template
            if ($request->nutrient_template_id) {
                $field->nutrient_template_id = $request->nutrient_template_id;
                $field->save();
            }
        }

        // Set HW Management Template (Common to all Node Types now)
        $hwconfig->hardware_management_id = $request->hardware_management_id; // FK

        // save node hardware config record
        $hwconfig->save();

        // add new node to group (optional)
        if ($request->has('group_id')) {
            if (Group::where('id', $request->group_id)->exists()) {
                DB::table('groups_nodes')->insert(['group_id' => $request->group_id, 'object_id' => $hwconfig->id]);
            }
        }

        $this->acc->logActivity('Add', 'Node Config', "Node:{$hwconfig->node_type}:{$hwconfig->node_address}, Field:{$field->field_name}");

        Eventy::action('node_config.new', $hwconfig, $field);
        Eventy::action("node_config." . strtolower($hwconfig->node_type) . ".new", $hwconfig, $field);

        return response()->json([
            'message' => 'node_added',
            'node_id' => $hwconfig->id
        ], 200);
    }

    // Update existing Node
    public function save(Request $request)
    {
        $this->validateNodeConfig($request, false);

        $subsystem = Utils::convertNodeTypeToSubsystem($request->node_type);

        $field_values = ['field_name', 'wl_station_name', 'perimeter'];

        // HARDWARE CONFIG
        $hw_req = $request->except($field_values);
        // FIELD RECORD
        $fl_req = $request->only($field_values);

        $node_address = $hw_req['node_address'];

        // NOTE: // perimeter is A-L-W-A-Y-S a FeatureCollection
        $prev_perimeter_hash = NULL;
        $curr_perimeter_hash = NULL;

        $company_changed = false;
        $node_type_changed = false;

        $old_node_type = null;
        $old_company_id = null;

        // hardware config / node still exists?
        $hwconfig = hardware_config::where('node_address', $node_address)->first();
        if (!$hwconfig) {
            return response()->json(['message' => 'nonexistent', 'obj' => 'node']);
        }

        // permission check
        if (!$this->acc->is_admin) {
            // permission check
            $grants = $this->acc->requestAccess([
                $subsystem    => ['p' => ['Edit'], 'o' => $hwconfig->id, 't' => 'O'],
                'Node Config' => ['p' => ['Edit']]
            ]);

            if (empty($grants['Node Config']['Edit']['O']) && empty($grants[$subsystem]['Edit']['O'])) {
                return response()->json(['message' => 'access_denied', 'context' => 'O'], 403);
            }
        }

        // CHECK IF FIELD RECORD EXISTS, ADD IN CASE IT DIDNT'T EXIST
        $field = fields::where('node_id', $node_address)->first();
        if (!$field) {
            $field = new fields();
        }
        $field->company_id = $request->company_id;
        $field->field_name = $request->field_name;
        $field->node_id = $node_address;
        $field->eto = 0;

        if ($request->has('wl_station_name')) {
            $field->wl_station_name = $request->wl_station_name;
        }

        // Node Type Change - Update Default Graph Type
        if ($request->node_type == 'Nutrients') {
            $field->graph_type = 'nutrient_ppm_avg';
        } else if (in_array($request->node_type, ['Wells', 'Water Meter'])) {
            $field->graph_type = 'pulse';
        }

        // save node associated field record
        $field->save();

        // CHECK IF CM RECORD EXISTS, ADD IN CASE IT DIDNT'T EXIST
        $cm = cultivars_management::where('field_id', $field->id)->first();
        if (!$cm) {
            // save cultivars_management record
            $cm = new cultivars_management();
            $cm->field_id = $field->id;
            $cm->company_id = $field->company_id;
            $cm->NI = 1;
            $cm->NR = 1;
            $cm->save();
        }

        // NODE COMPANY CHANGE
        if ($hwconfig->company_id != $request->company_id) {

            $company_changed = true;
            $old_company_id = $hwconfig->company_id;

            $subsystems = $this->acc->subsystems();

            // Remove Node from Old Node Groups
            $groups = Group::where('company_id', $hwconfig->company_id)->where('subsystem_id', $subsystems[$subsystem]['id'])->get();
            if ($groups) {
                foreach ($groups as $g) {
                    DB::table($subsystems[$subsystem]['group_table'])->where('group_id', $g->id)->where('object_id', $hwconfig->id)->delete();
                }
            }

            // Update Field Company
            fields::where('node_id', $request->node_address)->update(['company_id' => $request->company_id]);

            // conditionally update cm record (if it exists)
            $cm = cultivars_management::where('field_id', $field->id)->first();
            if ($cm) {
                // remove cm record from old cm groups
                $groups = Group::where('company_id', $cm->company_id)->where('subsystem_id', $subsystems['Cultivars']['id'])->get();
                if ($groups) {
                    foreach ($groups as $g) {
                        DB::table($subsystems['Cultivars']['group_table'])->where('group_id', $g->id)->where('object_id', $cm->id)->delete();
                    }
                }

                // update cm company
                $cm->update(['company_id' => $request->company_id]);

                // process cm stages
                $stages = cultivars::where('cultivars_management_id', $cm->id)->get();
                if ($stages) {
                    foreach ($stages as $s) {
                        // remove cm stage records from old cm stage groups
                        $groups = Group::where('company_id', $s->company_id)->where('subsystem_id', $subsystems['Cultivar Stages']['id'])->get();
                        if ($groups) {
                            foreach ($groups as $g) {
                                DB::table($subsystems['Cultivar Stages']['group_table'])->where('group_id', $g->id)->where('object_id', $s->id)->delete();
                            }
                        }
                        // update cm stage record
                        $s->update(['company_id' => $request->company_id]);
                    }
                }
            }

            // Reset a Nutrient Node's Template to the new Entity's Default Nutrient Template.
            if ($hwconfig->node_type == 'Nutrients') {
                $default_nutrient_template_id = nutrient_templates::where('name', 'Default Template')->where('company_id', $request->company_id)->value('id');
                $field->nutrient_template_id = $default_nutrient_template_id;
                $field->save();
            }

            $company_name = Company::where('id', $request->company_id)->pluck('company_name')->first();
            $this->acc->logActivity('Edit', 'Node Config', "Node:{$hwconfig->node_type}:{$hwconfig->node_address} Changed Entity: $company_name");
        }

        // NODE TYPE CHANGE
        if ($hwconfig->node_type != $request->node_type) {

            $node_type_changed = true;
            $old_node_type = $hwconfig->node_type;
        }

        // INTEGRATIONS FIELDS SANITY CHECKS + STRINGIFY
        if (!empty($request->integration_opts)) {
            $json = json_encode($request->integration_opts, JSON_FORCE_OBJECT);
            if ($json === NULL && json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['message' => 'malformed_json', 'context' => 'O'], 403);
            } else {
                $hw_req['integration_opts'] = $json;
            }
        } else {
            $hw_req['integration_opts'] = NULL;
        }

        // UPDATE HARDWARE CONFIG RECORD
        hardware_config::where('node_address', $hw_req['node_address'])->update($hw_req);

        // PERIMETER FIELD PROCESSING ** We Add Custom <Node Types> Properties **
        if (!empty($fl_req['perimeter'])) {
            $data = $fl_req['perimeter'];
            if (!empty($data['type']) && $data['type'] == 'FeatureCollection') {
                if (!empty($data['features']) && is_array($data['features']) && count($data['features']) > 0) {

                    foreach ($data['features'] as $index => $feat) {

                        $data['features'][$index]['id'] = $hw_req['node_address']; // necessary?

                        if ($hwconfig->node_type == 'Soil Moisture') {
                            $data['features'][$index]['properties'] = [
                                'Soil Moisture' => true
                            ];
                        }
                        if ($hwconfig->node_type == 'Nutrients') {
                            $data['features'][$index]['properties'] = [
                                'Soil Moisture' => true,
                                'Nutrients' => true
                            ];
                        }

                        // Mark as Field Boundary
                        $data['features'][$index]['properties']['Field'] = true;
                    }

                    $fl_req['perimeter'] = json_encode($data); // JSON ENCODE

                } else {
                    $fl_req['perimeter'] = NULL;
                }
            } else {
                $fl_req['perimeter'] = NULL;
            }
        } else {
            $fl_req['perimeter'] = NULL;
        }

        // CALC PERIMETER HASHES ** With Custom Properties **
        $prev_perimeter_hash = md5($field->perimeter); // JSON encoded 
        $curr_perimeter_hash = md5($fl_req['perimeter']);

        // UPDATE FIELD (Without affecting object)
        fields::where('id', $field->id)->update($fl_req);

        $info = [
            'perimeter_changed'   => $curr_perimeter_hash != $prev_perimeter_hash,
            'perimeter_new'       => json_decode($fl_req['perimeter'], true),
            'perimeter_old'       => $field->perimeter,

            'company_changed'     => $company_changed,
            'company_id_old'      => $old_company_id,
            'company_id_new'      => $request->company_id,

            'node_type_changed'   => $node_type_changed,
            'node_type_old'       => $old_node_type,
            'node_type_new'       => $request->node_type,

            'field_name_changed'  => $field->field_name != $fl_req['field_name'],
            'field_name_new'      => $fl_req['field_name'],
            'field_name_old'      => $field->field_name,

            'coordinates_changed' => $hwconfig->latt != $hw_req['latt'] || $hwconfig->lng != $hw_req['lng'], // delta calc doesn't apply here
            'coordinates_new'     => [$hw_req['latt'], $hw_req['lng']],
            'coordinates_old'     => [$hwconfig->latt, $hwconfig->lng],

            'integrations'        => json_decode($hw_req['integration_opts'], true) // integration options
        ];

        $this->acc->logActivity('Edit', 'Node Config', "Node:{$hwconfig->node_type}:{$hwconfig->node_address}");

        Eventy::action('node_config.save', $hwconfig, $field, $info);
        Eventy::action("node_config." . Utils::slugify($hwconfig->node_type) . ".save", $hwconfig, $field, $info);

        return response()->json([
            'message' => 'success'
        ]);
    }

    // used by both new and update
    public function validateNodeConfig($request, $bIsNew = false)
    {
        $nodeTypes = ['Soil Moisture', 'Nutrients', 'Wells', 'Water Meter'];

        $node_address_rule = $bIsNew ? ['required', 'alpha_num'] : ['required', 'alpha_dash'];

        if ($bIsNew) {
            $request->validate([
                'group_id' => 'integer|nullable',
                'nutrient_template_id' => 'integer|nullable'
            ]);
        }

        $water_meter = array_search('Water Meter', $nodeTypes);

            // dd('hi');
            $request->validate([
                'node_address' => $node_address_rule,
                'probe_address' => 'nullable|integer|min:0',
                'field_name' => 'required',
                'hardware_management_id' => 'required|exists:hardware_management,id',
                'commissioning_date' => $bIsNew ? 'required' : 'nullable',
                'wl_station_name' => 'nullable|string',
                'latt' => 'regex:/^-?\d+(\.\d{1,15})?$/',
                'lng' => 'regex:/^-?\d+(\.\d{1,15})?$/',
                'zone' => 'nullable',
                'coords_locked' => 'nullable|integer',
                'node_type' => ['required', Rule::in($nodeTypes)],
                'company_id' => 'required|integer',
                'perimeter' => 'array|nullable',
                'node_serial_number' => 'string|nullable',
                'device_serial_number' => 'string|nullable',
                'integration_opts' => 'array|nullable'
            ]);
    }

    // Perform address update of node across system
    public function update_address(Request $request)
    {
        // permission check
        if (!$this->acc->is_admin) {
            $grants = $this->acc->requestAccess([
                'Node Config' => ['p' => ['Edit'], 'o' => $request->id, 't' => 'O']
            ]);
            if (empty($grants['Node Config']['Edit']['O'])) {
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // NOTE: Changing a Node's Address would not cause a change in permissions (as the node record's PK value is used (And not the Node Address))

        $request->validate([
            'old_address'   => 'required|alpha_dash',
            'node_address'  => 'required|alpha_num',
            'probe_address' => 'nullable|integer'
        ]);

        $probe_address = $request->probe_address;

        if ($probe_address != NULL) {
            $new_address = $request->node_address . '-' . $probe_address;
        } else {
            $new_address = $request->node_address;
        }
        
        $old_address = $request->old_address;

        $hwconfig = hardware_config::where('node_address', $old_address)->first();

        // Update address in various tables

        $count = 0;

        $count += hardware_config::where('node_address', $old_address)->update([
            'node_address'  => $new_address,
            'probe_address' => $probe_address
        ]);

        $count += fields::where('node_id', $old_address)->update([
            'node_id'  => $new_address
        ]);

        $count += node_data::where('probe_id', $old_address)->update([
            'probe_id' => $new_address
        ]);

        $count += node_data_meter::where('node_id', $old_address)->update([
            'node_id'  => $new_address
        ]);

        $count += nutri_data::where('node_address', $old_address)->update([
            'node_address'  => $new_address
        ]);

        // update node last record date
        $node = DB::table('hardware_config')->where('node_address', $new_address)->first();
        if ($node) {

            $last_date = '';

            if ($node->node_type =='Soil Moisture' || $node->node_type == 'Water Meter') {
                $data = DB::table('node_data')->where('probe_id', $new_address)->orderBy('id', 'desc')->first();
                if ($data) {
                    $last_date = $data->date_time;
                }
            } else if ($node->node_type == 'Nutrients') {
                $data = DB::table('nutri_data')->where('node_address', $new_address)->orderBy('id', 'desc')->first();
                if ($data) {
                    $last_date = $data->date_sampled;
                }
            } else if ($node->node_type == 'Wells') {
                $data = DB::table('node_data_meters')->where('node_id', $new_address)->orderBy('id', 'desc')->first();
                if ($data) {
                    $last_date = $data->date_time;
                }
            }

            if ($last_date) {
                DB::table('hardware_config')->where('node_address', $new_address)->update([
                    'date_time' => $last_date
                ]);
            }
        }

        $this->acc->logActivity('Edit', 'Node Config', "Node Address Update: {$old_address} -> {$new_address}");

        // leave raw data tables as is

        $info = [
            'node_address_changed' => true,
            'node_address_old'     => $old_address,
            'node_address_new'     => $new_address,
            'integrations'         => $hwconfig->integration_opts
        ];

        Eventy::action('node_config.update_address', $hwconfig, $info);
        Eventy::action("node_config." . strtolower($hwconfig->node_type) . ".update_address", $hwconfig, $info);

        return response()->json([
            'message' => 'success',
            'count' => $count
        ]);
    }

    // utility method used by reboot() and toggle_wm()
    public function to_xml(\SimpleXMLElement $object, array $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $new_object = $object->addChild($key);
                $this->to_xml($new_object, $value);
            } else {

                $object->addChild($key, $value);
            }
        }
    }

    // Toggle Well
    public function toggle_wm(Request $request)
    {
        $request->validate(['node_address' => 'required']);

        $hwconfig = hardware_config::where('node_address', $request->node_address)->first();
        if (!$hwconfig) {
            return response()->json(['message' => 'nonexistent']);
        }

        if (!$this->acc->is_admin) {
            // permission check
            $grants = $this->acc->requestAccess(['Well Controls' => ['p' => ['Toggle']]]);
            if (!in_array($hwconfig->id, $grants['Well Controls']['Toggle']['O'])) {
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        if ($hwconfig->node_make == 'LF-AG02') {
            $body = $this->toggle_intellect($hwconfig);
        } else if ($hwconfig->node_make == 'DMT (Eagle)') {
            $body = $this->toggle_dmt($hwconfig);
        }

        $this->acc->logActivity('Toggle', 'Well Controls', "Node:{$hwconfig->node_type}:{$hwconfig->node_address}");

        return response()->json($body);
    }

    // Toggle Output on Intellect Device
    public function toggle_intellect($hwconfig)
    {
        $data = openssl_random_pseudo_bytes(16, $secure);
        if (false === $data) {
            return false;
        }

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        $tag = time() + (15 * 60); // 15 mins from now
        $timestamp = time(); // now
        $duration = 500; // ms
        $fast = 3; //  3 messages

        // 5 == 'Relay Instruction' command
        $data1 = pack('cVvc', 5, $tag, $duration, $fast);

        $send_me = array();
        $send_me['datagramDownlinkRequest']['tag'] = $uuid;
        $send_me['datagramDownlinkRequest']['nodeId'] = $hwconfig->node_address;
        $send_me['datagramDownlinkRequest']['payload'] = bin2hex($data1);
        $send_me['datagramDownlinkRequest']['timestamp'] = $timestamp;

        $xml = new \SimpleXMLElement("<downlink xmlns='http://www.ingenu.com/data/v1/schema'/>");
        $this->to_xml($xml, $send_me);
        $dom = dom_import_simplexml($xml);
        $xml = $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);

        $client = new \GuzzleHttp\Client(['verify' => false]);
        $res = $client->request('POST', 'https://glds.ingenu.com/config/v1/session', [
            'headers' => ['username' => 'mab@liquidfibre.com', 'password' => 'M@b123456']
        ]);

        $body = json_decode($res->getBody());

        $url = 'https://glds.ingenu.com/data/v1/send';
        $options = [
            'headers' => [
                'Authorization' => $body->token,
                'Content-Type' => 'application/xml; charset=UTF8',
            ],
            'body' => $xml,
        ];

        $response = $client->request('POST', $url, $options);

        return json_decode($response->getBody());
    }

    // Toggle output on DMT Device
    public function toggle_dmt($hwconfig)
    {
        $serial = $hwconfig->node_serial_number;
        $now_plus_one_day = new \DateTime("now");
        $now_plus_one_day->add(new \DateInterval("P1D"));

        $client = new \GuzzleHttp\Client(['verify' => false]);
        $response = $client->request('POST', "https://api.oemserver.com/v1.0/asyncmessaging/send/{$serial}", [
            'auth' => ['dave@liquidfibre.com', 'Liquid2021'], /* HTTP Basic Auth */
            'body' => json_encode([
                "MessageType" => 4, /* 0x004 - Set Digital Output */
                "CANAddress" => 0xFFFFFFFF, /* Host Address */
                "ExpiryDateUTC" => $now_plus_one_day->format('c'),
                "Data" => [
                    1, /* Change Mask LSB, output 0 select */
                    0, /* Change Mask MSB */
                    1, /* Logical Level LSB, output 0, set to 1 */
                    0  /* Logical Level MSB */
                ]
            ])
        ]);
        return json_decode($response->getBody());
    }

    // Reboot Node
    public function reboot(Request $request)
    {
        $request->validate([
            'node_address' => 'required|exists:hardware_config,node_address'
        ]);

        $hwconfig = hardware_config::where('node_address', $request->node_address)->first();
        if (!$hwconfig) {
            return response()->json(['message' => 'nonexistent']);
        }

        if (!$this->acc->is_admin) {
            // permission check
            $grants = $this->acc->requestAccess(['Node Config' => ['p' => ['Reboot']]]);
            if (!in_array($hwconfig->id, $grants['Node Config']['Reboot']['O'])) {
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        if ($hwconfig->node_make == 'LF-AG02') {
            $body = $this->reboot_intellect($hwconfig);
        } else if ($hwconfig->node_make == 'DMT (Eagle)') {
            $body = $this->reboot_dmt($hwconfig);
        }

        $this->acc->logActivity('Reboot', 'Node Config', "Node:{$hwconfig->node_type}:{$hwconfig->node_address}");

        Eventy::action('node_config.reboot', $hwconfig);

        return response()->json([
            'message' => 'initiated',
            'response' => $body
        ]);
    }

    public function reboot_intellect($hwconfig)
    {
        $data = openssl_random_pseudo_bytes(16, $secure);
        if (false === $data) {
            return false;
        }

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        $timestamp = time(); // now

        // 8 == 'Reboot Instruction' command
        $data1 = pack('cH*', 8, bin2hex('REBOOT'));

        $send_me = array();
        $send_me['datagramDownlinkRequest']['tag'] = $uuid;
        $send_me['datagramDownlinkRequest']['nodeId'] = $hwconfig->node_address;
        $send_me['datagramDownlinkRequest']['payload'] = bin2hex($data1);
        $send_me['datagramDownlinkRequest']['timestamp'] = $timestamp;

        $xml = new \SimpleXMLElement("<downlink xmlns='http://www.ingenu.com/data/v1/schema'/>");
        $this->to_xml($xml, $send_me);
        $dom = dom_import_simplexml($xml);
        $xml = $dom->ownerDocument->saveXML($dom->ownerDocument->documentElement);

        $client = new \GuzzleHttp\Client(['verify' => false]);
        $res = $client->request('POST', 'https://glds.ingenu.com/config/v1/session', [
            'headers' => ['username' => 'mab@liquidfibre.com', 'password' => 'M@b123456']
        ]);

        $body = json_decode($res->getBody());

        $url = 'https://glds.ingenu.com/data/v1/send';
        $options = [
            'headers' => [
                'Authorization' => $body->token,
                'Content-Type' => 'application/xml; charset=UTF8',
            ],
            'body' => $xml,
        ];

        $response = $client->request('POST', $url, $options);
        return json_decode($response->getBody());
    }

    public function reboot_dmt($hwconfig)
    {
        $serial = $hwconfig->node_serial_number;
        $now_plus_one_day = new \DateTime("now");
        $now_plus_one_day->add(new \DateInterval("P1D"));

        $client = new \GuzzleHttp\Client(['verify' => false]);
        $response = $client->request('POST', "https://api.oemserver.com/v1.0/asyncmessaging/send/{$serial}", [
            'auth' => ['dave@liquidfibre.com', 'Liquid2021'], /* HTTP Basic Auth */
            'body' => json_encode([
                "MessageType" => 2, /* 0x002 - Remote Restart */
                "CANAddress" => 0xFFFFFFFF, /* Host Address */
                "ExpiryDateUTC" => $now_plus_one_day->format('c')
            ])
        ]);
        return json_decode($response->getBody());
    }

    // perform remote firmware update
    public function flash(Request $request)
    {
        return response()->json(['message' => 'not_implemented']);
    }

    // Remove Existing Node
    public function destroy(Request $request)
    {
        $grants = [];

        // check if node exists before attempting delete
        $request->validate([
            'node_address' => 'required|alpha_dash'
        ]);

        $hwconfig = hardware_config::where('node_address', $request->node_address)->first();
        if (!$hwconfig) {
            return response()->json(['message' => 'nonexistent']);
        }

        // permission check
        if (!$this->acc->is_admin) {
            $subsystem = Utils::convertNodeTypeToSubsystem($hwconfig->node_type);
            $grants = $this->acc->requestAccess([
                'Node Config' => ['p' => ['All'], 'o' => $hwconfig->id, 't' => 'O'],
                $subsystem    => ['p' => ['All'], 'o' => $hwconfig->id, 't' => 'O'],
            ]);
            if (
                empty($grants['Node Config']['Delete']['O']) &&
                empty($grants[$subsystem]['Delete']['O'])
            ) {
                return response()->json(['message' => 'access_denied'], 403);
            }
        }

        // NOTE: we don't delete the field, the cm, the cm stages. They become orphans (in case somebody accidentally deleted the node).
        $this->acc->logActivity('Delete', 'Node Config', "Node:{$hwconfig->node_type}:{$hwconfig->node_address}");

        $integration_opts = json_decode($hwconfig->integration_opts, true);

        Eventy::action('node_config.delete', $hwconfig, [
            'integrations' => $integration_opts
        ]);

        Eventy::action("node_config." . strtolower($hwconfig->node_type) . ".delete", $hwconfig, [
            'integrations' => $integration_opts
        ]);

        hardware_config::where('id', $hwconfig->id)->delete();

        $result = ['message' => 'node_removed'];
        if ($grants) {
            $result['grants'] = $grants;
        }

        return response()->json($result);
    }

    public function exists(Request $request, $node_address)
    {
        return response()->json(['node_exists' => DB::table('hardware_config')->where('node_address', $node_address)->exists()]);
    }

    public function get_latest_coords(Request $request, $node_address)
    {
        // TODO: Add Permissions/Limit which nodes can be looked up? ... 

        $latt = null;
        $lng  = null;

        $result = [
            'status' => 'not_found',
            'latt' => null,
            'lng'  => null
        ];

        if (!$node_address) {
            Log::debug("Node address empty");
            return response()->json($result);
        }

        // try nutri_data table first (nutrient probes are the future)
        $row = DB::table('nutri_data')->where('node_address', $node_address)->orderBy('date_sampled', 'desc')->limit(1)->first();

        // try node_data next (soil moisture)
        if (empty($row->latt) && empty($row->lng)) {
            $row = DB::table('node_data')->where('probe_id', $node_address)->orderBy('date_time', 'desc')->limit(1)->first();
        }

        // node_data_meters doesnt have lat/lng fields

        // ensure non empty coords
        if (!empty($row->latt) && !empty($row->lng)) {
            $result['status'] = 'found';
            $result['latt']   = $row->latt;
            $result['lng']    = $row->lng;
        }

        return response()->json($result);
    }
}
