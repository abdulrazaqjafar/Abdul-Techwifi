<?php

namespace App\Http\Controllers;

use App\Exports\CustomersExport;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\ActivityLogService;
use App\Services\CustomerMonitoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService,
        protected CustomerMonitoringService $customerMonitoringService
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

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
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.create');
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::query()->create($request->validated());

        $this->activityLogService->log(
            'customer.created',
            "Created customer {$customer->name}."
        );

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $customer->loadCount(['vouchers', 'sales']);
        $customer->load([
            'vouchers' => fn ($query) => $query->with('package')->latest()->limit(10),
            'sales' => fn ($query) => $query->latest('sold_at')->limit(10),
            'offers' => fn ($query) => $query->latest()->limit(5),
        ]);

        $serviceStatus = $this->customerMonitoringService->assessCustomer($customer);
        $serviceHistory = $customer->serviceSnapshots()->latest('recorded_at')->limit(10)->get();

        return view('customers.show', compact('customer', 'serviceStatus', 'serviceHistory'));
    }

    public function refreshServiceStatus(Customer $customer): RedirectResponse
    {
        $this->authorize('view', $customer);

        $this->customerMonitoringService->assessCustomer($customer);

        return back()->with('success', 'Hali ya huduma ya mteja imesasishwa.');
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.edit', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        $this->activityLogService->log(
            'customer.updated',
            "Updated customer {$customer->name}."
        );

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        if ($customer->sales()->exists()) {
            return back()->with('error', 'Cannot delete a customer with existing sales.');
        }

        $name = $customer->name;
        $customer->delete();

        $this->activityLogService->log(
            'customer.deleted',
            "Deleted customer {$name}."
        );

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('export', Customer::class);

        $filters = $request->only(['search']);

        return Excel::download(
            new CustomersExport($filters),
            'customers-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }
}
