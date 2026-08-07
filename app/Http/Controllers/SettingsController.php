<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfferSettingsRequest;
use App\Http\Requests\RouterSettingsRequest;
use App\Http\Requests\SettingsRequest;
use App\Models\BusinessSetting;
use App\Models\RouterSetting;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function index(): View
    {
        return view('settings.index', [
            'business' => BusinessSetting::getSettings(),
            'router' => RouterSetting::resolve(),
        ]);
    }

    public function updateBusiness(SettingsRequest $request): RedirectResponse
    {
        $settings = BusinessSetting::getSettings();
        $data = $request->validated();

        if ($request->hasFile('business_logo')) {
            if ($settings->business_logo) {
                Storage::disk('public')->delete($settings->business_logo);
            }

            $data['business_logo'] = $request->file('business_logo')->store('logos', 'public');
        } else {
            unset($data['business_logo']);
        }

        $settings->update($data);

        $this->activityLogService->log(
            'settings.business_updated',
            'Updated business settings.'
        );

        return back()->with('success', 'Business settings updated successfully.');
    }

    public function updateOffers(OfferSettingsRequest $request): RedirectResponse
    {
        $settings = BusinessSetting::getSettings();

        $settings->update([
            'monthly_offer_enabled' => $request->boolean('monthly_offer_enabled'),
            'monthly_offer_discount_percent' => $request->integer('monthly_offer_discount_percent'),
            'monthly_offer_message' => $request->input('monthly_offer_message'),
            'sms_provider' => $request->string('sms_provider')->toString(),
            'sms_sender_id' => $request->input('sms_sender_id'),
        ]);

        $this->activityLogService->log(
            'settings.offers_updated',
            'Updated monthly offer and SMS settings.'
        );

        return back()->with('success', 'Offer na SMS settings zimesasishwa.');
    }

    public function updateRouter(RouterSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['router_password'])) {
            unset($data['router_password']);
        }

        $data['is_connected'] = false;

        $settings = RouterSetting::query()->first();

        if ($settings) {
            $settings->update($data);
        } else {
            RouterSetting::query()->create(array_merge($data, [
                'is_connected' => false,
            ]));
        }

        $this->activityLogService->log(
            'settings.router_updated',
            'Updated router settings.'
        );

        return back()->with('success', 'Router settings zimehifadhiwa.');
    }
}
