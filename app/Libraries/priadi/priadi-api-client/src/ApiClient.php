<?php

namespace Priadi\ApiClient;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ApiClient
{
    private $baseUrl = 'https://restapi.priadi.id/index.php/api/v1/';
    private $clientId;
    private $clientSecret;
    private $httpClient;
    private $token;
    private $refreshToken; // Menyimpan refresh token
    private $useJwt;

    /**
     * Inisialisasi ApiClient.
     *
     * @param string $clientId API Key (mitra-username) untuk autentikasi JWT atau OAuth client ID.
     * @param string $clientSecret Secret (mitra-password) untuk autentikasi JWT atau OAuth client secret.
     * @param bool $useJwt True untuk autentikasi JWT, False untuk OAuth.
     */
    public function __construct(string $clientId, string $clientSecret, bool $useJwt = true)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->httpClient = new Client();
        $this->useJwt = $useJwt;
    }

    /**
     * Mendapatkan access token. Jika token expired, akan otomatis menggunakan refresh token.
     *
     * @return string Access token.
     * @throws \Exception Jika gagal mendapatkan token.
     */
    public function getToken(): string
    {
        if ($this->token) {
            return $this->token;
        }

        if ($this->useJwt) {
            // Autentikasi JWT
            $response = $this->httpClient->post($this->baseUrl . 'auth/login', [
                'json' => [
                    'login' => $this->clientId,
                    'password' => $this->clientSecret
                ]
            ]);
            $data = json_decode((string) $response->getBody(), true);
            $this->token = $data['access_token'] ?? $data['token'];
            $this->refreshToken = $data['refresh_token'] ?? null; // Simpan refresh token
        } else {
            // Autentikasi OAuth
            $response = $this->httpClient->post($this->baseUrl . 'oauth/token', [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'client_credentials'
                ]
            ]);
            $data = json_decode((string) $response->getBody(), true);
            $this->token = $data['access_token'] ?? $data['token'];
        }

        return $this->token;
    }

    /**
     * Mencoba refresh token jika access token expired.
     *
     * @return bool True jika berhasil, False jika gagal.
     */
    private function _refreshToken(): bool
    {
        if (!$this->refreshToken) {
            return false;
        }

        try {
            $response = $this->httpClient->post($this->baseUrl . 'auth/refresh', [
                'json' => [
                    'refresh_token' => $this->refreshToken
                ]
            ]);
            $data = json_decode((string) $response->getBody(), true);
            $this->token = $data['access_token'] ?? null;
            $this->refreshToken = $data['refresh_token'] ?? null; // Update refresh token
            return !empty($this->token);
        } catch (RequestException $e) {
            return false;
        }
    }

    /**
     * Mengirimkan permintaan GET ke endpoint API.
     *
     * @param string $endpoint Endpoint API (misalnya 'instance-group').
     * @return array Data respons atau error.
     */
    public function get(string $endpoint)
    {
        try {
            $response = $this->httpClient->get($this->baseUrl . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getToken(),
                    'X-API-Key'     => $this->clientId,
                ]
            ]);
            return json_decode((string) $response->getBody(), true);
        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 401) {
                // Coba refresh token
                if ($this->_refreshToken()) {
                    // Ulangi permintaan dengan token baru
                    return $this->get($endpoint);
                }
            }
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Mengirimkan permintaan POST ke endpoint API.
     *
     * @param string $endpoint Endpoint API (misalnya 'instance-group').
     * @param array $data Data yang akan dikirim.
     * @return array Data respons atau error.
     */
    public function post(string $endpoint, array $data)
    {
        try {
            $response = $this->httpClient->post($this->baseUrl . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getToken(),
                    'X-API-Key'     => $this->clientId,
                ],
                'json' => $data
            ]);
            return json_decode((string) $response->getBody(), true);
        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 401) {
                // Coba refresh token
                if ($this->_refreshToken()) {
                    // Ulangi permintaan dengan token baru
                    return $this->post($endpoint, $data);
                }
            }
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Mengirimkan permintaan PUT ke endpoint API.
     *
     * @param string $endpoint Endpoint API (misalnya 'instance-group/1').
     * @param array $data Data yang akan dikirim.
     * @return array Data respons atau error.
     */
    public function put(string $endpoint, array $data)
    {
        try {
            $response = $this->httpClient->put($this->baseUrl . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getToken(),
                    'X-API-Key'     => $this->clientId,
                ],
                'json' => $data
            ]);
            return json_decode((string) $response->getBody(), true);
        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 401) {
                // Coba refresh token
                if ($this->_refreshToken()) {
                    // Ulangi permintaan dengan token baru
                    return $this->put($endpoint, $data);
                }
            }
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Mengirimkan permintaan DELETE ke endpoint API.
     *
     * @param string $endpoint Endpoint API (misalnya 'instance-group/1').
     * @return array Data respons atau error.
     */
    public function delete(string $endpoint)
    {
        try {
            $response = $this->httpClient->delete($this->baseUrl . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getToken(),
                    'X-API-Key'     => $this->clientId,
                ]
            ]);
            return json_decode((string) $response->getBody(), true);
        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 401) {
                // Coba refresh token
                if ($this->_refreshToken()) {
                    // Ulangi permintaan dengan token baru
                    return $this->delete($endpoint);
                }
            }
            return ['error' => $e->getMessage()];
        }
    }
}
