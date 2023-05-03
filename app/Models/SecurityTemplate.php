<?php

namespace App\Models;

use Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use DB;

class SecurityTemplate extends Model
{
    protected $table = 'security_templates';
    protected $primaryKey = 'id';
    protected $fillable = [ 'template_name', 'template_data', 'company_id', 'created_at', 'updated_at' ];
    protected $hidden = ['pivot'];
    protected $dateFormat = 'Y-m-d H:i:s';
    public $timestamps = true;

    public function getCreatedAtAttribute($date)
    {
        if($date){
            return Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $date)->format('Y-m-d H:i:s');
        } else {
            return '';
        }
    }
    
    public function getUpdatedAtAttribute($date)
    {
        if($date){
            return Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $date)->format('Y-m-d H:i:s');
        } else {
            return '';
        }
    }
}
