<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\MasterVehicle as MasterVehicleDB;
use App\Models\MwMapping;
use App\Models\MasterFleetGroup;
use App\Helpers\Api;
use App\Helpers\RestCurl;
use Carbon\Carbon;
use App\Models\IntegrationVendor; 
use App\Models\ParserConfigIntegrationVendor;

class IntegrationScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integrationscheduler:sync';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Integration Scheduler Execution';
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->IsApproval  = ["Status" => false, "FinalApprovalStatus" => ""];
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $parserConfigEnseval = ParserConfigIntegrationVendor::where('env', env('PARSER_ENV'))->where('id',1)->first();
        
        if(empty($parserConfigEnseval))
            throw new \Exception('Parser Configuration Not Found.');
        
        $databody = [
            "Vendor_id"  => $parserConfigEnseval->vendor_id
        ];

        $loginparser = RestCurl::post($parserConfigEnseval->server_url.'/GPSVendor/api/oauth/token', $databody, ['Authorization: Basic '. $parserConfigEnseval->basic_outh]);

        if($loginparser['status'] == 200){
            $access_token = trim($loginparser['data']->access_token);
        }else{
            throw new \Exception(json_encode($loginparser));
        }

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
                        $senddata = RestCurl::post($parserConfigEnseval->server_url.'/GPSVendor/api/pushlocdev', $send_databody, ['Authorization: Bearer '. $access_token]);
                        if($senddata['data']->responseCode == "000"){
                            echo "<pre>";
                            print_r('OK');
                        }else{
                            echo "<pre>";
                            print_r($senddata['data']->errorDesc);
                        }
                    }
                }
            }    
        }    
    }   
}
/*php artisan reservationscheduler:sync*/