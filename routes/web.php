<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function(){
    return response()->json(['Welcome to Customer Dashboard'], 200);
});

$router->group(['prefix' => 'integration', 'middleware' => 'cors'], function() use ($router){
    
    //$router->post('oauth/token', 'AuthTokenController@loginIntegration');
    // TOKEN STATIC
    $router->group(['middleware' => 'auth:integration'], function() use ($router){
        $router->get('newbrand', 'BrandController@index');
        //INTEGRASI To VENDOR
        $router->post('enseval/addtrip', 'IntegrationController@EnsevalToMonica');
        
        //INTEGRASI XLOCATE to MONICA
        $router->post('cpn/xlocate_mapping', 'XlocateMappingController@store');

        $router->get('enseval/cron', 'IntegrationController@MonicaToEnseval'); 
    });

});

/*$router->group(['prefix' => 'vendor', 'middleware' => 'cors'], function() use ($router){
    
    $router->post('oauth/token', 'AuthTokenController@loginIntegrationVendor');

    $router->group(['middleware' => 'auth:vendor'], function() use ($router){
        $router->get('newbrand', 'BrandController@index');
        $router->post('cpn/xlocate_mapping', 'XlocateMappingController@store');
    });

});*/

$router->group(['prefix' => 'api',  'middleware' => 'cors'], function() use ($router){
    // User
    $router->post('login', 'AuthenticateController@login');
    $router->post('logout', 'AuthenticateController@logout');
    $router->get('refresh-token', 'AuthenticateController@refreshToken');
    $router->get('user/activation/{code}', 'UserController@activation');
    $router->put('create-password/{code}', 'UserController@createPassword');
    $router->get('forgot-password', 'UserController@forgotPassword');

    $router->group(['middleware' => 'auth'], function() use ($router){
        $router->get('user', 'UserController@index');
        $router->get('user/{id}', 'UserController@show');
        $router->post('user', 'UserController@store');
        $router->put('user/{id}', 'UserController@update');
        $router->delete('user/{id}', 'UserController@destroy');
        $router->get('user-resend/{id}', 'UserController@resendEmail');

        // Role
        $router->get('role', 'RoleController@index');
        $router->get('role/{id}', 'RoleController@show');
        $router->post('role', 'RoleController@store');
        $router->put('role/{id}', 'RoleController@update');
        $router->delete('role/{id}', 'RoleController@destroy');

        // Menu
        $router->get('menu', 'MenuController@index');
        $router->get('menu/{id}', 'MenuController@show');
        $router->post('menu', 'MenuController@store');
        $router->put('menu/{id}', 'MenuController@update');
        $router->delete('menu/{id}', 'MenuController@destroy');

        // RoleMenu
        $router->get('role-menu', 'RoleMenuController@index');
        $router->get('role-menu/{id}', 'RoleMenuController@show');
        $router->post('role-menu', 'RoleMenuController@store');
        $router->put('role-menu/{id}', 'RoleMenuController@update');
        $router->delete('role-menu/{id}', 'RoleMenuController@destroy');

        // Brand
        $router->get('brand', 'BrandController@index');
        $router->get('brand/{id}', 'BrandController@show');
        $router->post('brand', 'BrandController@store');
        $router->put('brand/{id}', 'BrandController@update');
        $router->delete('brand/{id}', 'BrandController@destroy');

        // Model
        $router->get('model', 'ModelVehicleController@index');
        $router->get('model/{id}', 'ModelVehicleController@show');
        $router->post('model', 'ModelVehicleController@store');
        $router->put('model/{id}', 'ModelVehicleController@update');
        $router->delete('model/{id}', 'ModelVehicleController@destroy');

        // Level 
        $router->get('level', 'LevelController@index');
        $router->get('level/{id}', 'LevelController@show');
        $router->post('level', 'LevelController@store');
        $router->put('level/{id}', 'LevelController@update');
        $router->delete('level/{id}', 'LevelController@destroy');

        // Fleet Group
        $router->get('fleet-group', 'FleetGroupController@index');
        $router->get('fleet-group/{id}', 'FleetGroupController@show');
        $router->post('fleet-group', 'FleetGroupController@store');
        $router->put('fleet-group/{id}', 'FleetGroupController@update');
        $router->delete('fleet-group/{id}', 'FleetGroupController@destroy');
        $router->get('fleet-group/child/{id}', 'FleetGroupController@getAllChild');

        // Alert
        $router->get('alert', 'AlertController@index');
        $router->get('alert/{id}', 'AlertController@show');
        $router->post('alert', 'AlertController@store');
        $router->put('alert/{id}', 'AlertController@update');
        $router->delete('alert/{id}', 'AlertController@destroy');

        // Alert Mapping
        // $router->get('alert-mapping', 'AlertMappingController@index');
        $router->get('alert-mapping', 'AlertMappingController@getAllMapping');
        $router->get('alert-mapping/{id}', 'AlertMappingController@show');
        $router->post('alert-mapping', 'AlertMappingController@store');
        $router->put('alert-mapping/{id}', 'AlertMappingController@update');
        $router->delete('alert-mapping/{id}', 'AlertMappingController@destroy');

        // Alert Mapping Detail
        $router->get('alert-mapping-detail', 'AlertMappingDetailController@index');
        $router->get('alert-mapping-detail/{id}', 'AlertMappingDetailController@show');
        $router->post('alert-mapping-detail', 'AlertMappingDetailController@store');
        $router->put('alert-mapping-detail/{id}', 'AlertMappingDetailController@update');
        $router->delete('alert-mapping-detail/{id}', 'AlertMappingDetailController@destroy'); 

        // Vehicle
        $router->get('vehicle', 'VehicleController@index');
        $router->get('vehicle/{id}', 'VehicleController@show');
        $router->get('vehicle/fleet-group/{id}', 'VehicleController@showFleetGroup');
        $router->post('vehicle', 'VehicleController@store');
        $router->post('vehiclebulk', 'VehicleController@storeBulk');    
        $router->put('vehicle/{id}', 'VehicleController@update');
        $router->put('vehicle/status/{vin}', 'VehicleController@updateStatusBulk');
        $router->put('vehiclebulk/{vin}', 'VehicleController@updateBulk');    
        $router->delete('vehicle/{id}', 'VehicleController@destroy');

        // Vehicle Second OBD
        $router->get('vehicle-secondobd', 'VehicleSecondObdController@index');
        $router->get('vehicle-secondobd/{id}', 'VehicleSecondObdController@show');
        $router->put('vehicle-secondobd/{id}', 'VehicleSecondObdController@update');
        $router->get('vehicle-secondobd/fleet-group/{id}', 'VehicleSecondObdController@showFleetGroup');

        // Geofence
        $router->get('geofence', 'GeofenceController@index');
        $router->get('geofence/{id}', 'GeofenceController@show');
        $router->post('geofence', 'GeofenceController@store');
        $router->put('geofence/{id}', 'GeofenceController@update');
        $router->delete('geofence/{id}', 'GeofenceController@destroy');

        $router->post('geofence_test', 'GeofenceController@storeTest');
        $router->put('geofence_test/{id}', 'GeofenceController@updateTest');

        // Geofence Detail
        $router->get('geofence-detail', 'GeofenceDetailController@index');
        $router->get('geofence-detail/{id}', 'GeofenceDetailController@show');
        $router->get('geofence-detail/geofence/{id}', 'GeofenceDetailController@showGeofence');
        $router->post('geofence-detail', 'GeofenceDetailController@store');
        $router->put('geofence-detail/{id}', 'GeofenceDetailController@update');
        $router->delete('geofence-detail/{id}', 'GeofenceDetailController@destroy');

        // Geofence Vehicle
        $router->get('geofence-vehicle', 'GeofenceVehicleController@index');
        $router->get('geofence-vehicle/geofence/{id}', 'GeofenceVehicleController@showGeofenceVehicle');
        $router->get('geofence-vehicle/{id}', 'GeofenceVehicleController@show');
        $router->post('geofence-vehicle', 'GeofenceVehicleController@store');
        $router->post('geofence-vehiclebulk', 'GeofenceVehicleController@storeBulk');
        $router->put('geofence-vehicle/{id}', 'GeofenceVehicleController@update');
        $router->put('geofence-vehiclebulk/geofence/{id}', 'GeofenceVehicleController@updateBulk');
        $router->delete('geofence-vehicle/{id}', 'GeofenceVehicleController@destroy');

        // Vehicle Maintenance
        $router->get('vehicle-maintenance', 'VehicleMaintenanceController@index');
        $router->post('vehicle-maintenance', 'VehicleMaintenanceController@takeOutEmai');
        $router->put('vehicle-maintenance', 'VehicleMaintenanceController@reProvisioning');
        $router->post('vehicle-maintenance/batch', 'VehicleMaintenanceController@updateBatch');

        // MONITORING
        $router->get('vehicle-monitoring/filter', 'MonitoringController@filter');
        $router->post('vehicle-monitoring/filter/license-plate', 'MonitoringController@filterLicensePlate');
        $router->get('vehicle-monitoring/cluster/fleet-group/{id}', 'MonitoringController@cluster');
        $router->get('vehicle-monitoring/cluster/mw-mapping/{id}', 'MonitoringController@clusterDetail');
        $router->get('vehicle-monitoring/child/fleet-group/{id}', 'MonitoringController@getAllChild');
        $router->get('vehicle-monitoring/child-vehicle/fleet-group/{id}', 'MonitoringController@childVehicle');        
        
        // Dashboard Per Day
        $router->get('total-vehicle/fleet-group/{id}', 'DashboardController@totalVehicle');
        $router->get('geofence-violation/fleet-group/{id}', 'DashboardController@geofenceViolation');
        $router->get('moving-vehicle/fleet-group/{id}', 'DashboardController@movingVehicle');
        $router->get('idle-vehicle/fleet-group/{id}', 'DashboardController@idleVehicle');
        $router->get('stop-vehicle/fleet-group/{id}', 'DashboardController@stopVehicle');
        $router->get('silent-vehicle/fleet-group/{id}', 'DashboardController@silentVehicle');
        $router->get('total-vehicle/fleet-group/{id}/list', 'DashboardController@totalVehicleParam');

        // MiddleWare Mapping
        $router->get('mw-mapping/download/{type}', 'MwMappingController@index');
        $router->get('mw-mapping/{id}', 'MwMappingController@show');
        $router->put('mw-mapping/{id}', 'MwMappingController@update');
        $router->delete('mw-mapping/{id}', 'MwMappingController@destroy');
        $router->get('mw-mapping/cluster/{fleet_group_id}', 'MwMappingController@cluster');

        //Subscribe & Unsubscribe
        $router->get('mw-mapping/subscribe/{vehicleNumber}', 'MwMappingController@subscribe');
        $router->get('mw-mapping/unsubscribe/{vehicleNumber}', 'MwMappingController@unsubscribe');

        //Mw Mapping History
        $router->get('mw-mapping-history', 'MwMappingHistoryController@index');
        $router->get('mw-mapping-history/alert', 'MwMappingHistoryController@getAlertHistory');
        $router->get('mw-mapping-history/notification', 'MwMappingHistoryController@getAlertHistoryNotification');
        $router->get('mw-mapping-history/{id}', 'MwMappingHistoryController@show');
        $router->put('mw-mapping-history/{id}', 'MwMappingHistoryController@update');

        // Group Device Parse
        $router->get('device-groups', 'ParserDeviceController@listDeviceGroup');
        $router->get('device-models', 'ParserDeviceController@listDeviceModel');
        $router->get('device-types', 'ParserDeviceController@listDeviceTypes');
        $router->get('end-points', 'ParserDeviceController@listDeviceEndPoints');

        // Vehicle Activity
        $router->get('vehicle-activity', 'VehicleActivityController@index');
        $router->get('vehicle-activity/{id}', 'VehicleActivityController@show');
        $router->post('vehicle-activity', 'VehicleActivityController@store');
        $router->put('vehicle-activity/{id}', 'VehicleActivityController@update');
        $router->delete('vehicle-activity/{id}', 'VehicleActivityController@destroy');

        // Category POI
        $router->get('category_poi', 'CategoryPoiController@index');
        $router->get('category_poi/{id}', 'CategoryPoiController@show');
        $router->post('category_poi', 'CategoryPoiController@store');
        $router->put('category_poi/{id}', 'CategoryPoiController@update');
        $router->delete('category_poi/{id}', 'CategoryPoiController@destroy');

        // POI
        $router->get('poi', 'PoiController@index');
        $router->get('poi/{id}', 'PoiController@show');
        $router->post('show_poi', 'PoiController@showAll');
        $router->post('poi', 'PoiController@store');
        $router->put('poi/{id}', 'PoiController@update');
        $router->delete('poi/{id}', 'PoiController@destroy');

        // IMO
        $router->get('imo', 'ImoController@index');
        $router->get('imo/{id}', 'ImoController@show');
        $router->post('imo', 'ImoController@store');
        $router->put('imo/{id}', 'ImoController@update');
        $router->delete('imo/{id}', 'ImoController@destroy');

        // REPORT
        $router->post('report', 'ReportController@store');
        $router->put('report/{id}', 'ReportController@update');
        $router->get('report', 'ReportController@index');
        $router->get('report/{id}', 'ReportController@show');
        $router->delete('report/{id}', 'ReportController@destroy');

        // REPORT MAPPING
        $router->get('report-mapping', 'ReportMappingController@index');
        $router->get('report-mapping/fleet-group', 'ReportMappingController@getFleetGroup');
        $router->get('report-mapping/{id}', 'ReportMappingController@show');
        $router->post('report-mapping', 'ReportMappingController@store');
        $router->put('report-mapping/{id}', 'ReportMappingController@update');
        $router->delete('report-mapping/{id}', 'ReportMappingController@destroy');

        // Driver Pairing
        $router->get('driver', 'DriverPairingController@pairing');

        // Vehicle Category
        $router->get('vehicle-category', 'VehicleController@category');

        // Get Address
        $router->get('address', 'MonitoringController@getAddress');
    });

    // consumed by middleware
    $router->post('mw-mapping', 'MwMappingController@store');
    $router->post('mw-mapping-gps', 'MwMappingGpsController@store');
    $router->post('mw-mapping-geofence', 'MwMappingController@storeGeofence');
    $router->post('mw-mappingvendor', 'IntegrationController@storeVendor');

    //Login PowerBI
    $router->post('login/power-bi', 'PowerBIController@login');
    $router->get('power-bi', 'PowerBIController@index');
    $router->put('power-bi/{id}', 'PowerBIController@update');
});
