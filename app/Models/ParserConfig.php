<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParserConfig extends Model {

    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'ParserConfig';
    protected $guarded = ['id'];

}