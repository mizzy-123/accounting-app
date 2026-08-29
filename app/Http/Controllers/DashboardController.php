<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function __invoke(Request $request): Response
    {
        $entity = $this->activeEntity($request);
        $this->authorize('view', $entity);

        $dashboard = $this->dashboardService->forEntity($entity);

        return Inertia::render('dashboard', [
            'entityType' => $entity->type,
            'accounts' => $dashboard['accounts'],
            'totals' => $dashboard['totals'],
            'chart' => $dashboard['chart'],
            'categoryExpenses' => $dashboard['category_expenses'],
            'recentTransactions' => $dashboard['recent_transactions'],
            'businessOverview' => $dashboard['business_overview'],
            'invoiceReminders' => $dashboard['invoice_reminders'],
        ]);
    }
}
