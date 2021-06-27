<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\Bid;
use App\Models\Subscriber;
use IDEABIZ;
use Carbon\Carbon;

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

    public function receiveRegsms(Request $request){
        
        $statusCode = $request->statusCode;
        if($statusCode == "SUCCESS"){
            $message = $request->message;
            $subscribeResponse = $request->data['subscribeResponse'];
            $msisdn = $subscribeResponse['msisdn'];
            $status = $subscribeResponse['status'];
            $serviceId = $subscribeResponse['serviceID'];

            $sub = Subscriber::where('msisdn', $msisdn)->first();
            if($sub == null){
                $subscriber = new Subscriber;
                $subscriber->msisdn = $msisdn;
                $subscriber->subscribed_time = Carbon::now();
                $subscriber->status = $status;
                $saved = $subscriber->save();

                if($saved){
                    return response("Subscribed Successfully");
                }

            }
            
        }
        
    }

    public function test(){
        IDEABIZ::generateAccessToken();
        $access_token = IDEABIZ::getAccessToken();
        $url = "https://ideabiz.lk/apicall/subscription/v3/status/tel%3A%2B94770453201";
        $method = "GET";
        $headers = [
                "Content-Type" => "application/json",
                "Authorization" => "Bearer ".$access_token,
                "Accept" => "application/json",
           ];
        $request_body = [];
        $response = IDEABIZ::apiCall($url, $method, $headers, $request_body);
        print_r((string)$response->getBody());
    }

}
