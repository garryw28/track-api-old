<?php 
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class GeofenceVehicleHistory extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'geofence_vehicle_history';

    protected $guarded = ['id'];

}