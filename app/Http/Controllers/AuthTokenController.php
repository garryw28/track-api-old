<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\UserIntegration as UserIntegrationDB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Helpers\Api;

class AuthTokenController extends Controller
{
    public function __construct()
    {
        $this->status   = "true";
        $this->data     = [];
        $this->errorMsg = null;
    }

    public function loginIntegration(Request $request){
        try{
            $rules = [
                'vendor_code'   => 'required',
                'password'   => 'required'
            ];
    
            $customMessages = [
               'required' => ':attribute tidak boleh kosong'
            ];
            $this->validate($request, $rules, $customMessages);

            $credentials = $request->only(['vendor_code', 'password']);
            $token = Auth::guard('integration')->attempt($credentials);
            $uservendor = UserIntegrationDB::where('vendor_code',$request->vendor_code)->first();
            
            if(!$token) {
                return response()->json(Api::format("false", $this->data, 'vendor_code or password is incorrect'), 200);
            }

            if($uservendor->is_active != 1) {
                return response()->json(Api::format("false", $this->data, 'Your account is not activated'), 200);
            }

            $expired_date = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) + 60 * 60);

            $upduservendor = $uservendor->update([
                'expired_date' => $expired_date,
                'device_token' => $token
            ]);

            $data = array(
                'access_token' => $token,
                'token_type' => 'bearer',
                'expired_in' => Auth::factory()->getTTL() * 60
            );
            
            $this->data = $data;
    
            return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);

        }catch (JWTException $e) {  
            return response()->json(Api::format("false", $this->data, 'could_not_create_token'), 200);
        }
    }

    public function loginIntegrationVendor(Request $request){
        try{
            $rules = [
                'vendor_code'   => 'required',
                'password'   => 'required'
            ];
    
            $customMessages = [
               'required' => ':attribute tidak boleh kosong'
            ];
            $this->validate($request, $rules, $customMessages);

            $credentials = $request->only(['vendor_code', 'password']);
            config()->set('jwt.ttl', 4380*60);
            $token = Auth::guard('vendor')->attempt($credentials);
            $uservendor = UserIntegrationDB::where('vendor_code',$request->vendor_code)->first();
            
            if(!$token) {
                return response()->json(Api::format("false", $this->data, 'vendor_code or password is incorrect'), 200);
            }

            if($uservendor->is_active != 1) {
                return response()->json(Api::format("false", $this->data, 'Your account is not activated'), 200);
            }

            $expired_date = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) + (4380 * 60 * 60));

            $upduservendor = $uservendor->update([
                'expired_date' => $expired_date,
                'device_token' => $token
            ]);

            $data = array(
                'access_token' => $token,
                'token_type' => 'bearer',
                'expired_in' => 4380 * 60
            );
            
            $this->data = $data;
    
            return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);

        }catch (JWTException $e) {  
            return response()->json(Api::format("false", $this->data, 'could_not_create_token'), 200);
        }
    }
}