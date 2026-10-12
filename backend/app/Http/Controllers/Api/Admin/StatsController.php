<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function stats(AnalyticsService $analytics): JsonResponse
    {
        return response()->json($analytics->adminStats());
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $q = AuditLog::with('user:id,name,email');
        if ($request->filled('action')) {
            $q->whereLike('action', $request->input('action') . '%');
        }

        return response()->json($q->orderByDesc('id')->paginate(50));
    }
}
