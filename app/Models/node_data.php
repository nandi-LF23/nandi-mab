<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class node_data extends Model
{
    protected $table = 'node_data';
    protected $dates = ['date_time'];
    protected $primaryKey = 'id';
    
    // fillable due to imports
    protected $fillable = [
        'probe_id','date_time','average','accumulative',
        'sm1','sm2','sm3','sm4','sm5','sm6','sm7','sm8','sm9','sm10','sm11','sm12','sm13','sm14','sm15',
        't1','t2','t3','t4','t5','t6','t7','t8','t9','t10','t11','t12','t13','t14','t15',
        'rg','bv','bp','latt','lng','ambient_temp','message_id_1','message_id_2'
    ];

    public $timestamps = false;

    protected $casts = [
        'date_time' => 'datetime:Y-m-d H:i:s'
    ];

}
