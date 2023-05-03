<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subsystem extends Model
{
    protected $table = 'subsystems';
    protected $primaryKey = 'id';
    protected $fillable = ['subsystem_name', 'group_table', 'resource_table'];
    protected $hidden = ['pivot'];
    public $timestamps = false;

    public function permissions()
    {
        return $this->belongsToMany('App\Models\Permission', 'subsystem_permissions', 'subsystem_id', 'permission_id');
    }

    public function groups()
    {
        return $this->hasMany('App\Models\Group');
    }

    public function security_rules()
    {
        return $this->hasMany('App\Models\SecurityRule');
    }
}
