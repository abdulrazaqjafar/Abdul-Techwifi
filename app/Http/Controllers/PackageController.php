<?php

namespace App\Http\Controllers;

use App\Http\Requests\PackageRequest;
use App\Models\Package;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Package::class);

        $packages = Package::query()
            ->withCount('vouchers', 'sales')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('packages.index', compact('packages'));
    }

    public function create(): View
    {
        $this->authorize('create', Package::class);

        return view('packages.create');
    }

    public function store(PackageRequest $request): RedirectResponse
    {
        $this->authorize('create', Package::class);

        $package = Package::query()->create($request->validated());

        $this->activityLogService->log(
            'package.created',
            "Created package {$package->name}."
        );

        return redirect()
            ->route('packages.index')
            ->with('success', 'Package created successfully.');
    }

    public function show(Package $package): View
    {
        $this->authorize('view', $package);

        $package->loadCount('vouchers', 'sales');

        return view('packages.show', compact('package'));
    }

    public function edit(Package $package): View
    {
        $this->authorize('update', $package);

        return view('packages.edit', compact('package'));
    }

    public function update(PackageRequest $request, Package $package): RedirectResponse
    {
        $this->authorize('update', $package);

        $package->update($request->validated());

        $this->activityLogService->log(
            'package.updated',
            "Updated package {$package->name}."
        );

        return redirect()
            ->route('packages.index')
            ->with('success', 'Package updated successfully.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        $this->authorize('delete', $package);

        if ($package->vouchers()->exists() || $package->sales()->exists()) {
            return back()->with('error', 'Cannot delete a package that has vouchers or sales.');
        }

        $name = $package->name;
        $package->delete();

        $this->activityLogService->log(
            'package.deleted',
            "Deleted package {$name}."
        );

        return redirect()
            ->route('packages.index')
            ->with('success', 'Package deleted successfully.');
    }
}
