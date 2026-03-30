<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IntaSendService
{
    protected $publicKey;
    protected $secretKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->publicKey = config('services.intasend.public_key');
        $this->secretKey = config('services.intasend.secret_key');
        $this->baseUrl = config('services.intasend.env') === 'live'
            ? 'https://payment.intasend.com/api/v1/'
            : 'https://sandbox.intasend.com/api/v1/';
    }

    /**
     * 1. Initiate STK Push
     */
    public function stkPush($phone, $amount, $accountRef, $narration = 'CloudBridge WiFi')
    {
        $payload = [
            'public_key' => $this->publicKey,
            'phone_number' => $phone,
            'amount' => $amount,
            'currency' => 'KES',
            'account_ref' => $accountRef, // Unique Session ID
            'narration' => $narration,
            'callback_url' => route('api.payment.callback'),
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . 'checkout/', $payload);

        return $response->json();
    }

    /**
     * 2. Payout to Tenant Till (Auto-Settlement)
     */
    public function payoutToTill($tillNumber, $amount, $narration)
    {
        $payload = [
            'public_key' => $this->publicKey,
            'phone_number' => $tillNumber, // Till Number
            'amount' => $amount,
            'currency' => 'KES',
            'narration' => $narration,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . 'payouts/', $payload);

        return $response->json();
    }
}