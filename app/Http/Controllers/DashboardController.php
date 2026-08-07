<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(): View
    {
        return view('dashboard.index', [
            'stats' => $this->dashboardService->getStats(),
            'dailyChart' => $this->dashboardService->getDailySalesChart(),
            'weeklyChart' => $this->dashboardService->getWeeklySalesChart(),
            'monthlyChart' => $this->dashboardService->getMonthlySalesChart(),
            'packageChart' => $this->dashboardService->getPackagePopularityChart(),
            'live' => $this->dashboardService->getLiveAnalytics(),
            'paymentMode' => $this->dashboardService->getPaymentModeLabel(),
        ]);
    }

    public function live(): JsonResponse
    {
        return response()->json($this->dashboardService->getLiveAnalytics());
    }
}
