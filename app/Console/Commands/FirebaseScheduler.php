<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;
use Kreait\Firebase\Messaging\Message;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use App\Helpers\RestCurl;
use App\Helpers\Api;
use Carbon\Carbon;
use App\Models\ParserConfig as ParserConfigDB;
use DB;
use App\Models\MasterFleetGroup as MasterFleetgroupDB;

class FirebaseScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firebasescheduler:sync';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Firebase Scheduler Execution';
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
        $serviceAccount = ServiceAccount::fromJsonFile(base_path().'/public/firebase_token.json');
        $firebase = (new Factory)
                    ->withServiceAccount($serviceAccount)
                    ->withDatabaseUri(env('REALTIME_DB'))
                    ->createDatabase();
                    
        // $database = $firebase->getDatabase();
        $reference = $firebase->getReference('alert');
        $reference->remove();

        $parser = ParserConfigDB::where('env', env('PARSER_ENV'))->first();

        $data = [
            'grant_type' => 'refresh_token',
            'client_id' => $parser->client_id,
            'client_secret' => $parser->client_secret,
            'refresh_token' => $parser->refresh_token,
            'scope' => $parser->scope
        ];

        $deviceGroup = RestCurl::post($parser->server_url.'/oauth/token', $data, ['Authorization: Bearer '. $parser->access_token]);
        if ($deviceGroup['status'] == 200) {
            $parser->update(
                [
                    'access_token' => $deviceGroup['data']->access_token, 
                    'refresh_token' => $deviceGroup['data']->refresh_token
                ]
            );
        }  
    }   
}
/*php artisan reservationscheduler:sync*/