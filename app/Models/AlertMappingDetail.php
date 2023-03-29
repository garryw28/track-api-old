<?php 

namespace App\Models;


use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlertMappingDetail extends Model {
    use SoftDeletes;
    use HasUUID;

    public $incrementing = false; 
    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'AlertMappingDetail';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];

    
    public function alert_mapping(){
		return $this->belongsTo('App\Models\AlertMapping', 'alert_mapping_id', 'id');
    }
}