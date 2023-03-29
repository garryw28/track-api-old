<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\MwMappingHistory;
use App\Models\MasterFleetGroup;
use App\Helpers\Api;
use App\Helpers\RestCurl;
use DB;
use Carbon\Carbon;

class MwMappingHistoryController extends Controller
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

    public function index(Request $request){		
        try{
            $limit = ($request->limit)? $request->limit : 6;
            $sort = ($request->sort == 'DESC')? 'DESC' : 'ASC';
            $result = MwMappingHistory::select('license_plate','direction','latitude', 'longitude', 'device_time', 'driver_name', 'driver_phone', 'event_type', 'ignition', 'speed')->where('license_plate',$request->license_plate);                                            
            $array_sub = [];

            if ($request->start_date)
                $result->whereBetween('device_time', [$request->start_date, $request->end_date]);
            if ($request->event_type)
                $result->where('event_type', $request->event_type);

            if ($request->last_track) {
                if ($request->event_type) {
                    $result->where('event_type', $request->event_type);
                }

                $dt = Carbon::now()->subMinutes(5)->format('Y-m-d H:i:s');
                $result = $result->orderBy('device_time', $sort)->limit(6)->get();                
                
                foreach ($result as $k => $v) {
                    $getAddress = RestCurl::get(env('REVERSE_GEOCODE').'/reversegeocoding', ['lat' => $v['latitude'], 'lng' => $v['longitude'], 'format' => 'JSON']);
                    $result[$k]['address'] = '';
                    if($getAddress['status'] == 200 && isset($getAddress['data']->result[0]) && !empty($getAddress['data']->result[0])){
                        $address = $getAddress['data']->result[0];
                        $result[$k]['address'] = $address->formatedFull;
                        if($result[$k]['device_time'] > $dt)
                            $array_sub[] = $result[$k];
                    }
                }

                if (!empty($array_sub))
                    $result = $array_sub;

            } else {
                $result = $result->orderBy('device_time', $sort);
                $data = $result->get()->toArray();
                $count = $result->count();
                $result = $data;
                $plus = 5;

                if ($count >= 1000)
                    $plus = 10;
                if ($count >= 5000)
                    $plus = 15;
                if ($count >= 10000)
                    $plus = 20;
                if ($count > 100) {
                    $result = [];
                    $a = 0;
                    do {
                        $result[] = $data[$a];
                        $a = $a + $plus;
                    } while ($a < $count);

                    $endData = end($data);
                    $endResult = end($result);
                    if($endData['_id'] != $endResult['_id'])
                        $result[] = $endData;
                }
                
            } 
            $this->data = $result;
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        try {            
            $param = $request->all();
            if (empty($param['imei']) || empty($id))
                return response()->json(Api::format("false", $this->data, "Error Processing Request. insert param first"), 200);
            
            MwMappingHistory::where('_id',$id)->where('imei', $param['imei'])->update($param);
            
            $mw_mapping_history = MwMappingHistory::find($id);
            if(!empty($mw_mapping_history)){                    
                foreach ($param as $k => $v) {
                    if ($v != $mw_mapping_history[$k])
                        return response()->json(Api::format("false", $this->data, "Error Processing Request. nothing updated"), 200);
                }
            }

            $this->data     = $mw_mapping_history;
        } catch (\Exception $e) {
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function getAlertHistory(Request $request){		
        try{
            $limit = 15;
            if($request->has('limit'))
                $limit = (int)$request->limit;
            
            $fleet_group = $this->getArrayFleetGroup($request->fleet_group_id);
            $history = MwMappingHistory::select('_id','license_plate','latitude', 'longitude', 'event_type', 'event_name', 'device_time', 'fleet_group_id', 'fleet_group_name', 'vehicle_id', 'speed', 'total_odometer', 'engine_speed_x', 'engine_coolant_temperature_x', 'digital_input_3', 'verified_by', 'verified_date', 'imei', 'verified_date', 'verified_by', 'dallas_temperature_1' )
                                            ->whereNotNull('event_name')
                                            ->whereIn('fleet_group_id', $fleet_group)
                                            ->whereNotIn('event_type', ['SAMPLING'])
                                            ->whereBetween('device_time', [$request->start_date, $request->end_date])
                                            ->orderBy('device_time', 'DESC');
                                            
            if($request->has('event_type'))
                $history->where('event_type', $request->event_type);
            if($request->has('license_plate'))
                $history->where('license_plate', $request->license_plate);
            if($request->has('is_new'))
                $history->where('verified_date', "");
            
            $this->data = $history->paginate($limit);
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function getAlertHistoryNotification(Request $request){
        try{
            $limit = 100;
            $fleet_group = $this->getArrayFleetGroup($request->fleet_group_id);
            $history = MwMappingHistory::select('license_plate','latitude', 'longitude', 'event_type', 'event_name', 'device_time', 'fleet_group_id', 'fleet_group_name', 'vehicle_id' )
                                            ->whereNotNull('event_name')
                                            ->whereIn('fleet_group_id', $fleet_group)
                                            ->whereNotIn('event_type', ['SAMPLING'])
                                            ->whereBetween('device_time', [$request->start_date, $request->end_date])
                                            ->orderBy('device_time', 'DESC');
                                            
            if($request->has('event_type'))
                $history->where('event_type', $request->event_type);
            if($request->has('license_plate')){
                $history->where('license_plate', $request->license_plate);
            }
            $result = [];
            $result['data'] = $history->limit(100)->get();
            $result['total'] = $history->count();

            $this->data = $result;
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = MwMappingHistory::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id=null){
        try{
            if(!empty($id))
                $this->data =  MwMappingHistory::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    private function getArrayFleetGroup($id = null){
        $fleet_group = MasterFleetgroup::where('id', $id)->with(['all_child'])->get()->toArray();
        $result_fleet_group = [];
        $result_fleet_group[] = $id;
        
        if (count($fleet_group[0]['all_child'])) {
            foreach ($fleet_group[0]['all_child'] as $v) {
                array_push($result_fleet_group, $v['id']);
                if (count($v['all_child'])) {
                    foreach ($v['all_child'] as $v2) {
                        array_push($result_fleet_group, $v2['id']);
                        if (count($v2['all_child'])) {
                            foreach ($v2['all_child'] as $v3) {
                                array_push($result_fleet_group, $v3['id']);
                                if (count($v3['all_child'])) {
                                    foreach ($v3['all_child'] as $v4) {
                                        array_push($result_fleet_group, $v4['id']);
                                        if (count($v4['all_child'])) {
                                            foreach ($v4['all_child'] as $v5) {
                                                array_push($result_fleet_group, $v5['id']);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result_fleet_group;
    }

}