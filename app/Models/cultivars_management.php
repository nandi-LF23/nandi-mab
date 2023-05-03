<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class cultivars_management extends Model
{
    protected $fillable = [ 'id','field_id','crop_type','crop_name','NI','NR','irrigation_type','updated_at','created_at', 'company_id' ];
    protected $primaryKey = 'id';
    protected $table = 'cultivars_management';
}
