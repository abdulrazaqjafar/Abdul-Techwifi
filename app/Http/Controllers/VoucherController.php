<?php

namespace App\Http\Controllers;

use App\Exports\VouchersExport;
use App\Http\Requests\VoucherGenerateRequest;
use App\Models\Package;
use App\Models\Voucher;
use App\Repositories\VoucherRepository;
use App\Services\ActivityLogService;
use App\Services\VoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VoucherController extends Controller
{
    public function __construct(
        protected VoucherService $voucherService,
        protected VoucherRepository $voucherRepository,
        protected ActivityLogService $activityLogService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Voucher::class);

        $filters = $request->only([
            'status',
            'package_id',
            'batch_id',
            'search',
            'date_from',
            'date_to',
        ]);

        $vouchers = $this->voucherRepository->paginateWithFilters(
            $filters,
            (int) $request->integer('per_page', 15)
        );

        $packages = Package::query()->where('is_active', true)->orderBy('name')->get();

        return view('vouchers.index', compact('vouchers', 'packages', 'filters'));
    }

    public function generate(VoucherGenerateRequest $request): RedirectResponse
    {
        $this->authorize('create', Voucher::class);

        try {
            $voucher = $this->voucherService->generateSingle(
                $request->packageId(),
                (int) Auth::id(),
                $request->customerId()
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        // The code must not travel in the query string, where it would sit in
        // the address bar, browser history and server access logs. The list is
        // sorted newest first, so the voucher is already on top; the session
        // only carries which row to highlight.
        return redirect()
            ->route('vouchers.index')
            ->with('success', "Voucher {$voucher->code} generated successfully.")
            ->with('highlight_voucher_id', $voucher->id);
    }

    public function generateBulk(VoucherGenerateRequest $request): RedirectResponse
    {
        $this->authorize('create', Voucher::class);

        try {
            $vouchers = $this->voucherService->generateBulk(
                $request->count(),
                $request->packageId(),
                (int) Auth::id(),
                $request->customerId()
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $batchId = $vouchers->first()?->batch_id;

        return redirect()
            ->route('vouchers.index', ['batch_id' => $batchId])
            ->with('success', "{$vouchers->count()} vouchers generated successfully.");
    }

    public function print(Request $request): View
    {
        $this->authorize('viewAny', Voucher::class);

        $validated = $request->validate([
            'batch_id' => ['nullable', 'string', 'max:255'],
            'voucher_ids' => ['nullable', 'array'],
            'voucher_ids.*' => ['integer', 'exists:vouchers,id'],
        ]);

        $vouchers = $this->voucherService->getPrintableVouchers(
            $validated['batch_id'] ?? null,
            $validated['voucher_ids'] ?? null
        );

        return view('vouchers.print', compact('vouchers'));
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('export', Voucher::class);

        $validated = $request->validate([
            'batch_id' => ['nullable', 'string', 'max:255'],
            'voucher_ids' => ['nullable', 'array'],
            'voucher_ids.*' => ['integer', 'exists:vouchers,id'],
            'status' => ['nullable', Rule::in([
                Voucher::STATUS_UNUSED,
                Voucher::STATUS_USED,
                Voucher::STATUS_EXPIRED,
                Voucher::STATUS_CANCELLED,
            ])],
        ]);

        $query = Voucher::query()->with('package');

        if (! empty($validated['batch_id'])) {
            $query->where('batch_id', $validated['batch_id']);
        }

        if (! empty($validated['voucher_ids'])) {
            $query->whereIn('id', $validated['voucher_ids']);
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $vouchers = $query->latest()->get();

        if ($vouchers->isEmpty()) {
            return back()->with('error', 'No vouchers found for export.');
        }

        return $this->voucherService->exportToPdf($vouchers);
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('export', Voucher::class);

        $filters = $request->only(['status', 'package_id', 'batch_id', 'date_from', 'date_to']);

        return Excel::download(
            new VouchersExport($filters),
            'vouchers-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function updateStatus(Request $request, Voucher $voucher): RedirectResponse
    {
        $this->authorize('update', $voucher);

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Voucher::STATUS_USED,
                Voucher::STATUS_EXPIRED,
                Voucher::STATUS_CANCELLED,
            ])],
        ]);

        try {
            match ($validated['status']) {
                Voucher::STATUS_USED => $this->voucherService->markAsUsed($voucher),
                Voucher::STATUS_EXPIRED => $this->voucherService->markAsExpired($voucher),
                Voucher::STATUS_CANCELLED => $this->cancelVoucher($voucher),
            };
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Voucher status updated successfully.');
    }

    public function destroy(Voucher $voucher): RedirectResponse
    {
        $this->authorize('delete', $voucher);

        if ($voucher->sale()->exists()) {
            return back()->with('error', 'Cannot delete a voucher that has an associated sale.');
        }

        $code = $voucher->code;

        // Off the router first: a code left there stays redeemable at the
        // captive portal even after it is gone from the database.
        $this->voucherService->removeFromRouter($voucher);

        $voucher->delete();

        $this->activityLogService->log(
            'voucher.deleted',
            "Deleted voucher {$code}."
        );

        return redirect()
            ->route('vouchers.index')
            ->with('success', 'Voucher deleted successfully.');
    }

    protected function cancelVoucher(Voucher $voucher): Voucher
    {
        if ($voucher->isUsed()) {
            throw new RuntimeException('Used vouchers cannot be cancelled.');
        }

        $voucher->update([
            'status' => Voucher::STATUS_CANCELLED,
            'router_synced_at' => null,
        ]);

        $this->voucherService->removeFromRouter($voucher);

        $this->activityLogService->log(
            'voucher.cancelled',
            "Voucher {$voucher->code} marked as cancelled."
        );

        return $voucher->fresh(['package', 'admin', 'customer', 'sale']);
    }
}
