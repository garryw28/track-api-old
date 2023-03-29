<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Models\MasterVehicle;
use App\Models\MasterGeofenceVehicle;
use App\Models\MasterGeofence;
use App\Models\MasterGeofenceDetail;
use App\Models\MasterFleetGroup;
use App\Models\MwMapping;
use App\Models\MwMappingSecond;
use App\Models\MwMappingHistory;
use App\Models\MwMappingGeofence;
use App\Models\MasterAlert;
use App\Models\AlertMapping;
use App\Models\Geofence;
use App\Models\GeofenceVehicle;
use App\User;
use App\Helpers\Api;
use Carbon\Carbon;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;
use Kreait\Firebase\Messaging\Message;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use App\Helpers\RestCurl;
use App\Helpers\WindowsAzure;
use DB, Log;

class MwMappingController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    static protected $temp;

    public function __construct()
    {
        $this->status   = "true";
        $this->data     = [];
        $this->errorMsg = null;
        self::$temp = array();
    }

    public function index($type){		
        try{
            if ($type == 'total') {
                $vehicle = MwMapping::select('fleet_group_name','device_time','vehicle_number', 'license_plate', 'imei', 'receive_message', 'simcard_number', 'vehicle_brand', 'vehicle_model', 'driver_name', 'vehicle_status', 'latitude', 'longitude');
            } else {
                $maxNotUpdate = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - 60 * 60 * 1);
                $list_type = ['IDLE','STOP','MOVING'];
                $vehicle   = MwMapping::select('fleet_group_name','license_plate', 'vehicle_number', 'imei', 'driver_name', 'driver_phone', 'device_time', 'latitude', 'longitude')
                            ->where('vehicle_status', 'Activated')
                            ->whereNotNull('latitude');
            }
            
            if (in_array(strtoupper($type), $list_type))
                $vehicle->where('vehicle_activity',strtoupper($type))->where('device_time', '>', $maxNotUpdate);
            
            if (strtoupper($type) == 'SILENCE') 
                $vehicle->where('device_time', '<', $maxNotUpdate);
            
            $this->data     =  $vehicle->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function cluster(Request $request, $fleet_group_id){		
        try{
            $this->data = MwMapping::select('license_plate','latitude', 'longitude', 'fleet_group_id', 'fleet_group_name')
                                   ->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request){
        try {
            // get vehicle
            $vehicle = MasterVehicle::join('MsFleetGroup', 'MsFleetGroup.id','=', 'MsVehicle.fleet_group_id')
                                    ->where('imei_obd_number', $request->imei)
                                    ->orWhere('imei_obd_second', $request->imei)
                                    ->select('MsVehicle.*', 'MsFleetGroup.fleet_group_name')
                                    ->first();
            
            if(empty($vehicle))
                return response()->json(Api::format("false", $this->data, "Error Processing Request. vehicle not found"), 200);

            // SECOND OBD
            if($vehicle->imei_obd_second == $request->imei){
                $this->storeSecondObd($vehicle,$request->all()); 
                $this->data = $vehicle;
                return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
            }

            if ($vehicle->status == 2) 
                $vehicle->update(['status' => 1]);
                     
            if ($vehicle->vehicle_category == 'Passanger') {
                $main_battery_status = ((float)$request->external_power_voltage < 12)?'ABNORMAL':'NORMAL';
            } else {
                $main_battery_status = ((float)$request->external_power_voltage < 24)?'ABNORMAL':'NORMAL';
            }

            self::$temp = [
                'device_id'                         => $request->device_id, 
                'imei'                              => $request->imei,
                'device_type'                       => $request->device_type,
                'device_model'                      => $request->device_model,
                'priority'                          => $request->priority,
                'latitude'                          => $request->latitude,
                'longitude'                         => $request->longitude,
                'location'                          => $request->location,
                'altitude'                          => $request->altitude,
                'direction'                         => $request->direction,
                'speed'                             => $request->speed,
                'satellite'                         => $request->satellite,
                'accuracy'                          => $request->accuracy,
                'dtc_number'                        => $request->dtc_number,
                'lac'                               => $request->lac,
                'gps_pdop'                          => $request->gps_pdop,
                'gps_hdop'                          => $request->gps_hdop,
                'gsm_signal_level'                  => $request->gsm_signal_level,
                'trip_odometer'                     => $request->trip_odometer,
                'total_odometer'                    => $request->total_odometer,
                'external_power_voltage'            => $request->external_power_voltage,
                'internal_battery_voltage'          => $request->internal_battery_voltage,
                'internal_battery_current'          => $request->internal_battery_current,
                'cell_id'                           => $request->cell_id,
                'pto_state'                         => $request->pto_state,
                'engine_total_fuel_used'            => $request->engine_total_fuel_used,
                'fuel_level_1_x'                    => $request->fuel_level_1_x,
                'server_time'                       => date("Y-m-d H:i:s", strtotime($request->server_time)),
                'device_time'                       => date("Y-m-d H:i:s", strtotime($request->device_time)),
                'device_timestamp'                  => $request->device_timestamp/1000,
                'engine_total_hours_of_operation_x' => $request->engine_total_hours_of_operation_x,
                'service_distance'                  => $request->service_distance,
                'at_least_pto_engaged'              => $request->at_least_pto_engaged,
                'eco_driving_type'                  => $request->eco_driving_type,
                'eco_driving_value'                 => $request->eco_driving_value,
                'wheel_based_speed'                 => $request->wheel_based_speed,
                'accelerator_pedal_position'        => $request->accelerator_pedal_position,
                'engine_percent_load'               => $request->engine_percent_load,
                'engine_speed_x'                    => $request->engine_speed_x,
                'tacho_vehicle_speed_x'             => $request->tacho_vehicle_speed_x,
                'engine_coolant_temperature_x'      => $request->engine_coolant_temperature_x,
                'instantaneous_fuel_economy_x'      => $request->instantaneous_fuel_economy_x,
                'digital_input_1'                   => $request->digital_input_1,
                'digital_input_2'                   => $request->digital_input_2,
                'digital_input_3'                   => $request->digital_input_3,
                'digital_input_4'                   => $request->digital_input_4,
                'sensor'                            => $request->sensor,
                'ignition'                          => $request->ignition,
                'crash_detection'                   => $request->crash_detection,
                'geofence_zone_01'                  => $request->geofence_zone_01,
                'digital_output_1'                  => $request->digital_output_1, 
                'digital_output_2'                  => $request->digital_output_2,
                'gps_status'                        => $request->gps_status,
                'movement_sensor'                   => $request->movement_sensor,
                'data_mode'                         => $request->data_mode,
                'deep_sleep'                        => $request->deep_sleep,
                'analog_input_1'                    => $request->analog_input_1,
                'gsm_operator'                      => $request->gsm_operator,
                'dallas_temperature_1'              => $request->dallas_temperature_1,
                'dallas_temperature_2'              => $request->dallas_temperature_2,
                'dallas_temperature_3'              => $request->dallas_temperature_3,
                'dallas_temperature_4'              => $request->dallas_temperature_4,
                'dallas_id_1'                       => $request->dallas_id_1,
                'dallas_id_2'                       => $request->dallas_id_2,
                'dallas_id_3'                       => $request->dallas_id_3,
                'dallas_id_4'                       => $request->dallas_id_4,
                'event'                             => $request->event,
                'event_type_id'                     => $request->event_type_id,
                'event_type'                        => $request->event_type,
                'telemetry'                         => $request->telemetry,
                // additional value
                'is_blackbox'                       => $request->has('is_blackbox') ? $request->is_blackbox : 0,
                'fleet_group_id'                    => $vehicle->fleet_group_id,
                'fleet_group_name'                  => $vehicle->fleet_group_name,
                'driver_code'                       => ($vehicle->driver_code)?$vehicle->driver_code:"",
                'driver_name'                       => ($vehicle->driver_name)?$vehicle->driver_name:"",
                'driver_phone'                      => ($vehicle->driver_phone)?$vehicle->driver_phone:"",
                'license_plate'                     => $vehicle->license_plate,
                'simcard_number'                    => $vehicle->simcard_number,
                'machine_number'                    => $vehicle->machine_number,
                'vehicle_number'                    => $vehicle->vin,
                'vehicle_brand'                     => $vehicle->vehicle_brand,
                'vehicle_model'                     => $vehicle->vehicle_model,
                'vehicle_id'                        => $vehicle->id,
                'fuel_consumed'                     => $request->total_odometer / $vehicle->fuel_ratio,
                'vehicle_status'                    => 'Activated',
                'receive_message'                   => 1,
                'main_battery_status'               => $main_battery_status,
                'engine_temperature_status'         => ((float)$request->engine_coolant_temperature_x > 100)?'ABNORMAL':'NORMAL',
                'engine_rpm_status'                 => ((float)$request->engine_speed_x > 2500)?'ABNORMAL':'NORMAL',
                'coordinate'                        => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float)trim(str_replace(",",".",$request->longitude)),
                        (float)trim(str_replace(",",".",$request->latitude))
                    ]
                ]
            ];
            // check vehicle activity
            self::checkVehicleActivity($request->all());

            if (self::$temp['is_blackbox'] == 0) {
                $checkMapping = MwMapping::where('vehicle_number', $vehicle->vin)->first();                                 
                if(isset($checkMapping->total_subscriber) && !empty($checkMapping->total_subscriber)){                    
                    
                    self::$temp['panic'] = ($checkMapping->panic)?$checkMapping->panic:"";
                    self::$temp['dleft'] = ($checkMapping->dleft)?$checkMapping->dleft:"";
                    self::$temp['dright'] = ($checkMapping->dright)?$checkMapping->dright:"";
                    self::$temp['drear'] = ($checkMapping->drear)?$checkMapping->drear:"";
                    self::$temp['fcamera'] = ($checkMapping->fcamera)?$checkMapping->fcamera:"";
                    self::$temp['is_overstay'] = ($checkMapping->is_overstay)?$checkMapping->is_overstay:"";

                    self::checkDriverCondition($checkMapping);
                    self::checkAlertActivity(self::$temp['event_type'],$request->digital_input_3, strtotime($checkMapping->device_time));

                    $this->realtimeDB($vehicle->license_plate, self::$temp);
                }
                
                if(empty($checkMapping)){
                    //insert
                    $data = MwMapping::create(self::$temp);
                }else{
                    //update vehicle activity
                    if (!empty($checkMapping->vehicle_activity)) {
                        if (!empty(self::$temp['vehicle_activity']) && (self::$temp['vehicle_activity'] != $checkMapping->vehicle_activity))
                            self::$temp['activity_date'] = date('Y-m-d H:i:s');
                    }
                    //update
                    $Obj = MwMapping::where('vehicle_number',$checkMapping->vehicle_number)->update(self::$temp);
                    $data = self::$temp;
                }

                //check alert vehicle
                // self::checkAlertVehicle($vehicle, $request->all());
                self::checkAlertVehicle($vehicle, self::$temp);

                 // insert to history service bus
                self::$temp['location_coordinate'] = ['type' => 'Point', 'coordinates' => [$request->longitude, $request->latitude]]; 
                self::$temp['verified_date'] = "";
                self::$temp['verified_by'] = "";
                
                MwMappingHistory::create(self::$temp);
            }

            // insert to history service bus
            self::$temp['location_coordinate'] = ['type' => 'Point', 'coordinates' => [$request->longitude, $request->latitude]];
                
            WindowsAzure::sendQueueMessage(env('SERVICE_BASH_HISTORY_URL'), env('SERVICE_BASH_HISTORY_DB'), self::$temp);
            
            // ADD Service Bus SELOG
            if(!empty($vehicle->user_integration_id)){
               WindowsAzure::sendQueueMessage(env('SERVICE_BASH_INTEGRATION_URL'), env('SERVICE_BASH_INTEGRATION_DB'), self::$temp);
            }

            // check checkGeofence 
            self::checkGeofence($vehicle['id'], ['longitude' => $request->longitude, 'latitude' => $request->latitude]);
    
            return response()->json(Api::format($this->status, $data, $this->errorMsg), 200);
        }catch(\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
            Log::error($e);

            return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 500);
        }
        
    }

    public function storeSecondObd($vehicle,$value){
        self::$temp = [
            'device_id'                         => $value['device_id'], 
            'imei'                              => $value['imei'],
            'device_type'                       => $value['device_type'],
            'device_model'                      => $value['device_model'],
            'vehicle_number'                    => $vehicle->vin,
            'priority'                          => $value['priority'],
            'latitude'                          => $value['latitude'],
            'longitude'                         => $value['longitude'],
            'location'                          => $value['location'],
            'altitude'                          => $value['altitude'],
            'direction'                         => $value['direction'],
            'speed'                             => $value['speed'],
            'satellite'                         => $value['satellite'],
            'accuracy'                          => $value['accuracy'],
            'dtc_number'                        => $value['dtc_number'],
            'lac'                               => $value['lac'],
            'gps_pdop'                          => $value['gps_pdop'],
            'gps_hdop'                          => $value['gps_hdop'],
            'gsm_signal_level'                  => $value['gsm_signal_level'],
            'trip_odometer'                     => $value['trip_odometer'],
            'total_odometer'                    => $value['total_odometer'],
            'external_power_voltage'            => $value['external_power_voltage'],
            'internal_battery_voltage'          => $value['internal_battery_voltage'],
            'internal_battery_current'          => $value['internal_battery_current'],
            'cell_id'                           => $value['cell_id'],
            'pto_state'                         => $value['pto_state'],
            'engine_total_fuel_used'            => $value['engine_total_fuel_used'],
            'fuel_level_1_x'                    => $value['fuel_level_1_x'],
            'server_time'                       => date("Y-m-d H:i:s", strtotime($value['server_time'])),
            'device_time'                       => date("Y-m-d H:i:s", strtotime($value['device_time'])),
            'device_timestamp'                  => $value['device_timestamp']/1000,
            'engine_total_hours_of_operation_x' => $value['engine_total_hours_of_operation_x'],
            'service_distance'                  => $value['service_distance'],
            'at_least_pto_engaged'              => $value['at_least_pto_engaged'],
            'eco_driving_type'                  => $value['eco_driving_type'],
            'eco_driving_value'                 => $value['eco_driving_value'],
            'wheel_based_speed'                 => $value['wheel_based_speed'],
            'accelerator_pedal_position'        => $value['accelerator_pedal_position'],
            'engine_percent_load'               => $value['engine_percent_load'],
            'engine_speed_x'                    => $value['engine_speed_x'],
            'tacho_vehicle_speed_x'             => $value['tacho_vehicle_speed_x'],
            'engine_coolant_temperature_x'      => $value['engine_coolant_temperature_x'],
            'instantaneous_fuel_economy_x'      => $value['instantaneous_fuel_economy_x'],
            'digital_input_1'                   => $value['digital_input_1'],
            'digital_input_2'                   => $value['digital_input_2'],
            'digital_input_3'                   => $value['digital_input_3'],
            'digital_input_4'                   => $value['digital_input_4'],
            'sensor'                            => $value['sensor'],
            'ignition'                          => $value['ignition'],
            'crash_detection'                   => $value['crash_detection'],
            'geofence_zone_01'                  => $value['geofence_zone_01'],
            'digital_output_1'                  => $value['digital_output_1'], 
            'digital_output_2'                  => $value['digital_output_2'],
            'gps_status'                        => $value['gps_status'],
            'movement_sensor'                   => $value['movement_sensor'],
            'data_mode'                         => $value['data_mode'],
            'deep_sleep'                        => $value['deep_sleep'],
            'analog_input_1'                    => $value['analog_input_1'],
            'gsm_operator'                      => $value['gsm_operator'],
            'dallas_temperature_1'              => $value['dallas_temperature_1'],
            'dallas_temperature_2'              => $value['dallas_temperature_2'],
            'dallas_temperature_3'              => $value['dallas_temperature_3'],
            'dallas_temperature_4'              => $value['dallas_temperature_4'],
            'dallas_id_1'                       => $value['dallas_id_1'],
            'dallas_id_2'                       => $value['dallas_id_2'],
            'dallas_id_3'                       => $value['dallas_id_3'],
            'dallas_id_4'                       => $value['dallas_id_4'],
            'event'                             => $value['event'],
            'event_type_id'                     => $value['event_type_id'],
            'event_type'                        => $value['event_type'],
            'telemetry'                         => $value['telemetry'],
            'is_blackbox'                       => $value['is_blackbox'],
            'fleet_group_id'                    => $vehicle->fleet_group_id,
            'fleet_group_name'                  => $vehicle->fleet_group_name,
            'driver_code'                       => ($vehicle->driver_code)?$vehicle->driver_code:"",
            'driver_name'                       => ($vehicle->driver_name)?$vehicle->driver_name:"",
            'driver_phone'                      => ($vehicle->driver_phone)?$vehicle->driver_phone:"",
            'license_plate'                     => $vehicle->license_plate,
            'simcard_number'                    => $vehicle->simcard_number,
            'machine_number'                    => $vehicle->machine_number,
            'vehicle_brand'                     => $vehicle->vehicle_brand,
            'vehicle_model'                     => $vehicle->vehicle_model,
            'vehicle_id'                        => $vehicle->id,
            'fuel_consumed'                     => $value['total_odometer'] / $vehicle->fuel_ratio,
            'vehicle_status'                    => 'Activated',
            'receive_message'                   => 1
        ];
        self::checkVehicleActivity($value);
        if(self::$temp['is_blackbox'] == 0){

            $checkMapping_1st = MwMapping::where('vehicle_number', $vehicle->vin)->first();
            $checkMapping_2nd = MwMappingSecond::where('vehicle_number', $vehicle->vin)->first();

            if(empty($checkMapping_2nd)){
                $data = MwMappingSecond::create(self::$temp);
            }else{
                $waktuawal  = date_create(date('Y-m-d H:i:s',$checkMapping_1st->device_timestamp));
                $waktuakhir = date_create(); // Waktu Sekarang
                $diff  = date_diff($waktuawal, $waktuakhir);
                $selisih_menit = $diff->i;
    
                if($checkMapping_1st->dleft == 'OPEN' AND $selisih_menit > 5){
                    if(isset($checkMapping_1st->total_subscriber) && !empty($checkMapping_1st->total_subscriber)){
                        self::$temp['panic'] = ($checkMapping_1st->panic)?$checkMapping_1st->panic:"";
                        self::$temp['dleft'] = ($checkMapping_1st->dleft)?$checkMapping_1st->dleft:"";
                        self::$temp['dright'] = ($checkMapping_1st->dright)?$checkMapping_1st->dright:"";
                        self::$temp['drear'] = ($checkMapping_1st->drear)?$checkMapping_1st->drear:"";
                        self::$temp['fcamera'] = ($checkMapping_1st->fcamera)?$checkMapping_1st->fcamera:"";
                        self::$temp['is_overstay'] = ($checkMapping_1st->is_overstay)?$checkMapping_1st->is_overstay:"";

                        $this->realtimeDB($vehicle->license_plate, self::$temp);
                    }

                    $data_upd = [
                        'latitude'        => $value['latitude'],
                        'longitude'       => $value['longitude'],
                        'location'        => $value['location'],
                        'altitude'        => $value['altitude'],
                        'direction'       => $value['direction'],
                        'speed'           => $value['speed'],
                        'ignition'        => $value['ignition'],
                        'server_time'     => date("Y-m-d H:i:s", strtotime($value['server_time'])),
                        'device_time'     => date("Y-m-d H:i:s", strtotime($value['device_time'])),
                        'event'           => $value['event'],
                        'event_type_id'   => $value['event_type_id'],
                        'event_type'      => $value['event_type']
                    ];
                    $Obj = MwMapping::where('vehicle_number',$checkMapping_1st->vehicle_number)->update($data_upd);
                    
                    self::$temp['verified_date'] = "";
                    self::$temp['verified_by'] = "";

                    if($value['event_type'] == 'SAMPLING'){
                        // CREATE SAMPLING
                        // MwMappingHistory::create(self::$temp);
                        self::$temp['event_type'] = "PTT_LST";
                        self::checkAlertVehicle($vehicle, self::$temp);
                        MwMappingHistory::create(self::$temp);
                    }

                    if($value['event_type'] == 'KEY ON' or $value['event_type'] == 'KEY OFF'){
                        MwMappingHistory::create(self::$temp);
                    }
                }
                
                $Obj = MwMappingSecond::where('vehicle_number',$checkMapping_2nd->vehicle_number)->update(self::$temp);
                
            }
        }
    }

    public function show($id=null){
        try{
            $this->data     = MwMapping::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        try{
            $this->data = MwMapping::where('id', $id)->update($request->all());
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id=null){
        try{
            if(!empty($id))
                $Obj = MwMapping::find($id);
                $Obj->delete();
                $this->data =  $Obj;

        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    private function checkGeofence($vehicle_id, $param){
        $point      = array($param['longitude'],$param['latitude']);
        $query = GeofenceVehicle::select('geofence', 'out_of_geofence')->where('vehicle_id', $vehicle_id);
        $geofence = $query->first();
        if (!empty($geofence->geofence)) {
            $max = count($geofence->geofence);            
            $geofenceValue = [];
            $geofenceList = [];
            $outOfGeofence = 1;
            if ($max > 0) {
                for ($i=0; $i < $max; $i++) {
                    $locations = Geofence::select('_id','geofence_detail')->where("_id",$geofence->geofence[$i]['geofence_id']);  
                    $result = $locations->where('geofence_detail','$geoIntersects', ['$geometry' => ['type' => 'Point','coordinates' => $point]])->first();
                    $geo_result = [
                        'direction' => self::$temp['direction'],
                        'license_plate' => self::$temp['license_plate'],
                        'latitude' => self::$temp['latitude'],
                        'longitude' => self::$temp['longitude'],
                        'vehicle_number' => self::$temp['vehicle_number'],
                        'fleet_group_id' => self::$temp['fleet_group_id'],
                        'fleet_group_name' => self::$temp['fleet_group_name'],
                        'speed' => self::$temp['speed'],
                        'engine_coolant_temperature_x' => self::$temp['engine_coolant_temperature_x'],
                        'engine_speed_x' => self::$temp['engine_speed_x'],
                        'dallas_temperature_1' => self::$temp['dallas_temperature_1'],
                        'driver_name' => self::$temp['driver_name'],
                        'driver_phone' => self::$temp['driver_phone'],
                        'device_time' => self::$temp['device_time'],
                        'device_timestamp' => self::$temp['device_timestamp'],
                        'is_blackbox' => self::$temp['is_blackbox'],
                        'imei' => self::$temp['imei'],
                        'vehicle_id' => $vehicle_id,
                        'out_of_geofence' => 1,
                        'geofence_name' => $geofence->geofence[$i]['geofence_name'],
                        'geofence_type' => $geofence->geofence[$i]['type']
                    ];
                    
                    if (!empty($result) || (isset($geofence->geofence[$i]['is_red_zone']) && $geofence->geofence[$i]['is_red_zone'] == 1)){
                        $geo_result['out_of_geofence'] = 0;
                        $outOfGeofence = 0;
                    }
                    
                    $geofenceArr = [
                        'geofence_id' => $geofence->geofence[$i]['geofence_id'],
                        'alert_in_zone' => $geofence->geofence[$i]['alert_in_zone'],
                        'alert_out_zone' => $geofence->geofence[$i]['alert_out_zone'],
                        'created_by' => $geofence->geofence[$i]['created_by'],
                        'geofence_name' => $geofence->geofence[$i]['geofence_name'],
                        'type' => $geofence->geofence[$i]['type'],
                        'colour' => (isset($geofence->geofence[$i]['colour']))?$geofence->geofence[$i]['colour']:"#f1f1f1",
                        'out_of_geofence' => $geo_result['out_of_geofence']
                    ];

                    self::$temp['geofence_name'] = $geo_result['geofence_name'];
                    self::$temp['geofence_type'] = $geo_result['geofence_type'];
                    self::$temp['out_of_geofence'] = $geo_result['out_of_geofence'];
                    
                    if (!isset($geofence->geofence[$i]['out_of_geofence']) || $geofence->geofence[$i]['out_of_geofence'] != $geo_result['out_of_geofence']){                        
                        $geofenceArr['updated_at'] = date('Y-m-d H:i:s');            
                        $geo_result['verified_date'] = "";
                        $geo_result['verified_by'] = "";
                        if (self::$temp['is_blackbox'] == 0) {
                            $param = [
                                'latitude' => $geo_result['latitude'],
                                'longitude' => $geo_result['longitude'],
                                'device_time' => $geo_result['device_time']
                            ];
                            if ($geofence->geofence[$i]['alert_in_zone'] == 1 && $geo_result['out_of_geofence'] == 0){
                                if (!empty($geofence->geofence[$i]['is_red_zone']) && $geofence->geofence[$i]['is_red_zone'] == 1) {
                                    $geo_result['event_type'] = 'RED_ZONE';
                                    $geo_result['event_name'] = 'Red Zone';
                                    $param['event_type'] = 'RED_ZONE';
                                    self::checkAlertVehicle($geo_result,$param,"Vehicle In Red Zone");

                                    MwMappingHistory::create($geo_result);
                                    MwMappingGeofence::create($geo_result);
                                }
                                
                                $geo_result['event_type'] = 'GEO_IN';
                                $geo_result['event_name'] = 'Geofence In';         
                                $param['event_type'] = 'GEO_IN';
                                self::checkAlertVehicle($geo_result,$param,"Vehicle In Zone");
                            }
                            
                            if ($geofence->geofence[$i]['alert_out_zone'] == 1 && $geo_result['out_of_geofence'] == 1 && ($geofence->geofence[$i]['is_red_zone'] == 0 || !isset($geofence->geofence[$i]['is_red_zone']))){
                                $geo_result['event_type'] = 'GEO_OUT';
                                $geo_result['event_name'] = 'Geofence Out';
                                $param['event_type'] = 'GEO_OUT';
                                self::checkAlertVehicle($geo_result,$param,"Vehicle Out Zone");
                            }
                            
                            MwMappingHistory::create($geo_result);
                        }
                    }

                    $geofenceValue[] = self::$temp;
                    $geofenceList[] = $geofenceArr;

                    MwMappingGeofence::create($geo_result);
                }
                                
                $GeofenceVehicleUpdate['geofence'] = $geofenceList;
                $GeofenceVehicleUpdate['vehicle_number'] = self::$temp['vehicle_number'];
                $GeofenceVehicleUpdate['fleet_group_name'] = self::$temp['fleet_group_name'];
                $GeofenceVehicleUpdate['fleet_group_id'] = self::$temp['fleet_group_id'];
                $GeofenceVehicleUpdate['driver_name'] = (isset(self::$temp['driver_name'])) ? self::$temp['driver_name']:"";
                $GeofenceVehicleUpdate['driver_phone'] = (isset(self::$temp['driver_phone'])) ? self::$temp['driver_phone']:"";
                if (!isset($geofence->out_of_geofence) || $geofence->out_of_geofence != $outOfGeofence) {
                    $GeofenceVehicleUpdate['out_of_geofence'] = $outOfGeofence;
                    $GeofenceVehicleUpdate['update_time'] = date('Y-m-d H:i:s');
                }

                GeofenceVehicle::where('vehicle_id', $vehicle_id)->update($GeofenceVehicleUpdate);
                WindowsAzure::sendQueueMessage(env('SERVICE_BASH_GEOFENCE_URL'), env('SERVICE_BASH_GEOFENCE_DB'), $geofenceValue);
            }
        }
    }

    private function checkVehicleActivity($param){
        // Moving
        if($param['ignition'] == 1 && $param['speed'] > 0)
            self::$temp['vehicle_activity'] = 'MOVING';
        // Idle
        if($param['ignition'] == 1 && $param['speed'] <= 0)
            self::$temp['vehicle_activity'] = 'IDLE';
        // Stop
        if($param['ignition'] == 0  && $param['speed'] <= 0)
            self::$temp['vehicle_activity'] = 'STOP';
        // Crash
        if($param['event_type'] == 'CRASH')
            self::$temp['vehicle_activity'] = 'CRASH';
    }

    private function checkDriverCondition($param){
        //Moving
        if ($param['vehicle_activity'] == 'MOVING') {
            $dt = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - 60 * 60 * 4);
            if (!empty($param['activity_date']) && $param['activity_date'] < $dt)
                self::$temp['event_type'] = 'FATIGUE_ON';
        } else {
            if (!empty($param['fcamera']) && $param['fcamera'] =='ON')
                self::$temp['event_type'] = 'FATIGUE_OFF';
        }
        //Idle
        if ($param['vehicle_activity'] == 'IDLE') {
            $dt = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) - 60 * 30);
            if (!empty($param['activity_date']) && $param['activity_date'] < $dt && (empty($param['is_overstay']) || $param['is_overstay'] != 1))
                self::$temp['event_type'] = 'OVERSTAY';
        } else {
            if (!empty($param['is_overstay']) && $param['is_overstay'] == 1)
                self::$temp['is_overstay'] = 0;
        }
        // dd(self::$temp['event_type']);
    }

    private function checkAlertActivity($param,$digitalinput, $device_timestamp = null){
        if ($param == 'OVERSTAY')
            self::$temp['is_overstay'] = 1;
        if ($param == 'FATIGUE_ON')
            self::$temp['fcamera'] = 'ON';
        if ($param == 'FATIGUE_OFF') 
            self::$temp['fcamera'] = 'OFF';   
        if ($param == 'PANIC_OFF')
            self::$temp['panic'] = 'OFF';
        if ($param == 'PANIC_ON') 
            self::$temp['panic'] = 'ON';
        if ($param == 'DREAR_OPEN') 
            self::$temp['drear'] = 'OPEN';
        if ($param == 'DREAR_CLOSE') 
            self::$temp['drear'] = 'CLOSED';
        if ($param == 'DLEFT_OPEN') 
            self::$temp['dleft'] = 'OPEN';
        if ($param == 'DLEFT_CLOSE') 
            self::$temp['dleft'] = 'CLOSED';
        if ($param == 'DRIGHT_OPEN') 
            self::$temp['dright'] = 'OPEN';
        if ($param == 'DRIGHT_CLOSE') 
            self::$temp['dright'] = 'CLOSED';
    }

    private function checkAlertVehicle($vehicle, $param, $alert_geofence = null){     
        $allParent = MasterFleetGroup::find($vehicle['fleet_group_id'])->getParentsAttribute();
        $allChild  = $this->getArrayFleetGroup($vehicle['fleet_group_id']);

        if (!empty($param)) {
            $alertMapping = MasterAlert::join('AlertMapping','AlertMapping.alert_id','=','MsAlert.id')
                                   ->where('AlertMapping.fleet_group_id', $vehicle->fleet_group_id)
                                   ->where('alert_code', $param['event_type'])
                                   ->whereNull('AlertMapping.deleted_at')
                                   ->whereNotIn('alert_code', ['SAMPLING'])
                                   ->select('MsAlert.alert_name', 'AlertMapping.*')
                                   ->first();    

            if(!empty($alertMapping)){
                self::$temp['event_name'] = $alertMapping->alert_name;

                $message = [
                    'vehicle_id' => $vehicle->id,
                    'license_plate' => $vehicle->license_plate,
                    'waktu' => Carbon::parse($param['device_time'])->format('Y-m-d H:i:s'),
                    'alert' => $alertMapping->alert_name,
                    'fleet_group_id' => $vehicle->fleet_group_id,
                    'fleet_group_name' => $vehicle->fleet_group_name,
                    'lokasi' => "https://www.google.co.id/maps/place/".$param['latitude'].",".$param['longitude'],
                ];

                Api::pushNotificationFormat($vehicle->fleet_group_id, 'Gpstracking:', $message, '', 'alert');
                foreach ($allParent as $value) {
                    Api::pushNotificationFormat($value['id'], 'Gpstracking:', $message, '', 'alert');
                }

                if (!empty($alert_geofence)) {
                    foreach ($allChild as $value) {
                        Api::pushNotificationFormat($value, 'Gpstracking:', $message, '', 'alert');
                    }
                }
            }
        }
    }

    private function checkPolygon($point, $polygon){
        if($polygon[0] != $polygon[count($polygon) - 1])
            $polygon[count($polygon)] = $polygon[0];
        
        $j = 0;
        $oddNodes = false;
        $x = $point[1];
        $y = $point[0];
        $n = count($polygon);

        for($i = 0; $i < $n; $i++){
            $j++;
            if($j == $n){
                $j = 0;
            }

            if ((($polygon[$i][0] < $y) && ($polygon[$j][0] >= $y)) || (($polygon[$j][0] < $y) && ($polygon[$i][0] >=
            $y))){
                if ($polygon[$i][1] + ($y - $polygon[$i][0]) / ($polygon[$j][0] - $polygon[$i][0]) * ($polygon[$j][1] -
                    $polygon[$i][1]) < $x){
                    $oddNodes = !$oddNodes;
                }
            }
        }
        return $oddNodes;
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

    public function subscribe($vehicleNumber){
        try{
            $geofenceId = $result = [];

            $query = MwMapping::where('vehicle_number', $vehicleNumber);
            $mw = $query->first();
            if(empty($mw))
                throw new \Exception("Error Processing Request. vehicle not found");	

            $query->update(['total_subscriber' => (int)$mw->total_subscriber+1]);

            $msGeofenceVehicle = GeofenceVehicle::where('vin', $vehicleNumber)->select('geofence')->get();
            if(!empty($msGeofenceVehicle)) foreach($msGeofenceVehicle as $val){
                foreach ($val->geofence as $v) {
                    $geofenceId[] = $v['geofence_id'];
                }
            }
            if(!empty($geofenceId)){
                $msGeofence = Geofence::whereIn('_id', $geofenceId)->get();
                $result = $msGeofence;
            }

            $this->realtimeDB($mw->license_plate, $mw);
            
            $this->data = $result;
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function unsubscribe($vehicleNumber){
        try{
            $query = MwMapping::where('vehicle_number', $vehicleNumber);
            $data = $query->first();
            if(!empty($data) && $data->total_subscriber > 0)
                $query->update(['total_subscriber' => (int)$data->total_subscriber-1]);

            $this->data = true;
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