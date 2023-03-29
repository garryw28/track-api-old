<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\User as UserDB;
use App\Models\MasterUserLoggingHistory;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Helpers\Api;

class AuthenticateController extends Controller
{
    public function __construct()
    {
        $this->status   = "true";
        $this->data     = [];
        $this->errorMsg = null;
    }

    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        try {
            $token = Auth::attempt($credentials);
            $user = UserDB::where('email', $request->email)->first();
            if(!$token) {
                if(!empty($user)){
                    if ($user['attempt'] < 5 ) {
                        $user->update(['attempt' => $user['attempt'] += 1]);
                    } else {
                        $user->update(['deactivate_date' => date('Y-m-d H:i:s', strtotime("+5 min"))]);

                        return response()->json(Api::format("false", $this->data, 'Please wait 5 minutes'), 200);        
                    }
                }
                return response()->json(Api::format("false", $this->data, 'Email or Password is incorrect'), 200);
            }

            if(Auth::user()->deactivate_date >= date('Y-m-d H:i:s'))
            {
                $date1=date_create(date('Y-m-d H:i:s'));
                $date2=date_create(Auth::user()->deactivate_date);
                $diff=date_diff($date1,$date2);
                $dt =$diff->format("%i Minute %s Seconds");

                return response()->json(Api::format("false", $this->data, 'Please wait '.$dt), 200);  
            }
                

            if(Auth::user()->is_active != 1)
                return response()->json(Api::format("false", $this->data, 'Your account is not activated'), 200);

        } catch (JWTException $e) {
            return response()->json(Api::format("false", $this->data, 'could_not_create_token'), 200);
        }
        
        $user =  Auth::user();
        $expired_date = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) + 60 * 60);
        $query = UserDB::where('id', Auth::user()->id)->with(['role', 'role.role_menu' => function($q) use ($user){
                                                                $q->join('MsMenu', 'MsRoleMenu.menu_id','=','MsMenu.id')->where('fleet_group_id', $user->fleet_group_id);
                                                            },'fleet_group']);
        $user_data = $query->get();
        $update = $query->update([
            'device_token' => $token,
            'deactivate_date' => null,
            'attempt' => null
        ]);  
        
        $logging_data = [
            'user_id' => Auth::user()->id,
            'server_time' => gmdate('Y-m-d H:i:s'),
            'device_time' => date('Y-m-d H:i:s'),
            'platform' => $request->has('platform')?$request->platform:""
        ];
        $add_history = MasterUserLoggingHistory::create($logging_data);

        return response()->json(Api::format($this->status, compact('token','user_data', 'expired_date'), $this->errorMsg), 200);
    }

    public function refreshToken(){
        $token = JWTAuth::getToken();
        $user =  Auth::user();
        $expired_date = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')) + 60 * 60);
        $query = UserDB::where('id', Auth::user()->id)->with(['role', 'role.role_menu' => function($q) use ($user){
                                                            $q->where('fleet_group_id', $user->fleet_group_id);
                                                        },'fleet_group']);
        
        if(empty($token)){
            return response()->json(Api::format("false", $this->data, 'Token is not provided'), 200);
        }else{
            $newToken = JWTAuth::refresh($token);
            $update = $query->update(['device_token' => $newToken]);
            return response()->json(Api::format("true", array('token'=>$newToken, 'expired_date' => $expired_date), $this->errorMsg), 200);
        }
    }

    public function logout(Request $request){
        try {
            $user_id = $request->user_id;
            $user = UserDB::find($user_id);
            if (!empty($user)) 
                $user->update(['device_token' => null]);
        } catch (\Exception $e) {
            return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
        }
        return response()->json(Api::format("true", compact('user_id'), ""), 200);
    }
}