<?php 

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class ParserPowerBi extends Model {

    public $incrementing = true;
    public $primaryKey   = 'id';

    protected $table = 'ParserPowerBi';
    protected $guarded = ['id'];

    
    
}