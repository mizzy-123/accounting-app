<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $entity = $this->activeEntity($request);
        $this->authorize('viewAuditLogs', [Transaction::class, $entity]);

        $logs = AuditLog::query()
            ->forEntity($entity)
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->paginate(30)
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'model_type' => class_basename($log->model_type),
                'model_id' => $log->model_id,
                'changes' => $log->changes,
                'created_at' => $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ] : null,
            ]);

        return Inertia::render('audit-logs/index', [
            'logs' => $logs,
        ]);
    }
}
