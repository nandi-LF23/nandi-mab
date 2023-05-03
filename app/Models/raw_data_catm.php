<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class raw_data_catm extends Model
{
    protected $table = 'raw_data_catm';
    
    // fillable due to imports
    protected $fillable = [
        'device_id', 'device_data', 'created_at', 'updated_at'
    ];

    protected $primaryKey = 'id';
    public $timestamps = true;
}
