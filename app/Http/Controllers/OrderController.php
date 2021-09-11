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
        if (!array_unique($house_id)&& $house->status == 'còn trống'){
            $order->user_id = $user->id;
            $order->house_id = $id;
            $order->start_date = $request->start_date;
            $order->end_date = $request->end_date;
            $order->total_price = (int)($date * $house->price);
            $order->status = 'chờ xác nhận';
            $order->save();
            (new MailController)->sendMail($email, $content);
            return response()->json(['success' => 'thành công', $user]);
        }elseif ($house->status == 'đang nâng cấp'){
            return response()->json(['message'=>'House đang được nâng cấp không thể thuê được'],403);
        }
        else{
            return response()->json(['message'=>'House đã được cho thuê trong khoảng thời gian này'],403);
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
        $manager = User::with('managers')->where('id','=',auth()->user()->id)->get();
//
        return response()->json($manager);
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
        return response()->json(['error' => 'Bạn không phải manager'], 403);
    }


    public function incomeStatistics($id)
    {
//        $sum = Order::where('house_id','=',$id)->where('status','=','đã thanh toán')->get()->sum('total_price');
        $manager = User::with('managers')->where('id',auth()->user()->id)->get();
        $orders =  $manager[0]['managers'];
        $revenue1 = 0;
        $revenue2 = 0;
        $revenue3 = 0;
        $revenue4 = 0;
        $revenue5 = 0;
        $revenue6 = 0;
        $revenue7 = 0;
        $revenue8 = 0;
        $revenue9 = 0;
        $revenue10 = 0;
        $revenue11 = 0;
        $revenue12 = 0;
        define('STATUS','đã thanh toán');
        foreach ($orders as $order){
            if ($order->house_id == $id && $order->status == STATUS){
                if ($order->end_date >= '2021-01-01' && $order->end_date <= '2021-01-31'){
                    $revenue1 += $order->total_price;
                }
                if ($order->end_date >= '2021-02-01' && $order->end_date <= '2021-02-28'){
                    $revenue2 += $order->total_price;
                }
                if ($order->end_date >= '2021-03-01' && $order->end_date <= '2021-03-31'){
                    $revenue3 += $order->total_price;
                }
                if ($order->end_date >= '2021-04-01' && $order->end_date <= '2021-04-30'){
                    $revenue4 += $order->total_price;
                }
                if ($order->end_date >= '2021-05-01' && $order->end_date <= '2021-05-31'){
                    $revenue5 += $order->total_price;
                }
                if ($order->end_date >= '2021-06-01' && $order->end_date <= '2021-06-3'){
                    $revenue6 += $order->total_price;
                }
                if ($order->end_date >= '2021-07-01' && $order->end_date <= '2021-07-31'){
                    $revenue7 += $order->total_price;
                }
                if ($order->end_date >= '2021-08-01' && $order->end_date <= '2021-08-31'){
                    $revenue8 += $order->total_price;
                }
                if ($order->end_date >= '2021-09-01' && $order->end_date <= '2021-09-30'){
                    $revenue9 += $order->total_price;
                }
                if ($order->end_date >= '2021-10-01' && $order->end_date <= '2021-10-31'){
                    $revenue10 += $order->total_price;
                }
                if ($order->end_date >= '2021-11-01' && $order->end_date <= '2021-11-3'){
                    $revenue11 += $order->total_price;
                }
                if ($order->end_date >= '2021-12-01' && $order->end_date <= '2021-12-31'){
                    $revenue12 += $order->total_price;
                }
            }
        }
        $revenue = ['revenue1'=>$revenue1,'revenue2'=>$revenue2,'revenue3'=>$revenue3,'revenue4'=>$revenue4,'revenue5'=>$revenue5,'revenue6'=>$revenue6,'revenue7'=>$revenue7,'revenue8'=>$revenue8,'revenue9'=>$revenue9,'revenue10'=>$revenue10,'revenue11'=>$revenue11,'revenue12'=>$revenue12];
        return response()->json($revenue);
    }
}
