<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Api;
use App\Models\ParserConfig as ParserConfigDB;
use App\Helpers\RestCurl;

class ParserDeviceController extends Controller
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

        $this->config = ParserConfigDB::where('env', env('PARSER_ENV'))->first();
    }

    public function listDeviceGroup(){
        try{
            if(empty($this->config))
                throw new \Exception('Parser Configuration Not Found.');

            $deviceGroup = RestCurl::get($this->config->server_url.'/api/v1/backend/device-groups', [], ['Authorization: Bearer '. $this->config->access_token]);
            if($deviceGroup['status'] != 200)
                throw new \Exception('Parser Error.');

            $this->data = $deviceGroup['data']->data;
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function listDeviceModel(){
        try{
            if(empty($this->config))
                throw new \Exception('Parser Configuration Not Found.');

            $deviceModel = RestCurl::get($this->config->server_url.'/api/v1/backend/device-models', [], ['Authorization: Bearer '. $this->config->access_token]);
            if($deviceModel['status'] != 200)
                throw new \Exception('Parser Error.');

            $this->data = $deviceModel['data']->data;
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function listDeviceTypes(){
        try{
            if(empty($this->config))
                throw new \Exception('Parser Configuration Not Found.');

            $deviceType = RestCurl::get($this->config->server_url.'/api/v1/backend/device-types', [], ['Authorization: Bearer '. $this->config->access_token]);
            if($deviceType['status'] != 200)
                throw new \Exception('Parser Error.');

            $this->data = $deviceType['data']->data;
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function listDeviceEndPoints(){
        try {
            if(empty($this->config))
                throw new \Exception('Parser Configuration Not Found.');

            $deviceType = RestCurl::get($this->config->server_url.'/api/v1/backend/end-points', [], ['Authorization: Bearer '. $this->config->access_token]);
            if($deviceType['status'] != 200)
                throw new \Exception('Parser Error.');

            $this->data = $deviceType['data']->data;
        } catch (\Throwable $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

}

