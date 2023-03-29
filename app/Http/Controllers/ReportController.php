<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterReport as Report;
use App\Helpers\Api;

class ReportController extends Controller
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
            $this->data = Report::with('power_bi')->get();
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request){
        $this->validate($request, ['name' => 'required', 'param' => 'required', 'label' => 'required', 'parser_power_bi_id' => 'required']);
        try {
            $this->data     = Report::create([
                'name' => $request->name, 
                'param' => $request->param, 
                'label' => $request->label,
                'parser_power_bi_id' => $request->parser_power_bi_id
                ]);
        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = Report::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        try{
            $report = Report::find($id);
            if(!empty($report))
                $report->update($request->all());
                
            $this->data     = Report::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id=null){
        try{
            if(!empty($id))
                $this->data = Report::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

}

