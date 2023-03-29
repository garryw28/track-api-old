<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\MasterVehicle as MasterVehicleDB;
use App\Models\GeofenceVehicle as MasterGeofenceVehicleDB;
use App\Models\Geofence;
use App\Helpers\Api;
use DB;

class GeofenceVehicleController extends Controller
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
            $this->data = MasterGeofenceVehicleDB::orderBy('updated_at','DESC')->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request){
		$rules = [
			'geofence_id' => 'required',
            'vehicle_id' => 'required',
            'fleet_group_id' => 'required',
            'license_plate' => 'required',
            'created_by' => 'required',
            // 'geofence' => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);

        try {
            $data = array ( 
                'fleet_group_id' => trim($request->input('fleet_group_id')),
                'vehicle_id' => trim($request->input('vehicle_id')),
                'license_plate' => trim($request->input('license_plate')),
                'created_by' => trim($request->input('created_by')),
                'geofence' => [trim($request->input('geofence_id'))]
            );
            $check = MasterGeofenceVehicleDB::where('vehicle_id', $data['vehicle_id'])->first();
    
            if (empty($check)) {
                $check = MasterGeofenceVehicleDB::create($data);
            } else {
                $geofencelist = $check->geofence;
                if(!in_array($data['geofence'][0], $geofencelist)){
                    array_push($geofencelist, $data['geofence'][0]);
                    $check = $check->update(['geofence' => $geofencelist]);
                } 
            }

            $this->data     = $check;
        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function storeBulk(Request $request){
        $rules = [
            'data_vehicle.*.vehicle_id' => 'required',
            'data_vehicle.*.license_plate' => 'required',
            'geofence_id' => 'required',
            'fleet_group_id' => 'required',
            'created_by' => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);

        try {
            $doInsert = [];
            $arrayvehicle = $request->input('data_vehicle');
            $highestRow = count($arrayvehicle);

            $getGeofence = Geofence::where('_id',$request->input('geofence_id'))->first();

            DB::beginTransaction();
            for ($row = 0; $row < $highestRow; $row++){
                $geofencedata = [
                    'geofence_id' => trim($request->input('geofence_id')),
                    'alert_in_zone' => (trim($request->input('alert_in_zone'))) ? 1 : 0,
                    'alert_out_zone' => (trim($request->input('alert_out_zone'))) ? 1 : 0,
                    'created_by' => trim($request->input('created_by')),
                    'geofence_name' => $getGeofence->geofence_name,
                    'type' => $getGeofence->type,
                    'is_red_zone' => ($getGeofence->is_red_zone)?$getGeofence->is_red_zone:0
                ];
                $data = array ( 
                    'vehicle_id' => trim($arrayvehicle[$row]['vehicle_id']),
                    'license_plate' => trim($arrayvehicle[$row]['license_plate']),
                    'fleet_group_id' => trim($request->input('fleet_group_id')),
                    'vin' => trim($arrayVehicle[$row]['vin']),
                    'imei_obd_number' => trim($arrayVehicle[$row]['imei_obd_number']),
                    'reff_vehicle_id' => trim($arrayVehicle[$row]['reff_vehicle_id']),
                    'geofence' => [$geofencedata]
                );

                $check = MasterGeofenceVehicleDB::where('vehicle_id', trim($arrayvehicle[$row]['vehicle_id']))->first();
                if (empty($check)) {
                    $MsVehicle = MasterVehicleDB::select('vin', 'imei', 'reff_vehicle_id')->where('id',(trim($arrayvehicle[$row]['vehicle_id'])));
                    
                    $data['vin'] = $MsVehicle->vin;
                    $data['imei'] = $MsVehicle->imei;
                    $data['reff_vehicle_id'] = $MsVehicle->reff_vehicle_id;
                   
                    $check = MasterGeofenceVehicleDB::create($data);
                } else {
                    $geofencelist = $check->geofence;
                    $getgeofencelist = [];
                    foreach ($geofencelist as $gf) {
                        array_push($getgeofencelist, $gf['geofence_id']);
                    }
                    
                    if(!in_array($geofencedata['geofence_id'], $getgeofencelist)){
                        array_push($geofencelist, $geofencedata);
                        $check = MasterGeofenceVehicleDB::where('_id', $check->_id)->update(['geofence' => $geofencelist]);
                    } 
                }
            }
            $this->data = $getGeofence;
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
            return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = MasterGeofenceVehicleDB::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function showGeofenceVehicle($id=null){
        try{
            $this->data     = MasterGeofenceVehicleDB::where('geofence.geofence_id',$id)->get();
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        $rules = [
			'geofence_id' => 'required',
            'vehicle_id' => 'required',
            'license_plate' => 'required',
            'fleet_group_id' => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);

        try{
            $fleetgroup = MasterGeofenceVehicleDB::find($id);
            if(!empty($fleetgroup))
                $fleetgroup->update($request->all());
                
            $this->data     = MasterGeofenceVehicleDB::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function updateBulk(Request $request, $id){
        $rules = [
            'data_vehicle.*.vehicle_id' => 'required',
            'data_vehicle.*.license_plate' => 'required',
            'data_vehicle.*.fleet_group_id' => 'required',
            'geofence_id' => 'required',
            'created_by' => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);

        $getGeofence = Geofence::select('geofence_name', 'type', 'colour', 'is_red_zone')->where('_id',$request->input('geofence_id'))->first();
        $getVehicle = MasterGeofenceVehicleDB::select('license_plate')->where('geofence.geofence_id', $id)->get()->toArray();
        $arrayVehicle = $request->input('data_vehicle');
        $old = [];
        $new = [];

        //-------check deleted geofence--------------//
        foreach ($getVehicle as $v) {
            array_push($old, $v['license_plate']);
        }

        foreach ($arrayVehicle as $v) {
            array_push($new, $v['license_plate']);
        }

        $deletedVehicle = array_diff($old, $new);    
        
        if (!empty($deletedVehicle)) {
            $getDeleted = MasterGeofenceVehicleDB::where('geofence.geofence_id', $id)->whereIn('license_plate',$deletedVehicle)->get();
            foreach ($getDeleted as $v) {
                $geofencelist = $v->geofence;
                $key = array_search($id, array_column($geofencelist, 'geofence_id'));
                
                unset($geofencelist[$key]);
                
                $update = MasterGeofenceVehicleDB::where('vehicle_id', $v->vehicle_id)->update(['geofence' => array_values($geofencelist)]);
            }
        }
        //------------------end--------------------//

        try{
            $highestRow = count($arrayVehicle);

            DB::beginTransaction();

            for ($row = 0; $row < $highestRow; $row++){
                $geofencedata = [
                    'geofence_id' => trim($request->input('geofence_id')),
                    'alert_in_zone' => trim($arrayVehicle[$row]['alert_in_zone']),
                    'alert_out_zone' => trim($arrayVehicle[$row]['alert_out_zone']),
                    'created_by' => trim($request->input('created_by')),
                    'geofence_name' => $getGeofence->geofence_name,
                    'type' => $getGeofence->type,
                    'colour' => $getGeofence->colour,
                    'is_red_zone' => ($getGeofence->is_red_zone)?$getGeofence->is_red_zone:0
                ];
                $data = array ( 
                    'vehicle_id' => trim($arrayVehicle[$row]['vehicle_id']),
                    'license_plate' => trim($arrayVehicle[$row]['license_plate']),
                    'fleet_group_id' => trim($arrayVehicle[$row]['fleet_group_id']),
                    'vin' => trim($arrayVehicle[$row]['vin']),
                    'imei_obd_number' => trim($arrayVehicle[$row]['imei_obd_number']),
                    'reff_vehicle_id' => trim($arrayVehicle[$row]['reff_vehicle_id']),
                    'geofence' => [$geofencedata]
                );

                $check = MasterGeofenceVehicleDB::where('vehicle_id', trim($arrayVehicle[$row]['vehicle_id']))->first();
                if (empty($check)) {
                    $check = MasterGeofenceVehicleDB::create($data);
                } else {
                    $geofencelist = $check->geofence;
                    $getgeofencelist = [];
                    foreach ($geofencelist as $k => $gf) {
                        $getgeofencelist[] = $gf['geofence_id'];
                        if ($gf['geofence_id'] == $id) {
                            $geofencelist[$k] = $geofencedata; 
                        }
                    }

                    if(!in_array($geofencedata['geofence_id'], $getgeofencelist)){
                        array_push($geofencelist, $geofencedata);   
                    } 

                    $data['geofence'] = array_values($geofencelist);
                    
                    $check = MasterGeofenceVehicleDB::where('vehicle_id', $check->vehicle_id)->update($data);
                }
            }
            $this->data = $getGeofence;
            DB::commit();
        }catch(\Exception $e){
            DB::rollback();
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id=null){
        try{
            if(!empty($id))
                $this->data =  MasterGeofenceVehicleDB::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    private function destroyGeofenceId($id){
        try{
            if(!empty($id))
                $this->data =  MasterGeofenceVehicleDB::where('geofence_id',$id)->delete();
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

}