<?php

namespace App;

use App\Traits\HasUUID;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Tymon\JWTAuth\Contracts\JWTSubject as JWTSubject;

class UserIntegration extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, HasUUID;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'MsUserIntegration';
   

    protected $fillable = [
        'id', 
        'client_secret', 
        'vendor_id', 
        'password',
        'is_active', 
        'created_by',
        'updated_by',
        'expired_date',
        'device_token'
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

}
