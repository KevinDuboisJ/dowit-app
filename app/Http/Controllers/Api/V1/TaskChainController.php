<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TaskChainService;

class TaskChainController extends Controller
{
    protected $taskChainService;

    public function __construct(TaskChainService $taskChainService)
    {
        $this->taskChainService = $taskChainService;
    }

    public function trigger(Request $request)
    {
        $request->validate([
            'event' => 'required|string',
            'source' => 'nullable|string',
            'context' => 'nullable|array'
        ]);

        $this->taskChainService->handleEvent($request->event, $request->context ?? []);

        return response()->json(['message' => 'Task chain processed.']);
    }
}