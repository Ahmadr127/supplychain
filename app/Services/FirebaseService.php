<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Exception\FirebaseException;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private ?Messaging $messaging = null;
    private ?array $credentials = null;

    /**
     * Initialize Firebase Messaging service
     *
     * @return Messaging
     * @throws FirebaseException
     */
    public function getMessaging(): Messaging
    {
        if ($this->messaging !== null) {
            return $this->messaging;
        }

        $credentials = $this->getCredentials();

        if (!$credentials) {
            throw new FirebaseException('Firebase credentials not configured');
        }

        try {
            $factory = (new Factory)->withServiceAccount($credentials);
            $this->messaging = $factory->createMessaging();

            return $this->messaging;
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase Messaging', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new FirebaseException('Failed to initialize Firebase: ' . $e->getMessage());
        }
    }

    /**
     * Get Firebase credentials from config
     *
     * @return array|null
     */
    public function getCredentials(): ?array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        // Try to get credentials from environment variable
        $credentialsJson = env('FIREBASE_CREDENTIALS_JSON');
        if ($credentialsJson) {
            $this->credentials = json_decode($credentialsJson, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->credentials;
            }
        }

        // Fallback to file
        $credentialsPath = storage_path('app/firebase-auth.json');
        if (file_exists($credentialsPath)) {
            $fileContent = file_get_contents($credentialsPath);
            $this->credentials = json_decode($fileContent, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->credentials;
            }
        }

        return null;
    }

    /**
     * Get Firebase project ID
     *
     * @return string|null
     */
    public function getProjectId(): ?string
    {
        $credentials = $this->getCredentials();
        return $credentials['project_id'] ?? null;
    }

    /**
     * Get Firebase client email
     *
     * @return string|null
     */
    public function getClientEmail(): ?string
    {
        $credentials = $this->getCredentials();
        return $credentials['client_email'] ?? null;
    }

    /**
     * Test Firebase connection
     *
     * @return array
     * @throws FirebaseException
     */
    public function ping(): array
    {
        $messaging = $this->getMessaging();
        $credentials = $this->getCredentials();

        return [
            'status' => 'connected',
            'project_id' => $credentials['project_id'] ?? null,
            'client_email' => $credentials['client_email'] ?? null,
        ];
    }
}
