<?php 

namespace App\Models;


use App\Traits\HasUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterFleetGroup extends Model {
    use SoftDeletes;
    use HasUUID;

    public $incrementing = false;
    public $keyType      = 'string';
    public $primaryKey   = 'id';

    protected $table = 'MsFleetGroup';
    protected $guarded = ['id'];
    protected $dates = ['deleted_at'];

    public function alert_mapping(){
		  return $this->hasMany('App\Models\AlertMapping', 'fleet_group_id', 'id');
    }

    public function report_mapping(){
        return $this->hasMany('App\Models\MasterReportMapping', 'fleet_group_id', 'id');
    }

    public function child(){
        return $this->hasMany('App\Models\MasterFleetGroup', 'parent_id', 'id');
    }

    public function all_child()
    {
        return $this->child()->with('all_child');
    }

    public function parent()
    {
        return $this->belongsTo('App\Models\MasterFleetGroup', 'parent_id');
    }

    public function getParentsAttribute()
    {
        $parents = collect([]);

        $parent = $this->parent;

        while(!is_null($parent)) {
            $parents->push($parent);
            $parent = $parent->parent;
        }

        return $parents;
    }
    
}