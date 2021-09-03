<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function houseRent($id, Request $request, Order $order)
    {
        $date = $this->dateDifference($request->start_date, $request->end_date);
        $user = auth()->user();
        $house = House::find($id);
        if (!($user->id == $house->user_id)) {
            if ($house->status == 'còn trống') {
                $order->user_id = $user->id;
                $order->house_id = $id;
                $order->start_date = $request->start_date;
                $order->end_date = $request->end_date;
                $order->total_price = (int)($date * $house->price);
                $order->status = 'chờ xác nhận';
                $order->save();
                return response()->json(['success' => 'thành công', $user]);
            }
        } else {
            return response()->json(['error' => 'Đây là sản phẩm của bạn']);
        }
        if ($house->status == 'đã cho thuê') {
            return response()->json(['error' => 'Đã có người thuê']);
        }
        if ($house->status == 'đang nâng cấp') {
            return response()->json(['error' => 'House đang trong thời gian tu sửa']);
        }
    }

    function dateDifference($start, $end)
    {
        // calculating the difference in timestamps
        $diff = strtotime($start) - strtotime($end);

        // 1 day = 24 hours
        // 24 * 60 * 60 = 86400 seconds
        return ceil(abs($diff / 86400));
    }

    public function rentConfirm($id, Request $request)
    {
        $order = Order::find($id);
        if ($request->status == 'xác nhận') {
            $order->status = $request->status;
            $order->save();
            $house = House::find($order->house_id);
            $house->status = 'đã cho thuê';
            $house->save();
            return response()->json(['success' => 'Bạn đã xác nhận']);
        } else {
            $order->status = 'không xác nhận';
            $order->save();
            return response()->json(['error' => 'Bạn đã hủy xác nhận']);

        }
    }

    public function getList()
    {
        $orders = auth()->user()->ordersManager;
        return response()->json($orders);
    }
}