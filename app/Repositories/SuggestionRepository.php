<?php

namespace App\Repositories;

use Geokit\Math;
use App\Imports\GeonameImport;

class SuggestionRepository
{
    public $suggestions;

    public function __construct()
    {
        $this->suggestions = [];
    }

    /**
     * Search cities by the given lat and long.
     *
     * @param $query
     * @param $lat
     * @param $long
     *
     * @return array
     */
    public function search($query, $lat, $long)
    {
        /** @var GeonameImport $geonameImportObj */
        $geonameImportObj = app(GeonameImport::class);

        $geoname_array = $geonameImportObj->toArray(public_path() . '/data/cities_canada-usa.tsv');

        foreach ($geoname_array as $row) {

            foreach ($row as $key => $value) {
                if ($key == 0) {
                    continue;
                }

                $cells = explode("\t", $value[0]);

                $city_population = (int)$cells[14];
                if ($city_population < 5000) {
                    continue;
                }

                $city_name = $cells[1];
                if (stripos($city_name, $query) === false) {
                    continue;
                }

                $city_lat = (double)$cells[4];
                $city_long = (double)$cells[5];
                $state = $cells[10];
                $country = $cells[8] == 'US' ? 'USA' : 'Canada';

                $math = new Math();
                $from = ['latitude' => $lat, 'longitude' => $long];
                $to = ['latitude' => $city_lat, 'longitude' => $city_long];
                $distance = $math->distanceVincenty($from, $to);

                $geo = new \stdClass();
                $geo->name = $city_name . ', ' .$state . ', ' . $country;
                $geo->latitude = $city_lat;
                $geo->longitude = $city_long;

                $similarity_score = 1 - (float)number_format(levenshtein(strtolower($city_name), strtolower($query)) / 10, 1);
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
                if ($a->score == $b->score) {
                    if (strripos($a->name, $query) == strripos($b->name, $query)) {
                        return 0;
                    }
                    return (strripos($a->name, $query) < strripos($b->name, $query)) ? -1 : 1;
                }
                return ($a->score < $b->score) ? 1 : -1;
            }
        );

        return $this->suggestions;
    }
}
