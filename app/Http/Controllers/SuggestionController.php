<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetSuggestions;
use App\Imports\GeonameImport;
use App\Rules\Latitude;
use App\Rules\Longitude;
use Geokit\Math;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class SuggestionController extends Controller
{
    protected $suggestions;

    public function __construct()
    {
        $this->suggestions = [];
    }

    public function index(GetSuggestions $request): JsonResponse
    {
        $query = $request->input('q');
        $lat = $request->input('latitude');
        $long = $request->input('longitude');

        $headers = ['Content-type' => 'application/json; charset=utf-8'];

        $lat_rule = new Latitude;
        try {
            $this->validate($request, ['latitude' => $lat_rule]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $lat_rule->message()], 404, $headers, JSON_PRETTY_PRINT);
        }

        $long_rule = new Longitude;
        try {
            $this->validate($request, ['longitude' => $long_rule]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $long_rule->message()], 404, $headers, JSON_PRETTY_PRINT);
        }

        $geoname_array = (new GeonameImport)->toArray(public_path() . '/data/cities_canada-usa.tsv');

        foreach ($geoname_array as $row) {

            foreach ($row as $key => $column) {
                if ($key == 0) {
                    continue;
                }

                $cell = explode("\t", $column[0]);

                if ($cell[14] < 5000) {
                    continue;
                }

                $new_name = $cell[1];
                if (stripos($new_name, $query) === false) {
                    continue;
                }

                $math = new Math();

                $new_lat = (double)$cell[4];
                $new_long = (double)$cell[5];
                $distance = $math->distanceVincenty(['latitude' => $lat, 'longitude' => $long], ['latitude' => $new_lat, 'longitude' => $new_long]);
                $country = $cell[8] == 'US' ? 'USA' : 'Canada';

                $geo = new \stdClass();
                $geo->name = $cell[1] . ', ' . $cell[10] . ', ' . $country;
                $geo->latitude = $new_lat;
                $geo->longitude = $new_long;

                $similarity_score = 1 - (float)number_format(levenshtein(strtolower($new_name), strtolower($query)) / 10, 1);
                $distance_score = 1 - (float)number_format($distance->km() / 1000, 2);
                $geo->score = (float)number_format(($similarity_score + $distance_score) / 2, 1);
                if ($geo->score < 0.1) {
                    continue;
                }

                $this->suggestions[] = $geo;

            }
        }

        usort($this->suggestions,
            function ($a, $b) use ($query) {
                if ($a->score < $b->score) {
                    $result = 1;
                } else if ($a->score > $b->score) {
                    $result = -1;
                } else {
                    $result = 0;
                    if (strrpos(strtolower($a->name), strtolower($query)) < strrpos(strtolower($b->name), strtolower($query))) {
                        $result = 1;
                    } else if (strrpos(strtolower($a->name), strtolower($query)) > strrpos(strtolower($b->name), strtolower($query))) {
                        $result = -1;
                    }
                }
                return $result;
            }
        );

        if (empty($this->suggestions)) {
            return Response::json(['suggestions' => [], 404, $headers, JSON_PRETTY_PRINT]);
        }

        return Response::json(['suggestions' => $this->suggestions], 200, $headers, JSON_PRETTY_PRINT);

    }
}
