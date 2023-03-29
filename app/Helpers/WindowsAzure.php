<?php

namespace App\Helpers;

use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\ServiceBus\Models\BrokeredMessage;
use Log;

Class WindowsAzure{
    public static function sendQueueMessage($connectionString, $queue, $param){
        $serviceBusRestProxy = ServicesBuilder::getInstance()->createServiceBusService($connectionString);
        try {
            $message = new BrokeredMessage();
            $message->setBody(json_encode($param));
            $serviceBusRestProxy->sendQueueMessage($queue, $message);
        } catch(ServiceException $ebus){
            // Handle exception based on error codes and messages.
            // Error codes and messages are here: 
            // https://docs.microsoft.com/rest/api/storageservices/Common-REST-API-Error-Codes
            Log::error("Error code ".$ebus->getCode()." : ".$ebus->getMessage());
            throw $ebus;
        } catch(Exeption $e){
            Log::error("Unexpected Error ".$ebus->getMessage());
            throw $e;
        }
        // return $serviceBusRestProxy;
    }
}