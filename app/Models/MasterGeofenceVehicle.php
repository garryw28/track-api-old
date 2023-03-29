<?php 

namespace App\Models;


use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterGeofenceVehicle extends Model {
    use HasUUID;

    public $incrementing = false;
    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'MsGeofenceVehicle';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];

    
    
}