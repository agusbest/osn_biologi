<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show(SettingsService $settings): JsonResponse
    {
        return response()->json($settings->all());
    }

    public function update(Request $request, SettingsService $settings): JsonResponse
    {
        $d = $request->validate([
            'site_name' => ['sometimes', 'string', 'max:100'],
            'practice_default_count' => ['sometimes', 'integer', 'between:1,100'],
            'practice_max_count' => ['sometimes', 'integer', 'between:1,100'],
            'daily_target_min' => ['sometimes', 'integer', 'between:5,600'],
            'daily_target_max' => ['sometimes', 'integer', 'between:5,600'],
            'autosave_seconds' => ['sometimes', 'integer', 'between:5,300'],
            'demo_lesson_id' => ['sometimes', 'nullable', 'integer', 'exists:lessons,id'],
            'payment_instructions' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'recommendation_rules' => ['sometimes', 'array'],
            'recommendation_rules.min_answers' => ['required_with:recommendation_rules', 'integer', 'between:1,100'],
            'recommendation_rules.tiers' => ['required_with:recommendation_rules', 'array', 'min:1', 'max:6'],
            'recommendation_rules.tiers.*.below' => ['required', 'numeric', 'between:1,101'],
            'recommendation_rules.tiers.*.key' => ['required', 'string', 'max:30'],
            'recommendation_rules.tiers.*.label' => ['required', 'string', 'max:80'],
            'recommendation_rules.tiers.*.actions' => ['required', 'array', 'min:1', 'max:6'],
            'recommendation_rules.tiers.*.actions.*' => ['string', 'max:200'],
        ]);

        foreach ($d as $k => $v) {
            $settings->set($k, $v);
        }
        AuditLogger::log($request->user(), 'settings.updated', null, ['keys' => array_keys($d)]);

        return response()->json($settings->all());
    }
}
