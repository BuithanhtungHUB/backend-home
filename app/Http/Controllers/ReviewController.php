<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function rate($id, Request $request, Review $review){
        $user = auth()->user();
        $house = House::find($id);
        if(!($user->id == $house->user_id)){
            $review->user_id = $user->id;
            $review->house_id = $house->id;
            $review->rate = $request->rate;
        }
}
}
