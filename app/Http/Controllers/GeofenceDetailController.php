<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\MasterGeofenceDetail as MasterGeofenceDetailDB;
use App\Helpers\Api;
use DB;

class GeofenceDetailController extends Controller
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
            $this->data = MasterGeofenceDetailDB::orderBy('updated_at','DESC')->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request){		
		$rules = [
            'data_geofence.*.latitude'  => 'required',
            'data_geofence.*.longitude' => 'required',
			'geofence_id' => 'required',
            'created_by' => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);
        
        try {
            $doInsert = [];
    		$arraygeofence = $request->input('data_geofence');
            $highestRow = count($arraygeofence);

            DB::beginTransaction();
		
		    for ($row = 0; $row < $highestRow; $row++){
                $data = array ( 
                    'geofence_id' => trim($request->input('geofence_id')),
                    'latitude'    => trim($arraygeofence[$row]['latitude']),
                    'longitude'   => trim($arraygeofence[$row]['longitude']),
                    'created_by'  => trim($request->input('created_by'))
                );
                
               $doInsert[]  = MasterGeofenceDetailDB::create($data);
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
            $this->data     = MasterGeofenceDetailDB::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function showGeofence($id=null){
        try{
            $this->data     = MasterGeofenceDetailDB::where('geofence_id', $id)->get();
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        $rules = [
			'geofence_id' => 'required',
            'data_geofence.*.latitude' => 'required',
            'data_geofence.*.longitude' => 'required',
            'created_by' => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);

        $this->destroyGeofenceId(trim($request->input('geofence_id')));
        
        try {
            $doUpdate = [];
    		$arraygeofence = $request->input('data_geofence');
            $highestRow = count($arraygeofence);
        
            DB::beginTransaction();

		    for ($row = 0; $row < $highestRow; $row++){
			    $data = array ( 
                    'geofence_id' => trim($request->input('geofence_id')),
                    'latitude' => trim($arraygeofence[$row]['latitude']),
                    'longitude' => trim($arraygeofence[$row]['longitude']),
                    'created_by' => trim($request->input('created_by'))
                );
                $doUpdate[]  = MasterGeofenceDetailDB::create($data);
            }
            $this->data  = $doUpdate;
            // print_r($this->data);die();
            DB::commit();
		} catch (\Exception $e) {
            DB::rollback();
			$this->status   = "false";
			$this->errorMsg = $e->getMessage();
		}			
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);		
    }

    public function destroy($id=null){
        try{
            if(!empty($id))
                $this->data =  MasterGeofenceDetailDB::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }
	
	private function destroyGeofenceId($id=null){
        try{
            if(!empty($id))
                $this->data =  MasterGeofenceDetailDB::where('geofence_id',$id)->delete();
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

}