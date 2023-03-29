<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\MasterVehicle as MasterVehicleDB;
use App\Models\MwMapping;
use App\Models\MwMappingVendor;
use App\Models\MasterVehicleCpn;
use App\Models\MasterFleetGroup;
use App\Helpers\Api;
use App\Helpers\RestCurl;
use Carbon\Carbon;
use App\Models\IntegrationVendor; 
use App\Models\ParserConfigIntegrationVendor;
use DB;

class IntegrationController extends Controller
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

    public function storeVendor(Request $request){
        $data = $request->all();

        try {
            $vin = trim($request->vehicle_number);
            $data_mwmaping = MwMappingVendor::select('latitude','longitude','vehicle_number','vehicle_id')->where('vehicle_id',$request->vehicle_id)->orderBy('device_time', 'desc')->first();
            if(empty($data_mwmaping)){
                $Obj = MwMappingVendor::create($data);
            }else {
                $Obj = MwMappingVendor::where('vehicle_number',$data_mwmaping->vehicle_number)->update($data);
            }
            $this->data = $data_mwmaping;
            return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);    
        }catch(\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
            //Log::error($e);
            return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 500);
        }
    }

    public function EnsevalToMonica(Request $request){
        try{
            $vehicle1 = MasterVehicleDB::join('MsUserIntegration', 'MsUserIntegration.id','=', 'MsVehicle.user_integration_id')
                                    ->where('license_plate', $request->plat_nomor)
                                    ->select('MsVehicle.*', 'MsUserIntegration.name')
                                    ->first();

            $vehicle2 = MasterVehicleCpn::join('MsUserIntegration', 'MsUserIntegration.id','=', 'MsVehicleCPN.user_integration_id')
                                    ->where('license_plate', $request->plat_nomor)
                                    ->select('MsVehicleCPN.*', 'MsUserIntegration.name')
                                    ->first();

            if(empty($vehicle1) AND !empty($vehicle2)){
                $vehicle = $vehicle2;
            }

            if(!empty($vehicle1) AND empty($vehicle2)){
                $vehicle = $vehicle1;
            }

            $cekdataorder = sizeof($request->list_order);

            if(empty($vehicle1) and empty($vehicle2)){
                $res['responseCode'] =  "001";
                $res['status'] =  "NOK: Plat Nomor Tidak Ditemukan";
                return response($res, 200);
            }

            if($cekdataorder > 5){
                $res['responseCode'] =  "001";
                $res['status'] =  "NOK: Maximal 5 Order";
                return response($res, 200);
            }

            $data_order = array();
            $array_status = array('radius_order' => 1);
            for ($i = 0; $i < count($request->list_order); $i++) {
                $data_order[] = array_merge($request->list_order[$i],$array_status);  
            }

            $date_addtwoday = date('Y-m-d',strtotime($request->tanggal_sampai. ' + 2 days'));
            $date_mintwoday = date('Y-m-d',strtotime($date_addtwoday. ' - 2 days'));

            $tglsampai = date('Y-m-d',strtotime($request->tanggal_sampai));

            self::$temp = [
                'licence_plate'         => $vehicle->license_plate,
                'vehicle_id'            => $vehicle->id, 
                'id_trip'               => $request->id_trip,
                'finish_date'           => $date_mintwoday,
                'finishnot_senddate'    => $date_addtwoday,
                'list_order'            => $data_order,
                'status_trip'           => 1,
                'is_deleted'            => 0,
                'userintegration_id'    => $vehicle->user_integration_id,
                'userintegration_name'  => $vehicle->name
            ];

            $count_cektrip = IntegrationVendor::where('id_trip', $request->id_trip)->where('status_trip',1)->where('is_deleted',0)->count();

            if($count_cektrip > 0){
                $res['responseCode'] =  "001";
                $res['status'] =  "NOK: ".$request->id_trip. " - Trip Masih Dalam Perjalan";
                return response($res, 200);
            }else {
                $count_uniquecektrip = IntegrationVendor::where('id_trip', $request->id_trip)->where('is_deleted',0)->count();
                if($count_uniquecektrip > 0){
                    $res['responseCode'] =  "001";
                    $res['status'] =  "NOK: ".$request->id_trip. " - id_trip Harus Unique";
                    return response($res, 200);
                }else{
                    $data = IntegrationVendor::create(self::$temp);
                }
            }

            $res['responseCode'] =  "000";
            $res['status'] =  "OK";
            return response($res, 200);
        }catch(\Exception $e) {
            $res['responseCode'] =  "001";
            $res['status'] =  "NOK: ".$e->getMessage();
            return response($res, 200);
        }
    }

    public function MonicaToEnseval(){

        $parserConfigEnseval = ParserConfigIntegrationVendor::where('env', env('PARSER_ENV'))->where('id',1)->first();
        
        if(empty($parserConfigEnseval))
            throw new \Exception('Parser Configuration Not Found.');
        
        $databody = [
            "Vendor_id"  => $parserConfigEnseval->vendor_id
        ];

        // $loginparser = RestCurl::post($parserConfigEnseval->server_url.'/GPSVendor/api/oauth/token', $databody, ['Authorization: Basic '. $parserConfigEnseval->basic_outh]);

        // if($loginparser['status'] == 200){
        //     $access_token = trim($loginparser['data']->access_token);
        // }else{
        //     throw new \Exception(json_encode($loginparser));
        // }

        // Cron 5 Menit Sekali
        $vehicletrip = IntegrationVendor::where('status_trip',1)->where('list_order.radius_order',1)->get();
        if(sizeof($vehicletrip) < 1){
            echo "<pre>";
            print_r('Tidak Ada Trip');
            die();
        }else {
            foreach($vehicletrip as $valtrip){
                $datenow = date('Y-m-d',time());
                // CEK KONDISI Tanggal Sekarang Lebih Besar dari Tanggal Sampai + 2 hari
                if ($datenow > ($valtrip->finishnot_senddate)) {
                    $dataupd = ['status_trip' => 0,
                                'updated_at' => date('Y-m-d H:i:s',time())
                            ];
                    $Obj = IntegrationVendor::where('_id',$valtrip->_id)->update($dataupd);
    
                    echo "<pre>";
                    print_r('Trip Berakhir');
                    die();
                }else {
                    $data_mwmaping = MwMappingVendor::select('latitude','longitude','device_time','speed','ignition','direction')->where('vehicle_id',$valtrip->vehicle_id)->orderBy('device_time','desc')->first();
                    if(empty($data_mwmaping)){
                        echo "<pre>";
                        print_r('Error Data MwMappingVendor Tidak Ada');
                    }else {
                        $status_engine = 'ENGINE OFF';
                        if($data_mwmaping->ignition == 1){
                            $status_engine = 'ENGINE ON';
                        }
        
                        $status_vehicle = 'Stop';
                        if($data_mwmaping->ignition == 1 and $data_mwmaping->speed > 0){
                            $status_vehicle = 'Running';
                        }
                        if($data_mwmaping->ignition == 1 and $data_mwmaping->speed == 0){
                            $status_vehicle = 'Parking';
                        }
                        $datato_enseval = array();
    
                        $datato_enseval = [
                            'vendor_id'      => $parserConfigEnseval->vendor_id,
                            'id_trip'        => $valtrip->id_trip,
                            "plat_nomor"     => $valtrip->licence_plate,
                            "terminal"       => "",
                            "status_engine"  => $status_engine,
                            "longitude"      => (string)$data_mwmaping->longitude,
                            "latitude"       => (string)$data_mwmaping->latitude,
                            "direction"      => ($data_mwmaping->direction == ""  ? "0" : (string)$data_mwmaping->direction),
                            "speed"          => $data_mwmaping->speed,
                            "suhu"           => "",
                            "status_vehicle" => $status_vehicle,
                            "last_update"    => date('Y-m-d H:i:s',strtotime($data_mwmaping->device_time))
                        ];

                        $upd_last = IntegrationVendor::where('_id',$valtrip->_id)->update($datato_enseval);

                        for ($i = 0; $i < count($valtrip->list_order); $i++) {
    
                            if($valtrip->list_order[$i]['radius_order'] == 1){
                                $latitude = $valtrip->list_order[$i]['location_tujuan']['latitude'];
                                $longitude = $valtrip->list_order[$i]['location_tujuan']['longitude'];
            
                                // CEK RADIUS 500 Meter dari Lokasi TUJUAN di setiap Order
                                $cekradius_meter = round($this->distance($data_mwmaping->latitude, $data_mwmaping->longitude, $latitude, $longitude, "K") * 1000);
                                
                                if ($cekradius_meter <= 500) {
                                    $dataupdorder = ['list_order.radius_order' => 0,
                                                    'updated_at' => date('Y-m-d H:i:s',time())
                                                ];
                                    $Objorder = IntegrationVendor::where('_id',$valtrip->_id)->where('list_order.id_order',$valorder->id_order)->update($dataupdorder);
                                   
                                }else {
                                    $datato_enseval['list_order'][] = array(
                                        "id_order"             =>  $valtrip->list_order[$i]['id_order'],
                                        "status_order"         =>  3,
                                        "record_statusorder"   => array(
                                            "status_date"      => "",
                                            "status_latitude"  => "",
                                            "status_longitude" => ""
                                        )
                                    ); 
                                }    
                            }
                        }

                        $send_databody = $datato_enseval;                    
                        // POST To VENDOR
                        // $senddata = RestCurl::post($parserConfigEnseval->server_url.'/GPSVendor/api/pushlocdev', $send_databody, ['Authorization: Bearer '. $access_token]);
                        // if($senddata['data']->responseCode == "000"){
                        //     echo "<pre>";
                        //     print_r('OK');
                        // }else{
                        //     echo "<pre>";
                        //     print_r($senddata['data']->errorDesc);
                        // }
                    }
                }
            }    
        }
    }

    private function distance($lat1, $lon1, $lat2, $lon2, $unit) {
        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
          return 0;
        }
        else {
          $theta = $lon1 - $lon2;
          $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
          $dist = acos($dist);
          $dist = rad2deg($dist);
          $miles = $dist * 60 * 1.1515;
          $unit = strtoupper($unit);
      
          if ($unit == "K") {
            return ($miles * 1.609344);
          } else if ($unit == "N") {
            return ($miles * 0.8684);
          } else {
            return $miles;
          }
        }
    }


}
