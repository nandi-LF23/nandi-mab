<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class nutri_data extends Model
{
    protected $table = 'nutri_data';

    protected $primaryKey = 'id';

    // fillable due to imports
    protected $fillable = [
        'node_address',
        'probe_serial',
        'vendor_model_fw',
        'ver',

'M0_1',
'M0_2',
'M0_3',
'M0_4',

'M1_1',
'M1_2',
'M1_3',
'M1_4',

'M2_1',
'M2_2',
'M2_3',
'M2_4',

'M3_1',
'M3_2',
'M3_3',
'M3_4',

'M4_1',
'M4_2',
'M4_3',
'M4_4',

'M5_1',
'M5_2',
'M5_3',
'M5_4',

'M6_1',
'M6_2',
'M6_3',
'M6_4',

'M7_1',
'M7_2',
'M7_3',
'M7_4',

'M8_1',
'M8_2',
'M8_3',
'M8_4',

'M9_1',
'M9_2',
'M9_3',
'M9_4',

        'date_reported',
        'date_sampled',
        'message_id',
        'bv',
        'bp',
        'latt',
        'lng',
        'ambient_temp'
    ];

    public $timestamps = false;
}
