<?php 
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class MwMappingHistory extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'mw_mapping_historynew';

    protected $guarded = ['id'];

}