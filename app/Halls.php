<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Halls extends Model
{
    //
    public static function get_all_elements()
    {
        return static::all();
    }

    public static function create_new_hall($hall_code, $hall_name)
    {
        $newHall = new Halls;
        $newHall->hall_code = $hall_code;
        $newHall->name = $hall_name;
        $newHall->save();
    }
}
