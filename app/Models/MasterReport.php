<?php 

namespace App\Models;


use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterReport extends Model {
    use SoftDeletes;
    use HasUUID;

    public $incrementing = false;
    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'MsReport';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];
    
    public function power_bi(){
        return $this->belongsTo('App\Models\ParserPowerBi', 'parser_power_bi_id');
    }
}