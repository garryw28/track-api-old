<?php 
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Geofence extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'geofence';

    protected $guarded = ['id'];

}