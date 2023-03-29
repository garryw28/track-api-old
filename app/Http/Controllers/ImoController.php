<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\ImoHistory;
use App\Helpers\Api;

class ImoController extends Controller
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
            $query = ImoHistory::orderBy('created_at','DESC');

            if (!empty($request->vehicle_id)) 
                $query->where('vehicle_id', $request->vehicle_id);
                
            $this->data = $query->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request){
		$rules = [
            'vehicle_id' => 'required',
            'commands' => 'required',
            'syntax' => 'required',
            'reason' => 'required',
            'created_by' => 'required'
        ];
        
        $customMessages = ['required' => ':attribute tidak boleh kosong'];

        $this->validate($request, $rules, $customMessages);

        try {
            $this->data     = ImoHistory::create($request->all());
        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = ImoHistory::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        $rules = [
            'vehicle_id' => 'required',
            'commands' => 'required',
            'syntax' => 'required',
            'reason' => 'required',
            'updated_by' => 'required'
        ];
        
        $customMessages = ['required' => ':attribute tidak boleh kosong'];

        $this->validate($request, $rules, $customMessages);

        try{
            $poi = ImoHistory::find($id);
            if(!empty($poi))
                $update = ImoHistory::where('id',$id)->update($request->all());
                
            $this->data     = ImoHistory::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id=null){
        try{
            if(!empty($id))
                $this->data =  ImoHistory::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }
}

