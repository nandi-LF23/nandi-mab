<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class nutrient_templates extends Model
{
    protected $fillable = ['id','user_id', 'name', 'template', 'company_id'];
    protected $table = 'nutrient_templates';
    protected $publicKey = 'id';
    public $timestamps = true;
    protected $casts = [
        'created_at'  => 'datetime:Y-m-d H:i:s',
        'updated_at'  => 'datetime:Y-m-d H:i:s'
    ];

    public function groups()
    {
        return $this->belongsToMany('App\Models\Group', 'groups_nutrient_templates', 'object_id', 'group_id');
    }
}
