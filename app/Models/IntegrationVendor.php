<?php 
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class IntegrationVendor extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'integration_vendor';

    protected $guarded = ['id'];

    protected $dates   = ['created_at', 'updated_at'];
}