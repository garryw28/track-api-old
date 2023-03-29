<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\MasterVehicle;
use App\Models\MasterGeofenceVehicle;
use App\Models\MasterGeofence;
use App\Models\Geofence;
use App\Models\GeofenceVehicle;
use App\Models\MasterGeofenceDetail;
use App\Models\MwMapping;
use App\Models\MwMappingHistory;
use App\Models\MwMappingGeofence;
use App\Models\MasterAlert;
use App\Models\AlertMapping;
use App\Models\Geom;
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
use DB;

class Testing2Controller extends Controller
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

    public function checkLogicActivity(Request $request){		
        try{
            // get vehicle
            $vehicle = MasterVehicle::join('MsFleetGroup', 'MsFleetGroup.id','=', 'MsVehicle.fleet_group_id')
                                    ->where('imei_obd_number', $request->imei)
                                    ->select('MsVehicle.*', 'MsFleetGroup.fleet_group_name')
                                    ->first();
            
            if(empty($vehicle)){
                throw new \Exception("Error Processing Request. vehicle not found");	
            }

            self::$temp = [
                'device_id'                         => $request->device_id, 
                'imei'                              => $request->imei,
                'device_type'                       => $request->device_type,
                'device_model'                      => $request->device_model,
                'vehicle_number'                    => $request->vehicle_number,
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
                'server_time'                       => date('Y-m-d H:i:s',strtotime($request->server_time)),
                'device_time'                       => date('Y-m-d H:i:s',strtotime($request->device_time)),
                'device_timestamp'                  => date('Y-m-d H:i:s',strtotime($request->device_timestamp)),
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
                'driver_code'                       => $vehicle->driver_code,
                'driver_name'                       => $vehicle->driver_name,
                'license_plate'                     => $vehicle->license_plate,
                'simcard_number'                    => $vehicle->simcard_number,
                'machine_number'                    => $vehicle->machine_number,
                'vehicle_brand'                     => $vehicle->vehicle_brand,
                'vehicle_model'                     => $vehicle->vehicle_model,
                'vehicle_id'                        => $vehicle->id,
                'fuel_consumed'                     => $request->total_odometer / $vehicle->fuel_ratio, 
            ];
             //check vehicle activity
             self::checkVehicleActivity($request->all());

             $checkMapping = MwMapping::where('imei', $request->imei)->first();
             // insert to mw mapping
             if(empty($checkMapping)){
                 //insert
                 $data = MwMapping::create(self::$temp);
             }else{
                 //update
                 $Obj = MwMapping::find($checkMapping->id);
                 foreach(self::$temp as $key=>$val){
                     $Obj->{$key} = $val;
                 }
                 $Obj->save();
                 $data =  $Obj;
             }

             $this->data    = $data;
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function checkLogicGeofence(Request $request){		
        try{
            // get vehicle
            $vehicle = MasterVehicle::join('MsFleetGroup', 'MsFleetGroup.id','=', 'MsVehicle.fleet_group_id')
                                    ->where('imei_obd_number', $request->imei)
                                    ->select('MsVehicle.fleet_group_id','MsVehicle.driver_code','MsVehicle.driver_name','MsVehicle.license_plate','MsVehicle.simcard_number','MsVehicle.machine_number','MsVehicle.vehicle_brand','MsVehicle.vehicle_model','MsVehicle.id','MsVehicle.fuel_ratio', 'MsFleetGroup.fleet_group_name')
                                    ->first();
            
            if(!isset($vehicle)){
                throw new \Exception("Error Processing Request. vehicle not found");	
            }

            self::$temp = [
                'device_id'                         => $request->device_id, 
                'imei'                              => $request->imei,
                'device_type'                       => $request->device_type,
                'device_model'                      => $request->device_model,
                'vehicle_number'                    => $request->vehicle_number,
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
                'server_time'                       => date('Y-m-d H:i:s',strtotime($request->server_time)),
                'device_time'                       => date('Y-m-d H:i:s',strtotime($request->device_time)),
                'device_timestamp'                  => date('Y-m-d H:i:s',strtotime($request->device_timestamp)),
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
                'driver_code'                       => $vehicle->driver_code,
                'driver_name'                       => $vehicle->driver_name,
                'license_plate'                     => $vehicle->license_plate,
                'simcard_number'                    => $vehicle->simcard_number,
                'machine_number'                    => $vehicle->machine_number,
                'vehicle_brand'                     => $vehicle->vehicle_brand,
                'vehicle_model'                     => $vehicle->vehicle_model,
                'vehicle_id'                        => $vehicle->id,
                'fuel_consumed'                     => $request->total_odometer / $vehicle->fuel_ratio, 
            ];
             //check vehicle activity
             self::checkVehicleActivity($request->all());

             $checkMapping = MwMapping::select('id')->where('imei', $request->imei)->first();

             // insert to mw mapping
             if(!isset($checkMapping)){
                 //insert
                 $data = MwMapping::create(self::$temp);
             }else{
                 //update
                 $Obj = MwMapping::find($checkMapping->id);
                 foreach(self::$temp as $key=>$val){
                     $Obj->{$key} = $val;
                 }
                 $Obj->save();
                 $data =  $Obj;
             }

             // check checkGeofence
            self::checkGeofence($vehicle->id, ['longitude' => $request->longitude, 'latitude' => $request->latitude]);

            $this->data    = $data;
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function checkLogicAlert(Request $request){
        try {
            // get vehicle
            $vehicle = MasterVehicle::join('MsFleetGroup', 'MsFleetGroup.id','=', 'MsVehicle.fleet_group_id')
                                    ->where('imei_obd_number', $request->imei)
                                    ->select('MsVehicle.*', 'MsFleetGroup.fleet_group_name')
                                    ->first();
            
            if(empty($vehicle)){
                throw new \Exception("Error Processing Request. vehicle not found");	
            }

            self::$temp = [
                'device_id'                         => $request->device_id, 
                'imei'                              => $request->imei,
                'device_type'                       => $request->device_type,
                'device_model'                      => $request->device_model,
                'vehicle_number'                    => $request->vehicle_number,
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
                'server_time'                       => date('Y-m-d H:i:s',strtotime($request->server_time)),
                'device_time'                       => date('Y-m-d H:i:s',strtotime($request->device_time)),
                'device_timestamp'                  => date('Y-m-d H:i:s',strtotime($request->device_timestamp)),
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
                'driver_code'                       => $vehicle->driver_code,
                'driver_name'                       => $vehicle->driver_name,
                'license_plate'                     => $vehicle->license_plate,
                'simcard_number'                    => $vehicle->simcard_number,
                'machine_number'                    => $vehicle->machine_number,
                'vehicle_brand'                     => $vehicle->vehicle_brand,
                'vehicle_model'                     => $vehicle->vehicle_model,
                'vehicle_id'                        => $vehicle->id,
                'fuel_consumed'                     => $request->total_odometer / $vehicle->fuel_ratio
            ];


            $checkMapping = MwMapping::where('imei', $request->imei)->first();
            // insert to mw mapping
            if(empty($checkMapping)){
                //insert
                $data = MwMapping::create(self::$temp);
            }else{
                //update
                $Obj = MwMapping::find($checkMapping->id);
                foreach(self::$temp as $key=>$val){
                    $Obj->{$key} = $val;
                }
                $Obj->save();
                $data =  $Obj;
            }

            // insert to history
            self::$temp['location_coordinate'] = ['type' => 'Point', 'coordinates' => [$request->longitude, $request->latitude]];
            MwMappingHistory::create(self::$temp);

            // firebase
            if(isset($checkMapping->total_subscriber) && !empty($checkMapping->total_subscriber))
                $this->realtimeDB($vehicle->license_plate, self::$temp);

            //check alert vehicle
            self::checkAlertVehicle($vehicle, $request->all());
            $this->data = $data;

        }catch(\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    private function checkGeofence($vehicle, $param){
        $point      = array($param['longitude'],$param['latitude']);
        $master_temp = json_encode($param);
        $geofence = GeofenceVehicle::select('geofence')->where('vehicle_id', $vehicle)->first();
        $max = count($geofence->geofence);
        for ($i=0; $i < $max; $i++) {
            $locations = Geofence::select('_id')->where("_id",$geofence->geofence[$i]['geofence_id']);
            $result = $locations->where('geofence_detail','$geoIntersects', ['$geometry' => ['type' => 'Point','coordinates' => $point]])->first();
            
            $geo_result = [
                'vehicle_id' => $vehicle,
                'out_of_geofence' => 0,
                'geofence_name' => $geofence->geofence[$i]['geofence_name'],
                'geofence_type' => $geofence->geofence[$i]['type']
            ];

            if (!isset($result))
                $geo_result['out_of_geofence'] = 1;

            MwMappingGeofence::create($geo_result);
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

    private function checkAlertVehicle($vehicle, $param){
        $alertMapping = MasterAlert::join('AlertMapping','AlertMapping.alert_id','=','MsAlert.id')
                                   ->where('AlertMapping.fleet_group_id', $vehicle->fleet_group_id)
                                   ->where('alert_code', $param['event_type'])
                                   ->select('MsAlert.alert_name', 'AlertMapping.*')
                                   ->first();

        if(!empty($alertMapping)){
            $waktu   = Carbon::parse($param['device_time'])->format('Y-m-d H:i:s');
            $message = 'No.Pol: '.$vehicle->license_plate.' | Waktu: '.$waktu.' | Alert: '. $alertMapping->alert_name.' | Lokasi: '."https://www.google.co.id/maps/place/".$param['latitude'].",".$param['longitude'];
            Api::pushNotificationFormat($vehicle->fleet_group_id, 'Gpstracking:', $message, '', 'alert');
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
                    ->create();
                    
        $database = $firebase->getDatabase();
        $newPost  = $database
                    ->getReference('tracking/'.$topic)
                    ->set($message);

        $doStore = $newPost->getKey();
    }

    public function subscribe($licensePlate){
        try{
            $geofenceId = $result = [];
            $data       = MwMapping::where('license_plate', $licensePlate)->first();
            if(empty($data))
                throw new \Exception("Error Processing Request. vehicle not found");	

            $data->{'total_subscriber'} = $data->total_subscriber+1;
            $data->save();

            $msGeofenceVehicle = MasterGeofenceVehicle::where('license_plate', $licensePlate)->select('geofence_id')->get();
            if(!empty($msGeofenceVehicle)) foreach($msGeofenceVehicle as $val){
                $geofenceId[] = $val->geofence_id;
            }
            if(!empty($geofenceId)){
                $msGeofence = MasterGeofence::with('geofence_detail')->whereIn('id', $geofenceId)->get();
                $result = $msGeofence;
            }

            $this->data = $result;
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function unsubscribe($licensePlate){
        try{
            $data = MwMapping::where('license_plate', $licensePlate)->first();
            if(!empty($data) && $data->total_subscriber > 0){
                $data->{'total_subscriber'} = $data->total_subscriber-1;
                $data->save();
            }
            $this->data = true;
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

}