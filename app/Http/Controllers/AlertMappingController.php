<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\AlertMapping as AlertMappingDB;
use App\Models\MasterFleetGroup as MasterFleetgroupDB;
use App\Helpers\Api;
use DB;

class AlertMappingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->status   = "true";
        $this->data     = [];
        $this->errorMsg = null;
    }

    public function index(){		
        try{
            $this->data = AlertMappingDB::orderBy('updated_at','DESC')->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request){
		$rules = [
            // 'alert_id' => 'required',
            'data_alert'  => 'required',
			'fleet_group_id' => 'required',
            'created_by' => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);

        try {

            $doInsert = [];
    		$arrayalert = $request->input('data_alert');
            $highestRow = count($arrayalert);

            DB::beginTransaction();
		
		    for ($row = 0; $row < $highestRow; $row++){
                $data = array ( 
                    'alert_id' => trim($arrayalert[$row]),
                    'fleet_group_id' => trim($request->input('fleet_group_id')),
                    'created_by' => trim($request->input('created_by'))
                );
                
               $doInsert[]  = AlertMappingDB::create($data);
            }
            $this->data = $doInsert;
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = AlertMappingDB::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        $rules = [
            'fleet_group_id' => 'required',
            'data_alert' => 'required',
            'created_by' => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);

        $this->destroyFleetGroupId(trim($request->input('fleet_group_id')));

        try{
            $doUpdate = [];
    		$arrayalert = $request->input('data_alert');
            $highestRow = count($arrayalert);
        
            DB::beginTransaction();

		    for ($row = 0; $row < $highestRow; $row++){
			    $data = array ( 
                    'fleet_group_id' => trim($request->input('fleet_group_id')),
                    'alert_id' => trim($arrayalert[$row]),
                    'created_by' => trim($request->input('created_by'))
                );
                $doUpdate[]  = AlertMappingDB::create($data);
            }
            $this->data  = $doUpdate;
            DB::commit();
        }catch(\Exception $e){
            DB::rollback();
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function delete($id=null){
        try{
            if(!empty($id))
                $this->data =  AlertMappingDB::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function getAllMapping(){
        try{
            $this->data = MasterFleetgroupDB::with(['alert_mapping','alert_mapping.alert'])->has('alert_mapping')->get();
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    private function destroyFleetGroupId($id=null){
        try{
            if(!empty($id))
                $this->data =  AlertMappingDB::where('fleet_group_id',$id)->delete();
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

}