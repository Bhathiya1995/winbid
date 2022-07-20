<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CustomMessageController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
//     return view('dashboard');
// })->name('dashboard');

Route::middleware(['auth:sanctum', 'verified'])->group(function(){
    Route::match(['GET','POST'],'/dashboard',[CampaignController::class, 'dashboard'])->name('dashboard');

    Route::get('/dashboard/new', function () {
         return view('pages.newcampaignpage');
    });

    Route::post('/dashboard/createNewCampign', [CampaignController::class, 'createCampaign']);
    Route::match(['GET','POST'],'/dashboard/winner', [CampaignController::class, 'winner']);
    Route::get('/dashboard/editCampaignPage/{id}',[CampaignController::class, 'editCampaignPage']);
    Route::post('/dashboard/updateCampaignPage/{id}',[CampaignController::class, 'updateCampaign']);
    Route::get('/dashboard/customMessage/',[CustomMessageController::class, 'customMessagePage']);
    Route::post('/dashboard/newCustomMessage',[CustomMessageController::class, 'newCustomMessage']);

});

Route::get('/test', [CampaignController::class, 'test']);
Route::get('/test1', [CampaignController::class, 'test1']);
Route::post('/receiveRegsms', [CampaignController::class, 'receiveRegsms']);
Route::post('/receivesms', [CampaignController::class, 'receiveSms']);
Route::post('/adminapi', [CampaignController::class, 'admin']);