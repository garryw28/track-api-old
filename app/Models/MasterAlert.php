<?php 

namespace App\Models;


use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterAlert extends Model {
    use SoftDeletes;
    use HasUUID;

    public $incrementing = false;
    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'MsAlert';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];

    public function alert_mapping(){
		return $this->hasMany('App\Models\AlertMapping', 'alert_id', 'id');
    }
    
}