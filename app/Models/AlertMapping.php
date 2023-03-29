<?php 

namespace App\Models;


use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlertMapping extends Model {
    use SoftDeletes;
    use HasUUID;

    public $incrementing = false;
    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'AlertMapping';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];

    public function alert(){
		return $this->belongsTo('App\Models\MasterAlert', 'alert_id', 'id');
    }

    public function fleet_group(){
      return $this->belongsTo('App\Models\MasterFleetGroup', 'fleet_group_id', 'id');
      }
    
}