<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>
    
    <div class="container">
        <div class="row">
            <div class="col-md-4 mt-5">
                <a href="/dashboard/new" class="btn btn-primary">New Campaign</a>
                <a href="/dashboard/winner" class="btn btn-primary">Winner</a>
                <a href="/dashboard/customMessage" class="btn btn-primary">Custom Message</a>
            </div><div class="col-md-4 mt-5">
                
            </div>
        </div>

        <div class="row mt-5">
            <form action="/dashboard" method="POST">
                 @csrf
                <div class="form-group mb-3">
                    <div class="row">
                        <div class="col">
                            <select name="campaignTime" id="campaignTime">
                                @foreach($allCampaigns as $allCampaign)
                                    <option value={{$allCampaign->id}}> {{$allCampaign->create_date}} - {{$allCampaign->expire_date}}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary ml-3">Load</button>
                        </div>
                        
                    </div>
                   
                </div>
            </form>
        </div>

        <div class="row mt-3">
            <table class="table table-bordered table-striped table-dark">
                <thead class="thead-dark">
                    <th>ID</th>
                    <th>Name</th>
                    <th>Welcome Message</th>
                    <th>End Message</th>
                    <th>Create Date</th>
                    <th>Expire Date</th>
                    <th>Status</th>
                    <th></th>
                </thead>
                <tbody>
                    @foreach($campaigns as $campaign)
                        <tr>
                        <td>{{$campaign->id}}</td>
                        <td>{{$campaign->name}}</td>
                        <td>{{$campaign->welcome_msg}}</td>
                        <td>{{$campaign->end_msg}}</td>
                        <td>{{$campaign->create_date}}</td>
                        <td>{{$campaign->expire_date}}</td>
                        <td>{{$campaign->state == "1" ? 'active': 'deactive'}}</td>
                        <td> <a href="{{ URL('/dashboard/editCampaignPage/'.$campaign->id )}}" class="btn btn-primary">edit</a> <a href="#" class="btn btn-danger">Delete</a> </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>



    
</x-app-layout>
