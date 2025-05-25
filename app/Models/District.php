<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    //table is district
    protected $table = 'districts';

    //ignore timestamps
    public $timestamps = false;
    //fillable fields
    protected $fillable = [
        'name',
        'district_status',
        'region_id',
        'subregion_id',
        'map_id',
        'zone_id',
        'land_type_id',
        'user_id',
    ];
}
