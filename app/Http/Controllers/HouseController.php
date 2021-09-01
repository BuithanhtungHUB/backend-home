<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateHouseRequest;
use App\Models\House;
use App\Models\User;

class HouseController extends Controller
{
    public function getAll()
    {
        $houses = House::with('user','category')->get();
        return response()->json($houses);
    }

    public function getById($id)
    {
        $house = House::with('user','category')->find($id);
        return response()->json($house);
    }

    public function create(CreateHouseRequest $request,House $house)
    {
        $user = User::find($request->user_id);
        if ($user->role == 'manager'){
            $house->user_id = $request->user_id;
            $house->name= $request->name;
            $house->category_id = $request->category_id;
            $house->address = $request->address;
            $house->bedroom = $request->bedroom;
            $house->bathroom = $request->bathroom;
            $house->description = $request->description;
            $house->price = $request->price;
            $house->status = $request->status;
            $house->save();
            return response()->json(['success'=>'Đăng nhà thành công']);
        }else{
            return response()->json(['error'=>'bạn không phải là manager']);
        }
    }
}
