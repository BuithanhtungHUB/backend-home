<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UserController extends Controller
{
    // house của user manager
    public function getHouseList()
    {
        if (auth()->user()->role == 'manager') {
            $id = auth()->user()->id;
            $user = User::find($id);
            $houses = House::with('category','images')->where('user_id', $id)->get();
            $data = ['user' => $user, 'houses' => $houses];
            return response()->json($data);
        } else {
            return response()->json(['error' => 'Bạn không phải manager'],403);
        }
    }

    // chủ update status house
    public function updateHouse($id, Request $request)
    {
        $house = House::find($id);
        if (auth()->user()->id == $house->user_id) {
            if ($house->status != $request->status) {
                $house->status = $request->status;
                $house->save();
                return response()->json(['success' => 'Update thành công']);
            }
            return response()->json(['error' => 'House đang ở trạng thái: ' . $house->status],403);
        } else {
            return response()->json(['error' => 'Đấy không là sản phẩm của bạn'],403);
        }
    }
}
