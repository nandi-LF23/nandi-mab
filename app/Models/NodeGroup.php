<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NodeGroup extends Model
{
    protected $table = 'groups_nodes';
    protected $primaryKey = 'id';
    protected $fillable = ['group_id', 'node_id'];
    protected $hidden = ['pivot'];
    
    public $timestamps = false;

    public function group()
    {
        return $this->hasOne('App\Models\Group');
    }

    public function node()
    {
        return $this->hasOne('App\Models\hardware_config');
    }
}
