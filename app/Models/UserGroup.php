<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    protected $table = 'groups_users';
    protected $primaryKey = 'id';
    protected $fillable = ['group_id', 'user_id'];
    protected $hidden = ['pivot'];
    
    public $timestamps = false;

    public function group()
    {
        return $this->hasOne('App\Models\Group');
    }

    public function node()
    {
        return $this->hasOne('App\User');
    }
}
