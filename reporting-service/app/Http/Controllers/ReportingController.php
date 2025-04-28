<?php

namespace App\Http\Controllers;

use App\Services\ReportMetric;
use Illuminate\Http\JsonResponse;

class ReportingController extends Controller
{
    public function __invoke(ReportMetric $metric): JsonResponse
    {
        return response()->json($metric->getHandler()->getValue());
    }
}
