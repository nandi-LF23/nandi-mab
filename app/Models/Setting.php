<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use DB;

// Global App Settings (Key/Value) Persistent Pairs

class Setting extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'id';
    protected $fillable = ['key', 'value'];
    public $timestamps = false;

    public static function get($key, $default = NULL, $cacheTime = 300 /* 5 mins */)
    {
        // try cache first
        $value = Cache::get(config('mab.instance')."_{$key}");
        if($value){ return $value; }

        // cache miss, fetch from db
        $value = DB::table('settings')->where('key', $key)->value('value');

        // store in cache (for cacheTime seconds)
        if($value){
            Cache::put(config('mab.instance')."_{$key}", $value, $cacheTime);
        }

        // return value
        return $value ? $value : $default;
    }

    public static function set($key, $value, $cacheTime = 300)
    {
        // update cache
        Cache::put(config('mab.instance')."_{$key}", $value, $cacheTime);

        // update/create db
        return DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value]);
    }

    public static function del($key)
    {
        Cache::forget(config('mab.instance')."_{$key}");
        return DB::table('settings')->where('key', $key)->delete();
    }
}
