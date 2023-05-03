<?php

namespace App\Models;

use App\Traits\TraitUuid;

use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    use TraitUuid;

    protected $table = 'connections';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'type',
        'status',
        'started',
        'pid',
        'config'
    ];

    public $timestamps = false;

}
