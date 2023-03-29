<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterReportMapping as ReportMapping;
use App\Models\MasterFleetGroup as MasterFleetgroupDB;
use App\Helpers\Api;

class ReportMappingController extends Controller
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
            $result = MasterFleetgroupDB::with(['report_mapping','report_mapping.power_bi', 'report_mapping.report.power_bi'])->has('report_mapping');

            if ($request->has('fleet_group_id')) 
                $result->where('id', $request->fleet_group_id);
            if ($request->has('fleet_group_name')) 
                $result->where('fleet_group_name', 'LIKE', "%".$request->fleet_group_name."%");
            if ($request->has('fleet_group_code')) 
                $result->where('fleet_group_code', 'LIKE', "%".$request->fleet_group_code."%");
 
            $this->data = $result->paginate(10);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function getFleetGroup(Request $request){
        try{
            $result = MasterFleetgroupDB::select('id', 'fleet_group_name')->has('report_mapping');

            if ($request->has('id')) 
                $result->where('id', $request->id);
            if ($request->has('fleet_group_name')) 
                $result->where('fleet_group_name', 'LIKE', "%".$request->fleet_group_name."%");
            if ($request->has('fleet_group_code')) 
                $result->where('fleet_group_code', 'LIKE', "%".$request->fleet_group_code."%");

            $this->data = $result->get();
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request){
        $rules = [
            'fleet_group_id'       => 'required',
            'parser_power_bi_id'   => 'required',
			'report_id'            => 'required',
            'created_by'           => 'required'
        ];

        $customMessages = [
           'required' => ':attribute tidak boleh kosong'
        ];
        $this->validate($request, $rules, $customMessages);

        try {
            $check = ReportMapping::where('fleet_group_id', $request->fleet_group_id)->where('report_id', $request->report_id)->first();
            if (!empty($check)) {
                return response()->json(Api::format("false", $this->data, "Error Processing Request. Report already exist"), 200);
            }
            $this->data     = ReportMapping::create($request->all());
        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = ReportMapping::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        try{
            $report = ReportMapping::find($id);
            if(!empty($report))
                $report->update($request->all());
                
            $this->data     = ReportMapping::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id=null){
        try{
            if(!empty($id))
                $this->data = ReportMapping::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

}

