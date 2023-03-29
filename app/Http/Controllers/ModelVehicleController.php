<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModelVehicle;
use App\Helpers\Api;

class ModelVehicleController extends Controller
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
            $this->data = ModelVehicle::all();
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request){
        $this->validate($request, ['model_name' => 'required', 'brand_id' => 'required', 'created_by' => 'required']);
        try {
            $this->data     = ModelVehicle::create([
                                                'model_name' => strtoupper($request->model_name), 
                                                'brand_id'   => $request->brand_id, 
                                                'created_by' => $request->created_by
                                            ]);
        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = ModelVehicle::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        try{
            $ModelVehicle = ModelVehicle::find($id);
            if(!empty($ModelVehicle))
                $ModelVehicle->update($request->all());
                
            $this->data     = ModelVehicle::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id=null){
        try{
            if(!empty($id))
                $this->data = ModelVehicle::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

}

