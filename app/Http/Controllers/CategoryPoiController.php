<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\MasterCategoryPoi;
use App\Helpers\Api;

class CategoryPoiController extends Controller
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
            $this->data = MasterCategoryPoi::orderBy('updated_at','DESC')->get();
        }catch(\Exception $e){
            $this->status   = false;
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request){
		$rules = [
			'catpoi_name' => 'required',
            'catpoi_code' => 'required',
            'created_by' => 'required',
            'images' => 'required',
            'marker' => 'required',
        ];

        $customMessages = ['required' => ':attribute tidak boleh kosong'];

        $this->validate($request, $rules, $customMessages);
        
        $filename = $request->input('catpoi_code');
        $img = $request->input('images');
        $marker = $request->input('marker');

        list($type, $img) = explode(';', $img);
        list(, $img)      = explode(',', $img);
        $img = base64_decode($img);
        $ext = explode('/', $type);
        
        file_put_contents('uploads/'.$filename.'.'.$ext[1], $img);

        list($type, $marker) = explode(';', $marker);
        list(, $marker)      = explode(',', $marker);
        $marker = base64_decode($marker);
        $ext = explode('/', $type);
        
        file_put_contents('uploads/'.$filename.'-marker.'.$ext[1], $marker);

		$data = array ( 
            'catpoi_name' => trim($request->input('catpoi_name')),
            'catpoi_code' => trim($request->input('catpoi_code')),
            'created_by' => trim($request->input('created_by')),
            'images' => '/uploads/'.$filename.'.'.$ext[1],
            'marker' => '/uploads/'.$filename.'-marker.'.$ext[1]
        );

        try {
            $this->data     = MasterCategoryPoi::create($data);
        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }

        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = MasterCategoryPoi::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        $rules = [
			'catpoi_name' => 'required',
            'catpoi_code' => 'required'
        ];

        $customMessages = ['required' => ':attribute tidak boleh kosong'];
        
        $this->validate($request, $rules, $customMessages);

        try{
            $update_data = array ( 
                'catpoi_name' => trim($request->input('catpoi_name')),
                'catpoi_code' => trim($request->input('catpoi_code'))
            );
    
            $filename = $request->input('catpoi_code');

            if (!empty($request->input('images'))) {
                $img = $request->input('images');
    
                list($type, $img) = explode(';', $img);
                list(, $img)      = explode(',', $img);

                $img = base64_decode($img);
                $ext = explode('/', $type);
                
                file_put_contents('uploads/'.$filename.'.'.$ext[1], $img);

                $update_data['images'] = '/uploads/'.$filename.'.'.$ext[1];
            }
    
            if (!empty($request->input('marker'))) {
                $marker = $request->input('marker');
    
                list($type, $marker) = explode(';', $marker);
                list(, $marker)      = explode(',', $marker);

                $marker = base64_decode($marker);
                $ext = explode('/', $type);
                
                file_put_contents('uploads/'.$filename.'-marker.'.$ext[1], $marker);

                $update_data['marker'] = '/uploads/'.$filename.'-marker.'.$ext[1];
            }

            $cat_poi = MasterCategoryPoi::where('id',$id)->update($update_data);
                
            $this->data     = MasterCategoryPoi::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id=null){
        try{
            if(!empty($id))
                $this->data =  MasterCategoryPoi::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }
}

