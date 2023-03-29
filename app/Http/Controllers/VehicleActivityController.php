<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\MasterVehicleActivity as VehicleActivityDB;
use App\Helpers\Api;

class VehicleActivityController extends Controller
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

    public function index(){
        try{
            $this->data = VehicleActivityDB::all();
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request){
        $rules = [
            'activity_name' => 'required',
            'icon'          => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];

        $this->validate($request, $rules, $customMessages);

        $data = array ( 
            'activity_name' => trim($request->activity_name),
            'icon'          => trim($request->icon)
        );

        try {
            $this->data     = VehicleActivityDB::create($data);
        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = VehicleActivityDB::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        $rules = [
            'activity_name' => 'required',
            'icon'          => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];

        $this->validate($request, $rules, $customMessages);

        try{
            $vehicleactivity = VehicleActivityDB::find($id);
            if(!empty($vehicleactivity))
                $vehicleactivity->update($request->all());
                
            $this->data     = VehicleActivityDB::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id=null){
        try{
            if(!empty($id))
                $this->data = VehicleActivityDB::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

}

