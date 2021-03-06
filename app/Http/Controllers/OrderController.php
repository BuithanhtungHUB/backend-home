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
        $date = $this->dateDifference($request->start_date, $request->end_date);
        $user = auth()->user();
        $house = House::with('user')->find($id);
        $email = $house->user->email;
        $content = 'order';
        $orders = Order::where('status', '=', 'xác nhận')->where('house_id', '=', $id)->get();
        $house_id = [];
        foreach ($orders as $ord) {
            if (
                ($request->start_date >= $ord->start_date && $request->start_date <= $ord->end_date) ||
                ($request->end_date >= $ord->start_date && $request->end_date <= $ord->end_date) ||
                ($ord->start_date <= $request->end_date && $ord->start_date >= $request->start_date) ||
                ($ord->end_date <= $request->end_date && $ord->end_date >= $request->start_date)
            ) {
                if ($user->id != $house->user_id) {
                    array_push($house_id, $ord->house_id);
                }
            }
        }
        if (!array_unique($house_id) && $house->status == 'còn trống') {
            $order->user_id = $user->id;
            $order->house_id = $id;
            $order->start_date = $request->start_date;
            $order->end_date = $request->end_date;
            $order->total_price = (int)($date * $house->price);
            $order->status = 'chờ xác nhận';
            $order->save();
            (new MailController)->sendMail($email, $content);
            return response()->json(['success' => 'thành công', $user]);
        } elseif ($house->status == 'đang nâng cấp') {
            return response()->json(['message' => 'House đang được nâng cấp không thể thuê được'], 403);
        } else {
            return response()->json(['message' => 'House đã được cho thuê trong khoảng thời gian này'], 403);
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
    public function rentConfirm($id, $value)
    {
        define('CONFIRM','xác nhận');
        define('DENIED','không xác nhận');
        $order = Order::with('user')->find($id);
        $email = $order->user->email;
        if ($value == CONFIRM) {
            $content = 'approved';
            $order->status = $value;
            $order->save();
            (new MailController)->sendMail($email, $content);
            return response()->json(['success' => 'Bạn đã xác nhận']);
        }
        if ($value == DENIED) {
            $content = 'not approved';
            $order->status = $value;
            $order->save();
            (new MailController)->sendMail($email, $content);
            return response()->json(['error' => 'Bạn đã hủy xác nhận']);
        }
        return response()->json(['message'=>'Bạn không được thực hiện thao tác này'],403);
    }

// danh sách đơn hàng của manager
    public function getListOrderManager()
    {
        $orders = auth()->user()->ordersManager;
        return response()->json($orders);
    }

    //lịch sử thuê nhà của 1 user
    public function rentHistory()
    {
        $id = auth()->user()->id;
        $user = User::find($id);
        $orders = Order::with('house')->where('user_id', $id)->OrderBy('created_at', 'DESC')->get();
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
        return response()->json(['error' => 'Bạn chỉ được phép hủy trước thời gian thuê 1 ngày'], 403);
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
                if (!$house->status == 'đã cho thuê') {
                    $house->status = 'còn trống';
                    $house->save();
                }
            }
            if ($order->status == 'xác nhận' && $date > $order->end_date) {
                $order->status = 'đã thanh toán';
                $order->save();
                // sendmail to write rate and comment
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
            ->with('house', 'images')
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
        if (auth()->user()->role == 'manager' && auth()->user()->id == $house->user_id) {
            $orders = Order::with('user', 'house')->where('house_id', '=', $id)->get();
            return response()->json($orders);
        }
    }


    public function incomeStatistics($id, $year)
    {
        define('PAID', 'đã thanh toán');
        $orders = auth()->user()->ordersManager
            ->where('house_id', '=', $id)
            ->where('status', '=', PAID)
            ->where('end_date', '>=', $year . '-01-01')
            ->where('end_date', '<=', $year . '-12-31');
        $revenue = [];
        $month = [];
        for ($i = 0; $i < 12; $i++) {
            if ($i < 9) {
                $month[$i] = '0' . ($i + 1);
            } else {
                $month[$i] = $i + 1;
            }
            $revenue[$i] = 0;
        }
        foreach ($orders as $order) {
            for ($i = 0; $i < count($month); $i++) {
                $checkEndDateInMonth = date("Y-m", strtotime($order->end_date)) == $year . '-' . $month[$i];
                if ($checkEndDateInMonth) {
                    $revenue[$i] += $order->total_price;
                }
            }
        }
        return response()->json($revenue);
    }
}
