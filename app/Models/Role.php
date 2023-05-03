<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'id';
    protected $fillable = ['role_name', 'company_id'];
    protected $hidden = ['pivot'];
    public $timestamps = false;

    public function company()
    {
        return $this->belongsTo('App\Models\Company');
    }

    public function users()
    {
        return $this->hasMany('App\User');
    }

    public function groups()
    {
        return $this->belongsToMany('App\Models\Group', 'groups_roles', 'object_id', 'group_id');
    }

    public function security_rules()
    {
        return $this->hasMany('App\Models\SecurityRule');
    }
}
