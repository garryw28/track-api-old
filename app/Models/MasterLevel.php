<?php 

namespace App\Models;


use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterLevel extends Model {

    public $incrementing = false;
    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'MsLevel';
    protected $guarded = ['id'];

    
    
}