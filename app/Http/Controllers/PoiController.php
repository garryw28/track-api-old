<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\MasterPoi;
use App\Helpers\Api;

class PoiController extends Controller
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
            $query = MasterPoi::orderBy('updated_at','DESC');
            if (!empty($request->type)) 
                $query->where('type', ucwords($request->type));
            if (!empty($request->category_poi_id)) 
                $query->where('category_poi_id', $request->category_poi_id)->orderBy('updated_at','DESC');
            if (!empty($request->fleet_group_id)) 
                $query->where('fleet_group_id', $request->fleet_group_id)->orderBy('updated_at','DESC');
            if (!empty($request->lng) && !empty($request->lat)) {
                $radius = ($request->has('radius'))?$request->radius:2000;
                $query->where('coordinate', 'near', [
                    '$geometry' => [
                        'type' => 'Point',
                        'coordinates' => [(float)$request->lng,(float)$request->lat]],
                    '$maxDistance' => (float)$radius*0.0006213712
                ]);
            }
                
            $this->data = $query->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function showAll(Request $request){		
        try{
            $query = MasterPoi::orderBy('updated_at','DESC');
            if (!empty($request->type)) 
                $query->where('type', ucwords($request->type));
            if (!empty($request->category_poi_id)) 
                $query->where('category_poi_id', $request->category_poi_id)->orderBy('updated_at','DESC');
            if (!empty($request->fleet_group_id)) 
                $query->where('fleet_group_id', $request->fleet_group_id)->orderBy('updated_at','DESC');
            if (!empty($request->input('coordinates'))) {
                $array_rectangel = $request->input('coordinates');
                $highestRow = count($array_rectangel);
                for ($row = 0; $row < $highestRow; $row++){
                    $geofencedetail[] = array (
                        (float)trim(str_replace(",",".",$array_rectangel[$row]['longitude'])),
                        (float)trim(str_replace(",",".",$array_rectangel[$row]['latitude']))
                    );
                }

                $geofencedetail[] = $geofencedetail[0];
                $query->where('coordinate', 'geoWithin', [
                        '$geometry' => ['type' => 'Polygon', 'coordinates' => [$geofencedetail]
                    ]
                ]);
            }

            $this->data = $query->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request){
		$rules = [
			'name' => 'required',
            'address' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'type' => 'required',
            'category_poi_id' => 'required',
            'poi_code' => 'required',
            'created_by' => 'required'
        ];

        if ($request->input('type') == 'spesific') 
            $rules['fleet_group_id'] = 'required';
        
        $customMessages = ['required' => ':attribute tidak boleh kosong'];

        $this->validate($request, $rules, $customMessages);

        try {
            $check_poi = MasterPoi::where('poi_code', $request->poi_code)->first();
            if (!empty($check_poi)) 
                return response()->json(Api::format("false", $this->data, "Error Processing Request. POI already exist"), 200);
            
            $data_post = [
                'name' => $request->name,
                'address' => $request->address,
                'type' => $request->type,
                'category_poi_id' => $request->category_poi_id,
                'poi_code' => $request->poi_code,
                'created_by' => $request->created_by,
                'phone' => ($request->has('phone'))?$request->phone:"",
                'operating_time' => ($request->has('operating_time'))?$request->operating_time:"",
                'brand' => ($request->has('brand'))?$request->brand:"",
                'city' => ($request->has('city'))?$request->city:"",
                'province' => ($request->has('province'))?$request->province:"",
                'vmd' => ($request->has('vmd'))?$request->vmd:"",
                'status' => ($request->has('status'))?$request->status:"",
                'coordinate' => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float)trim(str_replace(",",".",$request->longitude)),
                        (float)trim(str_replace(",",".",$request->latitude))
                    ]
                ]
            ];

            $this->data     = MasterPoi::create($data_post);
        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = MasterPoi::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        $rules = [
			'name' => 'required',
            'address' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'type' => 'required',
            'category_poi_id' => 'required',
            'updated_by' => 'required'
        ];

        if ($request->input('type') == 'spesific') 
            $rules['fleet_group_id'] = 'required';

        $customMessages = ['required' => ':attribute tidak boleh kosong'];

        $this->validate($request, $rules, $customMessages);

        try{
            $poi = MasterPoi::find($id);
            if(!empty($poi))
                $update = MasterPoi::where('_id',$id)->update($request->all());
                
            $this->data     = MasterPoi::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id=null){
        try{
            if(!empty($id))
                $this->data =  MasterPoi::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }
}

