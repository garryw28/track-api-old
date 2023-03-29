<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;    
use App\Helpers\Api;
use App\User;
use Auth;
use Carbon\Carbon;


class UserController extends Controller
{
    public function __construct()
    {
        $this->status   = "true";
        $this->data     = [];
        $this->errorMsg = null;
    }

    public function index(Request $request)
    {
        try{
            $user = new User;
            if($request->has('fleet_group_id')){
                $user = $user->where('fleet_group_id', $request->fleet_group_id);
            }
            if($request->has('name')){
                $user = $user->where('name', 'like', "%$request->name%");
            }

            $this->data = $user->paginate(10);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'           => 'required',
            'email'          => 'required|unique:MsUser',
            'no_telp'        => 'required',
            'role_id'        => 'required',
            'fleet_group_id' => 'required',
            'created_by'     => 'required',
        ]);
        try {
            $input  = [
                'name'           => $request->name,
                'email'          => $request->email,
                'no_telp'        => $request->no_telp,
                'role_id'        => $request->role_id,
                'fleet_group_id' => $request->fleet_group_id,
                'is_active'      => 0,
                'created_by'     => $request->created_by,
                'expired_date'   => date('Y-m-d H:i:s')
            ];
            $this->data     = User::create($input);
            if($this->data){
                $param  = [
                    'name'  => $request->name, 
                    'email' => $request->email,
                    'token' => base64_encode(Api::cryptoJsAesEncrypt(env("APP_KEY"), $request->email))
                ];
                Mail::send('emails.registration', $param, function ($message) use ($param){
                    $message->from(SEND_FROM, 'FMS Customer Dashboard');
                    $message->to($param['email']);
                    $message->subject("Registration Successfull!");
                });
            }

        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show(Request $request, $id)
    {
        try{
            $this->data     = User::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name'           => 'sometimes|required',
            'email'          => 'sometimes|required',
            'no_telp'        => 'sometimes|required',
            'role_id'        => 'sometimes|required',
            'fleet_group_id' => 'sometimes|required',
            'is_active'      => 'sometimes|required'
        ]);
        try{
            $input = $request->except(['token']);
            $user  = User::find($id);
            if (!empty($request->password) || $request->role_id != $user->role_id || $request->fleet_group_id != $user->fleet_group_id) {
                if (!empty($request->password))
                    $input['password'] = Hash::make($request->password);                
                if ($request->role_id != $user->role_id)
                    $input['role_id'] = $request->role_id;
                if ($request->fleet_group_id != $user->fleet_group_id)
                    $input['fleet_group_id'] = $request->fleet_group_id;
                
                $input['device_token'] = '';
            }
            

            if($request->has('is_active'))
                $input['is_active'] = $request->is_active;
            else    
                $input['is_active'] = 1;

            if(!empty($user))
                $user->update($input);
                
            $this->data     = User::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id)
    {
        try{
            if(!empty($id))
                $this->data =  User::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function activation(Request $request, $code){
		try{
            $email  = Api::cryptoJsAesDecrypt(env("APP_KEY"), $code);
            $MsUser = User::where('email', $email)->first();
            if(empty($MsUser))
                throw new \Exception('User is not found.', 404);
            if(empty($MsUser->expired_date))
                throw new \Exception('Link already used.', 404);

            $createdAt = Carbon::parse($MsUser->expired_date);
            $expiredAt = $createdAt->diffInMinutes(Carbon::now());
            if($expiredAt >= 30)
                throw new \Exception('Token expired.', 404);

            // activate user
            User::where('email', $email)->update(['is_active' => 1]);

            $this->data = ['id' => $MsUser->id, 'name' => $MsUser->name];
		} catch(\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
		}
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    //after activation
    public function createPassword(Request $request, $code){
        $this->validate($request, [
            'password'         => 'required',
            'confirm_password' => 'required|same:password',
        ]);

		try{
            $email  = Api::cryptoJsAesDecrypt(env("APP_KEY"), $code);
            $MsUser = User::where('email', $email)->first();
            if(empty($MsUser))
                throw new \Exception('User is not found.', 404);

            $this->data = User::where('email', $email)->update([
                'password' => Hash::make($request->password),
                'expired_date' => null
            ]);
            
		} catch(\Exception $e) {
            $status   =  "false";
            $errorMsg = $e->getMessage();
		}
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function forgotPassword(Request $request){
        $this->validate($request, ['email' => 'required|email']);
        try{
            $MsUser = User::where('email', $request->email)->first();
            if(empty($MsUser))
                throw new \Exception('User not found.', 404);

            if($MsUser){
                $param  = [
                    'name'  => $MsUser->name, 
                    'email' => $MsUser->email,
                    'token' => base64_encode(Api::cryptoJsAesEncrypt(env("APP_KEY"), $MsUser->email))
                ];

                User::where('email', $request->email)->update(['expired_date' => date('Y-m-d H:i:s')]);

                Mail::send('emails.forgot_password', $param, function ($message) use ($param){
                    $message->from(SEND_FROM, 'FMS Customer Dashboard');
                    $message->to($param['email']);
                    $message->subject("Reset Password!");
                });
            }

            $this->data = 1;
		} catch(\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
		}
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function resendEmail($id){
        try{
            $this->data     = User::find($id);
            if($this->data){
                $param  = [ 
                    'name'  => $this->data['name'],
                    'email' => $this->data['email'],
                    'token' => base64_encode(Api::cryptoJsAesEncrypt(env("APP_KEY"), $this->data['email']))
                ];

                User::where('id', $id)->update(['expired_date' => date('Y-m-d H:i:s')]);

                Mail::send('emails.registration', $param, function ($message) use ($param){
                    $message->from(SEND_FROM, 'FMS Customer Dashboard');
                    $message->to($param['email']);
                    $message->subject("Resend Activation !");
                });
            }; 

		} catch(\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
		}
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }
}
