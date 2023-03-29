<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\MasterVehicle as MasterVehicleDB;
use App\Models\ParserConfig as ParserConfigDB;
use App\Helpers\Api;
use DB;

class DriverPairingController extends Controller
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

    public function pairing(Request $request){
        $rules = [
            'data_vehicle.*.vin' => 'required',
            'data_vehicle.*.driver_code' => 'required',
            'data_vehicle.*.driver_name' => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);
        
        try {
                for ($row = 0; $row < $highestRow; $row++){
                    $vehicleCheck = MasterVehicleDB::where('vin', trim($arrayvehicle[$row]['vin']))->first();
                    if(empty($vehicleCheck)){
                        $data = array ( 
                            'driver_code' => trim($arrayvehicle[$row]['driver_code']),
                            'driver_name' => trim($arrayvehicle[$row]['driver_name'])
                        );
    
                        $this->data[]   = MasterVehicleDB::where('vin', trim($arrayvehicle[$row]['vin']))->update($data);
                    }
                }
            } catch (\Exception $e) {
                $this->status   = "false";
                $this->errorMsg = $e->getMessage();
                return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
            }
                
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
		
    }

}
