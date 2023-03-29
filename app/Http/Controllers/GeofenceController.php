<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Geofence as MasterGeofenceDB;
use App\Models\GeofenceVehicle;
use App\Helpers\Api;
use DB;

class GeofenceController extends Controller
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
            $this->data = MasterGeofenceDB::orderBy('updated_at','DESC')->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request){
		$rules = [
			'geofence_name'  => 'required',
            'type'           => 'required',
            'colour'           => 'required',
            'created_by'     => 'required',
            'geofence_detail' => 'required'
        ];

        $this->validate($request, $rules, ['required' => ':attribute tidak boleh kosong']);
		
		$data = array ( 
            'geofence_name'  => trim($request->geofence_name),
            'type'           => trim(strtoupper($request->type)),
            'colour'         => trim($request->colour),
            'created_by'     => trim($request->created_by),
            'radius'         => ($request->has('radius'))?(float)$request->radius:0
        );

        if ($request->has('is_red_zone'))
            $data['is_red_zone'] = trim($request->is_red_zone);
            
        try {
            $geofencedetail = [];
            $arraygeofence = $request->input('geofence_detail');
            $highestRow = count($arraygeofence);
        
            for ($row = 0; $row < $highestRow; $row++){
                $geofencedetail[] = array (
                    (float)trim(str_replace(",",".",$arraygeofence[$row]['longitude'])),
                    (float)trim(str_replace(",",".",$arraygeofence[$row]['latitude']))
                );
            }
            
            if (trim(strtoupper($request->type)) == 'CIRCLE') {
                $data['geofence_detail'] = ['type' => 'Point', 'coordinates' => $geofencedetail[0]];
            } else {
                $geofencedetail[] = $geofencedetail[0];
                $data['geofence_detail'] = ['type' => 'Polygon', 'coordinates' => [$geofencedetail]];
            }
            
            $this->data     = MasterGeofenceDB::create($data);
        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = MasterGeofenceDB::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        $rules = [
			'geofence_name'  => 'required',
            'colour'           => 'required',
            'geofence_detail' => 'required',
            'updated_by' => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);

        try{
            $getGeofence = MasterGeofenceDB::find($id);
            if(!empty($getGeofence)){
                $geofencedetail = [];
                $arraygeofence = $request->geofence_detail;
                $highestRow = count($arraygeofence);
                
                for ($row = 0; $row < $highestRow; $row++){
                    $geofencedetail[] = array (
                        (float)trim(str_replace(",",".",$arraygeofence[$row]['longitude'])),
                        (float)trim(str_replace(",",".",$arraygeofence[$row]['latitude']))
                    );
                }

                $geofencedetail[] = $geofencedetail[0];

                $data = [
                    'geofence_name' => $request->geofence_name,
                    'colour' => $request->colour,
                    'geofence_detail' => ['type' => 'Polygon', 'coordinates' => [$geofencedetail]],
                    'updated_by' => $request->updated_by
                ];

                if ($request->has('is_red_zone'))
                    $data['is_red_zone'] = trim($request->is_red_zone);

                if ($getGeofence->type == 'CIRCLE') {
                    $data['geofence_detail'] = ['type' => 'Point', 'coordinates' => $geofencedetail[0]];
                    $data['radius'] = ($request->has('radius'))?(float)$request->radius:(float)$getGeofence->radius;
                }
                    
                $getGeofenceVehicle = GeofenceVehicle::where('geofence.geofence_id', 'LIKE', '%'.$id.'%')->get();
                foreach ($getGeofenceVehicle as $gv) {
                    $rev_id = $gv->_id;
                    $geofence = $gv->geofence;

                    foreach ($geofence as $k => $gf) {
                        if($gf['geofence_id'] == $id){
                            $gf['geofence_name'] = $data['geofence_name'];
                            $gf['colour'] = $data['colour'];
                            $geofence[$k]= $gf;
                        }
                    }

                    $updategv = GeofenceVehicle::where('vehicle_id',$gv->vehicle_id)->update(['geofence' => $geofence]);                    
                }
            }
            
            $getGeofence = MasterGeofenceDB::where('_id',$id)->update($data);
            
            $this->data     = MasterGeofenceDB::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id=null){
        try{
            if(empty($id))
                return response()->json(Api::format($this->status, $this->data, "Error Processing Request. insert param first"), 200);

            DB::beginTransaction();
            $getGeofenceVehicle = GeofenceVehicle::where('geofence.geofence_id', $id)->get();
            foreach ($getGeofenceVehicle as $gv) {
                $rev_id = $gv->vehicle_id;
                $geofence = $gv->geofence;
                foreach ($geofence as $k => $gf) {
                    if($gf['geofence_id'] == $id)
                        unset($geofence[$k]);
                }
                
                if (empty($geofence)) {
                    $updategv = GeofenceVehicle::destroy($gv->_id);
                } else {
                    $updategv = GeofenceVehicle::where('vehicle_id',$rev_id)->update(['geofence' => array_values($geofence)]);
                }
            }
            
            $this->data =  MasterGeofenceDB::destroy($id);      
            DB::commit();
        }catch(\Exception $e){
            DB::rollback();
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

}