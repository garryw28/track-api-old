<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\VehicleMaintenance as VehicleMaintenanceDB;
use App\Models\MwMapping;
use App\Models\MasterVehicle as MsVehicle;
use App\Models\ParserConfig as ParserConfigDB;
use App\Models\GeofenceVehicle as MasterGeofenceVehicleDB;
use App\Helpers\Api;
use App\Helpers\RestCurl;
use DB;

class VehicleMaintenanceController extends Controller
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
            $this->data = VehicleMaintenanceDB::where('status', 0)->orderBy('updated_at','DESC')->paginate(15);
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = VehicleMaintenanceDB::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function takeOutEmai(Request $request){
		$rules = [
            'vehicle_id'             => 'required',
            'license_plate'          => 'required',
			'description'            => 'required',
            'fleet_group_id'         => 'required',
            'vin'                    => 'required',
            'machine_number'         => 'required',
            'imei_old'               => 'required',
            'description'            => 'required',
            'created_by'             => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);
	
        try {
            DB::beginTransaction();

            $data = array ( 
                'vehicle_id'             => trim($request->vehicle_id),
                'license_plate'          => trim($request->license_plate),
                'description'            => trim($request->description),
                'fleet_group_id'         => trim($request->fleet_group_id),
                'vin'                    => trim($request->vin),
                'machine_number'         => trim($request->machine_number),
                'imei_old'               => trim($request->imei_old),
                'start_date_maintenance' => date('Y-m-d H:i:s'),
                'status'                 => 0,
                'created_by'             => trim($request->created_by)
            );
            
            $doInsert =  VehicleMaintenanceDB::create($data);
            if($doInsert){
                // Take out imei & reff_vehicle_id
                MsVehicle::where('vin', $request->vin)->update(['imei_obd_number' => null, 'reff_vehicle_id' => null, 'status' => 2]);
                MasterGeofenceVehicleDB::where('vehicle_id',$request->vehicle_id)->update(['imei_obd_number' => null, 'reff_vehicle_id' => null, 'vehicle_status' => 'Suspend']);
                // Delete mw mapping
                $mwMapping = MwMapping::where('vehicle_number', $request->vin);
                if(!empty($mwMapping->first())){
                    // $mwMapping->delete();
                    $mwMapping->update(['imei' => '', 'receive_message' => 0, 'vehicle_status' => 'Suspend']);
                }
            }

            DB::commit();
            $this->data     = $doInsert;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function reProvisioning(Request $request){
		$rules = [
            'license_plate'          => 'required',
            'imei_new'               => 'required',
            'vin'                    => 'required',
            'license_plate'          => 'required',
            'device_type_code'         => 'required',
            'device_model_code'        => 'required',
            'device_group_code'        => 'required',
            'updated_by'             => 'required',
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);
	
        try {
            DB::beginTransaction();

            $mwMapping = MwMapping::where('vehicle_number', $request->vin);
            $mw = $mwMapping->first();
            $parserConfig = ParserConfigDB::where('env', env('PARSER_ENV'))->first();
            $mwResult = RestCurl::get($parserConfig->server_url.'/api/v1/backend/devices',["filter[code]" => $request->imei_new], ['Authorization: Bearer '. $parserConfig->access_token]);
            $end_point_array = explode(",",$request->input('end_points'));

            $data = array ( 
                'license_plate'          => trim($request->license_plate),
                'imei_new'               => trim($request->imei_new),
                'end_date_maintenance'   => date('Y-m-d H:i:s'),
                'status'                 => 1,
                'updated_by'             => trim($request->updated_by)
            );

            $provisioning = [
                "code"            => $request->imei_new,
                "description"     => $request->license_plate,
                "vehicle_number"  => $request->vin,
                "device_type_code"  => $request->device_type_code,
                "device_model_code" => $request->device_model_code,
                "device_group_code" => $request->device_group_code,
                "end_point_codes" => $end_point_array,
                "installation_date" => $request->installation_date
            ];

            $doUpdate =  VehicleMaintenanceDB::where('vin', $request->vin)->where('status', 0)->update($data);
            if($doUpdate){
                if (!empty($mwResult['data']->data)) {
                    $reff_vehicle_id = $mwResult['data']->data[0]->id;

                     //get config parser
                     $parserConfig = ParserConfigDB::where('env', env('PARSER_ENV'))->first();
                     if(empty($parserConfig))
                         throw new \Exception('Parser Configuration Not Found.');
                    
                    $createParse = RestCurl::put($parserConfig->server_url.'/api/v1/backend/devices_by_code/'.$reff_vehicle_id, $provisioning, ['Authorization: Bearer '. $parserConfig->access_token]);
                    if($createParse['status'] == 200) {
                        $data['reff_vehicle_id'] =  trim($createParse['data']->data->id);
                    } else {
                        throw new \Exception(json_encode($createParse['data']));
                    }
                } else {
                    $parserConfig = ParserConfigDB::where('env', env('PARSER_ENV'))->first();
                    if(empty($parserConfig))
                        throw new \Exception('Parser Configuration Not Found.');

                    $createParse = RestCurl::post($parserConfig->server_url.'/api/v1/backend/devices_by_code', $provisioning, ['Authorization: Bearer '. $parserConfig->access_token]);
                    if($createParse['status'] == 201) {
                        $reff_vehicle_id =  trim($createParse['data']->data->id);
                    } else {
                        throw new \Exception(json_encode($createParse['data']));
                    }
                }

                if(!empty($mw)) {
                    $mwMapping->update(['imei' => $request->imei_new]);
                    MasterGeofenceVehicleDB::where('vehicle_id',$mw->vehicle_id)->update(['imei_obd_number' => $request->imei_new, 'reff_vehicle_id' => $reff_vehicle_id, 'vehicle_status' => 'Activation On Progress']);
                }
                    
                // insert imei & reff_vehicle_id
                MsVehicle::where('vin', $request->vin)->update(['imei_obd_number' => $request->imei_new, 'reff_vehicle_id' => $reff_vehicle_id, 'status' => 1]);
            }

            DB::commit();
            $this->data     = $doUpdate;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function updateBatch(Request $request) {

        $data = array ( 
            'license_plate'          => trim($request->license_plate),
            'vin'                    => trim($request->vin),
            'imei_old'               => trim($request->imei_old),
            'start_date_maintenance' => date('Y-m-d H:i:s'),
            'end_date_maintenance' => date('Y-m-d H:i:s'),
            'status'                 => 1,
        );

        try {
            DB::beginTransaction();
            $getVehicle = MsVehicle::where('vin', $request->vin)->where('imei_obd_number', $request->imei_old)->where('license_plate', $request->license_plate)->first();
            if(empty($getVehicle)){
                $this->status   = "false";
                $this->errorMsg = "vehicle not found";

                return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
            }else{
                $parserConfig = ParserConfigDB::where('env', env('PARSER_ENV'))->first();
                if(empty($parserConfig))
                    throw new \Exception('Parser Configuration Not Found.');

                $end_point_array = explode(",",$request->input('end_points'));
                $provisioning = [
                    "code"            => $request->imei_new,
                    "description"     => $request->license_plate,
                    "vehicle_number"  => $request->vin,
                    "device_type_code"  => $request->device_type_code,
                    "device_model_code" => $request->device_model_code,
                    "device_group_code" => $request->device_group_code,
                    "end_point_codes" => $end_point_array
                ];

                $mwResult = RestCurl::get($parserConfig->server_url.'/api/v1/backend/devices',["filter[code]" => $request->imei_new], ['Authorization: Bearer '. $parserConfig->access_token]);            
                if (!empty($mwResult['data']->data)) {
                    $reff_vehicle_id = $mwResult['data']->data[0]->id;
    
                    $createParse = RestCurl::put($parserConfig->server_url.'/api/v1/backend/devices_by_code/'.$reff_vehicle_id, $provisioning, ['Authorization: Bearer '. $parserConfig->access_token]);
                    if($createParse['status'] == 200)
                        $this->data = $data;
                    else{
                        $this->status   = "false";
                        $this->errorMsg = "update provisioning failed";

                        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
                    }

                } else {
                    $createParse = RestCurl::post($parserConfig->server_url.'/api/v1/backend/devices_by_code', $provisioning, ['Authorization: Bearer '. $parserConfig->access_token]);                    
                    if($createParse['status'] == 201)
                        $reff_vehicle_id =  trim($createParse['data']->data->id);
                    else {
                        throw new \Exception(json_encode($createParse['data']));
                    }
                }

                $doInsert =  VehicleMaintenanceDB::create($data);
                if($doInsert){
                    // Delete mw mapping
                    $mwMapping = MwMapping::where('vehicle_number', $request->vin);
                    if(!empty($mwMapping->first())){
                        // $mwMapping->delete();
                        $mwMapping->update(['imei' => $request->imei_new]);
                    }

                    // insert imei & reff_vehicle_id
                    $getVehicle->update(['imei_obd_number' => $request->imei_new, 'reff_vehicle_id' => $reff_vehicle_id, 'installation_date' => $request->installation_date]);
                    MasterGeofenceVehicleDB::where('vehicle_id',$getVehicle->vehicle_id)->update(['imei_obd_number' => $request->imei_new, 'reff_vehicle_id' => $reff_vehicle_id]);
                }

                DB::commit();
                $this->data = $data;
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);

    }

}