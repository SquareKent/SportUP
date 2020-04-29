<?php

namespace App\Http\Controllers;

use App\Trainer;
use DemeterChain\C;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use App\Halls;
use App\Client;
use App\Subscriptions;
use App\Uchet;


class SportClub extends Controller
{
    function GetClientInfoByCode($code) {
        return Client::all()
            ->where('client_code', $code)
            ->first();
    }

    function getNameByClientCode($code)
    {
        $ClientObject = Client::where('client_code', $code)->first();
        if($ClientObject == null)
            return null;
        return  $ClientObject->first_name . ' ' . $ClientObject->second_name;
    }

    function getHallNameByHallCode($code)
    {
        $HallObject = Halls::where('hall_code', $code)->first();
        if($HallObject == null)
            return null;

        return  $HallObject->name;
    }


    function GetPaidClientCodeBySubscription($sub_code)
    {
        $object = Uchet::all()
            ->where('subscription_code', $sub_code)
            ->where('paid', true)
            ->first();

        if(!$object)
            return null;

        return $this->GetClientInfoByCode($object['client_code']);
    }

    function GetPaidClientsOfHall(Request $request)
    {
        $code_hall = $request->get('code');
        if(!$code_hall)
            return null;

        $objects = Subscriptions::all('subscription_code', 'code_hall', 'description')->where('code_hall', $code_hall)->toArray();
        if(!$objects)
            return null;

        reset($objects);
        $client_list = array();
        do{
            $current = current($objects);
            $client = $this->GetPaidClientCodeBySubscription($current['subscription_code']);
            if(!$client) continue;

            foreach ($client_list as &$client_info) {
                if($client_info['client_code'] == $client['client_code']) {
                    $client = null;
                    break;
                }
            }

            if(!$client) continue;

            $client['posesheniye'] = $current['description'];

            array_push($client_list, $client);
        } while(next($objects));
        return $client_list;
    }




    function AddNewHall(Request $request)
    {
        $imagePath = '';
        $image = $request->file('image');
        if($image) {
            try {
                $this->validate($request, [
                    'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:4096'
                ]);
                $imagePath = Storage::disk('public')->put('avatars', $image);
            }
            catch (ValidationException $e) {
                return response()->json([
                    'status' => 'failed',
                    'description' => 'invalid_image'
                ]);
            }
        }

        $hall = new Halls();
        $hall->name = $request->get('name');
        $hall->description = $request->get('description');
        $hall->preview = $imagePath;
        $hall->save();

        $hall_object = $hall;
        if( $imagePath != '' )
            $hall_object['preview'] = 'http://127.0.0.1:8000/storage/' . $imagePath;

        return response()->json([
            'status' => 'success',
            'object' => $hall_object
        ]);
    }

    function GetHallList(Request $request){
        $objects =  Halls::all('hall_code', 'name', 'description', 'preview')->sortByDesc('hall_code')->toArray();

        if(!$objects)
            return null;

        reset($objects);
        $newObjects = array();
        do{
            $current = current($objects);
            $current['preview'] =  'http://127.0.0.1:8000/storage/' . $current['preview'];
            array_push($newObjects, $current);
        } while(next($objects));
        return $newObjects;
    }


    function GetAllHallNamesThenID()
    {
        $objects =  Halls::all('hall_code', 'name')->sortByDesc('hall_code')->toArray();
        if(!$objects)
            return null;


        reset($objects);
        $names = array();
        do{
            array_push($names, current($objects)['name']);
        } while(next($objects));

        reset($objects);
        $codes = array();
        do{
            array_push($codes, current($objects)['hall_code']);
        } while(next($objects));

        $result['names'] = $names;
        $result['codes'] = $codes;
        return $result;
    }

    function GetAllClientNamesThenID()
    {
        $objects =  Client::all('client_code', 'second_name', 'first_name')->sortByDesc('client_code')->toArray();
        if(!$objects)
            return null;

        reset($objects);
        $names = array();
        do{
            $current = current($objects);
            array_push($names, $current['first_name'] . ' ' . $current['second_name'] . ' #' . $current['client_code']);
        } while(next($objects));

        reset($objects);
        $codes = array();
        do{
            array_push($codes, current($objects)['client_code']);
        } while(next($objects));

        $result['names'] = $names;
        $result['codes'] = $codes;
        return $result;
    }

