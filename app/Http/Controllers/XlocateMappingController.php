<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Models\MasterVehicleCpn;
use App\Models\MwMappingVendor;
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
use App\Models\IntegrationVendor;
use DB, Log;

class XlocateMappingController extends Controller
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
            $vehicle = MasterVehicleCpn::join('MsUserIntegration', 'MsUserIntegration.id','=', 'MsVehicleCPN.user_integration_id')
                                    ->where('imei_obd_number', $request->imei)
                                    ->select('MsVehicleCPN.*', 'MsUserIntegration.name')
                                    ->first();

            $cekDataTrip = IntegrationVendor::where('licence_plate',$vehicle->licence_plate)->where('status_trip',1)->count();

            if($cekDataTrip == 0){
                return response()->json(Api::format("false", $this->data, "No Police is not on the trip"), 200);
            }
            
            if(empty($vehicle)){
                return response()->json(Api::format("false", $this->data, "Error Processing Request. vehicle not found"), 200);
            }

            self::$temp = [
                //'device_id'                         => $request->device_id, 
                'imei'                              => $request->imei,
                //'device_type'                       => $request->device_type,
                //'device_model'                      => $request->device_model,
                'vehicle_number'                    => $vehicle->vin,
                'priority'                          => $request->priority,
                'latitude'                          => $request->latitude,
                'longitude'                         => $request->longitude,
                'location'                          => $request->location,
                'altitude'                          => $request->altitude,
                'direction'                         => $request->direction,
                'speed'                             => $request->speed,
                'satellite'                         => $request->satellite,
                //'accuracy'                          => $request->accuracy,
                //'dtc_number'                        => $request->dtc_number,
                //'lac'                               => $request->lac,
                //'gps_pdop'                          => $request->gps_pdop,
                //'gps_hdop'                          => $request->gps_hdop,
                //'gsm_signal_level'                  => $request->gsm_signal_level,
                //'trip_odometer'                     => $request->trip_odometer,
                'total_odometer'                    => $request->total_odometer,
                'external_power_voltage'            => $request->external_power_voltage,
                'internal_battery_voltage'          => $request->internal_battery_voltage,
                'internal_battery_current'          => $request->internal_battery_current,
                'cell_id'                           => $request->cell_id,
                //'pto_state'                         => $request->pto_state,
                //'engine_total_fuel_used'            => $request->engine_total_fuel_used,
                //'fuel_level_1_x'                    => $request->fuel_level_1_x,
                'server_time'                       => date("Y-m-d H:i:s", strtotime($request->server_time)),
                'device_time'                       => date("Y-m-d H:i:s", strtotime($request->device_time)),
                'device_timestamp'                  => $request->device_timestamp/1000,
                //'engine_total_hours_of_operation_x' => $request->engine_total_hours_of_operation_x,
                //'service_distance'                  => $request->service_distance,
                //'at_least_pto_engaged'              => $request->at_least_pto_engaged,
                //'eco_driving_type'                  => $request->eco_driving_type,
                //'eco_driving_value'                 => $request->eco_driving_value,
                //'wheel_based_speed'                 => $request->wheel_based_speed,
                //'accelerator_pedal_position'        => $request->accelerator_pedal_position,
                //'engine_percent_load'               => $request->engine_percent_load,
                'engine_speed_x'                    => $request->engine_speed_x,
                //'tacho_vehicle_speed_x'             => $request->tacho_vehicle_speed_x,
                //'engine_coolant_temperature_x'      => $request->engine_coolant_temperature_x,
                //'instantaneous_fuel_economy_x'      => $request->instantaneous_fuel_economy_x,
                //'digital_input_1'                   => $request->digital_input_1,
                //'digital_input_2'                   => $request->digital_input_2,
                //'digital_input_3'                   => $request->digital_input_3,
                //'digital_input_4'                   => $request->digital_input_4,
                //'sensor'                            => $request->sensor,
                'ignition'                          => $request->ignition,
                //'crash_detection'                   => $request->crash_detection,
                //'geofence_zone_01'                  => $request->geofence_zone_01,
                //'digital_output_1'                  => $request->digital_output_1, 
                //'digital_output_2'                  => $request->digital_output_2,
                //'gps_status'                        => $request->gps_status,
                //'movement_sensor'                   => $request->movement_sensor,
                //'data_mode'                         => $request->data_mode,
                //'deep_sleep'                        => $request->deep_sleep,
                //'analog_input_1'                    => $request->analog_input_1,
                //'gsm_operator'                      => $request->gsm_operator,
                //'dallas_temperature_1'              => $request->dallas_temperature_1,
                //'dallas_temperature_2'              => $request->dallas_temperature_2,
                //'dallas_temperature_3'              => $request->dallas_temperature_3,
                //'dallas_temperature_4'              => $request->dallas_temperature_4,
                //'dallas_id_1'                       => $request->dallas_id_1,
                //'dallas_id_2'                       => $request->dallas_id_2,
                //'dallas_id_3'                       => $request->dallas_id_3,
                //'dallas_id_4'                       => $request->dallas_id_4,
                //'event'                             => $request->event,
                //'event_type_id'                     => $request->event_type_id,
                'event_type'                        => $request->event_type,
                'telemetry'                         => $request->telemetry,
                // additional value
                'is_blackbox'                       => $request->is_blackbox,
                'license_plate'                     => $vehicle->license_plate,
                'vehicle_id'                        => $vehicle->id,
                'vehicle_status'                    => 'Activated',
                'receive_message'                   => 1,
                'is_hasxlocate'                     => 1
            ];

            // check vehicle activity
            //self::checkVehicleActivity($request->all());   
            $data = self::$temp;

            if (self::$temp['is_blackbox'] == 0) {

                // insert to mw mapping
                $checkMapping = MwMappingVendor::where('vehicle_number', $vehicle->vin)->first();  
                
                if(empty($checkMapping)){
                    //insert
                    $data = MwMappingVendor::create(self::$temp);
                }else{
                    $Obj = MwMappingVendor::where('vehicle_number',$checkMapping->vehicle_number)->update(self::$temp);
                    $data = self::$temp;
                }                                
            }               
            
            // check checkGeofence 
            $this->data = $data;

            return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
        }catch(\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
            Log::error($e);

            return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 500);
        }
        
    }

}