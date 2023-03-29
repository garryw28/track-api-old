<?php 

namespace App\Models;


use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImoHistory extends Model {
    use SoftDeletes;
    use HasUUID;

    public $incrementing = false;
    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'ImoHistory';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];

    
    public function vehicle(){
		return $this->belongsTo('App\Models\MasterVehicle', 'vehicle_id', 'id');
    }
}