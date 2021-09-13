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
    public function create(CreateHouseRequest $request, House $house)
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
            $house->url_map = $request->url_map;
            $house->save();

            for ($i = 0; $i < count($request->images); $i++) {
                $image = new Image();
                $image->name = $house->name . ' - ' . ($i + 1);
                $image->house_id = $house->id;
                $image->url = $request->images[$i];
                $image->save();
            }

            return response()->json(['success' => 'Đăng nhà thành công']);
        }
    }

    // user search house ( thiếu check thời gian user search phòng đang ở status nào )
    public function search($start_date, $end_date, $bedroom, $bathroom, $price_min, $price_max, $address)
    {
        $house_id = [];
        $orders = Order::where('status', '=', 'xác nhận')->get();
        foreach ($orders as $order) {
            if (
                ($start_date >= $order->start_date && $start_date <= $order->end_date) ||
                ($end_date >= $order->start_date && $end_date <= $order->end_date) ||
                ($order->start_date <= $end_date && $order->start_date >= $start_date) ||
                ($order->end_date <= $end_date && $order->end_date >= $start_date)
            ) {
                array_push($house_id, $order->house_id);
            }
        }
        $houses = House::with('category','user','images')
            ->whereNotIn('id', array_unique($house_id))
            ->where('price', '>=', $price_min)
            ->where('price', '<=', $price_max)
            ->orwhere('bedroom', '=', $bedroom)
            ->orwhere('bathroom', '=', $bathroom)
            ->where('address', 'LIKE', '%' . $address . '%')->get();
        return response()->json($houses);
    }

}
