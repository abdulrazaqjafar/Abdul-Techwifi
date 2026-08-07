<?php

namespace App\Http\Controllers;

use App\Exports\SalesExport;
use App\Http\Requests\SaleRequest;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Voucher;
use App\Repositories\SaleRepository;
use App\Services\ActivityLogService;
use App\Services\VoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleController extends Controller
{
    public function __construct(
        protected SaleRepository $saleRepository,
        protected ActivityLogService $activityLogService,
        protected VoucherService $voucherService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Sale::class);

        $filters = $request->only([
            'payment_method',
            'package_id',
            'customer_id',
            'search',
            'date_from',
            'date_to',
            'min_amount',
            'max_amount',
        ]);

        $sales = $this->saleRepository->paginateWithFilters(
            $filters,
            (int) $request->integer('per_page', 15)
        );

        $packages = Package::query()->orderBy('name')->get();

        return view('sales.index', compact('sales', 'packages', 'filters'));
    }

    public function store(SaleRequest $request): RedirectResponse
    {
        $this->authorize('create', Sale::class);

        $validated = $request->validated();

        $sale = DB::transaction(function () use ($validated) {
            $voucher = Voucher::query()
                ->with('package')
                ->lockForUpdate()
                ->findOrFail($validated['voucher_id']);

            if (! $voucher->isUnused()) {
                abort(422, 'The selected voucher is no longer available.');
            }

            $amount = $validated['amount'] ?? $voucher->package->price;

            $sale = Sale::query()->create([
                'voucher_id' => $voucher->id,
                'package_id' => $voucher->package_id,
                'admin_id' => Auth::id(),
                'customer_id' => $validated['customer_id'] ?? $voucher->customer_id,
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'sold_at' => $validated['sold_at'] ?? now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            Payment::query()->create([
                'sale_id' => $sale->id,
                'amount' => $amount,
                'method' => $validated['payment_method'],
                'status' => 'completed',
                'paid_at' => $validated['sold_at'] ?? now(),
            ]);

            if ($validated['customer_id'] ?? null) {
                $voucher->update(['customer_id' => $validated['customer_id']]);
            }

            return $sale->load(['voucher', 'package', 'admin', 'customer', 'payment']);
        });

        $this->activityLogService->log(
            'sale.created',
            "Recorded sale #{$sale->id} for voucher {$sale->voucher->code}."
        );

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Sale recorded successfully.');
    }

    public function show(Sale $sale): View
    {
        $this->authorize('view', $sale);

        $sale->load(['voucher', 'package', 'admin', 'customer', 'payment']);

        return view('sales.show', compact('sale'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('export', Sale::class);

        $filters = $request->only(['date_from', 'date_to', 'payment_method', 'package_id']);

        return Excel::download(
            new SalesExport($filters),
            'sales-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }
}
