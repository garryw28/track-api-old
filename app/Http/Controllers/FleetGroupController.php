<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\MasterFleetGroup as MasterFleetgroupDB;
use App\Helpers\Api;

class FleetGroupController extends Controller
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
            $this->data = MasterFleetgroupDB::orderBy('updated_at','DESC')->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request){
		$rules = [
			'fleet_group_name' => 'required',
			'fleet_group_code' => 'required',
            'levels' => 'required',
            'created_by' => 'required',
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);
		
		$data = array ( 
            'fleet_group_name' => trim($request->input('fleet_group_name')),
            'fleet_group_code' => trim($request->input('fleet_group_code')),
            'levels' => trim($request->input('levels')),
            'parent_id' => trim($request->input('parent_id')),
            'created_by' => trim($request->input('created_by')),
            'is_fuel_consumption' => trim($request->input('is_fuel_consumption'))
        );

        try {
            $this->data     = MasterFleetgroupDB::create($data);
        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = MasterFleetgroupDB::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        $rules = [
			'fleet_group_name' => 'required',
			'fleet_group_code' => 'required',
            'levels' => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);

        try{
            $fleetgroup = MasterFleetgroupDB::find($id);
            if(!empty($fleetgroup))
                $fleetgroup->update($request->all());
                
            $this->data     = MasterFleetgroupDB::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id=null){
        try{
            if(!empty($id))
                $this->data =  MasterFleetgroupDB::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function getAllChild($id=null){
        try{
            $this->data     = MasterFleetgroupDB::where('id', $id)->with(['all_child'])->get();
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

}

