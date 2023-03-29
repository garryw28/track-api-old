<?php 

namespace App\Models;


use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterVehicle extends Model {
    use SoftDeletes;
    use HasUUID;

    public $incrementing = false;
    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'MsVehicle';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];

    
    public function fleet_group(){
        return $this->hasMany('App\Models\MasterFleetGroup', 'id', 'fleet_group_id');
    }
}