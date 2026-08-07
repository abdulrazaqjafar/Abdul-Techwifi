<?php

namespace App\Http\Controllers;

use App\Http\Requests\HotspotLoginRequest;
use App\Http\Requests\HotspotPurchaseRequest;
use App\Models\BusinessSetting;
use App\Models\Package;
use App\Services\HotspotAuthService;
use App\Services\HotspotPurchaseService;
use App\Services\Payment\MobileMoneyGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;

class HotspotController extends Controller
{
    public function __construct(
        protected HotspotAuthService $authService,
        protected HotspotPurchaseService $purchaseService,
        protected MobileMoneyGatewayManager $gatewayManager
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $this->storeMikrotikSession($request);

        $mikrotik = $this->mikrotikParams($request);
        $linkLogin = $mikrotik['link_login'] ?? session('hotspot.link_login');
        $mac = $mikrotik['mac'] ?? session('hotspot.mac');

        if ($mac && $linkLogin) {
            $voucher = $this->authService->findActiveVoucherForDevice($mac);

            if ($voucher) {
                try {
                    $result = $this->authService->reconnectDevice(
                        $voucher,
                        $linkLogin,
                        $this->deviceContext($request)
                    );

                    if ($result['redirect_url']) {
                        return redirect()->away($result['redirect_url']);
                    }
                } catch (RuntimeException $exception) {
                    Log::info('Hotspot auto-reconnect skipped', [
                        'mac' => $mac,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
        }

        $providers = $this->gatewayManager->availableProviders();
        $clickPesaClient = app(\App\Services\Payment\ClickPesa\ClickPesaClient::class);
        $isSandbox = (bool) config('hotspot.providers.clickpesa.sandbox', false)
            && config('hotspot.payment.default_provider') === 'clickpesa';
        $isLivePayments = config('hotspot.payment.default_provider') === 'clickpesa'
            && ! $isSandbox
            && $clickPesaClient->isConfigured();
        $usingSimulatedFallback = false;
        $paymentMinAmount = config('hotspot.payment.default_provider') === 'clickpesa'
            ? (int) config('hotspot.providers.clickpesa.min_amount', 908)
            : null;

        return view('hotspot.index', [
            'settings' => BusinessSetting::getSettings(),
            'packages' => Package::query()->where('is_active', true)->orderBy('price')->get(),
            'providers' => $providers,
            'packageIcons' => config('hotspot.package_icons', []),
            'mikrotik' => $mikrotik,
            'isPaymentSandbox' => $isSandbox,
            'isLivePayments' => $isLivePayments,
            'usingSimulatedFallback' => $usingSimulatedFallback,
            'paymentMinAmount' => $paymentMinAmount,
            'hotspotConfig' => [
                'routes' => [
                    'login' => route('hotspot.login'),
                    'purchase' => route('hotspot.purchase'),
                    'connect' => route('hotspot.connect'),
                    'autoConnect' => route('hotspot.auto-connect'),
                    'paymentStatus' => url('/hotspot/payment'),
                ],
                'mikrotik' => $mikrotik,
                'defaultProvider' => array_key_first($providers) ?: 'simulated',
                'i18n' => [
                    'waiting_payment' => __('hotspot.waiting_payment'),
                    'payment_failed' => __('hotspot.payment.failed'),
                    'network_error' => __('hotspot.errors.network'),
                    'light_mode' => __('hotspot.light_mode'),
                    'dark_mode' => __('hotspot.dark_mode'),
                'copied' => __('hotspot.copied'),
                'auto_reconnect' => __('hotspot.auto_reconnect'),
                'mac_required' => __('hotspot.errors.mac_required'),
            ],
                'businessName' => BusinessSetting::getSettings()->business_name,
            ],
        ]);
    }

    public function login(HotspotLoginRequest $request): JsonResponse
    {
        return $this->performVoucherConnect(
            $request,
            $request->input('voucher_code'),
            __('hotspot.login.success')
        );
    }

    public function purchase(HotspotPurchaseRequest $request): JsonResponse
    {
        try {
            $package = Package::query()->findOrFail($request->integer('package_id'));

            $transaction = $this->purchaseService->initiatePurchase(
                $package,
                $request->input('phone'),
                $request->input('provider'),
                [
                    'mac' => $request->input('mac') ?? session('hotspot.mac'),
                    'ip' => $request->input('ip') ?? session('hotspot.ip'),
                    'link_login' => $request->input('link_login') ?? session('hotspot.link_login'),
                    'link_orig' => $request->input('link_orig') ?? session('hotspot.link_orig'),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => __('hotspot.payment.initiated'),
                'reference' => $transaction->reference,
                'provider' => $transaction->provider,
                'amount' => number_format((float) $transaction->amount, 0),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (ConnectionException $exception) {
            Log::error('Hotspot purchase connection failed', ['message' => $exception->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Hakuna muunganisho thabiti wa intaneti kwa ClickPesa. Hakikisha PC ina internet, kisha jaribu tena.',
            ], 422);
        } catch (\Throwable $exception) {
            Log::error('Hotspot purchase failed', ['message' => $exception->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Malipo yameshindikana. Jaribu tena au tumia voucher.',
            ], 422);
        }
    }

    public function paymentStatus(string $reference): JsonResponse
    {
        try {
            $result = $this->purchaseService->checkPaymentStatus($reference);

            return response()->json($result);
        } catch (RuntimeException $exception) {
            return response()->json([
                'status' => 'failed',
                'message' => $this->purchaseService->explainGatewayError($exception->getMessage()),
            ], 422);
        }
    }

    public function autoConnect(Request $request): JsonResponse
    {
        $device = $this->deviceContext($request);
        $linkLogin = $request->input('link_login')
            ?? session('hotspot.link_login')
            ?? $request->query('link-login');

        $voucher = $this->authService->findActiveVoucherForDevice($device['mac']);

        if (! $voucher) {
            return response()->json([
                'success' => false,
                'action' => 'purchase',
            ]);
        }

        try {
            $result = $this->authService->reconnectDevice($voucher, $linkLogin, $device);

            if (blank($result['redirect_url']) && ! $result['connected']) {
                return response()->json([
                    'success' => false,
                    'action' => 'purchase',
                ]);
            }

            return response()->json([
                'success' => true,
                'action' => 'connect',
                'message' => __('hotspot.auto_reconnect'),
                'redirect_url' => $result['redirect_url'],
                'connected' => $result['connected'],
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'action' => 'purchase',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function connect(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'voucher_code' => ['required', 'string', 'max:50'],
            'link_login' => ['nullable', 'url', 'max:2048'],
            'mac' => ['nullable', 'string', 'max:64'],
            'ip' => ['nullable', 'ip'],
            'user_agent' => ['nullable', 'string', 'max:512'],
            'screen_width' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        return $this->performVoucherConnect(
            $request,
            $validated['voucher_code'],
            __('hotspot.connect.success')
        );
    }

    public function setLocale(Request $request): RedirectResponse
    {
        $locale = $request->validate([
            'locale' => ['required', 'in:en,sw'],
        ])['locale'];

        session(['locale' => $locale]);

        return back()->withCookie(cookie()->forever('locale', $locale));
    }

    /**
     * @return array{mac: ?string, ip: ?string, user_agent: ?string, screen_width: ?int}
     */
    protected function deviceContext(Request $request): array
    {
        return [
            'mac' => $this->resolveDeviceParam($request, 'mac'),
            'ip' => $this->resolveDeviceParam($request, 'ip') ?? $request->ip(),
            'user_agent' => $request->input('user_agent') ?? $request->userAgent(),
            'screen_width' => $request->integer('screen_width') ?: null,
        ];
    }

    protected function performVoucherConnect(Request $request, string $voucherCode, string $successMessage): JsonResponse
    {
        try {
            $result = $this->authService->connectVoucherWithDevice(
                $voucherCode,
                $request->input('link_login') ?? session('hotspot.link_login'),
                $this->deviceContext($request)
            );

            $message = $successMessage;

            if (blank($result['redirect_url']) && ! $result['connected']) {
                $message = __('hotspot.connect.manual_required', ['code' => $result['voucher']->code]);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'voucher_code' => $result['voucher']->code,
                'redirect_url' => $result['redirect_url'],
                'connected' => $result['connected'],
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    protected function resolveDeviceParam(Request $request, string $key): ?string
    {
        $value = $request->input($key) ?? session("hotspot.{$key}");

        if ($value) {
            return $value;
        }

        if ($key === 'mac') {
            return $request->query('mac') ?? $request->query('mac-address');
        }

        if ($key === 'ip') {
            return $request->query('ip');
        }

        return null;
    }

    protected function storeMikrotikSession(Request $request): void
    {
        $params = $this->mikrotikParams($request);

        if (collect($params)->filter()->isNotEmpty()) {
            session([
                'hotspot.mac' => $params['mac'],
                'hotspot.ip' => $params['ip'],
                'hotspot.link_login' => $params['link_login'],
                'hotspot.link_orig' => $params['link_orig'],
            ]);
        }
    }

    /**
     * @return array{mac: ?string, ip: ?string, link_login: ?string, link_orig: ?string}
     */
    protected function mikrotikParams(Request $request): array
    {
        return [
            'mac' => $request->input('mac')
                ?? $request->query('mac')
                ?? $request->query('mac-address'),
            'ip' => $request->input('ip') ?? $request->query('ip'),
            'link_login' => $request->input('link_login')
                ?? $request->query('link-login')
                ?? $request->query('link_login'),
            'link_orig' => $request->input('link_orig')
                ?? $request->query('link-orig')
                ?? $request->query('link_orig'),
        ];
    }
}
