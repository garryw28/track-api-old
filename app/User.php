<?php

namespace App;

use App\Traits\HasUUID;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject as JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, HasUUID;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'MsUser';
   

    protected $fillable = [
        'id', 
        'name', 
        'email', 
        'password', 
        'no_telp', 
        'role_id', 
        'fleet_group_id', 
        'is_active', 
        'created_by',
        'updated_by',
        'device_token',
        'deactivate_date',
        'expired_date'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT
     *
     * @return mixed
     */
    public function getJWTIdentifier(){
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT
     *
     * @return array
     */
    public function getJWTCustomClaims(){
        return [];
    }

    public function role(){
		return $this->hasOne('App\Models\Role', 'id', 'role_id');
    }
    
    public function fleet_group(){
		return $this->hasOne('App\Models\MasterFleetGroup', 'id', 'fleet_group_id');
	}

}
