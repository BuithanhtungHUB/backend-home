<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // user đặt thuê một ngôi nhà
    public function houseRent($id, Request $request, Order $order)
    {
//        dd($id,$request->start_date);
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
                (new MailController)->sendMail($email, $content);
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

    // check thời gian khách ở bn ngày
    function dateDifference($start, $end)
    {
        // calculating the difference in timestamps
        $diff = strtotime($start) - strtotime($end);

        // 1 day = 24 hours
        // 24 * 60 * 60 = 86400 seconds
        return ceil(abs($diff / 86400));
    }

    // chủ nhà xác nhận cho thuê
    public function rentConfirm($id, Request $request)
    {
        $order = Order::with('user')->find($id);
        $email = $order->user->email;
        if ($request->status == 'xác nhận') {
            $content = 'approved';
            $order->status = $request->status;
            $order->save();
            (new MailController)->sendMail($email, $content);
            return response()->json(['success' => 'Bạn đã xác nhận']);
        } else {
            $content = 'not approved';
            $order->status = 'không xác nhận';
            $order->save();
            (new MailController)->sendMail($email, $content);
            return response()->json(['error' => 'Bạn đã hủy xác nhận']);

        }
    }

// danh sách những
    public function getList()
    {
        $manager = auth()->user();
        auth()->user()->ordersManager;
//        auth()->user()->houses;
//        auth()->user()->orders;
        return response()->json($manager);
    }

    //lịch sử thuê nhà của 1 user
    public function rentHistory()
    {
        $id = auth()->user()->id;
        $user = User::find($id);
        $orders = Order::with('house')->where('user_id', $id)->OrderBy('created_at','DESC')->get();
        $data = ['user' => $user, 'order' => $orders];
        return response()->json($data);
    }

    // khách hủy thuê nhà ( check đk trước 1 ngày)
    public function cancelRent($id)
    {
        $order = Order::find($id);
        $rent_date = date('Y-m-d', strtotime("-2 day", strtotime($order->start_date)));
        $date = date('Y-m-d');
        $content = 'cancel';
        if ($date <= $rent_date) {
            if ($order->status == 'xác nhận') {
                $order->status = 'đã hủy';
                $order->save();
                $house = House::with('user')->find($order->house_id);
                $email = $house->user->email;
                (new MailController)->sendMail($email, $content);
                return response()->json(['success' => 'Bạn đã hủy đơn thuê']);
            }
            if ($order->status == 'chờ xác nhận') {
                $order->status = 'đã hủy';
                $order->save();
                $house = House::with('user')->find($order->house_id);
                $email = $house->user->email;
                (new MailController)->sendMail($email, $content);
                return response()->json(['success' => 'Bạn đã hủy đơn thuê']);
            }
        }
        return response()->json(['error'=>'Bạn chỉ được phép hủy trước thời gian thuê 1 ngày'],403);
    }

    // auto update trạng thái khi house tới thời gian start và end khi chủ nhà xác nhận cho thuê
    // (lấy ra top5 house có lượt thuê nhiều nhất)
    public function autoUpdate()
    {
        $date = date('Y-m-d');
        $orders = Order::with('house', 'user')->get();
        foreach ($orders as $order) {
            if ($order->status == 'xác nhận' && $date >= $order->start_date) {
                $house = House::find($order->house->id);
                $house->status = 'đã cho thuê';
                $house->save();
            }
            if ($order->status == 'xác nhận' && $date < $order->start_date) {
                $house = House::find($order->house->id);
                if (!$house->status=='đã cho thuê'){
                    $house->status = 'còn trống';
                    $house->save();
                }
            }
            if ($order->status == 'xác nhận' && $date > $order->end_date) {
                $order->status = 'đã thanh toán';
                $order->save();
                $house = House::find($order->house->id);
                $house->status = 'còn trống';
                $house->save();
            }
            if ($order->status == 'chờ xác nhận' && $date >= $order->start_date) {
                $order->status = 'không xác nhận';
                $order->save();
            }
        }
        $rentMost = Order::select('house_id', DB::raw('count(id) as count'))
            ->with('house','images')
            ->where('status', '=', 'đã thanh toán')
            ->groupBy('house_id')
            ->orderBy('count', 'DESC')
            ->limit(5)->get();
        return response()->json($rentMost);
    }

    // lịch sử thuê nhà của 1 house
    public function rentHistoryHouse($id)
    {
        $house = House::find($id);
        if (auth()->user()->role == 'manager'&& auth()->user()->id == $house->user_id){
            $orders = Order::with('user','house')->where('house_id','=',$id)->get();
            return response()->json($orders);
        }
        return response()->json(['error'=>'Bạn không phải manager'], 403);
    }
}
