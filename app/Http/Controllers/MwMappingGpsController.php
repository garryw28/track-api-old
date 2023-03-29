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

class MwMappingGpsController extends Controller
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

    public function store(Request $request){
        try {
            // get vehicle
            $vehicle = MasterVehicle::join('MsFleetGroup', 'MsFleetGroup.id','=', 'MsVehicle.fleet_group_id')
                                    ->where('license_plate', $request->license_plate)
                                    ->select('MsVehicle.*', 'MsFleetGroup.fleet_group_name')
                                    ->first();
            
            if(empty($vehicle))
                return response()->json(Api::format("false", $this->data, "Error Processing Request. vehicle not found"), 200);

            if ($vehicle->status == 2) 
                $vehicle->update(['status' => 1]);
                     
            if ($vehicle->vehicle_category == 'Passanger') {
                $main_battery_status = ((float)$request->external_power_voltage < 12)?'ABNORMAL':'NORMAL';
            } else {
                $main_battery_status = ((float)$request->external_power_voltage < 24)?'ABNORMAL':'NORMAL';
            }

            self::$temp = [
                'device_id'                         => $vehicle->reff_vehicle_id, 
                'imei'                              => $vehicle->imei,
                'device_type'                       => 'GV300',
                'device_model'                      => 'GV300',
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
                'gps_pdop'                          => ($request->has('gps_pdop')?$request->gps_pdop:"0"),
                'gps_hdop'                          => ($request->has('gps_hdop')?$request->gps_hdop:"0"),
                'gsm_signal_level'                  => ($request->has('gsm_signal_level')?$request->gsm_signal_level:0),
                'trip_odometer'                     => $request->trip_odometer,
                'total_odometer'                    => $request->total_odometer,
                'external_power_voltage'            => $request->external_power_voltage,
                'internal_battery_voltage'          => $request->internal_battery_voltage,
                'internal_battery_current'          => $request->internal_battery_current,
                'cell_id'                           => $request->cell_id,
                'pto_state'                         => ($request->has('pto_state')?$request->pto_state:"0"),
                'engine_total_fuel_used'            => ($request->has('engine_total_fuel_used')?$request->engine_total_fuel_used:"0"),
                'fuel_level_1_x'                    => ($request->has('fuel_level_1_x')?$request->fuel_level_1_x:"0"),
                'server_time'                       => date("Y-m-d H:i:s", strtotime($request->server_time)),
                'device_time'                       => date("Y-m-d H:i:s", strtotime($request->device_time)),
                'device_timestamp'                  => $request->device_timestamp,
                'engine_total_hours_of_operation_x' => ($request->has('engine_total_hours_of_operation_x')?$request->engine_total_hours_of_operation_x:"0"),
                'service_distance'                  => ($request->has('service_distance')?$request->service_distance:"0"),
                'at_least_pto_engaged'              => ($request->has('at_least_pto_engaged')?$request->at_least_pto_engaged:"0"),
                'eco_driving_type'                  => ($request->has('eco_driving_type')?$request->eco_driving_type:"0"),
                'eco_driving_value'                 => ($request->has('eco_driving_value')?$request->eco_driving_value:"0"),
                'wheel_based_speed'                 => ($request->has('wheel_based_speed')?$request->wheel_based_speed:"0"),
                'accelerator_pedal_position'        => ($request->has('accelerator_pedal_position')?$request->accelerator_pedal_position:"0"),
                'engine_percent_load'               => ($request->has('engine_percent_load')?$request->engine_percent_load:"0"),
                'engine_speed_x'                    => $request->engine_speed_x,
                'tacho_vehicle_speed_x'             => ($request->has('tacho_vehicle_speed_x')?$request->tacho_vehicle_speed_x:"0"),
                'engine_coolant_temperature_x'      => $request->engine_coolant_temperature_x,
                'instantaneous_fuel_economy_x'      => ($request->has('instantaneous_fuel_economy_x')?$request->instantaneous_fuel_economy_x:"0"),
                'digital_input_1'                   => ($request->has('digital_input_1')?$request->digital_input_1:"0"),
                'digital_input_2'                   => ($request->has('digital_input_2')?$request->digital_input_2:"0"),
                'digital_input_3'                   => ($request->has('digital_input_3')?$request->digital_input_3:"0"),
                'digital_input_4'                   => ($request->has('digital_input_4')?$request->digital_input_4:"0"),
                'sensor'                            => ($request->has('sensor')?$request->sensor:"0000"),
                'ignition'                          => $request->ignition,
                'crash_detection'                   => $request->crash_detection,
                'geofence_zone_01'                  => $request->geofence_zone_01,
                'digital_output_1'                  => ($request->has('digital_output_1')?$request->digital_output_1:"0"), 
                'digital_output_2'                  => ($request->has('digital_output_2')?$request->digital_output_2:"0"),
                'gps_status'                        => ($request->has('gps_status')?$request->gps_status:0),
                'movement_sensor'                   => ($request->has('movement_sensor')?$request->movement_sensor:0),
                'data_mode'                         => ($request->has('data_mode')?$request->data_mode:0),
                'deep_sleep'                        => ($request->has('deep_sleep')?$request->deep_sleep:0),
                'analog_input_1'                    => ($request->has('analog_input_1')?$request->analog_input_1:0),
                'gsm_operator'                      => ($request->has('gsm_operator')?$request->gsm_operator:0),
                'dallas_temperature_1'              => $request->dallas_temperature_1,
                'dallas_temperature_2'              => ($request->has('dallas_temperature_2')?$request->dallas_temperature_2:"0"),
                'dallas_temperature_3'              => ($request->has('dallas_temperature_3')?$request->dallas_temperature_3:"0"),
                'dallas_temperature_4'              => ($request->has('dallas_temperature_4')?$request->dallas_temperature_4:"0"),
                'dallas_id_1'                       => ($request->has('dallas_id_1')?$request->dallas_id_1:"0"),
                'dallas_id_2'                       => ($request->has('dallas_id_2')?$request->dallas_id_2:"0"),
                'dallas_id_3'                       => ($request->has('dallas_id_3')?$request->dallas_id_3:"0"),
                'dallas_id_4'                       => ($request->has('dallas_id_4')?$request->dallas_id_4:"0"),
                'event'                             => $request->event,
                'event_type_id'                     => $request->event_type_id,
                'event_type'                        => $request->event_type,
                'telemetry'                         => $request->telemetry,
                'is_blackbox'                       => $request->is_blackbox,
                // additional value
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
                'is_hasgps'                         => 1,
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
                
            // WindowsAzure::sendQueueMessage(env('SERVICE_BASH_HISTORY_URL'), env('SERVICE_BASH_HISTORY_DB'), self::$temp);

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
                // WindowsAzure::sendQueueMessage(env('SERVICE_BASH_GEOFENCE_URL'), env('SERVICE_BASH_GEOFENCE_DB'), $geofenceValue);
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
