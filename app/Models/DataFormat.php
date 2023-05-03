<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataFormat extends Model
{
    protected $table = 'dataformats';
    protected $primaryKey = 'id';

    protected $hidden = ['parser'];

    protected $fillable = [
        'name',
        'format',
        'node_type',
        'spec'
    ];

    public $timestamps = false;

}
