<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Helpers\RestCurl;
use App\Helpers\Api;
use App\Models\ParserConfig as ParserConfigDB;
use Carbon\Carbon;
use DB;

class ParserScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parserscheduler:sync';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Parser Scheduler Execution';
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
        $parser = ParserConfigDB::where('env', env('PARSER_ENV'))->first();

        $data = [
            'grant_type' => 'password',
            'client_id' => $parser->client_id,
            'client_secret' => $parser->client_secret,
            'username' => $parser->username,
            'password' => $parser->password,
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
        
        echo "<pre>";
        print_r($deviceGroup);
    }   
}
/*php artisan reservationscheduler:sync*/