    function CreateNewAbonement(Request $request)
    {
        $newAbonement = new Subscriptions();
        $newAbonement->description = $request->get('description');
        $newAbonement->price = $request->get('price');
        $newAbonement->code_hall = $request->get('code_hall');
        $newAbonement->save();

        $Abonement = Subscriptions::all('subscription_code as sub_code', 'description', 'price', 'code_hall')->last();
        if(!$Abonement)
            return null;

        $UchetZapis = new Uchet();
        $UchetZapis->client_code = $request->get('client_code');
        $UchetZapis->subscription_code = $Abonement->sub_code;
        $UchetZapis->month = date("m");
        $UchetZapis->paid = true;
        $UchetZapis->save();

        $Abonement['name'] = $this->getNameByClientCode($request->get('client_code'));
        if($Abonement['name'] == null)
            return null;

        $Abonement['hall'] = $this->getHallNameByHallCode($request->get('code_hall'));
        if($Abonement['hall'] == null)
            return null;




        return $Abonement;
    }



    function GetClientLists()
    {
        $clients = Client::all()->sortByDesc('client_code')->toArray();

        reset($clients);
        $clients_info = array();
        do{
            $current = current($clients);
            $current['more_info'] = false;

            if( $current['photo'] != '' )
                $current['photo'] = 'http://127.0.0.1:8000/storage/' . $current['photo'];

            if(!$current['trainer_code']) {
                array_push($clients_info, $current);
                continue;
            }

            $current['trainer_info'] = Trainer::all()->where('trainer_code', $current['trainer_code'])->first();
            if(!$current['trainer_info']) {
                array_push($clients_info, $current);
                continue;
            }

            $current['uchet'] = Uchet::all()->where('client_code', $current['client_code'])->last();
            if(!$current['uchet']) {
                array_push($clients_info, $current);
                continue;
            }

            $current['abonement'] = Subscriptions::all()->where('subscription_code', $current['uchet']->subscription_code)->last();
            if(!$current['abonement']) {
                array_push($clients_info, $current);
                continue;
            }

            $current['hall'] = Halls::all()->where('hall_code', $current['abonement']->code_hall)->first();
            if(!$current['hall']) {
                array_push($clients_info, $current);
                continue;
            }

            $current['more_info'] = true;
            array_push($clients_info, $current);
        } while(next($clients));

        return $clients_info;
    }




    function GetLastClientList()
    {
        $current = Client::all()->sortByDesc('client_code')->first();
        $current['more_info'] = false;

        if( $current['photo'] != '' )
            $current['photo'] = 'http://127.0.0.1:8000/storage/' . $current['photo'];

        if(!$current['trainer_code']) {
            return $current;
        }

        $current['trainer_info'] = Trainer::all()->where('trainer_code', $current['trainer_code'])->first();
        if(!$current['trainer_info']) {
            return $current;
        }

        $current['uchet'] = Uchet::all()->where('client_code', $current['client_code'])->last();
        if(!$current['uchet']) {
            return $current;
        }

        $current['abonement'] = Subscriptions::all()->where('subscription_code', $current['uchet']->subscription_code)->last();
        if(!$current['abonement']) {
            return $current;
        }

        $current['hall'] = Halls::all()->where('hall_code', $current['abonement']->code_hall)->first();
        if(!$current['hall']) {
            return $current;
        }

        $current['more_info'] = true;
        return $current;
    }

    function GetLastTrainer()
    {
        $currentObject = Trainer::all()->last();
        $currentObject['clients'] = null;
        if( $currentObject['photo'] != '' )
            $currentObject['photo'] = 'http://127.0.0.1:8000/storage/' . $currentObject['photo'];
        return $currentObject;
    }

    function AddNewTrainer(Request $request)
    {
        $imagePath = '';
        $image = $request->file('image');
        if($image) {
            try {
                $this->validate($request, [
                    'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:4096'
                ]);
                $imagePath = Storage::disk('public')->put('trainers', $image);
            }
            catch (ValidationException $e) {
                return response()->json([
                    'status' => 'failed',
                    'description' => 'invalid_image',
                    'test' => $image
                ]);
            }
        }

        $newTrainer = new Trainer();
        $newTrainer->full_name = $request->get('first_name') . ' ' . $request->get('second_name');
        $newTrainer->salary = $request->get('salary');
        $newTrainer->phone_number = $request->get('phone_number');
        $newTrainer->email = $request->get('email');
        $newTrainer->photo = $imagePath;
        $newTrainer->save();

        return response()->json([
            'status' => 'success',
            'object' => $this->GetLastTrainer()
        ]);

    }

