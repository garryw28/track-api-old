<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\MasterVehicle;
use App\Models\ParserConfig;
use App\Models\MwMappingGeofence;
use App\Models\MwMapping;
use App\Models\MasterFleetGroup;
use App\Models\GeofenceVehicle;
use App\Helpers\Api;
use Carbon\Carbon;
use DB;

class DashboardController extends Controller
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

    public function totalVehicleParam(Request $request, $id = null){
        try{
            $fleetGroup  = $this->getArrayFleetGroup($id);
            $dataVehicle = MwMapping::whereIn('fleet_group_id', $fleetGroup);
            $dt = date('Y-m-d 00:00:01');
            // $maxNotUpdate = Carbon::now('UTC')->subHours(4);
            $maxNotUpdate = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - 60 * 60 * 1);
            $type = ['IDLE','STOP','MOVING'];
            
            if (in_array(strtoupper($request->input('type')), $type))
                $dataVehicle->where('vehicle_activity',strtoupper($request->input('type')))->where('device_time', '>', $maxNotUpdate);
                
            if ($request->input('type') == 'SILENT') 
                $dataVehicle->where('device_time', '<', $maxNotUpdate);
                
            if(strtoupper($request->input('type')) == 'GEOFENCE')
                $dataVehicle = GeofenceVehicle::whereIn('fleet_group_id', $fleetGroup)->where('out_of_geofence', 1)->where('update_time', '>', $dt);
            if($request->has('imei')) {
                $dataVehicle->select('imei')->where('imei', 'like', "%$request->imei%");
                if (strtoupper($request->input('type')) == 'GEOFENCE')
                    $dataVehicle->select('imei_obd_number')->where('imei_obd_number', 'like', "%$request->imei%");
            }
            
            if($request->has('license_plate'))
                $dataVehicle->select('license_plate')->where('license_plate', 'like', "%$request->license_plate%");

            $this->data     = $dataVehicle->limit(10)->get();
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function totalVehicle(Request $request, $id = null){
        /**
         * vehicle_status != suspend
         */
        try{
            $fleetGroup  = $this->getArrayFleetGroup($id);
            $dataVehicle = MwMapping::whereIn('fleet_group_id', $fleetGroup)->where('vehicle_status','!=','Suspend')->where('vehicle_status','!=','Inactive');
            if($request->has('imei'))
                $dataVehicle->where('imei', 'like', "%$request->imei%");
            if($request->has('license_plate'))
                $dataVehicle->where('license_plate', 'like', "%$request->license_plate%");
            
            $this->data     = $dataVehicle->paginate(10);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function geofenceViolation(Request $request, $id = null){
        try{
            $limit = (!empty($request->limit))?$request->limit:10;
            $dt = date('Y-m-d 00:00:01');
            $fleetGroup   = $this->getArrayFleetGroup($id);
            $dataGeofence = GeofenceVehicle::select('vehicle_id','license_plate','geofence', 'imei_obd_number', 'update_time', 'vin', 'fleet_group_name', 'driver_name', 'out_of_geofence')
                                            ->where('vehicle_status', '!=', 'Suspend')
                                            ->where('vehicle_status','!=','Inactive')
                                            ->whereNotNull('imei_obd_number')
                                            ->whereIn('fleet_group_id', $fleetGroup)
                                            ->where('update_time', '>', $dt)
                                            ->where('out_of_geofence', 1); 
            
            if($request->has('imei'))
                $dataGeofence->where('imei_obd_number', 'like', "%$request->imei%");
            if($request->has('license_plate'))
                $dataGeofence->where('license_plate', 'like', "%$request->license_plate%");
                
            $this->data     = $dataGeofence->groupBy('vehicle_id')->orderBy('device_time', 'DESC')->paginate($limit);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function movingVehicle(Request $request, $id = null){
        try{
            // $dt = Carbon::now('UTC')->subHours(4);
            $dt = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - 60 * 60 * 1);
            $fleetGroup   = $this->getArrayFleetGroup($id);
            $dataMoving   = MwMapping::where('vehicle_status', 'Activated')->where('vehicle_activity', 'MOVING')->where('device_time', '>', $dt)->whereIn('fleet_group_id', $fleetGroup);
            if($request->has('imei'))
                $dataMoving->where('imei', 'like', "%$request->imei%");
            if($request->has('license_plate'))
                $dataMoving->where('license_plate', 'like', "%$request->license_plate%");

            $this->data     = $dataMoving->paginate(10);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function idleVehicle(Request $request, $id = null){
        try{
            // $dt = Carbon::now('UTC')->subHours(4);
            $dt = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - 60 * 60 * 1);
            $fleetGroup   = $this->getArrayFleetGroup($id);
            $dataIdle     = MwMapping::where('vehicle_status', 'Activated')->where('vehicle_activity','IDLE')->where('device_time', '>', $dt)->whereIn('fleet_group_id', $fleetGroup);
            
            if($request->has('imei'))
                $dataIdle->where('imei', 'like', "%$request->imei%");
            if($request->has('license_plate'))
                $dataIdle->where('license_plate', 'like', "%$request->license_plate%");

            $this->data     = $dataIdle->paginate(10);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function stopVehicle(Request $request, $id = null){
        try{
            // $dt = Carbon::now('UTC')->subHours(4);
            $dt = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - 60 * 60 * 1);
            $fleetGroup   = $this->getArrayFleetGroup($id);
            $dataStop     = MwMapping::where('vehicle_status', 'Activated')->where('vehicle_activity','STOP')->where('device_time', '>', $dt)->whereIn('fleet_group_id', $fleetGroup); 
            
            if($request->has('imei'))
                $dataStop->where('imei', 'like', "%$request->imei%");
            if($request->has('license_plate'))
                $dataStop->where('license_plate', 'like', "%$request->license_plate%");

            $this->data     =  $dataStop->paginate(10);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function silentVehicle(Request $request, $id = null){
        try{
            // $maxNotUpdate = Carbon::now('UTC')->subHours(4);
            $maxNotUpdate = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - 60 * 60 * 1);
            $fleetGroup   = $this->getArrayFleetGroup($id);
            $dataSilent   = MwMapping::where('vehicle_status', 'Activated')->where('device_time', '<', $maxNotUpdate)->whereIn('fleet_group_id', $fleetGroup);

            if($request->has('imei'))
                $dataSilent->where('imei', 'like', "%$request->imei%");
            if($request->has('license_plate'))
                $dataSilent->where('license_plate', 'like', "%$request->license_plate%");
            
            $this->data     =  $dataSilent->paginate(10);
        }catch(\Exception $e){
            $this->status   = "false";
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