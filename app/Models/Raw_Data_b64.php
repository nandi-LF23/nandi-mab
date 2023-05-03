<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Raw_Data_b64 extends Model
{
    protected $table = 'raw_data_b64';

    // fillable due to imports
    protected $fillable = [
        'device_id', 'b64_data', 'time_decoded', 'message_id', 'created_at', 'updated_at'
    ];

    protected $primaryKey = 'id';
    public $timestamps = true;
}
