<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Winner') }}
        </h2>
    </x-slot>

    <div class="container">
    
        <div class="row mt-5">
            <form action="/dashboard/winner" method="POST">
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

                    @isset($winner)
                        <h3 class="mt-3">Winner</h3>
                        <table class="table table-bordered table-striped table-success mt-3">
                            <thead class="thead-dark">
                                <th>ID</th>
                                <th>Bid Value</th>
                                <th>Tel-Numebr</th>
                                <th>Status</th>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{$winner->id}}</td>
                                    <td>{{$winner->bid_value}}</td>
                                    <td>{{$winner->tel_number}}</td>
                                    <td>{{$winner->status == "1" ? 'active': 'deactive'}}</td>
                                </tr>
                            </tbody>
                        </table>
                    @endisset

                    @isset($allUniqueWinners)
                        <h3>All Winners</h3>
                        <table class="table table-bordered table-striped table-dark mt-3">
                            <thead class="thead-dark">
                                <th>ID</th>
                                <th>Bid Value</th>
                                <th>Tel-Numebr</th>
                                <th>Status</th>
                            </thead>
                            <tbody>
                                    @foreach($allUniqueWinners as $allwinner)
                                    <tr>
                                        <td>{{$allwinner->bid_value}}</td>
                                        <td>{{$allwinner->id}}</td>
                                        <td>{{$allwinner->tel_number}}</td>
                                        <td>{{$allwinner->status == "1" ? 'active': 'deactive'}}</td>
                                      </tr>
                                    @endforeach

                            </tbody>
                        </table>

                    @endisset
                   
                </div>
            </form>
        </div>
    </div>


    
</x-app-layout>