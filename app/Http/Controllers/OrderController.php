<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function houseRent($id, Request $request, Order $order)
    {
        $date = $this->dateDifference($request->start_date, $request->end_date);
        $user = auth()->user();
        $house = House::with('user')->find($id);
        $email = $house->user->email;
        $content = 'order';
        if (!($user->id == $house->user_id)) {
            if ($house->status == 'còn trống') {
                $order->user_id = $user->id;
                $order->house_id = $id;
                $order->start_date = $request->start_date;
                $order->end_date = $request->end_date;
                $order->total_price = (int)($date * $house->price);
                $order->status = 'chờ xác nhận';
                $order->save();
                (new MailController)->sendMail($email,$content);
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
        $order = Order::with('user')->find($id);
        $email = $order->user->email;
        if ($request->status == 'xác nhận') {
            $content = 'approved';
            $order->status = $request->status;
            $order->save();
            $house = House::find($order->house_id);
            // bỏ
            $house->status = 'đã cho thuê';
            $house->save();
            //
            (new MailController)->sendMail($email,$content);
            return response()->json(['success' => 'Bạn đã xác nhận']);
        } else {
            $content = 'not approved';
            $order->status = 'không xác nhận';
            $order->save();
            (new MailController)->sendMail($email,$content);
            return response()->json(['error' => 'Bạn đã hủy xác nhận']);

        }
    }

    public function getList()
    {
        $orders = auth()->user()->ordersManager;
        return response()->json($orders);
    }

    public function rentHistory()
    {
        $id = auth()->user()->id;
        $user = User::find($id);
        $orders = Order::with('house')->where('user_id', $id)->get();
        $data = ['user' => $user, 'order' => $orders];
        return response()->json($data);
    }

    public function cancelRent($id)
    {
        $order = Order::find($id);
        $rent_date = date('Y-m-d', strtotime("-2 day", strtotime($order->start_date)));
        $date = date('Y-m-d');
        $content = 'cancel';
        if ($date <= $rent_date) {
            if ($order->status == 'xác nhận') {
                $order->status = 'không xác nhận';
                $order->save();
                $house = House::with('user')->find($order->house_id);
                // bỏ
                $house->status = 'còn trống';
                $house->save();
                //
                $email = $house->user->email;
                (new MailController)->sendMail($email,$content);
                return response()->json(['success' => 'khách hàng đã hủy đơn thuê']);
            }
            if ($order->status == 'chờ xác nhận') {
                $order->status = 'không xác nhận';
                $order->save();
                return response()->json(['success' => 'khách hàng đã hủy đơn thuê']);
            }
            return response()->json(['success' => 'status đang ở trạng thái khác ']);

        }
        return response()->json('Bạn chỉ được phép hủy trước thời gian thuê 1 ngày');
    }
}
