<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log';
    protected $primaryKey = 'id';
    protected $fillable = ['user_name', 'operation_id', 'subsystem_id', 'details', 'company_name', 'occurred'];
    protected $hidden = ['pivot'];
    public $timestamps = false;
}
