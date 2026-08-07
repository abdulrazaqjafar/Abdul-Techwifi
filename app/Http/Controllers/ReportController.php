<?php

namespace App\Http\Controllers;

use App\Exports\CustomersExport;
use App\Exports\SalesExport;
use App\Exports\VouchersExport;
use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Voucher;
use App\Repositories\SaleRepository;
use App\Services\DashboardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        protected SaleRepository $saleRepository,
        protected DashboardService $dashboardService
    ) {}

    public function daily(Request $request): View
    {
        $validated = $this->validateDateRange($request, defaultDays: 1);

        $report = $this->saleRepository->getSalesReport(
            $validated['date_from'],
            $validated['date_to']
        );

        return view('reports.daily', [
            'report' => $report,
            'filters' => $validated,
            'chart' => $this->dashboardService->getDailySalesChart(7),
        ]);
    }

    public function weekly(Request $request): View
    {
        $validated = $this->validateDateRange($request, defaultDays: 7);

        $report = $this->saleRepository->getSalesReport(
            $validated['date_from'],
            $validated['date_to']
        );

        return view('reports.weekly', [
            'report' => $report,
            'filters' => $validated,
            'chart' => $this->dashboardService->getWeeklySalesChart(),
        ]);
    }

    public function monthly(Request $request): View
    {
        $validated = $this->validateDateRange($request, defaultDays: 30);

        $report = $this->saleRepository->getSalesReport(
            $validated['date_from'],
            $validated['date_to']
        );

        return view('reports.monthly', [
            'report' => $report,
            'filters' => $validated,
            'chart' => $this->dashboardService->getMonthlySalesChart(),
        ]);
    }

    public function vouchers(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string'],
            'package_id' => ['nullable', 'integer', 'exists:packages,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = Voucher::query()->with(['package', 'admin', 'customer']);

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['package_id'])) {
            $query->where('package_id', $validated['package_id']);
        }

        if (! empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        $summary = [
            'total' => (clone $query)->count(),
            'unused' => (clone $query)->where('status', Voucher::STATUS_UNUSED)->count(),
            'used' => (clone $query)->where('status', Voucher::STATUS_USED)->count(),
            'expired' => (clone $query)->where('status', Voucher::STATUS_EXPIRED)->count(),
            'cancelled' => (clone $query)->where('status', Voucher::STATUS_CANCELLED)->count(),
        ];

        $vouchers = $query->latest()->paginate(25)->withQueryString();

        return view('reports.vouchers', compact('vouchers', 'summary', 'validated'));
    }

    public function sales(Request $request): View
    {
        $validated = $this->validateDateRange($request, defaultDays: 30);

        $report = $this->saleRepository->getSalesReport(
            $validated['date_from'],
            $validated['date_to']
        );

        $sales = Sale::query()
            ->with(['voucher', 'package', 'customer', 'admin'])
            ->when($validated['date_from'], fn ($query) => $query->whereDate('sold_at', '>=', $validated['date_from']))
            ->when($validated['date_to'], fn ($query) => $query->whereDate('sold_at', '<=', $validated['date_to']))
            ->latest('sold_at')
            ->paginate(25)
            ->withQueryString();

        return view('reports.sales', compact('report', 'sales', 'validated'));
    }

    public function customers(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $customers = Customer::query()
            ->withCount(['vouchers', 'sales'])
            ->when(! empty($validated['search']), function ($query) use ($validated): void {
                $search = $validated['search'];
                $query->where(function ($builder) use ($search): void {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $summary = [
            'total_customers' => Customer::query()->count(),
            'with_sales' => Customer::query()->whereHas('sales')->count(),
            'with_vouchers' => Customer::query()->whereHas('vouchers')->count(),
        ];

        return view('reports.customers', compact('customers', 'summary', 'validated'));
    }

    public function exportPdf(Request $request, string $type)
    {
        $settings = BusinessSetting::getSettings();

        return match ($type) {
            'daily', 'weekly', 'monthly', 'sales' => $this->exportSalesPdf($request, $type, $settings),
            'vouchers' => $this->exportVouchersPdf($request, $settings),
            'customers' => $this->exportCustomersPdf($request, $settings),
            default => abort(404),
        };
    }

    public function exportExcel(Request $request, string $type): BinaryFileResponse
    {
        return match ($type) {
            'daily', 'weekly', 'monthly', 'sales' => Excel::download(
                new SalesExport($this->reportFilters($request)),
                "{$type}-sales-".now()->format('Y-m-d-His').'.xlsx'
            ),
            'vouchers' => Excel::download(
                new VouchersExport($request->only(['status', 'package_id', 'date_from', 'date_to'])),
                'voucher-report-'.now()->format('Y-m-d-His').'.xlsx'
            ),
            'customers' => Excel::download(
                new CustomersExport($request->only(['search'])),
                'customer-report-'.now()->format('Y-m-d-His').'.xlsx'
            ),
            default => abort(404),
        };
    }

    /**
     * @return array{date_from: string|null, date_to: string|null}
     */
    protected function validateDateRange(Request $request, int $defaultDays): array
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $validated['date_from'] ??= now()->subDays($defaultDays - 1)->toDateString();
        $validated['date_to'] ??= now()->toDateString();

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    protected function reportFilters(Request $request): array
    {
        return $request->only(['date_from', 'date_to', 'payment_method', 'package_id']);
    }

    protected function exportSalesPdf(Request $request, string $type, BusinessSetting $settings)
    {
        $filters = $this->reportFilters($request);
        $filters['date_from'] ??= now()->subDays(match ($type) {
            'daily' => 0,
            'weekly' => 6,
            default => 29,
        })->toDateString();
        $filters['date_to'] ??= now()->toDateString();

        $report = $this->saleRepository->getSalesReport(
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null
        );

        return Pdf::loadView('pdf.report', [
            'report' => $report,
            'filters' => $filters,
            'settings' => $settings,
            'title' => ucfirst($type).' Sales Report',
        ])->download("{$type}-sales-report-".now()->format('Y-m-d-His').'.pdf');
    }

    protected function exportVouchersPdf(Request $request, BusinessSetting $settings)
    {
        $filters = $request->only(['status', 'package_id', 'date_from', 'date_to']);

        $query = Voucher::query()->with(['package', 'admin', 'customer']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['package_id'])) {
            $query->where('package_id', $filters['package_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $vouchers = $query->latest()->get();

        return Pdf::loadView('pdf.vouchers', [
            'vouchers' => $vouchers,
            'settings' => $settings,
        ])->download('voucher-report-'.now()->format('Y-m-d-His').'.pdf');
    }

    protected function exportCustomersPdf(Request $request, BusinessSetting $settings)
    {
        $search = $request->string('search')->toString();

        $customers = Customer::query()
            ->withCount(['vouchers', 'sales'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        return Pdf::loadView('pdf.customers', [
            'customers' => $customers,
            'settings' => $settings,
        ])->download('customer-report-'.now()->format('Y-m-d-His').'.pdf');
    }
}
