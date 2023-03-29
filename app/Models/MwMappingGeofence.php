<?php 
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class MwMappingGeofence extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'mw_mapping_geofence';

    protected $guarded = ['id'];

}