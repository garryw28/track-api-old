<?php 
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class MwMapping extends Eloquent {

    protected $connection = 'mongodb';
    protected $collection = 'mw_mapping';

    protected $guarded = ['id'];

    // protected $fillable = [
    //     'device_id', 'imei','device_type','device_model','vehicle_number','priority','latitude','longitude'
    //     ,'location','altitude','direction','speed','satellite','accuracy','dtc_number','lac','gps_pdop','gps_hdop'
    //     ,'gsm_signal_level','trip_odometer','total_odometer','external_power_voltage','internal_battery_voltage'
    //     ,'internal_battery_current','cell_id','pto_state','engine_total_fuel_used','fuel_level_1_x','server_time'
    //     ,'device_time','device_timestamp','engine_total_hours_of_operation_x','service_distance','at_least_pto_engaged'
    //     ,'eco_driving_type','eco_driving_value','wheel_based_speed','accelerator_pedal_position','engine_percent_load'
    //     ,'engine_speed_x','tacho_vehicle_speed_x','engine_coolant_temperature_x','instantaneous_fuel_economy_x'
    //     ,'digital_input_1','digital_input_2','digital_input_3','digital_input_4','sensor','ignition','crash_detection'
    //     ,'geofence_zone_01','digital_output_1','digital_output_2','gps_status','movement_sensor','data_mode','deep_sleep'
    //     ,'analog_input_1','gsm_operator','dallas_temperature_1','dallas_temperature_2','dallas_temperature_3'
    //     ,'dallas_temperature_4','dallas_id_1','dallas_id_2','dallas_id_3','dallas_id_4','event','event_type_id','event_type'
    //     ,'telemetry'
    // ];

    protected $dates   = ['device_time', 'server_time', 'created_at', 'updated_at'];

}