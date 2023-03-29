<?php 

namespace App\Models;


use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterGeofence extends Model {
    use SoftDeletes;
    use HasUUID;

    public $incrementing = false;
    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'MsGeofence';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];

    
    public function geofence_detail(){
    	return $this->hasMany('App\Models\MasterGeofenceDetail', 'geofence_id', 'id');
    }

    public function geofence_vehicle(){
    	return $this->hasMany('App\Models\MasterGeofenceVehicle', 'geofence_id', 'id');
    }
}