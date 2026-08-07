<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffRequest;
use App\Models\Admin;
use App\Models\Sale;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Admin::class);

        $search = $request->string('search')->toString();

        $staffMembers = Admin::query()
            ->where('role', 'cashier')
            ->withCount('sales')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('staff.index', compact('staffMembers', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', Admin::class);

        return view('staff.create');
    }

    public function store(StaffRequest $request): RedirectResponse
    {
        $this->authorize('create', Admin::class);

        $staff = Admin::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'role' => 'cashier',
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->activityLogService->log(
            'staff.created',
            "Registered cashier {$staff->name} ({$staff->email})."
        );

        return redirect()
            ->route('staff.index')
            ->with('success', 'Mfanyakazi amesajiliwa. Anaweza kuingia na kuuza voucher.');
    }

    public function show(Admin $staff): View
    {
        $this->authorize('view', $staff);

        $staff->loadCount('sales');

        $todaySales = Sale::query()
            ->where('admin_id', $staff->id)
            ->whereDate('sold_at', today())
            ->count();

        $todayRevenue = Sale::query()
            ->where('admin_id', $staff->id)
            ->whereDate('sold_at', today())
            ->sum('amount');

        $recentSales = Sale::query()
            ->with(['voucher', 'package', 'customer'])
            ->where('admin_id', $staff->id)
            ->latest('sold_at')
            ->limit(10)
            ->get();

        return view('staff.show', compact('staff', 'todaySales', 'todayRevenue', 'recentSales'));
    }

    public function edit(Admin $staff): View
    {
        $this->authorize('update', $staff);

        return view('staff.edit', compact('staff'));
    }

    public function update(StaffRequest $request, Admin $staff): RedirectResponse
    {
        $this->authorize('update', $staff);

        $data = [
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->string('password')->toString();
        }

        $staff->update($data);

        $this->activityLogService->log(
            'staff.updated',
            "Updated cashier {$staff->name}."
        );

        return redirect()
            ->route('staff.index')
            ->with('success', 'Taarifa za mfanyakazi zimesasishwa.');
    }

    public function destroy(Admin $staff): RedirectResponse
    {
        $this->authorize('delete', $staff);

        if ($staff->sales()->exists()) {
            return back()->with('error', 'Huwezi kufuta mfanyakazi aliye na mauzo. Zima akaunti badala yake.');
        }

        $name = $staff->name;
        $staff->delete();

        $this->activityLogService->log(
            'staff.deleted',
            "Deleted cashier {$name}."
        );

        return redirect()
            ->route('staff.index')
            ->with('success', 'Mfanyakazi amefutwa.');
    }
}
