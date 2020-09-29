<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\Barber;
use App\Models\BarberPhotos;
use App\Models\BarberServices;
use App\Models\BarberTestimonial;
use App\Models\BarberAvailability;

class BarberController extends Controller
{

    public function __construct()
    {
        $this->middleware("auth:api");
    }

    public function createRandom() 
    {
        $array = ['error'=>''];
        for($q=0; $q<15; $q++) {
            $names = ['Boniek', 'Paulo', 'Pedro', 'Amanda', 'Leticia', 'Gabriel', 'Gabriela', 'Thais', 'Luiz', 'Diogo', 'José', 'Jeremias', 'Francisco', 'Dirce', 'Marcelo' ];
            $lastnames = ['Santos', 'Silva', 'Santos', 'Silva', 'Alvaro', 'Sousa', 'Diniz', 'Josefa', 'Luiz', 'Diogo', 'Limoeiro', 'Santos', 'Limiro', 'Nazare', 'Mimoza' ];
            $servicos = ['Corte', 'Pintura', 'Aparação', 'Unha', 'Progressiva', 'Limpeza de Pele', 'Corte Feminino'];
            $servicos2 = ['Cabelo', 'Unha', 'Pernas', 'Pernas', 'Progressiva', 'Limpeza de Pele', 'Corte Feminino'];
            $depos = [
            'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
            'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
            'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
            'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.',
            'Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptate consequatur tenetur facere voluptatibus iusto accusantium vero sunt, itaque nisi esse ad temporibus a rerum aperiam cum quaerat quae quasi unde.'
            ];
            $newBarber = new Barber();
            $newBarber->name = $names[rand(0, count($names)-1)].' '.$lastnames[rand(0, count($lastnames)-1)];
            $newBarber->avatar = rand(1, 4).'.png';
            $newBarber->stars = rand(2, 4).'.'.rand(0, 9);
            $newBarber->latitude = '-23.5'.rand(0, 9).'30907';
            $newBarber->longitude = '-46.6'.rand(0,9).'82759';
            $newBarber->save();
            $ns = rand(3, 6);
            for($w=0;$w<4;$w++) {
                $newBarberPhoto = new BarberPhotos();
                $newBarberPhoto->id_barber = $newBarber->id;
                $newBarberPhoto->url = rand(1, 5).'.png';
                $newBarberPhoto->save();
            }
            for($w=0;$w<$ns;$w++) {
                $newBarberService = new BarberServices();
                $newBarberService->id_barber = $newBarber->id;
                $newBarberService->name = $servicos[rand(0, count($servicos)-1)].' de '.$servicos2[rand(0, count($servicos2)-1)];
                $newBarberService->price = rand(1, 99).'.'.rand(0, 100);
                $newBarberService->save();
            }
            for($w=0;$w<3;$w++) {
                $newBarberTestimonial = new BarberTestimonial();
                $newBarberTestimonial->id_barber = $newBarber->id;
                $newBarberTestimonial->name = $names[rand(0, count($names)-1)];
                $newBarberTestimonial->rate = rand(2, 4).'.'.rand(0, 9);
                $newBarberTestimonial->body = $depos[rand(0, count($depos)-1)];
                $newBarberTestimonial->save();
            }
            for($e=0;$e<4;$e++){
                $rAdd = rand(7, 10);
                $hours = [];
            for($r=0;$r<8;$r++) {
                $time = $r + $rAdd;
                if($time < 10) {
                    $time = '0'.$time;
                }
                $hours[] = $time.':00';
            }
                $newBarberAvail = new BarberAvailability();
                $newBarberAvail->id_barber = $newBarber->id;
                $newBarberAvail->weekday = $e;
                $newBarberAvail->hours = implode(',', $hours);
                $newBarberAvail->save();
            }
        }
        return $array;      
    }

    private function searchGeo($address)
    {
        $address = \urlencode($address);
        $key = env('MAPS_KEY', null);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$key}";
        $ch = \curl_init();
        \curl_setopt($ch, CURLOPT_URL, $url);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    
    }

    public function list(Request $request)
    {
        $array = ["error" => ""];
        $lat = $request->input("lat");
        $lng = $request->input("lng");
        $city = $request->input("city");

        if(!empty($city)) {
            $res = $this->searchGeo($city);

            if(count($res["results"]) > 0 ) {
                $lat = $res["results"][0]["geometry"]["location"]["lat"];
                $lng = $res["results"][0]["geometry"]["location"]["lng"];
            }

        } elseif(!empty($lat) && !empty($lng)) {
            $res = $this->searchGeo($lat.",".$lng);
            if(count($res["results"]) > 0 ) {
                $city = $res["results"][0]["formatted_address"];
            }
        } else {
            $lat = "-23.5630907";
            $lat = "-46.6682795";
            $city = "São Paulo";
        }

        $barbers = Barber::select(Barber::raw('*, SQRT(
            POW(69.1 * (latitude - '.$lat.'), 2) + 
            POW(69.1 * ('.$lng.' - longitude) * COS(latitude / 57.3), 2)) AS distance'))
            ->orderBy('distance', 'ASC')
            ->get();

        foreach($barbers as $key => $value) {
            $barbers[$key]["avatar"] = url("media/avatars")."/".$barbers[$key]["avatar"];
        }

        $array["data"] = $barbers;
        $array["loc"] = "São Paulo";

        return $array;

    }
}
