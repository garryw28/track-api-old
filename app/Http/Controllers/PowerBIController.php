<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\ParserPowerBi as PowerBi;
use App\Helpers\Api;

class PowerBIController extends Controller
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

    public function login (){
        try{
            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_URL => "https://login.microsoftonline.com/common/oauth2/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "grant_type=password&scope=openid&resource=https%3A%2F%2Fanalysis.windows.net%2Fpowerbi%2Fapi&client_id=3c4e5e0e-6adb-4259-b3b6-95911970ca39&password=08101994Al&username=alj00005673%40trac.astra.co.id&client_secret=7n%40BAOIkv%3Aa21*pd8Ud%3FIIW%3Fy%2Fs6n%2BIi",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded",
                "postman-token: 2b0c38f0-ff4a-6dde-db41-0dc04913e1dd",
                "access-control-allow-credentials: true",
                "access-control-allow-origin: *",
                "Access-Control-Request-Method: POST"
            ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $response, $this->errorMsg), 200);
    }

    public function index(Request $request){
        try {
            $result = PowerBi::orderBy('id');
            if ($request->has('name')) 
                $result->where('name', $request->name);
            if ($request->has('datasets')) 
                $result->where('datasets', $request->datasets);
            if ($request->has('report_id')) 
                $result->where('report_id', $request->report_id);

            $this->data = $result->get();
        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        try {
            $power_bi = PowerBi::find($id);
            $data_put = [];
            
            if ($request->has('name'))
                $data_put['name'] = $request->name;
            if ($request->has('url'))
                $data_put['url'] = $request->url;
            if ($request->has('datasets'))
                $data_put['datasets'] = $request->datasets;
            if ($request->has('report_id'))
                $data_put['report_id'] = $request->report_id;

            $update = $power_bi->update($data_put);
            
            $this->data     = PowerBi::find($id);
        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

}