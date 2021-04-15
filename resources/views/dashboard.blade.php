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

    </div>



    
</x-app-layout>
