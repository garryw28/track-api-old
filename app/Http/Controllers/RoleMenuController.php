<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RoleMenu;
use App\Helpers\Api;
use App\User;

class RoleMenuController extends Controller
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
            $roleId = $request->roleId;
            $fleetGroupId = $request->fleetGroupId;
            $this->data = RoleMenu::join('MsRole', 'MsRoleMenu.role_id','=','MsRole.id')
                                  ->join('MsMenu', 'MsRoleMenu.menu_id','=','MsMenu.id')
                                  ->where('MsRoleMenu.role_id', $roleId)
                                  ->where('MsRoleMenu.fleet_group_id', $fleetGroupId)
                                  ->orderBy('MsMenu.created_at', 'ASC')
                                  ->select('MsRoleMenu.*', 'MsRole.role_name', 'MsMenu.menu_name', 'MsMenu.parent_id')
                                  ->get();
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function store(Request $request){
        $this->validate($request, 
                [
                 'role_id'        => 'required', 
                 'menu_id'        => 'required', 
                 'fleet_group_id' => 'required', 
                 'levels'         => 'required', 
                 'created_by'     => 'required'
                 ]);
        try {
            $getUser = User::where('role_id', $request->role_id)->where('fleet_group_id', $request->fleet_group_id)->update(['device_token' => '']);
            $this->data     = RoleMenu::create($request->all());
        } catch (\Exception $e) {
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function show($id=null){
        try{
            $this->data     = RoleMenu::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function update(Request $request, $id){
        try{
            $role = RoleMenu::find($id);
            $getUser = User::where('role_id', $role->role_id)->where('fleet_group_id', $role->fleet_group_id)->update(['device_token' => '']);
            if(!empty($role))
                $role->update($request->all());
                
            $this->data     = RoleMenu::find($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

    public function destroy($id=null){
        try{
            $role = RoleMenu::find($id);
            $getUser = User::where('role_id', $role->role_id)->where('fleet_group_id', $role->fleet_group_id)->update(['device_token' => '']);
            if(!empty($id))
                $this->data = RoleMenu::destroy($id);
        }catch(\Exception $e){
            $this->status   = "false";
            $this->errorMsg = $e->getMessage();
        }
        return response()->json(Api::format($this->status, $this->data, $this->errorMsg), 200);
    }

}

