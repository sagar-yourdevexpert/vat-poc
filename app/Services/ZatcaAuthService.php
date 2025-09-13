<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ZatcaAuthService
{
    protected $clientId;
    protected $clientSecret;
    protected $apiBase;

    public function __construct()
    {
        $this->clientId = config('zatca.client_id');
        $this->clientSecret = config('zatca.client_secret');
        $this->apiBase = rtrim(config('zatca.api_base'), '/');
    }

    /**
     * Get OAuth2 access token from ZATCA, cache for 50 minutes.
     */
    public function getAccessToken()
    {
        return Cache::remember('zatca_access_token', 3000, function () {
            $response = Http::asForm()->post($this->apiBase . '/auth/realms/zakaa/protocol/openid-connect/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);
            if (!$response->ok()) {
                throw new \Exception('Failed to get ZATCA access token: ' . $response->body());
            }
            return $response->json('access_token');
        });
    }
}
