<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class fields extends Model
{
    protected $fillable = [
        'id',
        'node_id',
        'field_name',
        'full',
        'refill',
        'graph_type',
        'graph_model',
        'ni',
        'nr',
        'wl_station_id',
        'wl_product_number',
        'nutrient_template_id',
        'install_depth',
        'perimeter',
        'zones'
    ];
    protected $table = 'fields';
    protected $primaryKey = 'id';
}
