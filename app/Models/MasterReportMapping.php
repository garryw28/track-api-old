<?php 

namespace App\Models;


use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterReportMapping extends Model {
    use SoftDeletes;
    use HasUUID;

    public $incrementing = false;
    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'MsReportMapping';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];
    
    public function report(){
        return $this->belongsTo('App\Models\MasterReport', 'report_id');
    }

    public function power_bi(){
        return $this->belongsTo('App\Models\ParserPowerBi', 'parser_power_bi_id');
    }

    public function fleet_group(){
        return $this->belongsTo('App\Models\MasterFleetGroup', 'fleet_group_id');
    }
}