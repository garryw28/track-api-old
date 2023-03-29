<?php

namespace App\Helpers;

use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;
use Kreait\Firebase\Messaging\Message;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\RawMessageFromArray;

Class Api{

	public static function format($status, $data, $ErrorMessage){
			$arr['status']    = !empty($status) ? $status : '';
			$arr['data']      = !empty($data) ? $data : '';
			$arr['error_msg'] = !empty($ErrorMessage) ? $ErrorMessage : '';
			return $arr;
	}

	public static function generateID($prefix, $lastID = 0, $length = 3){
		$prefix = $prefix;
		$number = $lastID+1;
		$unique = str_pad($number, $length , "0", STR_PAD_LEFT);
		$unique = $prefix . date('s') .$unique;
		return $unique;
	}


	public static function trueOrFalse($param){
		return boolval($param) ? "true" : "false";
	}
	
    public static function cryptoJsAesDecrypt($passphrase, $jsonString){
        $jsonString = base64_decode($jsonString);
        $jsondata = json_decode($jsonString, true);
        $salt = hex2bin($jsondata["s"]);
        $ct = base64_decode($jsondata["ct"]);
        $iv  = hex2bin($jsondata["iv"]);
        $concatedPassphrase = $passphrase.$salt;
        $md5 = array();
        $md5[0] = md5($concatedPassphrase, true);
        $result = $md5[0];
        for ($i = 1; $i < 3; $i++) {
            $md5[$i] = md5($md5[$i - 1].$concatedPassphrase, true);
            $result .= $md5[$i];
        }
        $key = substr($result, 0, 32);
        $data = openssl_decrypt($ct, 'aes-256-cbc', $key, true, $iv);
        return json_decode($data, true);
    }

    public static function cryptoJsAesEncrypt($passphrase, $value){
        $salt = openssl_random_pseudo_bytes(8);
        $salted = '';
        $dx = '';
        while (strlen($salted) < 48) {
            $dx = md5($dx.$passphrase.$salt, true);
            $salted .= $dx;
        }
        $key = substr($salted, 0, 32);
        $iv  = substr($salted, 32,16);
        $encrypted_data = openssl_encrypt(json_encode($value), 'aes-256-cbc', $key, true, $iv);
        $data = array("ct" => base64_encode($encrypted_data), "iv" => bin2hex($iv), "s" => bin2hex($salt));
        return json_encode($data);
    }

    public static function pushNotificationFormat($fleetGroupId, $title, $body, $description=null, $table='alert'){
        $serviceAccount = ServiceAccount::fromJsonFile(base_path().'/public/firebase_token.json');
        $firebase = (new Factory)
                    ->withServiceAccount($serviceAccount)
                    ->withDatabaseUri(env('REALTIME_DB'))
                    ->createDatabase();
                    
        $path = !empty($table) ? $table.'/' : '';
        // $database = $firebase->getDatabase();
        $reference = $firebase->getReference($path.$fleetGroupId)->remove();
        
        $newPost  = $firebase
                    ->getReference($path.$fleetGroupId)
                    ->push([
                        'title' => $title,
                        'body'  => $body
                    ]);
        $doStore = $newPost->getKey();
    }

}
