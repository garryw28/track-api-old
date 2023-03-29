<?php 
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class GeofenceVehicle extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'geofence_vehicle';

    protected $guarded = ['id'];

}