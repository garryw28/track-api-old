<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\AlertMappingDetail;
use App\Helpers\Api;

class AlertMappingDetailController extends Controller
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
            $query = AlertMappingDetail::orderBy('created_at','DESC');

            if (!empty($request->alert_mapping_id)) 
                $query->where('alert_mapping_id', $request->alert_mapping_id);
                
            $this->data = $query->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request){
		$rules = [
            'alert_mapping_id' => 'required',
            'role_id' => 'required',
            'media_type_id' => 'required',
            'created_by' => 'required'
        ];
        
        $customMessages = ['required' => ':attribute tidak boleh kosong'];

        $this->validate($request, $rules, $customMessages);

        try {
            $this->data     = AlertMappingDetail::create($request->all());
        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = AlertMappingDetail::find($id); 
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        $rules = [
            'alert_mapping_id' => 'required',
            'role_id' => 'required',
            'media_type_id' => 'required',
            'updated_by' => 'required'
        ];
        
        $customMessages = ['required' => ':attribute tidak boleh kosong'];

        $this->validate($request, $rules, $customMessages);

        try{
            $poi = AlertMappingDetail::find($id);
            if(!empty($poi))
                $update = AlertMappingDetail::where('id',$id)->update($request->all());
                
            $this->data     = AlertMappingDetail::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id=null){
        try{
            if(!empty($id))
                $this->data =  AlertMappingDetail::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }
}

