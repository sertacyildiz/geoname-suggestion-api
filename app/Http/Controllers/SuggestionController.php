<?php

namespace App\Http\Controllers;

use App\Rules\Latitude;
use App\Rules\Longitude;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\GetSuggestions;
use Illuminate\Support\Facades\Response;
use App\Repositories\SuggestionRepository;
use Illuminate\Validation\ValidationException;

/**
 * Class SuggestionController
 * @package App\Http\Controllers
 */
final class SuggestionController extends Controller
{
    /**
     * The implementation of suggestion repository.
     *
     * @var SuggestionRepository
     */
    private $suggestionRepository;

    /**
     * @param SuggestionRepository $suggestionRepository
     */
    public function __construct(SuggestionRepository $suggestionRepository)
    {
        $this->suggestionRepository = $suggestionRepository;
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

        $latRule = new Latitude;

        try {
            $this->validate($request, ['latitude' => $latRule]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $latRule->message()], 404, ['Content-type' => 'application/json; charset=utf-8'], JSON_PRETTY_PRINT);
        }

        $longRule = new Longitude;

        try {
            $this->validate($request, ['longitude' => $longRule]);
        } catch (ValidationException $e) {
            return Response::json(['error' => $longRule->message()], 404, ['Content-type' => 'application/json; charset=utf-8'], JSON_PRETTY_PRINT);
        }

        $suggestions = $this->suggestionRepository->search($query, $lat, $long);

        if (empty($suggestions)) {
            return Response::json(['suggestions' => []], 404, ['Content-type' => 'application/json; charset=utf-8'], JSON_PRETTY_PRINT);
        }

        return Response::json(['suggestions' => $suggestions], 200, ['Content-type' => 'application/json; charset=utf-8'], JSON_PRETTY_PRINT);
    }
}