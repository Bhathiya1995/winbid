<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\Bid;
use App\Models\Subscriber;
use IDEABIZ;
use Carbon\Carbon;
use App\Models\Event;

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

    public function receiveSms(Request $request){
        \Log::info("receivesms URL");
        \Log::info($request);
    }

    public function receiveRegSms(Request $request){
        \Log::info("receivesms URL");
        \Log::info($request);
    }



    public function admin(Request $request){

        \Log::info("receiveRegsms URL");
        \Log::info($request);
        
        $status = $request->status;
        $action = $request->action;
        $msisdn = $request->msisdn;
        $serviceId = $request->serviceID;

        $sub = Subscriber::where('msisdn', "$msisdn")->first();
        
        if($action == "STATE_CHANGE" and $status== "SUBSCRIBED"){
            $campaign = Campaign::where('state', '1')->first();
            
            if($sub == null){
                $subscriber = new Subscriber;
                $subscriber->msisdn = $msisdn;
                $subscriber->subscribed_time = Carbon::now();
                $subscriber->status = $status;
                $saved = $subscriber->save();

                if($saved){
                    $event = new Event;
                    $event->msisdn = $msisdn;
                    $event->trigger = "SUBSCRIBER";
                    $event->event = "SUBSCRIBE"; 
                    $event->status = "SUCCESS";
                    $event->save();
                    
                    if($campaign != null){
                        $message = $campaign->welcome_msg;
                        $this->sendSmsForOne($msisdn, $message);
                    }
                    
                }

            }else if($sub->status == "UNSUBSCRIBED"){
                $sub->subscribed_time = Carbon::now();
                $sub->status = $status;
                $saved = $sub->save();
                if($saved){
                    $event = new Event;
                    $event->msisdn = $msisdn;
                    $event->trigger = "SUBSCRIBER";
                    $event->event = "SUBSCRIBE"; 
                    $event->status = "SUCCESS";
                    $event->save();

                    if($campaign != null){
                        $message = $campaign->welcome_msg;
                        $this->sendSmsForOne($msisdn, $message);
                    }
                }
                
            }
            
        } else if($action == "STATE_CHANGE" and $status= "UNSUBSCRIBED"){
            if ($sub != null and $sub->status == "SUBSCRIBED"){
                $sub->unsubscribed_time = Carbon::now();
                $sub->status = $status;
                $saved = $sub->save();
                if($saved){
                    $event = new Event;
                    $event->msisdn = $msisdn;
                    $event->trigger = "SUBSCRIBER";
                    $event->event = "UNSUBSCRIBE"; 
                    $event->status = "SUCCESS";
                    $event->save();
                }
            }
        }
        
    }


    public function sendSmsForOne($msisdn, $message){
        IDEABIZ::generateAccessToken();
        $access_token = IDEABIZ::getAccessToken();
    
        $url = "https://ideabiz.lk/apicall/smsmessaging/v3/outbound/87798/requests";        
        $method = "POST";
        $headers = [
            "Content-Type" => "application/json;charset=UTF-8",
            "Authorization" => "Bearer ".$access_token,
            "Accept" => "application/json",
        ];
    $request_body = [
        "outboundSMSMessageRequest"=> [
            "address" =>[
                $msisdn
            ],
            "senderAddress"=> "tel:87798",
            "outboundSMSTextMessage"=>[
                "message"=> $message
            ],
            "receiptRequest"=> [
                "notifyURL" => ""
                ],
            
        
        ]
    ];

    $response = IDEABIZ::apiCall($url, $method, $headers, $request_body);
    }

    

    public function activateCamapign(){
            $currentDate = Carbon::now()->format('Y-m-d');
            $campaign = Campaign::where('create_date','<=',$currentDate)->where('expire_date','>=',$currentDate)->first(); 
            if($campaign ==null){
                Campaign::where('state','=','1')->update(['state'=>0]);
            }elseif($campaign->state == 0){
                Campaign::where('state','=','1')->update(['state'=>0]);
                $campaign->state = 1;
                $this->activeCamapign = $campaign;
                $campaign->save();
                
                \Log::info("state become 1!"); 
            }

            \Log::info("Cron is working fine!");
                
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
