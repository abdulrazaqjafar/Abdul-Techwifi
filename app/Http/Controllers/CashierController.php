<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashierSellRequest;
use App\Models\BusinessSetting;
use App\Models\Package;
use App\Models\Sale;
use App\Services\CashierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class CashierController extends Controller
{
    public function __construct(
        protected CashierService $cashierService
    ) {}

    public function index(): View
    {
        $adminId = Auth::id();

        $todaySales = Sale::query()
            ->where('admin_id', $adminId)
            ->whereDate('sold_at', today())
            ->count();

        $todayRevenue = Sale::query()
            ->where('admin_id', $adminId)
            ->whereDate('sold_at', today())
            ->sum('amount');

        $recentSales = Sale::query()
            ->with(['voucher', 'package'])
            ->where('admin_id', $adminId)
            ->latest('sold_at')
            ->limit(5)
            ->get();

        return view('cashier.index', [
            'settings' => BusinessSetting::getSettings(),
            'todaySales' => $todaySales,
            'todayRevenue' => $todayRevenue,
            'recentSales' => $recentSales,
        ]);
    }

    public function create(): View
    {
        return view('cashier.sell', [
            'settings' => BusinessSetting::getSettings(),
            'packages' => Package::query()->where('is_active', true)->orderBy('price')->get(),
        ]);
    }

    public function store(CashierSellRequest $request): RedirectResponse
    {
        $this->authorize('create', Sale::class);

        try {
            $sale = $this->cashierService->sellForCash([
                'package_id' => $request->integer('package_id'),
                'admin_id' => (int) Auth::id(),
                'amount' => $request->input('amount'),
                'customer_name' => $request->input('customer_name'),
                'phone' => $request->input('phone'),
                'notes' => $request->input('notes'),
            ]);
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('cashier.sales.show', $sale)
            ->with('success', 'Voucher imetengenezwa na mauzo yamerekodiwa.');
    }

    public function sales(): View
    {
        $this->authorize('viewAny', Sale::class);

        $sales = Sale::query()
            ->with(['voucher', 'package', 'customer'])
            ->where('admin_id', Auth::id())
            ->latest('sold_at')
            ->paginate(15);

        return view('cashier.sales', [
            'settings' => BusinessSetting::getSettings(),
            'sales' => $sales,
        ]);
    }

    public function showSale(Sale $sale): View
    {
        $this->authorize('view', $sale);

        $sale->load(['voucher.package', 'package', 'customer', 'payment', 'admin']);

        return view('cashier.show', compact('sale'));
    }

    public function printReceipt(Sale $sale): View
    {
        $this->authorize('view', $sale);

        $sale->load(['voucher.package', 'package', 'customer', 'admin']);

        return view('cashier.receipt', [
            'sale' => $sale,
            'settings' => BusinessSetting::getSettings(),
        ]);
    }
}
