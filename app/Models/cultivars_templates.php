<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class cultivars_templates extends Model
{
    protected $fillable = ['user_id', 'name', 'template', 'company_id'];
    protected $table = 'cultivars_templates';
    protected $publicKey = 'id';
    public $timestamps = true; // Ensure its PUBLIC - THIS COST ME 3 HOURS IN DEBUGGING "CORS" VIOLATIONS. WHAT THE FUCKKKKKK.
    protected $casts = [
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s'
    ];

    public function groups()
    {
        return $this->belongsToMany('App\Models\Group', 'groups_cultivars_templates', 'object_id', 'group_id');
    }

}