    function GetTrainersList(Request $request)
    {
        $objects = Trainer::all()->sortByDesc('trainer_code')->toArray();

        reset($objects);
        $trainers = array();
        do{
            $currentObject = current($objects);

            if( $currentObject['photo'] != '' )
                $currentObject['photo'] = 'http://127.0.0.1:8000/storage/' . $currentObject['photo'];

            $clientsOfObject = Client::all()->where('trainer_code', $currentObject['trainer_code'])->toArray();
            if($clientsOfObject) {
                reset($clientsOfObject);
                $currentObject['clients'] = array();
                do {
                    $currentClientInfo = current($clientsOfObject);
                    $currentClientInfo['more_info'] = false;

                    if( $currentClientInfo['photo'] != '' )
                        $currentClientInfo['photo'] = 'http://127.0.0.1:8000/storage/' . $currentClientInfo['photo'];

                    $currentClientInfo['uchet'] = Uchet::all()->where('client_code', $currentClientInfo['client_code'])->last();
                    if(!$currentClientInfo['uchet']) {
                        array_push($currentObject['clients'], $currentClientInfo);
                        continue;
                    }

                    $currentClientInfo['abonement'] = Subscriptions::all()->where('subscription_code', $currentClientInfo['uchet']->subscription_code)->last();
                    if(!$currentClientInfo['abonement']) {
                        array_push($currentObject['clients'], $currentClientInfo);
                        continue;
                    }

                    $currentClientInfo['hall'] = Halls::all()->where('hall_code', $currentClientInfo['abonement']->code_hall)->first();
                    if(!$currentClientInfo['hall']) {
                        array_push($currentObject['clients'], $currentClientInfo);
                        continue;
                    }

                    $currentClientInfo['more_info'] = true;

                    array_push($currentObject['clients'], $currentClientInfo);
                } while (next($clientsOfObject));
            }
            else $currentObject['clients'] = null;

            array_push($trainers, $currentObject);
        } while(next($objects));

        return $trainers;
    }

    function AddNewClient(Request $request)
    {
        $imagePath = '';
        $avatar = $request->file('avatar');
        if($avatar) {
            try {
                $this->validate($request, [
                    'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:4096'
                ]);
                $imagePath = Storage::disk('public')->put('avatars', $avatar);
            }
            catch (ValidationException $e) {
                return response()->json([
                    'status' => 'failed',
                    'description' => 'invalid_image'
                ]);
            }
        }

        $client = new Client();
        $client->second_name = $request->get('second_name');
        $client->first_name = $request->get('first_name');
        $client->email = $request->get('email');
        $client->phone_number = $request->get('phone_number');
        $client->photo = $imagePath;
        $client->save();


        return response()->json([
            'status' => 'success',
            'object' => $this->GetLastClientList()
        ]);
    }




    function getNameBySubCode($code)
    {
        $UchetObject = Uchet::where('subscription_code', $code)->first();
        if($UchetObject == null)
            return null;
        $ClientObject = Client::where('client_code', $UchetObject->client_code)->first();
        if($ClientObject == null)
            return null;
        return  $ClientObject->first_name . ' ' . $ClientObject->second_name;
    }


    function GetAllSubscriptions()
    {
        $objects =  Subscriptions::all('subscription_code as name', 'description', 'price', 'code_hall')->sortByDesc('name')->toArray();
        if(!$objects)
            return null;

        reset($objects);
        $newObjects = array();
        do{
            $current = current($objects);
            $current['sub_code'] = $current['name'];
            $current['name'] = $this->getNameBySubCode($current['name']);
            if($current['name'] == null)
                continue;
            $hallObj = Halls::where('hall_code', $current['code_hall'])->first();
            if(!$hallObj)
                continue;
            $current['hall'] = $hallObj->name;
            array_push($newObjects, $current);
        } while(next($objects));

        return $newObjects;
    }



}
