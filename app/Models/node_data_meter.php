<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class node_data_meter extends Model
{
    protected $table = 'node_data_meters';
    protected $dates = ['date_time'];
    protected $primaryKey = 'idwm';

    protected $fillable = [
        'date_time','node_id', 'batt_volt', 'bp', 'power_state', 'pulse_1', 'pulse_2', 'state_of_measure_1', 'state_of_measure_2', 'pulse_1_mA', 'pulse_2_mA',
        'ultrasonic', 'message_id', 'deg_c'
    ];

    public $timestamps = false;

    protected $casts = [
        'date_time' => 'datetime:Y-m-d H:i:s'
    ];

}
