<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class raw_data_weatherstation extends Model
{
    protected $table = 'raw_data_weatherstation';
    
    // fillable due to imports
    protected $fillable = [
        'station_id', 'station_data', 'sensor_data'
    ];

    protected $primaryKey = 'id';
    public $timestamps = true;
}
