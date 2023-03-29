<?php 

namespace App\Models;


use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model {
    use SoftDeletes;
    use HasUUID;

    public $incrementing = false;
    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'MsRole';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];


    public function role_menu(){
    	return $this->hasMany('App\Models\RoleMenu', 'role_id', 'id');
    }
    
    
}