<?php 

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class MasterPoi extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'poi';

    protected $guarded = ['id'];
    
}