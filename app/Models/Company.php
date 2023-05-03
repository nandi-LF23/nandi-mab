<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Company extends Model
{
    protected $table = 'companies';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'company_name',
        'company_logo',
        'contact_name',
        'contact_email',
        'contact_phone',
        'address_physical_line_1',
        'address_physical_line_2',
        'address_physical_city',
        'address_physical_postalcode',
        'address_physical_country',
        'address_billing_line_1',
        'address_billing_line_2',
        'address_billing_city',
        'address_billing_postalcode',
        'address_billing_country',
        'integrations',
        'is_locked'
    ];

    protected $hidden = ['pivot'];
    
    public $timestamps = true;

    public function roles()
    {
        return $this->hasMany('App\Models\Role');
    }

    public function groups()
    {
        return $this->hasMany('App\Models\Group');
    }

    public function group_members()
    {
        return $this->belongsToMany('App\Models\Group', 'groups_companies', 'object_id', 'group_id');
    }

    public function users()
    {
        return $this->hasMany('App\User');
    }

    public function sensors()
    {
        return $this->hasMany('App\Models\hardware_management');
    }

    public function nodes()
    {
        return $this->hasMany('App\Models\hardware_config');
    }

    public function security_rules()
    {
        return $this->belongsToMany('App\Models\SecurityRule', 'security_rules_companies', 'company_id', 'security_rule_id');
    }

    // Utility Functions

    public function move_company($new_company_id, $object_types)
    {
        if(!DB::table('companies')->where('id', $new_company_id)->exists()){
            return false;
        }
        // TODO: Complete or Remove
    }

    public function get_options()
    {
        return DB::table('company_options')
        ->where('id', $this->id)
        ->select(['slug', 'value'])
        ->get()
        ->keyBy('slug');
    }

    public function get_option($slug)
    {
        return DB::table('company_options')
        ->where('company_id', $this->id)
        ->where('slug', $slug)
        ->select('value')
        ->value('value');
    }

    public static function set_default_options($company_id)
    {
        $default_options = DB::table('options_specs')->select('slug', 'default')->get();

        foreach($default_options as $option){
            DB::table('company_options')->insert([
                'company_id' => $company_id, 
                'slug'       => $option->slug, 
                'value'      => $option->default
            ]);
        }
    }

    public static function get_default_options()
    {
        return DB::table('options_specs')
        ->select([
            'options_groups.name AS group_name',
            'options_specs.label',
            'options_specs.desc',
            'options_specs.slug',
            'options_specs.type',
            'options_specs.default as value'
        ])
        ->join('options_groups', 'options_specs.group_id', '=', 'options_groups.id')
        ->get()
        ->mapToGroups(function ($item, $key) {
            return [ $item->group_name => $item ];
        });
    }

    // Gets list of subsidiary company ids (child companies of current parent company)
    // Can optionally drill down indefinitely (recursively) to obtain all children
    // in the hierarchy.
    
    public static function get_subsidiary_ids($company_id, $drill_down = false)
    {
        $get_dist_ccs = function($company_id, $drill_down) use (&$get_dist_ccs){
            $managed_company_ids = [];

            $cc_ids = DB::table('distributors_companies')
            ->select(DB::raw('distinct company_id'))
            ->where('parent_company_id', $company_id)
            ->pluck('company_id')
            ->toArray();

            if($cc_ids){
                $managed_company_ids = $cc_ids;
                if($drill_down){
                    foreach($cc_ids as $cc_id){
                        $managed_company_ids = array_unique(
                            array_merge(
                                $managed_company_ids,
                                $get_dist_ccs($cc_id, $drill_down)
                            )
                        );
                    }
                }
            }

            return $managed_company_ids;
        };

        return $get_dist_ccs($company_id, $drill_down);
    }
}
