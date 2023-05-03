<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A Cultivar Stage (badly named)
class cultivars extends Model
{
    protected $fillable = ['id', 'cultivars_management_id', 'stage_name', 'stage_start_date', 'duration', 'upper', 'lower', 'created_at', 'updated_at', 'company_id'];
    protected $primaryKey = 'id';
    protected $table = 'cultivars';
    protected $hidden = ['created_at', 'updated_at'];
    
    public $timestamps = true;

    public function groups()
    {
        return $this->belongsToMany('App\Models\Group', 'groups_cultivars_stages', 'object_id', 'group_id');
    }
}
