<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Topic;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Alur status konten: draft → review → published → archived. */
class StatusController extends Controller
{
    private const MAP = [
        'topics' => Topic::class,
        'modules' => Module::class,
        'lessons' => Lesson::class,
        'questions' => Question::class,
        'quizzes' => Quiz::class,
    ];

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        $class = self::MAP[$type] ?? abort(404);
        $item = $class::findOrFail($id);
        $to = $request->validate(['status' => ['required', Rule::in($class::statuses())]])['status'];

        if (! in_array($to, $class::allowedTransitions($item->status), true)) {
            return response()->json(['message' => "Transisi {$item->status} → {$to} tidak diizinkan."], 422);
        }

        if ($to === 'published') {
            $problems = $item->publishProblems();
            if ($problems) {
                return response()->json(['message' => 'Konten belum siap diterbitkan.', 'errors' => ['status' => $problems]], 422);
            }
        }

        $from = $item->status;
        $item->update(['status' => $to]);
        AuditLogger::log($request->user(), 'content.status', $item, ['type' => $type, 'from' => $from, 'to' => $to]);

        return response()->json(['id' => $item->id, 'status' => $item->status]);
    }
}
