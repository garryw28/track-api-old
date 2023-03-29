<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\MasterVehicle as MasterVehicleDB;
use App\Models\ParserConfig as ParserConfigDB;
use App\Models\GeofenceVehicle as MasterGeofenceVehicleDB;
use App\Models\VehicleMaintenance as VehicleMaintenanceDB;
use App\Models\MwMapping;
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

class VehicleController extends Controller
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
            $query = MasterVehicleDB::orderBy('updated_at','DESC');

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

    public function store(Request $request){
		$rules = [
            'license_plate'   => 'required',
            'vin'             => 'required|unique:MsVehicle,vin',
            'machine_number'  => 'required',
            'vehicle_brand'   => 'required',
            'vehicle_model'   => 'required',
            'vehicle_type'    => 'required',
            'vehicle_color'   => 'required',
            'vehicle_year'    => 'required',
            'stnk_date'       => 'required|date_format:"Y-m-d"',
            'installation_date' => 'required|date_format:"Y-m-d"',
            'odometer'        => 'required|integer',
            'imei_obd_number' => 'required|unique:MsVehicle,imei_obd_number',
            'simcard_number'  => 'required',
            'fleet_group_id'  => 'required',
            'created_by'      => 'required',
            'device_type_code'  => 'required',
            'device_model_code' => 'required',
            'device_group_code' => 'required',
            'fuel_ratio'      => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);

        try {
            $vehicleCheck = MasterVehicleDB::where('vin', $request->input('vin'))->orWhere('imei_obd_number', trim($request->input('imei_obd_number')))->first();
            if(!empty($vehicleCheck))
                return response()->json(Api::format("false", $vehicleCheck, "Error Processing Request. VIN/IMEI already exist"), 200);
            // BELUM CEK IMEI OBD NUMBER kETIKA DATA ADA AKAN ERROR
            $data = array ( 
                'license_plate'  => trim($request->input('license_plate')),
                'vin'            => trim($request->input('vin')),
                'machine_number' => trim($request->input('machine_number')),
                'vehicle_brand'  => trim($request->input('vehicle_brand')),
                'vehicle_model'  => trim($request->input('vehicle_model')),
                'vehicle_type'   => trim($request->input('vehicle_type')),
                'vehicle_color'  => trim($request->input('vehicle_color')),
                'vehicle_year'   => trim($request->input('vehicle_year')),
                'stnk_date'      => date('Y-m-d',strtotime(trim($request->input('stnk_date')))),
                'installation_date' => date('Y-m-d',strtotime(trim($request->input('installation_date')))),
                'odometer'       => trim($request->input('odometer')),
                'imei_obd_number'=> trim($request->input('imei_obd_number')),
                'simcard_number' => trim($request->input('simcard_number')),
                'fleet_group_id' => trim($request->input('fleet_group_id')),
                'fuel_ratio'     => trim($request->input('fuel_ratio')),
                'status'         => 1,
                'created_by'     => trim($request->input('created_by')),
                'end_points'     => trim($request->input('end_points')),
                'vehicle_category' => trim($request->input('vehicle_category')),
                'kir' => trim($request->input('kir')),
                "device_type_code"  => $request->device_type_code,
                "device_model_code" => $request->device_model_code,
                "device_group_code" => $request->device_group_code,
            );

            $end_point_array = explode(",",$request->input('end_points'));

            // PROVISIONING
            $provisioning = [
                "code"            => $request->imei_obd_number,
                "description"     => $request->license_plate,
                "vehicle_number"  => $request->vin,
                "device_type_code"  => $request->device_type_code,
                "device_model_code" => $request->device_model_code,
                "device_group_code" => $request->device_group_code,
                "end_point_codes" => $end_point_array
            ];

            if($request->has('driver_code'))
                $data['driver_code'] = trim($request->input('driver_code'));
            if($request->has('driver_name'))
                $data['driver_name'] = trim($request->input('driver_name'));
            if($request->has('driver_phone'))
                $data['driver_phone'] = trim($request->input('driver_phone'));

            $parserConfig = ParserConfigDB::where('env', env('PARSER_ENV'))->first();
            if(empty($parserConfig))
                throw new \Exception('Parser Configuration Not Found.');

            $createParse = RestCurl::post($parserConfig->server_url.'/api/v1/backend/devices_by_code', $provisioning, ['Authorization: Bearer '. $parserConfig->access_token]);
            if($createParse['status'] == 201)
                $data['reff_vehicle_id'] =  trim($createParse['data']->data->id);
            else
                throw new \Exception(json_encode($createParse['data']));

            $vehicle_data = MasterVehicleDB::create($data);
            
            $vehicle = MasterVehicleDB::join('MsFleetGroup', 'MsFleetGroup.id','=', 'MsVehicle.fleet_group_id')
                                    ->where('imei_obd_number', trim($request->input('imei_obd_number')))
                                    ->select('MsVehicle.*', 'MsFleetGroup.fleet_group_name')
                                    ->first();

            $data_mw_mapping = [
                'imei'                              => $request->imei_obd_number,
                'fleet_group_id'                    => $vehicle->fleet_group_id,
                'fleet_group_name'                  => $vehicle->fleet_group_name,
                'driver_code'                       => ($vehicle->driver_code)?$vehicle->driver_code:"",
                'driver_name'                       => ($vehicle->driver_name)?$vehicle->driver_name:"",
                'driver_phone'                      => ($vehicle->driver_phone)?$vehicle->driver_phone:"",
                'license_plate'                     => $vehicle->license_plate,
                'simcard_number'                    => $vehicle->simcard_number,
                'machine_number'                    => $vehicle->machine_number,
                'vehicle_number'                    => trim($request->input('vin')),
                'vehicle_brand'                     => $vehicle->vehicle_brand,
                'vehicle_model'                     => $vehicle->vehicle_model,
                'vehicle_id'                        => $vehicle->id,
                'fuel_consumed'                     => trim($request->input('fuel_ratio')),
                'device_time'                       => date('Y-m-d H:i:s'),
                'server_time'                       => date('Y-m-d H:i:s'),
                'device_id'                         => $data['reff_vehicle_id'],
                'vehicle_status'                    => 'Activation On Progress',
                'receive_message'                   => 0,
                'latitude'                          => "",
                'longitude'                         => "",
                'event_type'                        => "",
                'speed'                             => "",
                'direction'                         => "",
                'internal_battery_voltage'          => "",
                'external_power_voltage'            => "",
                'engine_coolant_temperature_x'      => "",
                'engine_speed_x'                    => "",
                'total_odometer'                    => "",
                'ignition'                          => "",
                'panic'                             => "",
                'dleft'                             => "",
                'dright'                            => "",
                'drear'                             => "",
                'fcamera'                           => "",
                'is_overstay'                       => ""
            ];

            $this->realtimeDB($vehicle->license_plate, $data_mw_mapping);

            $create_mw = MwMapping::create($data_mw_mapping);

            $this->data     = $vehicle_data;
        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function storeBulk(Request $request){
        $rules = [
            'data_vehicle.*.license_plate' => 'required',
            'data_vehicle.*.vin' => 'required',
            'data_vehicle.*.machine_number' => 'required',
            'data_vehicle.*.vehicle_brand' => 'required',
            'data_vehicle.*.vehicle_model' => 'required',
            'data_vehicle.*.vehicle_type' => 'required',
            'data_vehicle.*.vehicle_color' => 'required',
            'data_vehicle.*.vehicle_year' => 'required',
            'data_vehicle.*.stnk_date' => 'required|date',
            'data_vehicle.*.installation_date' => 'required',
            'data_vehicle.*.odometer' => 'required|integer',
            'data_vehicle.*.imei_obd_number' => 'required',
            'data_vehicle.*.simcard_number' => 'required',
            'data_vehicle.*.fleet_group_id' => 'required',
            'data_vehicle.*.fuel_ratio' => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);
        
        try {
                //get config parser
                $parserConfig = ParserConfigDB::where('env', env('PARSER_ENV'))->first();
                if(empty($parserConfig))
                    throw new \Exception('Parser Configuration Not Found.');

                $arrayvehicle = $request->input('data_vehicle');
                $highestRow = count($arrayvehicle);

                DB::beginTransaction();

                for ($row = 0; $row < $highestRow; $row++){
                    $vehicleCheck = MasterVehicleDB::where('vin', trim($arrayvehicle[$row]['vin']))->first();
                    if(empty($vehicleCheck)){
                        $data = array ( 
                            'license_plate'  => trim($arrayvehicle[$row]['license_plate']),
                            'vin'            => trim($arrayvehicle[$row]['vin']),
                            'machine_number' => trim($arrayvehicle[$row]['machine_number']),
                            'vehicle_brand'  => trim($arrayvehicle[$row]['vehicle_brand']),
                            'vehicle_model'  => trim($arrayvehicle[$row]['vehicle_model']),
                            'vehicle_type'   => trim($arrayvehicle[$row]['vehicle_type']),
                            'vehicle_color'  => trim($arrayvehicle[$row]['vehicle_color']),
                            'vehicle_year'   => trim($arrayvehicle[$row]['vehicle_year']),
                            'stnk_date'      => date('Y-m-d',strtotime(trim($arrayvehicle[$row]['stnk_date']))),
                            'installation_date' => date('Y-m-d',strtotime(trim($arrayvehicle[$row]['installation_date']))),
                            'odometer'        => trim($arrayvehicle[$row]['odometer']),
                            'imei_obd_number' => trim($arrayvehicle[$row]['imei_obd_number']),
                            'simcard_number'  => trim($arrayvehicle[$row]['simcard_number']),
                            'fleet_group_id'  => trim($arrayvehicle[$row]['fleet_group_id']),
                            'fuel_ratio'      => trim($arrayvehicle[$row]['fuel_ratio']),
                            'kir'             => trim($arrayvehicle[$row]['kir']),
                            'status'          => 1,
                            'created_by'      => trim($request->input('created_by')),
                            'end_points'     => trim($request->input('end_points')),
                            "device_type_code"  => trim($arrayvehicle[$row]['device_type_code']),
                            "device_model_code" => trim($arrayvehicle[$row]['device_model_code']),
                            "device_group_code" => trim($arrayvehicle[$row]['device_group_code']),
                            "vehicle_category" => trim($request->input('vehicle_category'))
                        );

                        $end_point_array = explode(",",$request->input('end_points'));
    
                        // PROVISIONING
                        $provisioning = [
                            "code"            => trim($arrayvehicle[$row]['imei_obd_number']),
                            "description"     => trim($arrayvehicle[$row]['license_plate']),
                            "vehicle_number"  => trim($arrayvehicle[$row]['vin']),
                            "device_type_code"  => trim($arrayvehicle[$row]['device_type_code']),
                            "device_model_code" => trim($arrayvehicle[$row]['device_model_code']),
                            "device_group_code" => trim($arrayvehicle[$row]['device_group_code']),
                            "end_point_codes" => $end_point_array
                        ];

                        if(!empty($arrayvehicle[$row]['driver_code']))
                            $data['driver_code'] = $arrayvehicle[$row]['driver_code'];
                        if(!empty($arrayvehicle[$row]['driver_name']))
                            $data['driver_name'] = $arrayvehicle[$row]['driver_name'];
                        if(!empty($arrayvehicle[$row]['driver_phone']))
                            $data['driver_phone'] = $arrayvehicle[$row]['driver_phone'];
                        
                        $createParse = RestCurl::post($parserConfig->server_url.'/api/v1/backend/devices_by_code', $provisioning, ['Authorization: Bearer '. $parserConfig->access_token]);
                        if($createParse['status'] == 201)
                            $data['reff_vehicle_id'] =  trim($createParse['data']->data->id);
                        else
                            throw new \Exception(json_encode($createParse['data']));
    
                        $this->data[]   = MasterVehicleDB::create($data);

                        $vehicle = MasterVehicleDB::join('MsFleetGroup', 'MsFleetGroup.id','=', 'MsVehicle.fleet_group_id')
                                    ->where('imei_obd_number', trim($arrayvehicle[$row]['imei_obd_number']))
                                    ->where('vin', trim($arrayvehicle[$row]['vin']))
                                    ->select('MsVehicle.*', 'MsFleetGroup.fleet_group_name')
                                    ->first();

                        $data_mw_mapping = [
                            'imei'                              => trim($arrayvehicle[$row]['imei_obd_number']),
                            'fleet_group_id'                    => $vehicle->fleet_group_id,
                            'fleet_group_name'                  => $vehicle->fleet_group_name,
                            'driver_code'                       => ($vehicle->driver_code)?$vehicle->driver_code:"",
                            'driver_name'                       => ($vehicle->driver_name)?$vehicle->driver_name:"",
                            'driver_phone'                      => ($vehicle->driver_phone)?$vehicle->driver_phone:"",
                            'license_plate'                     => $vehicle->license_plate,
                            'simcard_number'                    => $vehicle->simcard_number,
                            'machine_number'                    => $vehicle->machine_number,
                            'vehicle_number'                    => trim($arrayvehicle[$row]['vin']),
                            'vehicle_brand'                     => $vehicle->vehicle_brand,
                            'vehicle_model'                     => $vehicle->vehicle_model,
                            'vehicle_id'                        => $vehicle->id,
                            'fuel_consumed'                     => trim($arrayvehicle[$row]['fuel_ratio']),
                            'device_time'                       => date('Y-m-d H:i:s'),
                            'server_time'                       => date('Y-m-d H:i:s'),
                            'device_id'                         => $data['reff_vehicle_id'],
                            'vehicle_status'                    => 'Activation On Progress',
                            'receive_message'                   => 0,
                            'latitude'                          => "",
                            'longitude'                         => "",
                            'event_type'                        => "",
                            'speed'                             => "",
                            'direction'                         => "",
                            'internal_battery_voltage'          => "",
                            'external_power_voltage'            => "",
                            'engine_coolant_temperature_x'      => "",
                            'engine_speed_x'                    => "",
                            'total_odometer'                    => "",
                            'ignition'                          => "",
                            'panic'                             => "",
                            'dleft'                             => "",
                            'dright'                            => "",
                            'drear'                             => "",
                            'fcamera'                           => "",
                            'is_overstay'                       => ""
                        ];

                        $this->realtimeDB($vehicle->license_plate, $data_mw_mapping);
                        
                        $create_mw = MwMapping::create($data_mw_mapping);
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                $this->status   = "false";
                $this->errorMsg = $e->getMessage();
                return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
            }
                
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
		
    }

    public function category(){
        $this->data = ['Bus', 'Passanger', 'Truck'];

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
            $this->data     = MasterVehicleDB::whereIn('fleet_group_id',$fleetGroup)->get();
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function updateStatusBulk(Request $request, $vin){
        $rules = [
            'license_plate' => 'required',
            'status' => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);
        
        try {
            $status = $request->input('status');
            $MsVehicle = MasterVehicleDB::where('vin', $vin)->first();
            if (!empty($MsVehicle)) {
                $update_master = [
                    'status' => $status,
                    'end_points' => ""
                ];

                switch ($status) {
                    case '1':
                        if ($MsVehicle['status'] == 0) 
                            return response()->json(Api::format("false", $this->data, "Vehicle is inactive"), 200);

                        if (empty($request->input('end_points')))
                            return response()->json(Api::format("false", $this->data, "Error Processing Request. end point is empty"), 200);
                    
                        $end_point_array = explode(",",$request->input('end_points'));
                        
                        $update_master['end_points'] = $request->input('end_points');
                        $update_mw['vehicle_status'] = "Activation On Progress";
                        $update_mw['receive_message'] = 0;
                        break;
                    case '2':
                        $count = VehicleMaintenanceDB::where('vehicle_id',$MsVehicle->id)->where('status',0)->count();
                        if($count > 0){
                        return response()->json(Api::format("false", $this->data, "Data Vehicle Has Been Suspended"), 200);
                        }
                        $data = array ( 
                            'vehicle_id'             => trim($MsVehicle->id),
                            'license_plate'          => trim($MsVehicle->license_plate),
                            'description'            => "Dismantle",
                            'fleet_group_id'         => trim($MsVehicle->fleet_group_id),
                            'vin'                    => trim($MsVehicle->vin),
                            'machine_number'         => trim($MsVehicle->machine_number),
                            'imei_old'               => trim($MsVehicle->imei_obd_number),
                            'start_date_maintenance' => date('Y-m-d H:i:s'),
                            'status'                 => 0,
                            'created_by'             => trim($request->updated_by)
                        );
                        
                        $doInsert =  VehicleMaintenanceDB::create($data);
                        
                        $update_master['end_points'] = '';
                        $update_master['imei_obd_number'] = null;
                        $update_master['reff_vehicle_id'] = null;
                        $end_point_array = [''];
                        $update_mw['vehicle_status'] = 'Suspend';
                        $update_mw['suspend_date'] = date('Y-m-d H:i:s');
                        $update_mw['suspend_by'] = $request->updated_by;
                        $update_mw['imei'] = '';
                        $update_mw['receive_message'] = 0;
                        break;
                    default:
                        $update_master['end_points'] = '';
                        $update_master['imei_obd_number'] = '';
                        $end_point_array = [''];
                        $update_mw['vehicle_status'] = 'Inactive';
                        $update_mw['imei'] = '';
                        break;
                }
		
		        $provisioning = [
                    "code"            => $MsVehicle['imei_obd_number'],
                    "description"     => $MsVehicle['license_plate'],
                    "vehicle_number"  => $MsVehicle['vin'],
                    "device_type_code"  => $MsVehicle['device_type_code'],
                    "device_model_code" => $MsVehicle['device_model_code'],
                    "device_group_code" => $MsVehicle['device_group_code'],
                    "end_point_codes" => $end_point_array
                ];

                //get config parser
                $parserConfig = ParserConfigDB::where('env', env('PARSER_ENV'))->first();
                if(empty($parserConfig))
                     throw new \Exception('Parser Configuration Not Found.');
                
                $createParse = RestCurl::put($parserConfig->server_url.'/api/v1/backend/devices_by_code/'.$MsVehicle->reff_vehicle_id, $provisioning, ['Authorization: Bearer '. $parserConfig->access_token]);
                if($createParse['status'] == 200){
                    $data['reff_vehicle_id'] =  trim($createParse['data']->data->id);
                }else{
                    throw new \Exception(json_encode($createParse['data']));
                }
		    
                $updateVehicle = MasterVehicleDB::where('vin', $vin)->update($update_master);
                
                $MwMapping = MwMapping::where('vehicle_number', $vin)->update($update_mw);       
            }

            $this->data = $MsVehicle;
        } catch (\Exeption $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        $rules = [
            'license_plate' => 'required',
            'vin' => 'required',
            'machine_number' => 'required',
            'vehicle_brand' => 'required',
            'vehicle_model' => 'required',
            'vehicle_type' => 'required',
            'vehicle_color' => 'required',
            'vehicle_year' => 'required',
            'stnk_date' => 'required|date_format:"Y-m-d"',
            'installation_date' => 'required|date_format:"Y-m-d"',
            'odometer' => 'required|integer',
            'status' => 'required|integer',
            'imei_obd_number' => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);

        try{
            $MsVehicle = MasterVehicleDB::find($id);
            $vehicle = MasterGeofenceVehicleDB::where('vehicle_id',$id)->first();
            $mw_mapping = MwMapping::where('vehicle_id',$id)->get()->toArray();
            $ms_fleet_group = MasterFleetGroup::find($request->fleet_group_id);
            $data = $request->all();
            $end_point_array = explode(",",$request->input('end_points'));
            $vehicle_update = [
                'license_plate' => $request->license_plate,
                'vehicle_number' => $request->vin,
                'receive_message' => 0,
                'vehicle_brand' => $request->vehicle_brand,
                'vehicle_model' => $request->vehicle_model,
                'fleet_group_id' => $request->fleet_group_id,
                'fleet_group_name' => $ms_fleet_group['fleet_group_name'],
                'simcard_number' => $request->simcard_number,
                'machine_number' => $request->machine_number
            ];
            
            if(!empty($MsVehicle)){    
                
                switch ($request->status) {
                    case '1':
                        if (empty($request->input('end_points'))) {
                            return response()->json(Api::format("false", $this->data, "Endpoint is empty"), 200);
                        }

                        if ($mw_mapping[0]['vehicle_status'] != 'Activated') {
                            $vehicle_update['vehicle_status'] = 'Activation On Progress';
                        } else {
                            unset($vehicle_update['receive_message']);
			                $vehicle_update['vehicle_status'] = 'Activated';
                        }
                        
                        break;
                    case '2':
                        $count = VehicleMaintenanceDB::where('vehicle_id',$MsVehicle->id)->where('status',0)->count();
                        if($count > 0){
                        	return response()->json(Api::format("false", $this->data, "Data Vehicle Has Been Suspended"), 200);
                        }
                        $data = array ( 
                            'vehicle_id'             => trim($id),
                            'license_plate'          => trim($request->license_plate),
                            'description'            => "Dismantle",
                            'fleet_group_id'         => trim($request->fleet_group_id),
                            'vin'                    => trim($request->vin),
                            'machine_number'         => trim($request->machine_number),
                            'imei_old'               => trim($request->imei_obd_number),
                            'start_date_maintenance' => date('Y-m-d H:i:s'),
                            'status'                 => 0,
                            'created_by'             => trim($request->updated_by)
                        );
                        
                        $doInsert =  VehicleMaintenanceDB::create($update_maintenance);
                        
                        $data['end_points'] = '';
                        $data['imei_obd_number'] = null;
                        $data['reff_vehicle_id'] = null;
                        $end_point_array = [''];
                        $vehicle_update['vehicle_status'] = 'Suspend';
                        $vehicle_update['suspend_date'] = date('Y-m-d H:i:s');
                        $vehicle_update['suspend_by'] = $request->updated_by;
                        $vehicle_update['imei'] = '';
                        $vehicle_update['receive_message'] = 0;
                        break;
                    default:
                        $data['end_points'] = '';
                        $data['imei_obd_number'] = '';
                        $end_point_array = [''];
                        $vehicle_update['vehicle_status'] = 'Inactive';
                        $vehicle_update['imei'] = '';
                        break;
                }
		    
		        $provisioning = [
                    "code"            => $request->imei_obd_number,
                    "description"     => $request->license_plate,
                    "vehicle_number"  => $request->vin,
                    "device_type_code"  => $request->device_type_code,
                    "device_model_code" => $request->device_model_code,
                    "device_group_code" => $request->device_group_code,
                    "end_point_codes" => $end_point_array
                ];

                 //get config parser
                 $parserConfig = ParserConfigDB::where('env', env('PARSER_ENV'))->first();
                 if(empty($parserConfig))
                     throw new \Exception('Parser Configuration Not Found.');
                
                $createParse = RestCurl::put($parserConfig->server_url.'/api/v1/backend/devices_by_code/'.$MsVehicle->reff_vehicle_id, $provisioning, ['Authorization: Bearer '. $parserConfig->access_token]);
                if($createParse['status'] != 200){ 
                    throw new \Exception(json_encode($createParse['data']));
		        }
		    
                $MsVehicle->update($data);                
                
                $data['vehicle_status'] = $vehicle_update['vehicle_status'];

                if ($mw_mapping[0]['vehicle_number'] != $request->vin) {
                    $update_mw = array_merge($mw_mapping[0], $vehicle_update);
                    unset($update_mw['_id']);

                    $Obj = MwMapping::where('vehicle_number', $mw_mapping[0]['vehicle_number'])->delete();
                    $data = MwMapping::create($update_mw);
                } else {
                    $Obj = MwMapping::where('vehicle_number', $mw_mapping[0]['vehicle_number'])->update($vehicle_update);
                }

                if (!empty($vehicle))
                    $vehicle = MasterGeofenceVehicleDB::where('vehicle_id',$id)->update($vehicle_update);
            }
            
            $this->data     = MasterVehicleDB::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id=null){
        try{
            if(!empty($id))
                $this->data =  MasterVehicleDB::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function updateBulk(Request $request, $old_vin){
        $rules = [
            'license_plate' => 'required',
            'old_vin' => 'required',
            'new_vin' => 'required',
            'machine_number' => 'required',
            'vehicle_brand' => 'required',
            'vehicle_model' => 'required',
            'vehicle_type' => 'required',
            'vehicle_color' => 'required',
            'vehicle_year' => 'required',
            'stnk_date' => 'required|date_format:"Y-m-d"',
            'installation_date' => 'required|date_format:"Y-m-d"',
            'odometer' => 'required|integer',
            'fleet_group_id' => 'required',
        ];

        $customMessages = ['required' => ':attribute tidak boleh kosong'];
        
        $this->validate($request, $rules, $customMessages);

        try{
            $MsVehicle = MasterVehicleDB::where('vin',$old_vin)->where('status', 1)->first();
            $vehicle = MasterGeofenceVehicleDB::where('vehicle_id',$MsVehicle['id'])->first();
            $mw_mapping = MwMapping::where('vehicle_id',$MsVehicle['id'])->get()->toArray();
            $parserConfig = ParserConfigDB::where('env', env('PARSER_ENV'))->first();
            $MsFleetGroup = MasterFleetGroup::find($request->fleet_group_id);
            
            $data = $request->all();
            $end_point_array = explode(",",$MsVehicle['end_points']);

            if(empty($MsVehicle))
                throw new \Exception('Vehicle Not Found.');
            if(empty($parserConfig))
                throw new \Exception('Parser Configuration Not Found.');
            
            $provisioning = [
                "code"            => $mw_mapping[0]['imei'],
                "description"     => $request->license_plate,
                "vehicle_number"  => $request->new_vin,
                "device_type_code"  => $request->device_type_code,
                "device_model_code" => $request->device_model_code,
                "device_group_code" => $request->device_group_code,
                "end_point_codes" => $end_point_array
            ];
            
            $core_update = [
                'license_plate' => $request->license_plate,
                'machine_number' => $request->machine_number,
                'vehicle_brand' => $request->vehicle_brand,
                'vehicle_model' => $request->vehicle_model,
                'driver_name' => ($request->has('driver_name'))?$request->driver_name:" ",
                'driver_code' => ($request->has('driver_code'))?$request->driver_code:" ",
                'driver_phone' => ($request->has('driver_phone'))?$request->driver_phone:" ",
                'simcard_number' => $request->simcard_number, 
            ];

            $update_master = [
                'vin' => $request->new_vin,
                'vehicle_type' => $request->vehicle_type,
                'vehicle_color' => $request->vehicle_color,
                'vehicle_year' => $request->vehicle_year,
                'stnk_date' => $request->stnk_date,
                'installation_date' => $request->installation_date,
                'odometer' => $request->odometer,
                'driver_name' => ($request->has('driver_name'))?$request->driver_name:" ",
                'driver_code' => ($request->has('driver_code'))?$request->driver_code:" ",
                'driver_phone' => ($request->has('driver_phone'))?$request->driver_phone:" ",
                'simcard_number' => $request->simcard_number,
                'fuel_ratio' => $request->fuel_ratio,
                'vehicle_category' => $request->vehicle_category,
                'kir' => $request->kir,
                'updated_by' => $request->updated_by
            ];

            $update_mw = [
                'vehicle_number' => $request->new_vin,
                'receive_message' => 0, 
                'fleet_group_id' => $request->fleet_group_id,
                'fleet_group_name' => $MsFleetGroup['fleet_group_name']
            ];

            $update_geo_vehicle = [
                'vin' => $request->new_vin,
                'license_plate' => $request->license_plate,
                'fleet_group_id' => $request->fleet_group_id,
                'fleet_group_name' => $MsFleetGroup['fleet_group_name']
            ];
            
            $list_update_master = array_merge($core_update, $update_master);
            $list_update_mw = array_merge($core_update, $update_mw);

            foreach ($list_update_master as $key => $value) {
                if (empty($value)) 
                    unset($list_update_master[$key]);
            }
            foreach ($list_update_mw as $key => $value) {
                if (empty($value)) 
                    unset($list_update_mw[$key]);
            }

            if (!empty($vehicle))
                $vehicle = MasterGeofenceVehicleDB::where('vehicle_id',$MsVehicle->id)->update($update_geo_vehicle);

            if ($mw_mapping[0]['vehicle_number'] != $request->new_vin) {    
                $new_mw = array_merge($mw_mapping[0], $list_update_mw);

                unset($list_update_mw['_id']);

                $Obj = MwMapping::where('vehicle_number', $mw_mapping[0]['vehicle_number'])->delete();
                $data = MwMapping::create($new_mw);
            } else {
                $Obj = MwMapping::where('vehicle_number', $mw_mapping[0]['vehicle_number'])->update($list_update_mw);
            }

            $createParse = RestCurl::put($parserConfig->server_url.'/api/v1/backend/devices_by_code/'.$MsVehicle->reff_vehicle_id, $provisioning, ['Authorization: Bearer '. $parserConfig->access_token]);
            if($createParse['status'] != 200)
                throw new \Exception(json_encode($createParse['data']));
            
            $MsVehicle->update($list_update_master);            
            
            $this->data     = MasterVehicleDB::where('vin',$request->new_vin)->first();
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

    private function realtimeDB($topic, $message){
        
        //Store in realtime database
        $serviceAccount = ServiceAccount::fromJsonFile(base_path().'/public/firebase_token.json');
        $firebase = (new Factory)
                    ->withServiceAccount($serviceAccount)
                    ->withDatabaseUri(env('REALTIME_DB'))
                    ->createDatabase();

        // $database = $firebase->getDatabase();
        $newPost  = $firebase
                    ->getReference('tracking/'.$topic)
                    ->set($message);

        $doStore = $newPost->getKey();
    }
}
