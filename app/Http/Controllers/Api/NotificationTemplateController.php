<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationTemplateController extends Controller
{
    /**
     * GET /api/notification-templates
     */
    public function index(): JsonResponse
    {
        $templates = NotificationTemplate::all();

        return response()->json([
            'success' => true,
            'message' => 'Notification templates retrieved',
            'data'    => $templates,
        ]);
    }

    /**
     * POST /api/notification-templates
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'                => ['required', 'string', 'unique:notification_templates,code'],
            'name'                => ['required', 'string'],
            'subject'             => ['required', 'string'],
            'view'                => ['required', 'string'],
            'available_variables' => ['nullable', 'array'],
            'is_active'           => ['boolean'],
        ]);

        $template = NotificationTemplate::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Template created',
            'data'    => $template,
        ], 201);
    }

    /**
     * GET /api/notification-templates/{id}
     */
    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Template detail',
            'data'    => NotificationTemplate::findOrFail($id),
        ]);
    }

    /**
     * PUT /api/notification-templates/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $template = NotificationTemplate::findOrFail($id);

        $validated = $request->validate([
            'code'                => ['sometimes', 'string', Rule::unique('notification_templates', 'code')->ignore($id)],
            'name'                => ['sometimes', 'string'],
            'subject'             => ['sometimes', 'string'],
            'view'                => ['sometimes', 'string'],
            'available_variables' => ['nullable', 'array'],
            'is_active'           => ['boolean'],
        ]);

        $template->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Template updated',
            'data'    => $template,
        ]);
    }
}
