<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\MasterGeofenceVehicle as MasterGeofenceVehicleDB;
use App\Models\GeofenceVehicle as GeofenceVehicleDB;
use App\Helpers\Api;
use App\Helpers\RestCurl;
use App\Models\MasterFleetGroup;
use App\Models\MwMapping;


class MonitoringController extends Controller
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

    public function getAllChild($id=null){
        try{
        $fleet_group = $this->getArrayFleetGroup($id);   
        $this->data = GeofenceVehicleDB::select('license_plate','imei_obd_number','vin','vehicle_id','geofence.colour','geofence.geofence_name', 'geofence.geofence_id', 'fleet_group_id')->whereIn('fleet_group_id', $fleet_group)->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function getAddress(Request $request){
        try{
            $result = "";
            // $getAddress = RestCurl::get(env('REVERSE_GEOCODE').'/reversegeocoding', ['lat' => $request->latitude, 'lng' => $request->longitude, 'format' => 'JSON']);
            
            // if ($getAddress['status'] != 200) {
            //     $this->status   = false;
            //     $this->errorMsg = $getAddress['data']->error;    
                
            //     return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
            // }

            // if(isset($getAddress['data']->result[0]) && !empty($getAddress['data']->result[0])){
            //     $address = $getAddress['data']->result[0];
            //     $result = $address->formatedFull;
            // }

            $this->data = $result;
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function clusterDetail($id=null){
        try{
            $this->data = MwMapping::find($id);
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function cluster($id=null){
        try{
            $fleet_group = $this->getArrayFleetGroup($id);
            $this->data = MwMapping::select('longitude', 'latitude', 'vehicle_activity', '_id', 'device_time')->whereIn('fleet_group_id', $fleet_group)
                                    ->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function filter(Request $request){
        $rules = [
            'fleet_group_id' => 'required',
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);

        try{

            $fleet_group = $this->getArrayFleetGroup($request->input('fleet_group_id'));
            if($request->input('geofence_id') != ''){

                $array_vehicle = GeofenceVehicleDB::select('vehicle_id')->whereIn('fleet_group_id', $fleet_group)->where('geofence.geofence_id', $request->geofence_id)->get()->toArray();

                $array_push = array();
                foreach($array_vehicle as $rank) {
                    $array_push[] = $rank['vehicle_id'];
                }

                $this->data  =  MwMapping::select('latitude', 'longitude','vehicle_activity', '_id', 'device_time')->whereIn('vehicle_id', $array_push)->get();

            }else {
                if($request->input('fleet_group_id') != ''){
                    $this->data  =  MwMapping::select('latitude', 'longitude','vehicle_activity', '_id', 'device_time')->whereIn('fleet_group_id', $fleet_group)->get();
                }
                if($request->input('imei_obd_number') != ""){
                    $this->data  =  MwMapping::select('latitude', 'longitude','vehicle_activity', '_id', 'device_time')->where('imei', $request->input('imei_obd_number'))->get();
                }
                if($request->input('vin') != ""){
                    $this->data  =  MwMapping::select('latitude', 'longitude','vehicle_activity', '_id', 'device_time')->where('vehicle_number', $request->input('vin'))->get();
                }
            }
        
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function childVehicle($id=null){
        try{
            $fleet_group = $this->getArrayFleetGroup($id);
            $this->data  =  MwMapping::select('vehicle_id','license_plate','imei','vehicle_number')->whereIn('fleet_group_id', $fleet_group)->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function filterLicensePlate(Request $request){
        $rules = [
            'license_plate' => 'required',
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);

        try{
            $array_licenseplate = $request->input('license_plate');

            $this->data  =  MwMapping::whereIn('vehicle_id', $array_licenseplate)->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);

    }

    private function getArrayFleetGroup($id = null){
        $fleet_group = MasterFleetgroup::where('id', $id)->with(['all_child'])->get()->toArray();
        $result_fleet_group = [];
        $result_fleet_group[] = $id;
        
        if (count($fleet_group[0]['all_child'])) {
            foreach ($fleet_group[0]['all_child'] as $v) {
                array_push($result_fleet_group, $v['id']);
                if (count($v['all_child'])) {
                    foreach ($v['all_child'] as $v2) {
                        array_push($result_fleet_group, $v2['id']);
                        if (count($v2['all_child'])) {
                            foreach ($v2['all_child'] as $v3) {
                                array_push($result_fleet_group, $v3['id']);
                                if (count($v3['all_child'])) {
                                    foreach ($v3['all_child'] as $v4) {
                                        array_push($result_fleet_group, $v4['id']);
                                        if (count($v4['all_child'])) {
                                            foreach ($v4['all_child'] as $v5) {
                                                array_push($result_fleet_group, $v5['id']);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result_fleet_group;
    }

}
