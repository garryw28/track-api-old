<?php 
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class MwMappingVendor extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'mw_mappingvendor';

    protected $guarded = ['id'];

    protected $dates   = ['device_time', 'server_time', 'created_at', 'updated_at'];

}