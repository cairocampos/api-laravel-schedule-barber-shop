<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\UserAppointment;
use App\Models\UserFavorite;
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

        $offset = $request->input("page", 0);

        $barbers = Barber::select(Barber::raw('*, SQRT(
            POW(69.1 * (latitude - '.$lat.'), 2) + 
            POW(69.1 * ('.$lng.' - longitude) * COS(latitude / 57.3), 2)) AS distance'))
            //->havingRaw('distance < ?', [25])
            ->orderBy('distance', 'ASC')
            ->offset($offset)
            ->limit(5)
            ->get();

        foreach($barbers as $key => $value) {
            $barbers[$key]["avatar"] = url("media/avatars")."/".$barbers[$key]["avatar"];
        }

        $array["data"] = $barbers;
        $array["loc"] = "São Paulo";

        return $array;
    }

    public function one($id) {
        $array = ["error" => ''];

        $barber = Barber::findOrFail($id);
        $barber['avatar'] = url("media/avatars/".$barber['avatar']);
        $barber['favorited'] = false;
        $barber['photos'] = [];
        $barber['services'] = [];
        $barber['testimonials'] = [];
        $barber['available'] = [];

        // Verificando favorito
        $barber['favorited'] = UserFavorite::where("id_user", Auth::id())->where("id_barber", $barber->id)->count();

        // Pegando as fotos do barbeiro
        $barber['photos'] = BarberPhotos::select("id", "url")->where('id_barber', $barber->id)->get();
        foreach($barber['photos'] as $key => $value) {
            $barber['photos'][$key]['url'] = url("media/uploads/{$barber['photos'][$key]['url']}");
        }

        // Pegando os serviços
        $barber['services'] = BarberServices::select(["id", "name", "price"])->where("id_barber", $barber->id)->get();

        // Pegando os depoimentos
        $barber['testimonials'] = BarberTestimonial::select(["id", "name", "body", "rate"])->where("id_barber", $barber->id)->get();

        // Pegando disponibilidade
        /*$barber['available'] = BarberAvailability::select(["id", "weekday", "hours"])->where("id_barber", $barber->id)->get();
        foreach($barber['available'] as $key => $value) {
            $barber["available"][$key]['hours'] = explode(",", $barber['available'][$key]['hours']);
        }*/
        
        $availability = [];
        // - Pegando a disponibilidade crua
        $avails = BarberAvailability::where("id_barber", $barber->id)->get();
        $availWeekdays = [];
        foreach($avails as $item) {
            $availWeekdays[$item['weekday']] = explode(",", $item["hours"]);
        }
        
        // - Pegar os agendamentos dos próximos 20 dias
        $appointments = [];
        $appQuery = UserAppointment::where("id_barber", $barber->id)
            ->whereBetween('ap_datetime', [
                date('Y-m-d').'00:00:00',
                date('Y-m-d', strtotime('+20 days')).' 23:59:59'
            ])->get();

        foreach($appQuery as $appItem) {
            $appointments[] = $appItem['ap_datetime'];
        }

        // -  Gerar disponibilidade real
        for($q = 0; $q < 20;$q++) {
            $timeItem = strtotime('+'.$q.' days');
            $weekday = date('w', $timeItem);

            if(in_array($weekday, array_keys($availWeekdays))) {
                $hours = [];
                $dayItem =  date('Y-m-d', $timeItem);

                foreach($availWeekdays[$weekday] as $hourItem) {
                    $dayFormated = $dayItem.' '.$hourItem.':00';
                    if(!in_array($dayFormated, $appointments)) {
                        $hours[] = $hourItem;
                    }
                }

                if(count($hours) > 0) {
                    $availability[] = [
                        'date' => $dayItem,
                        'hours' => $hours
                    ];
                }

            }

        }

        $barber['available'] = $availability;


        // Pegando os dados do barbeiro
        $array['data'] = $barber;

        return $array;
    }
}
