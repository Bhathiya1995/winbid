<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>
    
    <div class="container">
        <div class="row">
            <div class="col-md-4 mt-5">
                <a href="/dashboard/new" class="btn btn-primary">New Compaign</a>
            </div>
        </div>

        <div class="row mt-5">
            <table class="table table-bordered table-striped table-dark">
                <thead class="thead-dark">
                    <th>ID</th>
                    <th>Welcome Message</th>
                    <th>End Message</th>
                    <th>Create Date</th>
                    <th>Expire Date</th>
                    <th>Status</th>
                </thead>
                <tbody>
                    @foreach($campaigns as $campaign)
                        <tr>
                        <td>{{$campaign->id}}</td>
                        <td>{{$campaign->welcome_msg}}</td>
                        <td>{{$campaign->end_msg}}</td>
                        <td>{{$campaign->create_date}}</td>
                        <td>{{$campaign->expire_date}}</td>
                        <td>{{$campaign->state == "1" ? 'active': 'deactive'}}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>



    
</x-app-layout>
