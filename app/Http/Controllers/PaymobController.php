<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PaymobController extends Controller
{
    private $paymobApiKey;
    private $integrationId;

    public function __construct()
    {
        $this->paymobApiKey = env('PAYMOB_API_KEY'); // Store your API key in .env
        $this->integrationId = env('PAYMOB_INTEGRATION_ID'); // Store your integration ID in .env
    }

    // Step 1: Authenticate with Paymob
    public function authenticate()
    {
        try {
            $client = new Client();
            $response = $client->post('https://uae.paymob.com/api/auth/tokens', [
                'json' => ['api_key' => $this->paymobApiKey]
            ]);

            $responseData = json_decode($response->getBody(), true);

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('Paymob Authentication Error: ' . $e->getMessage());
            return response()->json(['error' => 'Authentication failed.', 'message' => $e->getMessage()], 500);
        }
    }

    // Step 2: Create Order
    public function createOrder(Request $request)
    {
        $request->validate([
            'auth_token' => 'required|string',
            'amount_cents' => 'required|integer|min:1',
            'currency' => 'nullable|string|max:3',
        ]);

        try {
            $client = new Client();
            $authToken = $request->input('auth_token');
            $amountCents = $request->input('amount_cents');
            $currency = $request->input('currency', 'AED'); // Default to EGP if not provided

            $response = $client->post('https://uae.paymob.com/api/ecommerce/orders', [
                'json' => [
                    'auth_token' => $authToken,
                    'delivery_needed' => false,
                    'amount_cents' => $amountCents,
                    'currency' => $currency,
                    'items' => [],
                ]
            ]);

            $responseData = json_decode($response->getBody(), true);

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('Paymob Create Order Error: ' . $e->getMessage());
            return response()->json(['error' => 'Order creation failed.'], 500);
        }
    }

    // Step 3: Generate Payment Key
    public function generatePaymentKey(Request $request)
    {
        $request->validate([
            'auth_token' => 'required|string',
            'order_id' => 'required|string',
            'amount_cents' => 'required|integer|min:1',
            'billing_data' => 'required|array',
        ]);

        try {
            $client = new Client();
            $authToken = $request->input('auth_token');
            $orderId = $request->input('order_id');
            $billingData = $request->input('billing_data');
            $amountCents = $request->input('amount_cents');
            $currency = $request->input('currency', 'AED'); // Default to EGP if not provided

            $response = $client->post('https://uae.paymob.com/api/acceptance/payment_keys', [
                'json' => [
                    'auth_token' => $authToken,
                    'amount_cents' => $amountCents,
                    'order_id' => (string) $orderId,
                    'billing_data' => $billingData,
                    'currency' => 'AED',
                    'integration_id' => $this->integrationId,
                ]
            ]);

            $responseData = json_decode($response->getBody(), true);

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('Paymob Generate Payment Key Error: ' . $e->getMessage());
            return response()->json(['error' => 'Payment key generation failed.'], 500);
        }
    }
}
