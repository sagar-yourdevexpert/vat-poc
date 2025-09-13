<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Services\ZatcaAuthService;

class ZatcaApiService
{
    protected $authService;
    protected $deviceUuid;
    protected $apiBase;

    public function __construct()
    {
        $this->authService = new ZatcaAuthService();
        $this->deviceUuid = config('zatca.device_uuid');
        $this->apiBase = rtrim(config('zatca.api_base'), '/');
    }

    /**
     * Report or clear a signed invoice XML to ZATCA.
     * @param string $signedXml
     * @return array
     */
    public function reportInvoice($signedXml)
    {
        $accessToken = $this->authService->getAccessToken();
        $endpoint = $this->apiBase . '/compliance/invoices/report';
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Clearance-Status' => '1',
            'Device-UUID' => $this->deviceUuid,
            'Content-Type' => 'application/xml',
        ])->withBody($signedXml, 'application/xml')->post($endpoint);

        if (!$response->ok()) {
            throw new \Exception('ZATCA API error: ' . $response->body());
        }
        return $response->json();
    }
}
