<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class hardware_management extends Model
{
  protected $table = 'hardware_management';
  protected $primaryKey = 'id';
  protected $hidden = [ 'pivot' ];
  protected $fillable = [
    /* Same values as Node Type (Soil Moisture/Nutrients/Wells/Water Meter) */
    'device_type',
    'device_make',
    'device_category',
    'company_id',

    /* Soil Moisture/Nutrients */
    'device_length',
    'sensor_placing_1','sensor_placing_2','sensor_placing_3',
    'sensor_placing_4','sensor_placing_5','sensor_placing_6',
    'sensor_placing_7','sensor_placing_8','sensor_placing_9',
    'sensor_placing_10','sensor_placing_11','sensor_placing_12',
    'sensor_placing_13','sensor_placing_14','sensor_placing_15',
    'sensor_config', // for nutrient probes

    /* Wells/Meters */
    'diameter',
    'pulse_weight',
    'measurement_type',
    'application_type'
  ];

  public $timestamps = false;

  public function groups()
  {
      return $this->belongsToMany('App\Models\Group', 'groups_sensors', 'object_id', 'group_id');
  }

  public function company()
  {
      return $this->belongsToMany('App\Models\Company');
  }
}
