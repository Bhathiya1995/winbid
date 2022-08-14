<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\Bid;
use App\Models\CustomMessage;
use App\Models\Subscriber;
use IDEABIZ;
use Carbon\Carbon;
use App\Models\Event;
use Illuminate\Cache\RateLimiter;

class CampaignController extends Controller
{

    public function __construct(){
        set_time_limit(3600);
    }

    public function dashboard(Request $req){

        if($req->campaignTime != null){
            $campaigns = Campaign::where('id', $req->campaignTime)->get();
        }else {
            $campaigns = Campaign::orderBy('id', 'DESC')->paginate(5);
        }
        $allCampaigns = Campaign::all();
        return view('dashboard')->with('campaigns', $campaigns)->with('allCampaigns',$allCampaigns);
    }

    public function terms(){
        return view('pages.terms');
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

    public function customMessagePage(){
        $customMessages =CustomMessage::all();
        return view('pages.custommessagepage')->with('customMessages', $customMessages);
    }

    public function newCustomMessage(Request $req){
        $customMessage = new CustomMessage();
        $customMessage->custom_message = $req->customMsg;
        $customMessage->status = "1";
        $customMessage->save();

        $users = Subscriber::where('status', "SUBSCRIBED")->get();
        foreach($users as $user){
            $this->sendSmsForOne($user->msisdn, $req->customMsg);
        }

        return redirect()->back()->with('success', 'Message sent !!');   
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
        $campaign->state = $req->state;
        $campaign->save();
        return redirect()->back()->with('success', 'Campaign Updated !!');
    }

    public function getLastBidRange($campaign_id) {
        $bids = Bid::where('campaign_id', $campaign_id)->where('status',1)->orderBy('bid_value','ASC')->get();
        foreach ($bids as $bid){
            $bid_val = $bid->bid_value;
            $bidCount = Bid::where([['campaign_id','=', $campaign_id],['bid_value','=',$bid_val]])->count();
            if ($bidCount == 1){
                $low = (($bid_val - 9) <0 ? 0 : ($bid_val - 9));
                $high = (($bid_val - 9) <0 ? 20 : ($bid_val + 9));
                break;
            }
        }
        $range = [$low, $high];
        return $range;
    }

    public function receiveSms(Request $request){
       
    }

    public function isSubscriber($tel_no){
        IDEABIZ::generateAccessToken();
        $access_token = IDEABIZ::getAccessToken();
        $url = "https://ideabiz.lk/apicall/subscription/v3/status/tel%3A%2B{$tel_no}";
        $method = "GET";
        $headers = [
                "Content-Type" => "application/json",
                "Authorization" => "Bearer ".$access_token,
                "Accept" => "application/json",
        ];
        $request_body = [];
        $response = IDEABIZ::apiCall($url, $method, $headers, $request_body);
        $body = $response->getBody();
        $res = json_decode($body);
        if($res->statusCode == "SUCCESS" AND $res->data->subscribeResponse->status == "SUBSCRIBED"){
            return true;
        }else{
            return false;
        }
    }

    public function receiveRegSms(Request $request){
        \Log::info('receiveRegSms');
        \Log::info($request);
        $inboundSMSMessageNotification = $request->inboundSMSMessageNotification;
        $inboundSMSMessage = $inboundSMSMessageNotification['inboundSMSMessage'];
        $senderAddress = $inboundSMSMessage['senderAddress'];
        $message = $inboundSMSMessage['message'];
        $words = explode(" ", $message);

        $sub = Subscriber::where('msisdn', "$senderAddress")->first();

        if(!is_null($sub)) {
            if($this->isSubscriber($senderAddress) and $sub->status = "SUBSCRIBED" and $sub->paid = "PAID"){
                // if (count($words) == 2 and $words[0] == "REG" and $words[1] == "BID" ) {
                //     $message = "You have already subscribed to the service.";
                //     $this->sendSmsForOne($senderAddress, $message);    
                // } 
                if (count($words) == 2 and $words[0] == "BID" and is_numeric($words[1]) ){
                    $todayBidsCount = Bid::whereDate('created_at', Carbon::today())->where('status',1)->count();

                    if($todayBidsCount >=0 and $todayBidsCount<3){
                        $campaign = Campaign::where('state', '1')->first();

                        $bids = Bid::where('campaign_id', $campaign->id)->where('status',1)->get();
                        $bidCount = count($bids);

                        $bid = new Bid;
                        $bid->campaign_id = $campaign->id;
                        $bid->bid_value = $words[1];
                        $bid->tel_number = $senderAddress;
                        $bid->status = "1";
                        $bid->save();
                        $availabelBids = 2-$todayBidsCount;

                        $range=[];
                        if($bidCount >0){
                            $range = $this->getLastBidRange($campaign->id);
                            if($words[1] > $range[1]){
                                $message = "Hurry Up! Your bid of {$words[1]} is not the winning bid at the moment. Now Lowest bid range is {$range[0]} - {$range[1]}. You have {$availabelBids} more free bid(s) for today";
                                $this->sendSmsForOne($senderAddress, $message);
                            }
                        }
                        // print_r("SEND SMS ---> Thanks for your bid. You have {$availabelBids} chanses for today");
                    }else{
                        $message = "Sorry. Your daily bidding chances exceeded, Please try again tomorrow. To win more gifts stay tuned with WASANA SMS service.";
                        $this->sendSmsForOne($senderAddress, $message);
                        // print_r("SEND SMS ---> Your chanses for bid today is over. Try again tommorrow");
                    }
                    
                }

                elseif(($message != "REG BID") and $sub->paid = "PAID"){
                    // print_r("SEND SMS ---> Message is invalid");
                    $message = "Sorry invalid BID Amount! Method of bidding is, type BID<space> BID VALUE and SMS to 66777";
                    $this->sendSmsForOne($senderAddress, $message);
                }

            }
        }

           
    }



    public function admin(Request $request){

        \Log::info("admin URL");
        \Log::info($request);

        if($request->status != null ){

            $status = $request->status;
            $action = $request->action;
            if($status== "SUBSCRIBED"){
                $telno = explode('+',$request->msisdn);
                $msisdn = $telno[1];
            }else if($status= "UNSUBSCRIBED"){
                $msisdn =$request->msisdn;
            }
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
    
                        // $message1 = "Successfully subscribed to WINBID service. Rs.5.00+tax/day apply. To deactivate type UNREG BID & SMS to 66777. T&C: https://tinyurl.com/cad6bj97";
                        // $this->sendSmsForOne($msisdn, $message1);

                        $payRes = $this->payment($msisdn);
                        $body = $payRes->getBody();
                        $res = json_decode($body);
                        if(isset($res->requestError)){
                            $subscriber->paid = 'NOTPAID';
                            $saved = $subscriber->save();

                            $event = new Event;
                            $event->msisdn = $msisdn;
                            $event->trigger = "SYSTEM";
                            $event->event = "CHARGING"; 
                            $event->status = "FAILED";
                            $event->save();
                        }elseif (isset($res->amountTransaction) and $res->amountTransaction->transactionOperationStatus == 'Charged'){
                            $subscriber->paid = 'PAID';
                            $saved = $subscriber->save();

                            $event = new Event;
                            $event->msisdn = $msisdn;
                            $event->trigger = "SYSTEM";
                            $event->event = "CHARGING"; 
                            $event->status = "SUCCESS";
                            $event->save();
                        }

    
                        if($campaign != null){
                            $message = $campaign->welcome_msg;
                            $this->sendSmsForOne($msisdn, $message);
                        }
                        
                    }else{
                        $event = new Event;
                        $event->msisdn = $msisdn;
                        $event->trigger = "SUBSCRIBER";
                        $event->event = "SUBSCRIBE"; 
                        $event->status = "FAILED";
                        $event->save();
                    }
    
                }else if($status== "SUBSCRIBED" and $sub->status == "UNSUBSCRIBED"){
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
    
                        // $message1 = "Successfully subscribed to WINBID service. Rs.5.00+tax/day apply. To deactivate type UNREG BID & SMS to 66777. T&C: https://tinyurl.com/cad6bj97";
                        // $this->sendSmsForOne($msisdn, $message1);

                        $payRes = $this->payment($msisdn);
                        $body = $payRes->getBody();
                        $res = json_decode($body);
                        if(isset($res->requestError)){
                            $sub = Subscriber::where('msisdn', $msisdn)->first();
                            $sub->paid = 'NOTPAID';
                            $saved = $sub->save();

                            $event = new Event;
                            $event->msisdn = $msisdn;
                            $event->trigger = "SYSTEM";
                            $event->event = "CHARGING"; 
                            $event->status = "FAILED";
                            $event->save();
                        }elseif (isset($res->amountTransaction) and $res->amountTransaction->transactionOperationStatus == 'Charged'){
                            $sub = Subscriber::where('msisdn', $msisdn)->first();
                            $sub->paid = 'PAID';
                            $saved = $sub->save();

                            $event = new Event;
                            $event->msisdn = $msisdn;
                            $event->trigger = "SYSTEM";
                            $event->event = "CHARGING"; 
                            $event->status = "SUCCESS";
                            $event->save();
                        }

    
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
                        Bid::where('tel_number', $msisdn)->update(['status'=>0]);
                        $event = new Event;
                        $event->msisdn = $msisdn;
                        $event->trigger = "SUBSCRIBER";
                        $event->event = "UNSUBSCRIBE"; 
                        $event->status = "SUCCESS";
                        $event->save();

                        // $message = "You are successfully Deactivated the WINBID Service. Thank you for using WINBID SMS service. To Activate the WINBID service type REG BID & SMS to 66777";
                        // $this->sendSmsForOne($msisdn, $message);
    
                    }else{
                        $event = new Event;
                        $event->msisdn = $msisdn;
                        $event->trigger = "SUBSCRIBER";
                        $event->event = "UNSUBSCRIBE"; 
                        $event->status = "FAILED";
                        $event->save();
                    }
                }
            }
            

        }elseif($request->status == null){
             $action = $request->action;
             $msisdn = $request->msisdn;
             $sub = Subscriber::where('msisdn', $msisdn)->first();
             $events = Event::where('msisdn', $msisdn)->orderBy('created_at', 'desc')->get();
             $appId = '5dc483bc-a038-4d7d-adb0-b5c07ebd58c9';
             $serviceId = 'dc24fcb5-9fa3-4bdc-8c7f-b237bdacbbf2';

             $subEvent = $events->where('event','SUBSCRIBE')->first();
             $unsubEvent = $events->where('event','UNSUBSCRIBE')->first();
             $currentEvent = $events->first();

             if ($action == 'STATE_CHECK'){
                if($unsubEvent != null) {
                    $unsubRes = [
                            'datetime' => $unsubEvent->created_at->format('Y-m-d H:i:s'),
                            'method' => 'SMS'
                            ];
                }else{
                    $unsubRes = null;
                }
                $res = ['statusCode'=>'Success',
                      'message' => '',
                      'data' => [
                            'subscription' => [
                                'msisdn' => $msisdn,
                                'appID' => $appId,
                                'serviceID' => $serviceId,
                                'registration-log' => [
                                    'datetime' => $subEvent->created_at->format('Y-m-d H:i:s'),
                                    'method' => 'SMS'
                                    ],
                                'unregistration-log' => $unsubRes,
                                'status' => $currentEvent->event
                                
                                ]
                            ]  
                    ];
                return response()->json($res);

             }elseif($action == 'HISTORY'){
                $offset = $request->offset;
                $limit = $request->limit;
                $limitEvents = $events->take($limit);
                $historyRes =array();

                foreach($limitEvents as $event){
                    if ($event->note != null){
                        $noteRes = $event->note ;
                    }else{
                        $noteRes = '';
                    }
                   $historyRes[] = [
                        'datetime' => $event->created_at->format('Y-m-d H:i:s'),
                        'trigger' => $event->trigger,
                        'event' => $event->event,
                        'note' => $noteRes,
                        'status' => $event->status,
                        'content' => $event->content,
                        'serviceID' => $serviceId
                   ];
                }

                $res = [
                    'subscriberHistory' => [
                        'msisdn' => $msisdn,
                        'appID' => $appId,
                        'serviceID' => $serviceId,
                        'offset' => $offset,
                        'limit' => $limit,
                        'history'=> $historyRes
                    ]
                ];

                return response()->json($res);

             }
        }       
        
    }


    public function sendSmsForOne($msisdn, $message){

        if ($this->hasTooManyRequests()) {
            sleep(
                $this->limiter()->availableIn($this->throttleKey()) + 1 // <= optional plus 1 sec to be on safe side
            );      
            return $this->sendSmsForOne($msisdn, $message);
        }


        IDEABIZ::generateAccessToken();
        $access_token = IDEABIZ::getAccessToken();
    
        $url = "https://ideabiz.lk/apicall/smsmessaging/v3/outbound/66777/requests";        
        $method = "POST";
        $headers = [
            "Content-Type" => "application/json;charset=UTF-8",
            "Authorization" => "Bearer ".$access_token,
            "Accept" => "application/json",
        ];
    $request_body = [
        "outboundSMSMessageRequest"=> [
            "address" =>[
                "tel:+".$msisdn
            ],
            "senderAddress"=> "tel:66777",
            "outboundSMSTextMessage"=>[
                "message"=> $message
            ],
            "receiptRequest"=> [
                "notifyURL" => ""
                ],
            
        
        ]
    ];
    \Log::info($message); 
    $response = IDEABIZ::apiCall($url, $method, $headers, $request_body);

    $body = $response->getBody();
    $res = json_decode($body);
    if(isset($res->fault)){
        $event = new Event;
        $event->msisdn = $msisdn;
        $event->trigger = "SYSTEM";
        $event->event = "SMS"; 
        $event->status = "FAILED";
        $event->content = $message;
        $event->save();
    }else{
        $event = new Event;
        $event->msisdn = $msisdn;
        $event->trigger = "SYSTEM";
        $event->event = "SMS"; 
        $event->status = "SUCCESS";
        $event->content = $message;
        $event->save();
    }

    $this->limiter()->hit(
        $this->throttleKey(),60
    );

    }

    protected function hasTooManyRequests()
        {
            return $this->limiter()->tooManyAttempts(
                $this->throttleKey(), 300 // <= max attempts per minute
            );
        }

    protected function limiter()
        {
            return app(RateLimiter::class);
        }

    protected function throttleKey()
        {
            return '1';
        }


    

    public function activateCamapign(){
            $currentDate = Carbon::now()->format('Y-m-d');
            $campaign = Campaign::where('create_date','<=',$currentDate)->where('expire_date','>=',$currentDate)->first(); 
            if($campaign ==null){
                print_r('$campaign');
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

    public function dailyPayments(){
        $users = Subscriber::where('status', "SUBSCRIBED")->get();
            foreach($users as $user){
                $payRes = $this->payment($user->msisdn);
                $body = $payRes->getBody();
                $res = json_decode($body);
                \Log::info('dailyPayments');
                if(isset($res->requestError)){
                    $sub = Subscriber::where('msisdn', $user->msisdn)->first();
                    $sub->paid = 'NOTPAID';
                    $saved = $sub->save();

                    $event = new Event;
                    $event->msisdn = $user->msisdn;
                    $event->trigger = "SYSTEM";
                    $event->event = "CHARGING"; 
                    $event->status = "FAILED";
                    $event->save();
                }elseif (isset($res->amountTransaction) and $res->amountTransaction->transactionOperationStatus == 'Charged'){
                    $sub = Subscriber::where('msisdn', $user->msisdn)->first();
                    $sub->paid = 'PAID';
                    $saved = $sub->save();

                    $event = new Event;
                    $event->msisdn = $user->msisdn;
                    $event->trigger = "SYSTEM";
                    $event->event = "CHARGING"; 
                    $event->status = "SUCCESS";
                    $event->save();
                }        
            }
    }

    public function renew(){
        \Log::info('Renew');
        $users = Subscriber::where('status', "SUBSCRIBED")->where('paid', 'NOTPAID')->get();
        foreach($users as $user){
            $payRes = $this->payment($user->msisdn);
            $body = $payRes->getBody();
            $res = json_decode($body);
            if(isset($res->requestError)){
                $event = new Event;
                $event->msisdn = $user->msisdn;
                $event->trigger = "SYSTEM";
                $event->event = "CHARGING"; 
                $event->status = "FAILED";
                $event->save();
            }elseif (isset($res->amountTransaction) and $res->amountTransaction->transactionOperationStatus == 'Charged'){
                $sub = Subscriber::where('msisdn', $user->msisdn)->first();
                $sub->paid = 'PAID';
                $saved = $sub->save();

                $event = new Event;
                $event->msisdn = $user->msisdn;
                $event->trigger = "SYSTEM";
                $event->event = "CHARGING"; 
                $event->status = "SUCCESS";
                $event->save();
            }        
        }
    }

    public function sendAllCampaignSms(){
        $campaign = Campaign::where('state', '1')->first();
        $message = '';

        if($campaign ==null){
            if ($campaign->create_date == Carbon::now()->format('Y-m-d') ){
                $message = $campaign->welcome_msg;
            }elseif($campaign->expire_date == Carbon::now()->format('Y-m-d') ){
                $message = $campaign->end_msg;
            }
        }

        if ($message != ''){
            $users = Subscriber::where('status', "SUBSCRIBED")->where('paid', 'PAID')->get();
            foreach($users as $user){
                $this->sendSmsForOne($user->msisdn, $message);
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

    public function test1(){
        // $this->activateCamapign();

        // $this->sendSmsForOne('94770453201', 'test laravel');

        // $test = "94770453201";
        // $date = Carbon::now()->format('d-m-Y');
        // $clientCo = $test."#".$date;
        // print_r($clientCo);

        // $campaign = Campaign::where('state', '1')->first();
        // print_r($campaign->id);

        $payRes = $this->payment('94770453201');
        $body = $payRes->getBody();
        $res = json_decode($body);
        print_r($res);
    


        // $response = $this->payment("94770453201");
        // $body = $response->getBody();
        // $res = json_decode($body);
        // print_r($res->amountTransaction->transactionOperationStatus);

        // $res = $this->checkBalance("94770453201");
        // print_r($res);

        // print_r($res);
        // if ($response->requestError != null){
        //     print_r("Charging failed");
        // }else{
        //     print_r("Chargin passed0");
        // }
    }

    public function payment($msisdn){

        if ($this->hasTooManyRequests()) {
            sleep(
                $this->limiter()->availableIn($this->throttleKey()) + 1 // <= optional plus 1 sec to be on safe side
            );      
            return $this->payment($msisdn);
        }

        $date = Carbon::now()->format('d-m-Y');
        $clientCo = $msisdn."#".$date;

        IDEABIZ::generateAccessToken();
        $access_token = IDEABIZ::getAccessToken();
        $url = "https://ideabiz.lk/apicall/payment/v4/{$msisdn}/transactions/amount";        
        $method = "POST";
        $headers = [
            "Content-Type" => "application/json;charset=UTF-8",
            "Authorization" => "Bearer ".$access_token,
            "Accept" => "application/json",
        ];
        $request_body = [
            "amountTransaction" =>[
                "clientCorrelator"=> "{$clientCo}",
                "endUserId"=>"tel:+".$msisdn,
                "paymentAmount"=> [
                    "chargingInformation"=> [
                        "amount"=>5,
                        "currency"=>"LKR",
                        "description"=> "Subscriberd charges for WinBid Service"
                    ],
                    "chargingMetaData"=> [
                        "onBehalfOf"=> "IdeaBiz Test",
                        "purchaseCategoryCode"=> "Service",
                        "channel"=> "WAP",
                        "taxAmount"=> "0",
                        "serviceID"=> "dc24fcb5-9fa3-4bdc-8c7f-b237bdacbbf2"
                    ]
                ],
                "referenceCode"=> "REF-12345",
                "transactionOperationStatus"=> "Charged"
            ]
        ];
        $response = IDEABIZ::apiCall($url, $method, $headers, $request_body);
        \Log::info('payment function');
        return $response;

        $this->limiter()->hit(
            $this->throttleKey(),60
        );


    }

    public function checkBalance($msisdn){

        if ($this->hasTooManyRequests()) {
            sleep(
                $this->limiter()->availableIn($this->throttleKey()) + 1 // <= optional plus 1 sec to be on safe side
            );      
            return $this->sendSmsForOne($msisdn, $message);
        }

        IDEABIZ::generateAccessToken();
        $access_token = IDEABIZ::getAccessToken();
        $url = "https://ideabiz.lk/apicall/balancecheck/v4/{$msisdn}/transactions/amount/balance";  
        $method = "GET";
        $headers = [
            "Content-Type" => "application/json;charset=UTF-8",
            "Authorization" => "Bearer ".$access_token,
            "Accept" => "application/json",
        ];
        $request_body = [];
        $response = IDEABIZ::apiCall($url, $method, $headers, $request_body);
        return $response;

        $this->limiter()->hit(
            $this->throttleKey(),60
        );
    }


}
