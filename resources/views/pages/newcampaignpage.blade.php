<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Campaign') }}
        </h2>
    </x-slot>
    
    <div class="container">
        <div class="card mt-5">
            <div class="card-body">
                @include('components.replyMsg')
                <form action="/dashboard/createNewCampign" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="welcomeMsg">Welcome Message</label>
                        <input type="text" class="form-control" name="welcomeMsg" id="welcomeMsg" aria-describedby="emailHelp" placeholder="Enter Welcome Message">
                    </div>
                    <div class="form-group mb-3">
                        <label for="endMsg">End Message</label>
                        <input type="text" class="form-control" name="endMsg" id="endMsg" aria-describedby="emailHelp" placeholder="Enter End Message">
                    </div>
                    <div class="form-group mb-3">
                        <div class="row">
                            <div class="col">
                                <label for="createDate">Create date</label>
                                <input type="date" class="form-control" name="createDate" id="createDate" aria-describedby="emailHelp">
                            </div>
                            <div class="col">
                                <label for="expireDate">Expire Date</label>
                                <input type="date" class="form-control" name="expireDate" id="expireDate" aria-describedby="emailHelp">
                            </div>
                        </div>
                        
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>



    
</x-app-layout>