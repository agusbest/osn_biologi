<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Services\CurriculumService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurriculumController extends Controller
{
    public function __invoke(Request $request, CurriculumService $curriculum): JsonResponse
    {
        return response()->json(['topics' => $curriculum->tree($request->user())]);
    }
}
