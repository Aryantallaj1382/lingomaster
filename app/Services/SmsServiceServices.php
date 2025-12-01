<?php

namespace App\Services;

use Exception;
use IPPanel\Client;

class SmsServiceServices
{
    public function sendPatternSms(string $patternCode, string $recipient, array $variables, string $originator = '3000505'): array
    {
        try {
            $apiKey = config('app.sms_panel_apikey');

            if (empty($apiKey)) {
                throw new Exception('API Key پیامکی مقداردهی نشده است.');
            }

            if (empty($patternCode) || empty($originator) || empty($recipient) || empty($variables)) {
                throw new Exception('تمام مقادیر باید مقداردهی شوند.');
            }

            $client = new Client($apiKey);
            $messageId = $client->sendPattern($patternCode, $originator, $recipient, $variables);

            return [
                'success' => true,
                'message' => 'پیامک با موفقیت ارسال شد.',
                'message_id' => $messageId,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'خطا در ارسال پیامک: ' . $e->getMessage(),
            ];
        }
    }
}
