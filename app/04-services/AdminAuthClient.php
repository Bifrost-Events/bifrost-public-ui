<?php

declare(strict_types=1);

namespace App\Service;

use App\Support\Config;
use App\Support\Session;

/**
 * HTTP-klient mot bifrost-admin-core auth + public me/people (V3).
 * Proxier server-side og lagrer BIFROSTADMIN-cookie i public-sesjon.
 */
final class AdminAuthClient
{
    /** @var list<string>|null */
    private static ?array $lastResponseHeaders = null;

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function login(string $email, string $password): array
    {
        $result = $this->request('POST', '/api/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);
        if ($result['ok'] ?? false) {
            $this->captureAdminSessionCookieFromLastResponse();
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $body
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function register(array $body): array
    {
        $result = $this->request('POST', '/api/auth/register', $body);
        if ($result['ok'] ?? false) {
            $this->captureAdminSessionCookieFromLastResponse();
        }

        return $result;
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function logout(): array
    {
        $result = $this->request('POST', '/api/auth/logout', []);
        Session::clearAdminCookie();

        return $result;
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function me(): array
    {
        return $this->request('GET', '/api/auth/me');
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function people(): array
    {
        return $this->request('GET', '/api/public/me/people');
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function person(int $personId): array
    {
        return $this->request('GET', '/api/public/me/people/' . $personId);
    }

    /**
     * @param array<string, mixed> $body
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function createPerson(array $body): array
    {
        return $this->request('POST', '/api/public/me/people', $body);
    }

    /**
     * @param array<string, mixed> $body
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function updatePerson(array $body): array
    {
        return $this->request('PATCH', '/api/public/me/person', $body);
    }

    /**
     * @param array<string, mixed> $body
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function updatePeoplePerson(int $personId, array $body): array
    {
        return $this->request('PATCH', '/api/public/me/people/' . $personId, $body);
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        try {
            $baseUrl = rtrim((string) Config::get('admin.api_base_url', ''), '/');
            if ($baseUrl === '') {
                return [
                    'ok' => false,
                    'status' => 0,
                    'data' => null,
                    'error' => 'ADMIN_URL / Admin API base URL is not configured',
                ];
            }

            $url = $baseUrl . $path;
            $cookie = Session::getAdminCookie();

            if (function_exists('curl_init')) {
                return $this->decodeResponse($this->requestViaCurl($url, $method, $body, $cookie));
            }

            return $this->decodeResponse($this->requestViaStream($url, $method, $body, $cookie));
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 0,
                'data' => null,
                'error' => 'Admin auth request failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array{status: int, body: string|false, headers: list<string>}
     */
    private function requestViaCurl(string $url, string $method, ?array $body, string $cookie): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('curl_init failed');
        }

        $headerLines = ['Accept: application/json'];
        if ($cookie !== '') {
            $headerLines[] = 'Cookie: ' . $cookie;
        }

        $payload = null;
        if ($body !== null) {
            $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $headerLines[] = 'Content-Type: application/json';
        }

        $responseHeaders = [];
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$responseHeaders) {
                $trimmed = trim($headerLine);
                if ($trimmed !== '') {
                    $responseHeaders[] = $trimmed;
                }

                return strlen($headerLine);
            },
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $responseBody = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        if ($responseBody === false) {
            $err = curl_error($ch);
            throw new \RuntimeException($err !== '' ? $err : 'curl_exec failed');
        }

        self::$lastResponseHeaders = $responseHeaders;

        return [
            'status' => $status,
            'body' => $responseBody,
            'headers' => $responseHeaders,
        ];
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array{status: int, body: string|false, headers: list<string>}
     */
    private function requestViaStream(string $url, string $method, ?array $body, string $cookie): array
    {
        $headers = "Accept: application/json\r\n";
        if ($cookie !== '') {
            $headers .= 'Cookie: ' . $cookie . "\r\n";
        }

        $options = [
            'method' => $method,
            'timeout' => 12,
            'ignore_errors' => true,
            'header' => $headers,
        ];

        if ($body !== null) {
            $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $options['header'] .= "Content-Type: application/json\r\n";
            $options['content'] = $payload;
        }

        $context = stream_context_create([
            'http' => $options,
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        self::$lastResponseHeaders = null;
        $responseBody = @file_get_contents($url, false, $context);
        if (PHP_VERSION_ID >= 80500) {
            $hdrs = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : null;
            $rawHeaders = is_array($hdrs) ? $hdrs : [];
        } else {
            $rawHeaders = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : [];
        }
        self::$lastResponseHeaders = $rawHeaders;

        $status = 0;
        if (isset(self::$lastResponseHeaders[0]) && preg_match('#\s(\d{3})\s#', self::$lastResponseHeaders[0], $m)) {
            $status = (int) $m[1];
        }

        if ($responseBody === false) {
            throw new \RuntimeException('Could not reach admin API at ' . $url);
        }

        return [
            'status' => $status,
            'body' => $responseBody,
            'headers' => self::$lastResponseHeaders,
        ];
    }

    /**
     * @param array{status: int, body: string|false, headers: list<string>} $transport
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    private function decodeResponse(array $transport): array
    {
        $status = $transport['status'];
        $responseBody = $transport['body'];
        if (!is_string($responseBody) || $responseBody === '') {
            return [
                'ok' => false,
                'status' => $status,
                'data' => null,
                'error' => 'Empty response from admin API',
            ];
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [
                'ok' => false,
                'status' => $status,
                'data' => null,
                'error' => 'Invalid JSON from admin API',
            ];
        }

        $ok = $status >= 200 && $status < 300;
        $error = null;
        $code = null;
        if (!$ok) {
            if (is_array($decoded['error'] ?? null)) {
                $error = (string) ($decoded['error']['message'] ?? 'HTTP ' . $status);
                $code = isset($decoded['error']['code']) ? (string) $decoded['error']['code'] : null;
            } else {
                $error = (string) ($decoded['error'] ?? 'HTTP ' . $status);
            }
        }

        $data = $decoded;
        if (isset($decoded['data']) && is_array($decoded['data'])) {
            $data = $decoded['data'];
        }

        $result = [
            'ok' => $ok,
            'status' => $status,
            'data' => $ok ? $data : null,
            'error' => $error,
        ];
        if ($code !== null) {
            $result['code'] = $code;
        }

        return $result;
    }

    private function captureAdminSessionCookieFromLastResponse(): void
    {
        $headers = self::$lastResponseHeaders ?? [];
        foreach ($headers as $header) {
            if (!str_starts_with(strtolower($header), 'set-cookie:')) {
                continue;
            }
            if (!preg_match('/^Set-Cookie:\s*([^=]+)=([^;]+)/i', $header, $m)) {
                continue;
            }
            $name = trim($m[1]);
            if ($name === 'BIFROSTADMIN') {
                Session::setAdminCookie($name . '=' . trim($m[2]));
                break;
            }
        }
    }
}
