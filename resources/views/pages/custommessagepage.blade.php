<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Winner') }}
        </h2>
    </x-slot>

    <div class="container">
    
        <div class="row mt-5">
        @include('components.replyMsg')
            <form action="/dashboard/newCustomMessage" method="POST">
                 @csrf
                <div class="form-group mb-3">
                    <div class="row ">
                        <label for="customMsg">Custom Message</label>
                        <input type="text" class="form-control" name="customMsg" id="customMsg" placeholder="Enter Custom Message">
                                            
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Send</button>

                    @isset($customMessages)
                        <h3 class="mt-3">Custom Message</h3>
                        <table class="table table-bordered table-striped table-success mt-3">
                            <thead class="thead-dark">
                                <th>ID</th>
                                <th>Custom Message</th>
                                <th>Created Date</th>
                                <th>Status</th>
                            </thead>
                            <tbody>
                                <tr>
                                    @foreach($customMessages as $customMessage)
                                    <tr>
                                    <td>{{$customMessage->id}}</td>
                                    <td>{{$customMessage->custom_message}}</td>
                                    <td>{{$customMessage->created_at}}</td>
                                    <td>{{$customMessage->status == "1" ? 'active': 'deactive'}}</td>
                                    </tr>
                                    @endforeach 
                                </tr>
                            </tbody>
                        </table>
                    @endisset

                   
                </div>
            </form>
        </div>
    </div>


    
</x-app-layout>