<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'groups';
    protected $primaryKey = 'id';
    protected $fillable = ['group_name', 'company_id', 'subsystem_id'];
    protected $hidden = ['pivot'];
    
    public $timestamps = false;

    /*
        The only difference between hasOne and belongsTo is where the foreign key column is located.
        Let's say you have two entities: User and an Account. In short hasOne and belongsTo are inverses of one another.

        If one record belongTo the other, the other hasOne of the first.

        belongsToMany: Many-to-Many relationship
        hasMany: One-to-Many relationship
    */

    public function company()
    {
        return $this->belongsTo('App\Models\Company');
    }

    public function subsystem()
    {
        return $this->belongsTo('App\Models\Subsystem');
    }

    public function node_group()
    {
        return $this->belongsTo('App\Models\NodeGroup');
    }

    public function nodes()
    {
        return $this->belongsToMany('App\Models\hardware_config', 'groups_nodes', 'group_id', 'object_id');
    }

    public function sensors()
    {
        return $this->belongsToMany('App\Models\hardware_management', 'groups_sensors', 'group_id', 'object_id');
    }

    // for grouping cultivars_management records
    public function cultivars()
    {
        return $this->belongsToMany('App\Models\cultivars_management', 'groups_cultivars', 'group_id', 'object_id');
    }

    public function cultivar_stages()
    {
        return $this->belongsToMany('App\Models\cultivars', 'groups_cultivars_stages', 'group_id', 'object_id');
    }

    public function cultivar_templates()
    {
        return $this->belongsToMany('App\Models\cultivars_templates', 'groups_cultivars_templates', 'group_id', 'object_id');
    }

    public function nutrient_templates()
    {
        return $this->belongsToMany('App\Models\nutrient_templates', 'groups_nutrient_templates', 'group_id', 'object_id');
    }

    public function companies()
    {
        return $this->belongsToMany('App\Models\Company', 'groups_companies', 'group_id', 'object_id');
    }

    public function users()
    {
        return $this->belongsToMany('App\User', 'groups_users', 'group_id', 'object_id');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Models\Role', 'groups_roles', 'group_id', 'object_id');
    }

    // for grouping groups
    public function group()
    {
        return $this->belongsTo('App\Models\Group');
    }

    // for grouping security rules
    public function security_rules()
    {
        return $this->belongsToMany('App\Models\SecurityRule', 'groups_security_rules', 'group_id', 'object_id');
    }

    public function security_templates()
    {
        return $this->belongsToMany('App\Models\SecurityTemplate', 'groups_security_templates', 'group_id', 'object_id');
    }

    // for assigning groups to security rules
    public function security_rule()
    {
        return $this->belongsToMany('App\Models\SecurityRule', 'security_rules_groups', 'group_id', 'security_rule_id');
    }

}
