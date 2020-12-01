<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetSuggestions;
use App\Repositories\SuggestionRepository;
use App\Rules\Latitude;
use App\Rules\Longitude;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class SuggestionController extends Controller
{

    protected $suggestion_repository;

    /**
     * @param SuggestionRepository $suggestion_repository
     */
    public function __construct(SuggestionRepository $suggestion_repository)
    {
        $this->suggestion_repository = $suggestion_repository;
    }

    /**
     * @param GetSuggestions $request
     * @return JsonResponse
     */
    public function index(GetSuggestions $request): JsonResponse
    {
        $query = $request->input('q');
        $lat = (float)$request->input('latitude');
        $long = (float)$request->input('longitude');

        if (!isset($query)) {
            return Response::json(['suggestions' => []], 404, ['Content-type' => 'application/json; charset=utf-8'], JSON_PRETTY_PRINT);
        }

        if (!isset($lat)) {
            return Response::json(['suggestions' => []], 404, ['Content-type' => 'application/json; charset=utf-8'], JSON_PRETTY_PRINT);
        }

        if (!isset($long)) {
            return Response::json(['suggestions' => []], 404, ['Content-type' => 'application/json; charset=utf-8'], JSON_PRETTY_PRINT);
        }

        $lat_rule = new Latitude;
        try {
            $this->validate($request, ['latitude' => $lat_rule]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $lat_rule->message()], 404, ['Content-type' => 'application/json; charset=utf-8'], JSON_PRETTY_PRINT);
        }

        $long_rule = new Longitude;
        try {
            $this->validate($request, ['longitude' => $long_rule]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $long_rule->message()], 404, ['Content-type' => 'application/json; charset=utf-8'], JSON_PRETTY_PRINT);
        }

        $suggestions = $this->suggestion_repository->search($query, $lat, $long);

        if (empty($suggestions)) {
            return Response::json(['suggestions' => []], 404, ['Content-type' => 'application/json; charset=utf-8'], JSON_PRETTY_PRINT);
        }

        return Response::json(['suggestions' => $suggestions], 200, ['Content-type' => 'application/json; charset=utf-8'], JSON_PRETTY_PRINT);

    }
}
