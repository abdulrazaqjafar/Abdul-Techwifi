<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\HotspotTransaction;
use App\Models\Package;
use App\Models\Sale;
use App\Models\Voucher;
use App\Services\MikrotikRouterService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class DashboardService
{
    public function __construct(
        protected MikrotikRouterService $routerService,
        protected CustomerMonitoringService $monitoringService
    ) {}

    public function getStats(): array
    {
        $today = now()->startOfDay();

        return [
            'total_vouchers' => Voucher::query()->count(),
            'unused_vouchers' => Voucher::query()->where('status', Voucher::STATUS_UNUSED)->count(),
            'used_vouchers' => Voucher::query()->where('status', Voucher::STATUS_USED)->count(),
            'expired_vouchers' => Voucher::query()->where('status', Voucher::STATUS_EXPIRED)->count(),
            'total_sales' => Sale::query()->count(),
            'total_revenue' => (float) Sale::query()->sum('amount'),
            'today_sales' => Sale::query()->where('sold_at', '>=', $today)->count(),
            'today_revenue' => (float) Sale::query()->where('sold_at', '>=', $today)->sum('amount'),
            'total_customers' => Customer::query()->count(),
            'active_packages' => Package::query()->where('is_active', true)->count(),
        ];
    }

    public function getDailySalesChart(int $days = 7): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();

        $sales = Sale::query()
            ->selectRaw('DATE(sold_at) as date, COUNT(*) as count, SUM(amount) as revenue')
            ->where('sold_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(sold_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $counts = [];
        $revenues = [];

        for ($index = 0; $index < $days; $index++) {
            $date = $startDate->copy()->addDays($index)->toDateString();
            $labels[] = Carbon::parse($date)->format('M d');
            $counts[] = (int) ($sales[$date]->count ?? 0);
            $revenues[] = (float) ($sales[$date]->revenue ?? 0);
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'revenues' => $revenues,
        ];
    }

    public function getWeeklySalesChart(int $weeks = 8): array
    {
        $startDate = now()->subWeeks($weeks - 1)->startOfWeek();

        $sales = Sale::query()
            ->selectRaw('YEARWEEK(sold_at, 1) as week, COUNT(*) as count, SUM(amount) as revenue')
            ->where('sold_at', '>=', $startDate)
            ->groupBy(DB::raw('YEARWEEK(sold_at, 1)'))
            ->orderBy('week')
            ->get()
            ->keyBy('week');

        $labels = [];
        $counts = [];
        $revenues = [];

        for ($index = 0; $index < $weeks; $index++) {
            $weekStart = $startDate->copy()->addWeeks($index);
            $weekKey = (int) $weekStart->format('oW');
            $labels[] = 'Week '.$weekStart->format('W');
            $counts[] = (int) ($sales[$weekKey]->count ?? 0);
            $revenues[] = (float) ($sales[$weekKey]->revenue ?? 0);
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'revenues' => $revenues,
        ];
    }

    public function getMonthlySalesChart(int $months = 12): array
    {
        $startDate = now()->subMonths($months - 1)->startOfMonth();

        $sales = Sale::query()
            ->selectRaw('DATE_FORMAT(sold_at, "%Y-%m") as month, COUNT(*) as count, SUM(amount) as revenue')
            ->where('sold_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE_FORMAT(sold_at, "%Y-%m")'))
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $labels = [];
        $counts = [];
        $revenues = [];

        for ($index = 0; $index < $months; $index++) {
            $month = $startDate->copy()->addMonths($index);
            $monthKey = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $counts[] = (int) ($sales[$monthKey]->count ?? 0);
            $revenues[] = (float) ($sales[$monthKey]->revenue ?? 0);
        }

        return [
            'labels' => $labels,
            'counts' => $counts,
            'revenues' => $revenues,
        ];
    }

    public function getPackagePopularityChart(int $limit = 10): array
    {
        $packages = Sale::query()
            ->select('package_id', DB::raw('COUNT(*) as sales_count'), DB::raw('SUM(amount) as total_revenue'))
            ->with('package:id,name')
            ->groupBy('package_id')
            ->orderByDesc('sales_count')
            ->limit($limit)
            ->get();

        return [
            'labels' => $packages->map(fn (Sale $sale): string => $sale->package?->name ?? 'Unknown')->all(),
            'counts' => $packages->pluck('sales_count')->map(fn ($count): int => (int) $count)->all(),
            'revenues' => $packages->pluck('total_revenue')->map(fn ($revenue): float => (float) $revenue)->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getLiveAnalytics(): array
    {
        $today = now()->startOfDay();
        $lastHour = now()->subHour();
        $currency = \App\Models\BusinessSetting::getSettings()->currency ?? 'TSh';

        $recentSales = Sale::query()
            ->with(['package:id,name', 'customer:id,name', 'voucher:id,code'])
            ->latest('sold_at')
            ->limit(8)
            ->get()
            ->map(fn (Sale $sale): array => [
                'id' => $sale->id,
                'amount' => (float) $sale->amount,
                'amount_formatted' => number_format((float) $sale->amount, 0).' '.$currency,
                'package' => $sale->package?->name ?? 'N/A',
                'customer' => $sale->customer?->name ?? 'Walk-in',
                'method' => ucfirst(str_replace('_', ' ', $sale->payment_method)),
                'voucher' => $sale->voucher?->code,
                'sold_at' => $sale->sold_at?->format('H:i'),
                'sold_at_full' => $sale->sold_at?->format('M d, H:i'),
            ])
            ->all();

        try {
            if ($this->routerService->isConfigured()) {
                $this->routerService->connect();
                $this->routerService->syncActiveUsers();
            }
        } catch (Throwable) {
            // Router sync is best-effort for live dashboard.
        } finally {
            $this->routerService->disconnect();
        }

        $networkQuality = $this->monitoringService->assessActiveSessions();

        return [
            'updated_at' => now()->format('H:i:s'),
            'payment_mode' => $this->getPaymentModeLabel(),
            'stats' => [
                'today_revenue' => (float) Sale::query()->where('sold_at', '>=', $today)->sum('amount'),
                'today_sales' => Sale::query()->where('sold_at', '>=', $today)->count(),
                'last_hour_revenue' => (float) Sale::query()->where('sold_at', '>=', $lastHour)->sum('amount'),
                'last_hour_sales' => Sale::query()->where('sold_at', '>=', $lastHour)->count(),
                'pending_payments' => HotspotTransaction::query()
                    ->whereIn('status', [HotspotTransaction::STATUS_PENDING, HotspotTransaction::STATUS_PROCESSING])
                    ->count(),
                'online_users' => count($networkQuality['users']),
                'quality_good' => $networkQuality['summary']['good'] ?? 0,
                'quality_fair' => $networkQuality['summary']['fair'] ?? 0,
                'quality_poor' => $networkQuality['summary']['poor'] ?? 0,
                'total_revenue' => (float) Sale::query()->sum('amount'),
                'total_sales' => Sale::query()->count(),
                'active_vouchers' => Voucher::query()->where('status', Voucher::STATUS_USED)
                    ->where(function ($query): void {
                        $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })->count(),
            ],
            'recent_sales' => $recentSales,
            'hourly_today' => $this->getTodayHourlyChart(),
            'payment_methods' => $this->getPaymentMethodBreakdown($today),
            'network_quality' => $networkQuality,
        ];
    }

    public function getPaymentModeLabel(): string
    {
        $provider = config('hotspot.payment.default_provider');
        $sandbox = (bool) config('hotspot.providers.clickpesa.sandbox', true);

        if ($provider === 'clickpesa' && ! $sandbox) {
            return 'ClickPesa Live (Malipo Halisi)';
        }

        if ($provider === 'clickpesa' && $sandbox) {
            return 'ClickPesa Sandbox (Majaribio)';
        }

        if ($provider === 'simulated') {
            return 'Simulated (Majaribio)';
        }

        return ucfirst($provider);
    }

    protected function getOnlineUserCount(): int
    {
        if (! $this->routerService->isConfigured()) {
            return 0;
        }

        try {
            $this->routerService->connect();
            $count = count($this->routerService->getOnlineUsers());
            $this->routerService->disconnect();

            return $count;
        } catch (Throwable) {
            return 0;
        }
    }

    public function getTodayHourlyChart(): array
    {
        $start = now()->startOfDay();

        $sales = Sale::query()
            ->selectRaw('HOUR(sold_at) as hour, COUNT(*) as count, SUM(amount) as revenue')
            ->where('sold_at', '>=', $start)
            ->groupBy(DB::raw('HOUR(sold_at)'))
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $labels = [];
        $counts = [];
        $revenues = [];
        $currentHour = (int) now()->format('G');

        for ($hour = 0; $hour <= $currentHour; $hour++) {
            $labels[] = sprintf('%02d:00', $hour);
            $counts[] = (int) ($sales[$hour]->count ?? 0);
            $revenues[] = (float) ($sales[$hour]->revenue ?? 0);
        }

        return compact('labels', 'counts', 'revenues');
    }

    /**
     * @return array{labels: list<string>, amounts: list<float>}
     */
    public function getPaymentMethodBreakdown(?Carbon $since = null): array
    {
        $query = Sale::query()
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method');

        if ($since) {
            $query->where('sold_at', '>=', $since);
        }

        $rows = $query->get();

        return [
            'labels' => $rows->map(fn ($row): string => ucfirst(str_replace('_', ' ', $row->payment_method)))->all(),
            'amounts' => $rows->pluck('total')->map(fn ($amount): float => (float) $amount)->all(),
        ];
    }
}
