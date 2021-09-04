<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendMail;

class MailController extends Controller
{
    public function sendMail(){
        $title = '[Confirmation] You have a new order';
        $email = auth()->user()->email;
        $sendMail = Mail::to($email)->send(new SendMail($title));
        if (empty($sendMail)) {
            return response()->json(['message' => 'Mail Sent Successfully'], 200);
        }
        else{
            return response()->json(['message' => 'Mail Sent fail'], 400);
        }
    }
}
