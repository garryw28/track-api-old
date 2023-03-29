<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\MasterVehicle as MasterVehicleDB;
use App\Models\ParserConfig as ParserConfigDB;
use App\Models\GeofenceVehicle as MasterGeofenceVehicleDB;
use App\Models\VehicleMaintenance as VehicleMaintenanceDB;
use App\Models\MwMappingSecond;
use App\Models\MasterFleetGroup;
use App\Helpers\Api;
use App\Helpers\RestCurl;
use Carbon\Carbon;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;
use Kreait\Firebase\Messaging\Message;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use DB;

class VehicleSecondObdController extends Controller
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

    public function index(Request $request){		
        try{
            $limit = ($request->limit)?$request->limit:10; 
            $query = MasterVehicleDB::where('is_second_obd',1)->orderBy('updated_at','DESC');

            if ($request->license_plate)
                $query->where('license_plate', 'like', "%$request->license_plate%");
            if ($request->imei)
                $query->where('imei_obd_number', 'like', "%$request->imei%");

            $this->data = $query->paginate($limit);
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = MasterVehicleDB::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function showFleetGroup($id=null){
        try{
            $fleetGroup   = $this->getArrayFleetGroup($id);
            $this->data     = MasterVehicleDB::whereIn('fleet_group_id',$fleetGroup)->where('is_second_obd',1)->get();
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        $rules = [
           'status' => 'required',
           'is_second_obd' => 'required',
           'imei_obd_second' => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);

        try{
            $MsVehicle = MasterVehicleDB::find($id);
            $parserConfig = ParserConfigDB::where('env', env('PARSER_ENV'))->first();
            $vehicle_update = [
                'simcard_number_second' => $request->simcard_number_second,
                'status' => $request->status,
                'is_second_obd' => $request->is_second_obd 
            ];
            
            if(!empty($MsVehicle)){

                if($request->is_second_obd == '1') {

                    switch ($request->status) {
                        case '1':
                            $end_points_secondobd = ['MONICA'];
                            $vehicle_update['end_points_secondobd'] = 'MONICA';
                            $vehicle_update['imei_obd_second'] = $request->imei_obd_second;
                            break;
                        case '2':
                            $count = VehicleMaintenanceDB::where('vehicle_id',$id)->where('imei_old',$MsVehicle->imei_obd_second)->where('status',0)->count();
                            if($count > 0){
                              return response()->json(Api::format("false", $this->data, "Data Vehicle Has Been Suspended"), 200);
                            }
                            $update_maintenance = array ( 
                                'vehicle_id'             => trim($id),
                                'license_plate'          => trim($MsVehicle->license_plate),
                                'description'            => "Dismantle",
                                'fleet_group_id'         => trim($MsVehicle->fleet_group_id),
                                'vin'                    => trim($MsVehicle->vin),
                                'machine_number'         => trim($MsVehicle->machine_number),
                                'imei_old'               => trim($MsVehicle->imei_obd_second),
                                'start_date_maintenance' => date('Y-m-d H:i:s'),
                                'status'                 => 0,
                                'created_by'             => trim($request->updated_by)
                            );
                            
                            $doInsert =  VehicleMaintenanceDB::create($update_maintenance);
                            
                            $vehicle_update['end_points_secondobd'] = '';
                            $vehicle_update['imei_obd_second'] = null;
                            $vehicle_update['reff_vehicle_idsecond'] = null;
                            $end_points_secondobd = [''];
                            break;
                        default:
                            $vehicle_update['end_points_secondobd'] = '';
                            $vehicle_update['imei_obd_second'] = '';
                            $end_points_secondobd = [''];
                            break;
                    }
                    
                    $provisioning = [
                        "code"            => $request->imei_obd_second,
                        "description"     => $MsVehicle->license_plate,
                        "vehicle_number"  => $MsVehicle->vin,
                        "device_type_code"  => $MsVehicle->device_type_code,
                        "device_model_code" => $MsVehicle->device_model_code,
                        "device_group_code" => $MsVehicle->device_group_code,
                        "end_point_codes" => $end_points_secondobd
                    ];
    
                    // $mwResult = RestCurl::get($parserConfig->server_url.'/api/v1/backend/devices',["filter[code]" => $request->imei_obd_second], ['Authorization: Bearer '. $parserConfig->access_token]);
                    
                    // if (!empty($mwResult['data']->data)) {
                    //     $reff_vehicle_idsecond = $mwResult['data']->data[0]->id;
                        
                    //     $createParse = RestCurl::put($parserConfig->server_url.'/api/v1/backend/devices_by_code/'.$reff_vehicle_idsecond, $provisioning, ['Authorization: Bearer '. $parserConfig->access_token]);
                    //     if($createParse['status'] == 200) {
                    //         if (!empty($end_points_secondobd)) {
                    //             $vehicle_update['reff_vehicle_idsecond'] =  trim($createParse['data']->data->id);
                    //         }
                    //     } else {
                    //         throw new \Exception(json_encode($createParse['data']));
                    //     }
                    // }else {
                    //     $createParse = RestCurl::post($parserConfig->server_url.'/api/v1/backend/devices_by_code', $provisioning, ['Authorization: Bearer '. $parserConfig->access_token]);
                    //     if($createParse['status'] == 201) {
                    //         $reff_vehicle_idsecond =  trim($createParse['data']->data->id);
                    //         if (!empty($end_points_secondobd)) {
                    //             $vehicle_update['reff_vehicle_idsecond'] =  $reff_vehicle_idsecond;
                    //         }
                    //     } else {
                    //         throw new \Exception(json_encode($createParse['data']));
                    //     }
                    // }
    
                    $MsVehicle->update($vehicle_update);

                }else{
                    $end_points_secondobd = [''];

                    $data = [
                        'simcard_number_second' => null,
                        'is_second_obd' => 0,
                        'imei_obd_second' => null,
                        'reff_vehicle_idsecond' => null,
                        'end_points_secondobd' => null
                    ];

                    $provisioning = [
                        "code"            => $MsVehicle->imei_obd_second,
                        "description"     => $MsVehicle->license_plate,
                        "vehicle_number"  => $MsVehicle->vin,
                        "device_type_code"  => $MsVehicle->device_type_code,
                        "device_model_code" => $MsVehicle->device_model_code,
                        "device_group_code" => $MsVehicle->device_group_code,
                        "end_point_codes" => $end_points_secondobd
                    ];

                    // $createParse = RestCurl::put($parserConfig->server_url.'/api/v1/backend/devices_by_code/'.$MsVehicle->reff_vehicle_idsecond, $provisioning, ['Authorization: Bearer '. $parserConfig->access_token]);
                    // if($createParse['status'] == 200) {
                    //     $vehicle_update['reff_vehicle_idsecond'] =  trim($createParse['data']->data->id);
                    // } else {
                    //     throw new \Exception(json_encode($createParse['data']));
                    // }

                    $MsVehicle->update($data);
                }                
            }
            $this->data     = $MsVehicle;
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
