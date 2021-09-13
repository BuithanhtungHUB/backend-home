<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function review($id, Request $request, Review $review)
    {
        $userId = auth()->user()->getAuthIdentifier();
        $house = House::with('user')->find($id);
        $email = $house->user->email;
        $content = 'review';
        if (!($userId == $house->user->id)) {
            $review->user_id = $userId;
            $review->house_id = $house->id;
            $review->rate = $request->rate;
            $review->comment = $request->comment;
            $review->save();
            (new MailController)->sendMail($email, $content);
            return response()->json(['success' => 'bạn đã review thành công']);
        } else {
            return response()->json(['error' => 'bạn không thể tự review nhà của mình!']);
        }
    }

    public function getAvgRate($id)
    {
        $house = House::find($id);
        $rateOfHouse = [];
        $review = Review::all();
        foreach ($review as $reviews){
            if ($reviews->house_id == $house->id){
                array_push($rateOfHouse, $reviews->rate);
            }
        }
        $avgRate = collect($rateOfHouse)->avg();
        return response() -> json(round($avgRate));
    }

    public function getReview($id){
        $house = DB::table('houses')
            ->join('reviews', 'houses.id', '=', 'reviews.house_id')
            ->join('users', 'users.id', '=', 'reviews.user_id')
            ->select('rate', 'user_name', 'comment', 'avatar')
            ->where('houses.id', $id)
            ->get();
        return response() -> json($house);
    }
}
