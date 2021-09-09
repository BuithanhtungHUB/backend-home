<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateHouseRequest;
use App\Models\House;
use App\Models\Image;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HouseController extends Controller
{
    public function getAll()
    {
        $houses = House::with('user', 'category', 'images')->get();
        return response()->json($houses);
    }

    // thông tin 1 house
    public function getById($id)
    {
        $house = House::with('user', 'category', 'images')->find($id);
        return response()->json($house);
    }

    // manager tạo house
    public function create(CreateHouseRequest $request, House $house, Image $image)
    {
        $user = User::find($request->user_id);
        if ($user->role == 'manager') {
            $house->user_id = $request->user_id;
            $house->name = $request->name;
            $house->category_id = $request->category_id;
            $house->address = $request->address;
            $house->bedroom = $request->bedroom;
            $house->bathroom = $request->bathroom;
            $house->description = $request->description;
            $house->price = $request->price;
            $house->status = $request->status;
            $house->save();
            $image->name = $house->name;
            $image->house_id = $house->id;
            $image->url = $request->image;
            $image->save();
            return response()->json(['success' => 'Đăng nhà thành công']);
        } else {
            return response()->json(['error' => 'bạn không phải là manager'],403);
        }
    }

    // user search house ( thiếu check thời gian user search phòng đang ở status nào )
    public function search(Request $request)
    {
        $house_id = [];
        $orders = Order::where('status', '=', 'xác nhận')->get();
        foreach ($orders as $order) {
            if (
                ($request->start_date >= $order->start_date && $request->start_date <= $order->end_date) ||
                ($request->end_date >= $order->start_date && $request->end_date <= $order->end_date) ||
                ($order->start_date <= $request->end_date && $order->start_date >= $request->start_date) ||
                ($order->end_date <= $request->end_date && $order->end_date >= $request->start_date)
            ) {
                array_push($house_id, $order->house_id);
            }
        }
        $houses = House::with('category','user','images')
            ->whereNotIn('id', array_unique($house_id))
            ->where('price', '>=', +$request->prMin)
            ->where('price', '<=', +$request->prMax)
            ->orwhere('bedroom', '=', +$request->bedroom)
            ->orwhere('bathroom', '=', +$request->bathroom)
            ->where('address', 'LIKE', '%' . $request->address . '%')->get();
        return response()->json($houses);
    }
}
