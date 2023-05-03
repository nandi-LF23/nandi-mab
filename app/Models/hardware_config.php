<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class hardware_config extends Model
{
  protected $table = 'hardware_config';
  protected $primaryKey = 'id';
  protected $hidden = [ 'pivot' ];
  protected $fillable = [
    'node_type',
    'node_address',
    'probe_address',
    'latt',
    'lng',
    'hardware_management_id',
    'node_make',
    'commissioning_date',
    'node_serial_number',
    'device_serial_number',
    'company_id',
    'integration_opts',
    'coords_locked',
    'zone',
    'import_batch'
  ];

  public function groups()
  {
      return $this->belongsToMany('App\Models\Group', 'groups_nodes', 'object_id', 'group_id');
  }

  public function node_group()
  {
      return $this->belongsTo('App\Models\NodeGroup');
  }

  public function company()
  {
      return $this->belongsTo('App\Models\Company');
  }
}
