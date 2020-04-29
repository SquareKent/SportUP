<?php

use Illuminate\Support\Facades\Route;

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

use Illuminate\Http\Request;
use App\Halls;
use App\Trainer;
use App\Client;
use App\Subscriptions;
use App\Uchet;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/api/subscriptions/all/', 'SportClub@GetAllSubscriptions');

Route::get('/api/halls/all/', 'SportClub@GetHallList');

Route::get('/api/halls/get/paid_clients/', 'SportClub@GetPaidClientsOfHall');

Route::get('/api/halls/all_names/', 'SportClub@GetAllHallNamesThenID');

Route::get('/api/clients/all_names/', 'SportClub@GetAllClientNamesThenID');

Route::get('/api/subs/create/', 'SportClub@CreateNewAbonement');

Route::get('/api/clients/list/', 'SportClub@GetClientLists');

Route::get('/api/trainers/list/', 'SportClub@GetTrainersList');

Route::post('/api/trainers/create/', 'SportClub@AddNewTrainer');

Route::post('/api/client/create/', 'SportClub@AddNewClient');

Route::post('/api/halls/create/', 'SportClub@AddNewHall');

Route::get('/api/get_all_trainers/', function (Request $request){
    return Trainer::all('trainer_code as Код_тренера', 'full_name as Фамилия_имя_тренера', 'salary as Оклад', 'phone_number as Телефон');
});

Route::get('/api/get_clients_of_trainer/', function (Request $request) {
    return Client::all('client_code as Код_клиента',
        'second_name as Фамилия',
        'first_name as Имя',
        'phone_number as Телефон',
        'trainer_code as Код_тренера')->where('Код_тренера', '==', $request->get('trainer_code'));
});


