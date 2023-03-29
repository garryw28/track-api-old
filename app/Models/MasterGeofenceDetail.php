<?php 

namespace App\Models;


use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterGeofenceDetail extends Model {
    use HasUUID;

    public $incrementing = false;
    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'MsGeofenceDetail';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];

    
    
}