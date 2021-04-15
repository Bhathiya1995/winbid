<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;

class CampaignController extends Controller
{

    public function dashboard(){
        $campaigns = Campaign::orderBy('id', 'DESC')->paginate(5);
        return view('dashboard',['campaigns'=> $campaigns]);
    }

    public function createCampaign(Request $req){
        $campaign = new Campaign;
        $campaign->welcome_msg = $req->welcomeMsg;
        $campaign->end_msg = $req->endMsg;
        $campaign->create_date = $req->createDate;
        $campaign->expire_date = $req->expireDate;
        $campaign->state = '0';
        $campaign->save();
        return redirect()->back()->with('success', 'Campaign Added !!');

    }
}
