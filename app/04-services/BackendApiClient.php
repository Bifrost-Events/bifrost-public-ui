<?php

declare(strict_types=1);

namespace App\Service;

use App\Support\Config;
use App\Support\Session;

final class BackendApiClient
{
    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function health(): array
    {
        return $this->get('/api/health');
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function resolveTenant(string $host): array
    {
        return $this->get('/api/tenant/resolve?host=' . rawurlencode($host));
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function tenant(int $id): array
    {
        return $this->get('/api/tenants/' . $id);
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function tenants(): array
    {
        return $this->get('/api/tenants');
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function me(): array
    {
        return $this->get('/api/auth/me');
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function publicCalendar(string $host): array
    {
        return $this->get('/api/public/calendar?host=' . rawurlencode($host));
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function publicResultsIndex(string $host): array
    {
        return $this->get('/api/public/results?host=' . rawurlencode($host));
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function publicCompetitionResults(int $id, string $host): array
    {
        return $this->get('/api/public/competitions/' . $id . '/results?host=' . rawurlencode($host));
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function publicStandings(string $host): array
    {
        return $this->get('/api/public/standings?host=' . rawurlencode($host));
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function competitionSignup(int $id, string $host): array
    {
        return $this->get('/api/public/competitions/' . $id . '/signup?host=' . rawurlencode($host));
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function participantShooters(): array
    {
        return $this->get('/api/participant/shooters');
    }

    /**
     * @param array<string, mixed> $body
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function createParticipant(array $body): array
    {
        return $this->request('POST', '/api/participant/shooters', $body);
    }

    /**
     * @param array<string, mixed> $body
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function updateParticipant(int $id, array $body): array
    {
        return $this->request('PUT', '/api/participant/shooters/' . $id, $body);
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function participantSignups(string $host): array
    {
        return $this->get('/api/participant/signups?host=' . rawurlencode($host));
    }

    /**
     * @param array<string, mixed> $body
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function registerSignup(string $host, array $body): array
    {
        return $this->request('POST', '/api/participant/signups?host=' . rawurlencode($host), $body);
    }

    /**
     * @param array<string, mixed> $body
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function unregisterSignup(string $host, array $body): array
    {
        return $this->request('DELETE', '/api/participant/signups?host=' . rawurlencode($host), $body);
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function participantLogin(string $email, string $password): array
    {
        $result = $this->request('POST', '/api/auth/participant/login', [
            'email' => $email,
            'password' => $password,
        ]);
        if ($result['ok'] ?? false) {
            $this->storeBackendSessionFromLoginResponse($result['data'] ?? []);
            $this->captureSessionCookieFromLastResponse();
        }

        return $result;
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function participantRegister(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        string $phone,
        ?int $tenantId = null,
        ?string $userAgreementVersion = null,
    ): array {
        $name = trim($firstName . ' ' . $lastName);
        $body = [
            'email' => $email,
            'password' => $password,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'name' => $name,
        ];
        if ($tenantId !== null && $tenantId > 0) {
            $body['tenant_id'] = $tenantId;
        }
        if ($userAgreementVersion !== null && $userAgreementVersion !== '') {
            $body['user_agreement_version'] = $userAgreementVersion;
        }
        $result = $this->request('POST', '/api/auth/participant/register', $body);
        if ($result['ok'] ?? false) {
            $this->storeBackendSessionFromLoginResponse($result['data'] ?? []);
            $this->captureSessionCookieFromLastResponse();
        }

        return $result;
    }

    /** @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>} */
    public function participantProfile(): array
    {
        return $this->get('/api/participant/profile');
    }

    /**
     * @param array<string, mixed> $body
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function updateParticipantProfile(array $body): array
    {
        return $this->request('PUT', '/api/participant/profile', $body);
    }

    /** @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>} */
    public function onboardingParticipant(): array
    {
        return $this->get('/api/participant/onboarding/participant');
    }

    /** @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>} */
    public function claimParticipant(int $participantId): array
    {
        return $this->request('POST', '/api/participant/participants/' . $participantId . '/claim', []);
    }

    /** @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>} */
    public function participantOrganizations(): array
    {
        return $this->get('/api/participant/organizations');
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    public function logout(): array
    {
        $result = $this->post('/api/auth/logout', []);
        Session::clearBackendCookie();

        return $result;
    }

    /**
     * @param array<string, mixed> $body
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    private function post(string $path, array $body): array
    {
        return $this->request('POST', $path, $body);
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    private function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /** @var list<string>|null */
    private static ?array $lastResponseHeaders = null;

    /**
     * @param array<string, mixed>|null $body
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
     */
    private function request(string $method, string $path, ?array $body = null): array
    {
        try {
            $baseUrl = rtrim((string) Config::get('backend.api_base_url', ''), '/');
            if ($baseUrl === '') {
                return [
                    'ok' => false,
                    'status' => 0,
                    'data' => null,
                    'error' => 'BACKEND_API_URL is not configured',
                ];
            }

            $url = $baseUrl . $path;
            $cookie = Session::getBackendCookie();

            if (function_exists('curl_init')) {
                return $this->decodeResponse($this->requestViaCurl($url, $method, $body, $cookie));
            }

            return $this->decodeResponse($this->requestViaStream($url, $method, $body, $cookie));
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 0,
                'data' => null,
                'error' => 'Backend request failed: ' . $e->getMessage(),
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
            curl_close($ch);
            throw new \RuntimeException($err !== '' ? $err : 'curl_exec failed');
        }

        curl_close($ch);
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
        /** @var list<string> $rawHeaders */
        $rawHeaders = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : [];
        self::$lastResponseHeaders = $rawHeaders;

        $status = 0;
        if (isset(self::$lastResponseHeaders[0]) && preg_match('#\s(\d{3})\s#', self::$lastResponseHeaders[0], $m)) {
            $status = (int) $m[1];
        }

        if ($responseBody === false) {
            throw new \RuntimeException('Could not reach backend at ' . $url);
        }

        return [
            'status' => $status,
            'body' => $responseBody,
            'headers' => self::$lastResponseHeaders,
        ];
    }

    /**
     * @param array{status: int, body: string|false, headers: list<string>} $transport
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, errors?: array<string, string>}
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
                'error' => 'Empty response from backend',
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
                'error' => 'Invalid JSON from backend',
            ];
        }

        $ok = $status >= 200 && $status < 300;
        $result = [
            'ok' => $ok,
            'status' => $status,
            'data' => $decoded,
            'error' => $ok ? null : (string) ($decoded['error'] ?? 'HTTP ' . $status),
        ];

        if (!$ok && is_array($decoded['errors'] ?? null)) {
            /** @var array<string, string> $fieldErrors */
            $fieldErrors = $decoded['errors'];
            $result['errors'] = $fieldErrors;
            $first = reset($fieldErrors);
            if (is_string($first) && $first !== '') {
                $result['error'] = $first;
            }
        }

        return $result;
    }

    /** @param array<string, mixed> $data */
    private function storeBackendSessionFromLoginResponse(array $data): void
    {
        $session = $data['session'] ?? null;
        if (!is_array($session)) {
            return;
        }

        $name = trim((string) ($session['name'] ?? ''));
        $id = trim((string) ($session['id'] ?? ''));
        if ($name !== '' && $id !== '') {
            Session::setBackendCookie($name . '=' . $id);
        }
    }

    private function captureSessionCookieFromLastResponse(): void
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
            if ($name === 'BIFROSTSESSID') {
                Session::setBackendCookie($name . '=' . trim($m[2]));
                break;
            }
        }
    }
}
