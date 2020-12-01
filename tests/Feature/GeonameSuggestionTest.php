<?php

namespace Tests\Feature;

use Illuminate\Testing\AssertableJsonString;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Throwable;

final class GeonameSuggestionTest extends TestCase
{
    private AssertableJsonString $json_data;
    private ?TestResponse $response = null;

    protected function start(): void
    {
        parent::start(); //
        $this->response = $this->get('/suggestions?q=London');
        try {
            $this->json_data = $this->response->decodeResponseJson();
        } catch (Throwable $e) {
            $this->json_data = null;
        }

    }

    /**
     * returns 200
     */
    public function test_response()
    {
        $this->response->assertStatus(200);
    }

    /**
     * returns suggestions data as array
     * @throws Throwable
     */
    public function test_suggestions_array()
    {
        $this->assertNotNull($this->json_data);
        $this->assertInstanceOf(AssertableJsonString::class, $this->json_data);
    }

    /**
     * contains score, lat and long data
     */
    public function test_data_structure()
    {
        $this->assertNotNull($this->json_data);
        $data = json_decode($this->json_data->json);

        foreach ($data->suggestions as $geoname) {
            $this->assertTrue(isset($geoname->latitude) && isset($geoname->longitude));
            $this->assertTrue(isset($geoname->score));
        }
    }


}
