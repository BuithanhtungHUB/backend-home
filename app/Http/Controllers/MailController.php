<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMail;

class MailController extends Controller
{
    public function sendMail($email, $content)
    {
        $title = [
            'order' => '[Confirmation] You have a new order',
            'cancel' => '[Confirmation] You have an cancelled order',
            'approved' => '[Confirmation] The owner have approved your order',
            'not approved' => '[Confirmation] The owner did not approve your order',
            'review' => '[Review] New review'
        ];
        $detail = [
            'order' => 'You have an order. Please go to home from home website for more detail.',
            'cancel' => 'A customer have just cancelled their order. Please go to home from home website for more detail.',
            'approved' => 'Your order have just been approved. Hope you will have a good time.',
            'not approved' => 'There are some problem with your order. Please contact the owner for more detail.',
            'review' => 'Someone just leave a review on your house. Please go check it out.'

        ];
//        $email = auth()->user()->email;
        $sendMail = Mail::to($email)->send(new SendMail($title[$content], $detail[$content]));
        if (empty($sendMail)) {
            return response()->json(['message' => 'Mail Sent Successfully'], 200);
        } else {
            return response()->json(['message' => 'Mail Sent fail'], 400);
        }
    }
}
