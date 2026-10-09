<?php
/**
 * AnyPay Tanzania Payment Gateway Handler
 * Handles both COLLECTION (pull) and DISBURSEMENT (push)
 */

class PaymentGateway
{
    private string $provider;

    public function __construct(string $provider)
    {
        $this->provider = strtolower($provider);
    }

    public function initiatePayment(array $data): array
    {
        if ($this->provider === 'anypay') return $this->anypay($data);
        throw new Exception("Unsupported gateway: {$this->provider}");
    }

    /* ============================================================
       COLLECTION — customer pays YOU
       ============================================================ */
    private function anypay(array $data): array
    {
        $phone = $this->normalizePhoneLocal($data['phone']);

        $payload = [
            'order_id' => $data['order_ref'],
            'phone'    => $phone,
            'amount'   => (int) $data['amount'],
        ];

        $logFile = __DIR__ . '/anypay_collect.log';
        file_put_contents($logFile,
            date('Y-m-d H:i:s') . " [COLLECT REQ] " . json_encode($payload) . PHP_EOL,
            FILE_APPEND
        );

        $ch = curl_init(ANYPAY_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'API-Key: ' . ANYPAY_API_KEY,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        file_put_contents($logFile,
            date('Y-m-d H:i:s') . " [COLLECT RES] HTTP=$httpCode Err=" . ($curlErr ?: 'none') . " Body=" . substr($response, 0, 800) . PHP_EOL,
            FILE_APPEND
        );

        if ($curlErr) {
            throw new Exception('AnyPay connection error: ' . $curlErr);
        }

        $json = json_decode($response, true);

        if ($httpCode >= 400 || !is_array($json)) {
            throw new Exception('AnyPay error (HTTP ' . $httpCode . '): ' . substr($response, 0, 300));
        }

        if (isset($json['success']) && $json['success'] === false) {
            throw new Exception('AnyPay: ' . ($json['message'] ?? 'Payment request rejected'));
        }

        return [
            'gateway_ref'      => $json['transaction_id'] ?? $json['reference'] ?? $json['data']['transaction_id'] ?? $data['order_ref'],
            'awaiting_payment' => true,
            'redirect_url'     => null,
            'message'          => $json['message'] ?? 'Payment request sent. Check your phone.',
            'raw'              => $json,
        ];
    }

    /* ============================================================
       DISBURSEMENT — YOU pay a customer (loan disbursement)
       ============================================================ */
   /* ============================================================
   ANYPAY DISBURSEMENT (PAYOUT) — sends money TO a customer
   Per AnyPay docs:
     POST /api/payments/wellet/payout/
     Body: { recipientNames, amount, serviceCode, phone }
   ============================================================ */
public function disburse(array $data): array
{
    $phone  = $this->normalizePhoneLocal($data['phone']);
    $amount = (int)$data['amount'];

    // Safety checks
    if ($amount < DISBURSEMENT_MIN) {
        throw new Exception("Amount is below the minimum (TZS " . number_format(DISBURSEMENT_MIN) . ")");
    }
    if ($amount > DISBURSEMENT_MAX) {
        throw new Exception("Amount exceeds maximum (TZS " . number_format(DISBURSEMENT_MAX) . "). Please disburse manually.");
    }

    // Detect mobile network from phone prefix
    $serviceCode = $this->detectServiceCode($phone);

    $payload = [
        'recipientNames' => $data['recipient_name'] ?? 'Loan Applicant',
        'amount'         => $amount,
        'serviceCode'    => $serviceCode,
        'phone'          => $phone,
    ];

    // Log request
    $logFile = __DIR__ . '/anypay_payout.log';
    file_put_contents($logFile,
        date('Y-m-d H:i:s') . " [PAYOUT REQ] URL=" . ANYPAY_PAYOUT_URL . " Payload=" . json_encode($payload) . PHP_EOL,
        FILE_APPEND);

    $ch = curl_init(ANYPAY_PAYOUT_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'API-Key: ' . ANYPAY_PAYOUT_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    // Log response
    file_put_contents($logFile,
        date('Y-m-d H:i:s') . " [PAYOUT RES] HTTP=$httpCode Err=" . ($curlErr ?: 'none') . " Body=" . substr($response, 0, 1000) . PHP_EOL,
        FILE_APPEND);

    if ($curlErr) {
        throw new Exception('AnyPay payout connection error: ' . $curlErr);
    }

    $json = json_decode($response, true);

    if ($httpCode >= 400 || !is_array($json)) {
        throw new Exception('AnyPay payout error (HTTP ' . $httpCode . '): ' . substr(strip_tags($response), 0, 300));
    }

    if (isset($json['success']) && $json['success'] === false) {
        throw new Exception('AnyPay payout: ' . ($json['message'] ?? 'Request rejected'));
    }

    // Extract reference from AnyPay's nested response
    $tz = $json['anypay_tzresponse'] ?? [];
    $payment_ref = $tz['payment_reference']
                ?? $tz['order_id']
                ?? $json['payment_reference']
                ?? $json['reference']
                ?? $data['reference'];

    return [
        'disbursement_ref' => $payment_ref,
        'status'           => $tz['status'] ?? 'processing',
        'amount'           => $tz['amount'] ?? $amount,
        'payout_fee'       => $tz['payout_fee'] ?? 0,
        'message'          => $json['message'] ?? 'Payout initiated',
        'raw'              => $json,
    ];
}

/* ============================================================
   Detect mobile network service code from phone prefix
   Tanzanian prefixes:
      Vodacom (M-Pesa):   074x, 075x, 076x
      Airtel Money:       078x, 068x, 069x
      Tigo Pesa (Mixx):   071x, 065x, 067x
      Halotel (HaloPesa): 062x, 061x
      TTCL:               073x
   ============================================================ */
private function detectServiceCode(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);
    // Normalize to local format 0XXXXXXXXX
    if (str_starts_with($digits, '255')) $digits = '0' . substr($digits, 3);

    $prefix3 = substr($digits, 0, 3);  // e.g. "074"

    $map = [
        // Vodacom — M-Pesa
        '074' => 'VODACOM',
        '075' => 'VODACOM',
        '076' => 'VODACOM',
        // Airtel Money
        '078' => 'AIRTEL',
        '068' => 'AIRTEL',
        '069' => 'AIRTEL',
        // Tigo / Mixx by Yas
        '071' => 'TIGO',
        '065' => 'TIGO',
        '067' => 'TIGO',
        // Halotel
        '062' => 'HALOTEL',
        '061' => 'HALOTEL',
        // TTCL
        '073' => 'TTCL',
    ];

    if (isset($map[$prefix3])) {
        return $map[$prefix3];
    }

    // Default to Vodacom if unknown — most common network
    return 'VODACOM';
}

    /* ============================================================
       HELPERS
       ============================================================ */
    private function normalizePhoneLocal(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (str_starts_with($digits, '255')) $digits = substr($digits, 3);
        if (!str_starts_with($digits, '0'))  $digits = '0' . $digits;
        return $digits;
    }
}