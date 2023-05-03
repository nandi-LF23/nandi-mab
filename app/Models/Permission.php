<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';
    protected $primaryKey = 'id';
    protected $fillable = [ 'permission_name' /*, 'subsystem_id'*/ ];
    protected $hidden = ['pivot'];
    public $timestamps = false;

    // public function subsystems()
    // {
    //     return $this->belongsToMany('App\Models\Subsystem', 'subsystem_permissions', 'permission_id', 'subsystem_id');
    // }

    public function security_rule()
    {
        return $this->belongsToMany('App\Models\SecurityRule', 'security_rules_permissions', 'permission_id', 'security_rule_id');
    }
}
