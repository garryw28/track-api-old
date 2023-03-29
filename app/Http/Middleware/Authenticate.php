<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\UserIntegration as UserIntegrationDB;
use JWTAuth;
use Auth as authlogin;
use App\Helpers\Api;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if($guard == null or $guard == 'vendor') {
            if (! $token = $this->auth->setRequest($request)->getToken()) {
                return response()->json(Api::format('false', '', 'Token is not provided'), 200);
            }
        }else {
            if (! $request->header('Authorization')) {
                return response()->json(Api::format('false', '', 'Token is not provided'), 200);
            }
        }
        try {
            //if (authlogin::guard($guard)->check()) { }
            if($guard == "integration"){
                config()->set( 'auth.defaults.guard', 'integration');
                $token = $request->header('Authorization');
                //$user = JWTAuth::parseToken()->authenticate();
                $user = $this->doValidate($token);
            }
            if($guard == "vendor"){
                config()->set( 'auth.defaults.guard', 'vendor');
                $user = JWTAuth::parseToken()->authenticate();
            }
            if($guard == null){
                config()->set( 'auth.defaults.guard', 'api' );
                $user = JWTAuth::parseToken()->authenticate();
            }
        } catch (TokenExpiredException $e) {
            return response()->json(Api::format('false', '', 'JWT Token Expired'), 200);
        } catch (TokenInvalidException $e) {
            $message = $e->getMessage();
            return response()->json(Api::format('false', '', $message), 200);
        } catch (JWTException $e) {
            return response()->json(Api::format('false', '', 'There is a problem with JWT Token'), 200);
        }

        if($guard == null or $guard == 'vendor') {
            if (! $user) {
                return response()->json(Api::format('false', '', 'User not Found'), 200);
            }
    
            // if ($token != $user->device_token) 
            //     return response()->json(Api::format('false', '', 'JWT Token Expired'), 200);    
        }

        return $next($request);
    }

    private function doValidate($token){
        $counttoken = UserIntegrationDB::where('device_token',$token)->where('is_active',1)->count();
        if ($counttoken > 0) {
            return true;
        }else {
            throw new \Exception("The token is invalid.", 500);
        }
    }
}
