<?php

return [

    'payment' => [
        'default_provider' => env('HOTSPOT_PAYMENT_PROVIDER', 'simulated'),
        'timeout_seconds' => (int) env('HOTSPOT_PAYMENT_TIMEOUT', 120),
        'simulated_delay_seconds' => (int) env('HOTSPOT_SIMULATED_PAYMENT_DELAY', 8),
    ],

    'providers' => [
        'simulated' => [
            'label' => 'Simulated Gateway',
            'driver' => 'simulated',
        ],
        'mpesa' => [
            'label' => 'M-Pesa',
            'driver' => 'mpesa',
            'consumer_key' => env('MPESA_CONSUMER_KEY'),
            'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
            'passkey' => env('MPESA_PASSKEY'),
            'shortcode' => env('MPESA_SHORTCODE'),
        ],
        'airtel' => [
            'label' => 'Airtel Money',
            'driver' => 'airtel',
            'client_id' => env('AIRTEL_CLIENT_ID'),
            'client_secret' => env('AIRTEL_CLIENT_SECRET'),
        ],
        'halopesa' => [
            'label' => 'HaloPesa',
            'driver' => 'halopesa',
            'api_key' => env('HALOPESA_API_KEY'),
        ],
        'mixx' => [
            'label' => 'Mixx by Yas',
            'driver' => 'mixx',
            'api_key' => env('MIXX_API_KEY'),
        ],
        'clickpesa' => [
            'label' => env('CLICKPESA_SANDBOX', true)
                ? 'Mobile Money — Majaribio (Sandbox)'
                : 'Mobile Money (M-Pesa, Airtel, Tigo, HaloPesa)',
            'driver' => 'clickpesa',
            'sandbox' => env('CLICKPESA_SANDBOX', true),
            'client_id' => env('CLICKPESA_CLIENT_ID'),
            'api_key' => env('CLICKPESA_API_KEY'),
            'checksum_key' => env('CLICKPESA_CHECKSUM_KEY'),
            'base_url' => env('CLICKPESA_BASE_URL'),
            'checksum_enabled' => env('CLICKPESA_CHECKSUM_ENABLED', true),
            'min_amount' => (int) env('CLICKPESA_MIN_AMOUNT', 908),
            'max_amount' => (int) env('CLICKPESA_MAX_AMOUNT', 3000000),
        ],
    ],

    'package_icons' => [
        'Serengeti Daily' => 'sun',
        'Ruaha Weekly' => 'calendar',
        'Ngorongoro Fortnight' => 'calendar',
        'Kilimanjaro Unlimited' => 'unlimited',
    ],

    'package_images' => [
        'Serengeti Daily' => 'images/packages/serengeti-daily.jpg',       // Simba kwenye Serengeti
        'Ruaha Weekly' => 'images/packages/ruaha-weekly.jpg',             // Tembo — fahari ya Ruaha
        'Ngorongoro Fortnight' => 'images/packages/ngorongoro-fortnight.jpg', // Crater ya Ngorongoro
        'Kilimanjaro Unlimited' => 'images/packages/kilimanjaro-unlimited.jpg', // Mlima Kilimanjaro
    ],

    'package_image_focus' => [
        'Serengeti Daily' => 'center',
        'Ruaha Weekly' => 'center',
        'Ngorongoro Fortnight' => 'center top',
        'Kilimanjaro Unlimited' => 'center top',
    ],

    'package_badges' => [
        'Serengeti Daily' => 'Most Popular',
        'Ruaha Weekly' => 'Best Value',
        'Ngorongoro Fortnight' => 'Smart Choice',
        'Kilimanjaro Unlimited' => 'Premium',
    ],

    'package_tiers' => [
        'Serengeti Daily' => '1 Day',
        'Ruaha Weekly' => '7 Days',
        'Ngorongoro Fortnight' => '2 Weeks',
        'Kilimanjaro Unlimited' => '1 Month',
    ],

    'featured_package' => 'Serengeti Daily',

    'support' => [
        'email' => env('HOTSPOT_SUPPORT_EMAIL', 'abdulrazaqjafar66@gmail.com'),
    ],

    'hero' => [
        'heading' => 'Fast, Reliable and Affordable Internet',
        'description' => 'Connect instantly with secure high-speed Wi-Fi packages designed for homes, students, businesses, and visitors.',
        'system_name' => 'Tech-Sphere Wi-Fi Billing System',
    ],

    'mikrotik' => [
        'login_param_mac' => 'mac',
        'login_param_ip' => 'ip',
        'login_param_link_login' => 'link-login',
        'login_param_link_orig' => 'link-orig',
    ],

];
