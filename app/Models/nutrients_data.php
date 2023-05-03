<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class nutrients_data extends Model
{
    protected $table = 'nutrients_data';

    protected $primaryKey = 'id';

    // fillable due to imports
    protected $fillable = [
        'node_address',
        'probe_serial',
        'vendor_model_fw',
        'version',
        'identifier',
        'value',
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
