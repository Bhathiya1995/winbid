<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\Bid;

class CampaignController extends Controller
{

    public function dashboard(Request $req){

        if($req->campaignTime != null){
            $campaigns = Campaign::where('id', $req->campaignTime)->get();
        }else {
            $campaigns = Campaign::orderBy('id', 'DESC')->paginate(5);
        }
        $allCampaigns = Campaign::all();
        return view('dashboard')->with('campaigns', $campaigns)->with('allCampaigns',$allCampaigns);
    }

    public function winner(Request $req){

        $uniqueBidArray = array();
        $allCampaigns = Campaign::all();

        if($req->campaignTime != null){
            $allWinners = Bid::where('campaign_id', $req->campaignTime)->orderBy('bid_value')->get();
            
            foreach($allWinners as $winners){
                $bidCount = Bid::where([['campaign_id','=', $req->campaignTime],['bid_value','=',$winners->bid_value]])->count();
                if ($bidCount == 1){
                    $winner = Bid::where('id',$winners->id)->first();
                    break;
                }
            };

            foreach($allWinners as $winners){
                $bidCount = Bid::where([['campaign_id','=', $req->campaignTime],['bid_value','=',$winners->bid_value]])->count();
                if ($bidCount == 1){
                    array_push($uniqueBidArray, $winners->id);
                }
            };

            $allUniqueWinners = Bid::whereIn('id',$uniqueBidArray)->orderBy('bid_value')->take(10)->get();
            
            return view('pages.winnerpage')->with('allCampaigns',$allCampaigns)->with('winner', $winner)->with('allUniqueWinners',$allUniqueWinners);
        
        }else {
           return view('pages.winnerpage')->with('allCampaigns',$allCampaigns);
        }

        
        // return view('pages.winnerpage')->with('allCampaigns',$allCampaigns)->with('winner', $winner)->with('allUniqueWinners',$allUniqueWinners);
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

    public function editCampaignPage($id){
        $campaign = Campaign::where('id', $id)->first();
        return view('pages.editcampaignpage')->with('campaign',$campaign);
    }

    public function updateCampaign(Request $req, $id){
        $campaign = Campaign::find($id);
        $campaign->welcome_msg = $req->welcomeMsg;
        $campaign->end_msg = $req->endMsg;
        $campaign->create_date = $req->createDate;
        $campaign->expire_date = $req->expireDate;
        $campaign->state = '0';
        $campaign->save();
        return redirect()->back()->with('success', 'Campaign Updated !!');
    }

}
