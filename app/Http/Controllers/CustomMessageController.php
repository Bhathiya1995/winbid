<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomMessage;

class CustomMessageController extends Controller
{
    public function customMessagePage(){
        $customMessages =CustomMessage::all();
        return view('pages.custommessagepage')->with('customMessages', $customMessages);
    }

    public function newCustomMessage(Request $req){
        $customMessage = new CustomMessage();
        $customMessage->custom_message = $req->customMsg;
        $customMessage->status = "1";
        $customMessage->save();
        return redirect()->back()->with('success', 'Campaign Added !!');   
    }
}
