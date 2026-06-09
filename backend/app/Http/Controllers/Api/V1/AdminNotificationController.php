<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    private const DEFAULT_CONFIG = [
        'email' => [
            'enabled' => false,
            'events'  => [
                'order_placed'   => true,
                'status_changed' => true,
                'return_updated' => true,
            ],
        ],
        'sms' => [
            'enabled'     => false,
            'account_sid' => '',
            'auth_token'  => '',
            'from_number' => '',
            'events'      => [
                'order_placed'   => true,
                'status_changed' => true,
                'return_updated' => false,
            ],
        ],
        'whatsapp' => [
            'enabled'     => false,
            'from_number' => '',
            'events'      => [
                'order_placed'   => true,
                'status_changed' => false,
                'return_updated' => false,
            ],
        ],
    ];

    public function show(): JsonResponse
    {
        $setting = StoreSetting::active();
        $config  = $setting?->notification_config ?? self::DEFAULT_CONFIG;
        $config  = $this->mergeDefaults($config);

        // Mask auth token in the response
        if (!empty($config['sms']['auth_token'])) {
            $token = $config['sms']['auth_token'];
            $config['sms']['auth_token_masked'] = '••••' . substr($token, -4);
            $config['sms']['auth_token'] = '';
        }

        return response()->json(['config' => $config]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'                        => ['sometimes', 'array'],
            'email.enabled'                => ['sometimes', 'boolean'],
            'email.events'                 => ['sometimes', 'array'],
            'email.events.order_placed'    => ['sometimes', 'boolean'],
            'email.events.status_changed'  => ['sometimes', 'boolean'],
            'email.events.return_updated'  => ['sometimes', 'boolean'],

            'sms'                          => ['sometimes', 'array'],
            'sms.enabled'                  => ['sometimes', 'boolean'],
            'sms.account_sid'              => ['sometimes', 'nullable', 'string', 'max:100'],
            'sms.auth_token'               => ['sometimes', 'nullable', 'string', 'max:100'],
            'sms.from_number'              => ['sometimes', 'nullable', 'string', 'max:30'],
            'sms.events'                   => ['sometimes', 'array'],
            'sms.events.order_placed'      => ['sometimes', 'boolean'],
            'sms.events.status_changed'    => ['sometimes', 'boolean'],
            'sms.events.return_updated'    => ['sometimes', 'boolean'],

            'whatsapp'                     => ['sometimes', 'array'],
            'whatsapp.enabled'             => ['sometimes', 'boolean'],
            'whatsapp.from_number'         => ['sometimes', 'nullable', 'string', 'max:50'],
            'whatsapp.events'              => ['sometimes', 'array'],
            'whatsapp.events.order_placed' => ['sometimes', 'boolean'],
            'whatsapp.events.status_changed' => ['sometimes', 'boolean'],
            'whatsapp.events.return_updated' => ['sometimes', 'boolean'],
        ]);

        $setting = StoreSetting::active();
        if (!$setting) {
            return response()->json(['message' => 'No active store settings found.'], 404);
        }

        $existing = $setting->notification_config ?? self::DEFAULT_CONFIG;
        $existing = $this->mergeDefaults($existing);

        // Deep merge incoming data into existing config
        foreach (['email', 'sms', 'whatsapp'] as $channel) {
            if (!isset($data[$channel])) continue;
            foreach ($data[$channel] as $key => $value) {
                if ($key === 'events' && is_array($value)) {
                    foreach ($value as $event => $enabled) {
                        $existing[$channel]['events'][$event] = $enabled;
                    }
                } else {
                    // Don't overwrite auth_token with empty string (masking artifact)
                    if ($channel === 'sms' && $key === 'auth_token' && $value === '') {
                        continue;
                    }
                    $existing[$channel][$key] = $value;
                }
            }
        }

        $setting->update(['notification_config' => $existing]);

        $response = $existing;
        if (!empty($response['sms']['auth_token'])) {
            $token = $response['sms']['auth_token'];
            $response['sms']['auth_token_masked'] = '••••' . substr($token, -4);
            $response['sms']['auth_token'] = '';
        }

        return response()->json(['config' => $response]);
    }

    public function logs(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 30);
        $query   = NotificationLog::orderByDesc('created_at');

        if ($channel = $request->input('channel')) {
            $query->where('channel', $channel);
        }
        if ($event = $request->input('event')) {
            $query->where('event', $event);
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'logs' => $paginator->items(),
            'meta' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    private function mergeDefaults(array $config): array
    {
        foreach (self::DEFAULT_CONFIG as $channel => $defaults) {
            if (!isset($config[$channel])) {
                $config[$channel] = $defaults;
                continue;
            }
            foreach ($defaults as $key => $default) {
                if (!isset($config[$channel][$key])) {
                    $config[$channel][$key] = $default;
                } elseif ($key === 'events' && is_array($default)) {
                    foreach ($default as $event => $val) {
                        if (!isset($config[$channel]['events'][$event])) {
                            $config[$channel]['events'][$event] = $val;
                        }
                    }
                }
            }
        }
        return $config;
    }
}
