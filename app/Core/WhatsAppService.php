<?php

namespace App\Core;

/**
 * WhatsAppService — Sends messages/images to WhatsApp groups via Green API.
 * 
 * Green API is a lightweight REST-based WhatsApp API that supports group messaging.
 * Docs: https://green-api.com/en/docs/
 */
class WhatsAppService
{
    private $instanceId;
    private $apiToken;
    private $baseUrl = 'https://api.green-api.com';

    public function __construct(string $instanceId, string $apiToken)
    {
        $this->instanceId = $instanceId;
        $this->apiToken = $apiToken;
    }

    /**
     * Send an image with caption to a WhatsApp group.
     *
     * @param string $groupId  The group chat ID (e.g., "120363XXXXXXXXX@g.us")
     * @param string $imagePath Absolute path to the image file on server
     * @param string $caption  Text caption to send with the image
     * @return array ['success' => bool, 'response' => string]
     */
    public function sendImageToGroup(string $groupId, string $imagePath, string $caption): array
    {
        // First, upload the file
        $uploadUrl = "{$this->baseUrl}/waInstance{$this->instanceId}/sendFileByUpload/{$this->apiToken}";

        $postFields = [
            'chatId' => $groupId,
            'caption' => $caption,
            'file' => new \CURLFile($imagePath, 'image/jpeg', basename($imagePath))
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $uploadUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'response' => "CURL Error: {$error}"];
        }

        $decoded = json_decode($response, true);

        if ($httpCode === 200 && isset($decoded['idMessage'])) {
            return ['success' => true, 'response' => $response];
        }

        return ['success' => false, 'response' => $response];
    }

    /**
     * Send a text-only message to a WhatsApp group.
     *
     * @param string $groupId The group chat ID
     * @param string $message The text message
     * @return array ['success' => bool, 'response' => string]
     */
    public function sendTextToGroup(string $groupId, string $message): array
    {
        $url = "{$this->baseUrl}/waInstance{$this->instanceId}/sendMessage/{$this->apiToken}";

        $payload = json_encode([
            'chatId' => $groupId,
            'message' => $message
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'response' => "CURL Error: {$error}"];
        }

        $decoded = json_decode($response, true);

        if ($httpCode === 200 && isset($decoded['idMessage'])) {
            return ['success' => true, 'response' => $response];
        }

        return ['success' => false, 'response' => $response];
    }

    /**
     * Check if the WhatsApp instance is authorized and ready.
     *
     * @return array ['authorized' => bool, 'response' => string]
     */
    public function checkStatus(): array
    {
        $url = "{$this->baseUrl}/waInstance{$this->instanceId}/getStateInstance/{$this->apiToken}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($response, true);
        $authorized = ($decoded['stateInstance'] ?? '') === 'authorized';

        return ['authorized' => $authorized, 'response' => $response];
    }

    /**
     * Get list of groups the authorized WhatsApp account is part of.
     *
     * @return array List of groups with id and name
     */
    public function getGroups(): array
    {
        $url = "{$this->baseUrl}/waInstance{$this->instanceId}/getContacts/{$this->apiToken}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $contacts = json_decode($response, true) ?? [];
        $groups = [];

        foreach ($contacts as $contact) {
            if (isset($contact['id']) && strpos($contact['id'], '@g.us') !== false) {
                $groups[] = [
                    'id' => $contact['id'],
                    'name' => $contact['name'] ?? $contact['id']
                ];
            }
        }

        return $groups;
    }
}
