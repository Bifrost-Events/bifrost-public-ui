<?php

declare(strict_types=1);

namespace App\Service;

use App\Support\Config;

/**
 * HTTP-klient mot bifrost-events public API (V3).
 */
final class EventsApiClient
{
    /** @var list<string>|null */
    private static ?array $lastResponseHeaders = null;

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function publicContext(string $host): array
    {
        return $this->get('/api/public/context?host=' . rawurlencode($host));
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function publicCalendar(string $host): array
    {
        return $this->get('/api/public/calendar?host=' . rawurlencode($host));
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function publicEventSpace(string $host, int $spaceId): array
    {
        return $this->get('/api/public/event-spaces/' . $spaceId . '?host=' . rawurlencode($host));
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function publicSeriesForSpace(string $host, int $spaceId): array
    {
        return $this->get('/api/public/event-spaces/' . $spaceId . '/series?host=' . rawurlencode($host));
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function publicSeries(string $host, int $seriesId): array
    {
        return $this->get('/api/public/series/' . $seriesId . '?host=' . rawurlencode($host));
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function publicEvent(string $host, int $eventId): array
    {
        return $this->get('/api/public/events/' . $eventId . '?host=' . rawurlencode($host));
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function publicEventResults(string $host, int $eventId): array
    {
        return $this->get('/api/public/events/' . $eventId . '/results?host=' . rawurlencode($host));
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function publicSeriesStandings(string $host, int $seriesId): array
    {
        return $this->get('/api/public/series/' . $seriesId . '/standings?host=' . rawurlencode($host));
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function eventRegistrationsMe(string $host, int $eventId): array
    {
        return $this->request('GET', '/api/public/events/' . $eventId . '/registrations/me?host=' . rawurlencode($host), null, true);
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function createEventRegistration(string $host, int $eventId, int $personId): array
    {
        return $this->request('POST', '/api/public/events/' . $eventId . '/registrations?host=' . rawurlencode($host), [
            'person_id' => $personId,
        ], true);
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function myRegistrations(string $host, ?string $status = null, ?string $time = null, ?int $personId = null): array
    {
        $q = ['host' => $host];
        if ($status !== null && $status !== '') {
            $q['status'] = $status;
        }
        if ($time !== null && $time !== '') {
            $q['time'] = $time;
        }
        if ($personId !== null && $personId > 0) {
            $q['person_id'] = (string) $personId;
        }

        return $this->request('GET', '/api/public/me/registrations?' . http_build_query($q), null, true);
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function cancelRegistration(int $registrationId): array
    {
        return $this->request('POST', '/api/public/registrations/' . $registrationId . '/cancel', [], true);
    }

    /** @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string} */
    public function jaktfeltSlots(string $host, int $eventId): array
    {
        return $this->request('GET', '/api/public/jaktfelt/events/' . $eventId . '/slots?host=' . rawurlencode($host), null, false);
    }

    /** @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string} */
    public function jaktfeltRegistrationsMe(string $host, int $eventId): array
    {
        return $this->request('GET', '/api/public/jaktfelt/events/' . $eventId . '/registrations/me?host=' . rawurlencode($host), null, true);
    }

    /**
     * @param array<string, mixed> $body
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    public function createJaktfeltRegistration(string $host, int $eventId, array $body): array
    {
        return $this->request(
            'POST',
            '/api/public/jaktfelt/events/' . $eventId . '/registrations?host=' . rawurlencode($host),
            $body,
            true
        );
    }

    /** @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string} */
    public function cancelJaktfeltRegistration(int $registrationId): array
    {
        return $this->request('POST', '/api/public/jaktfelt/registrations/' . $registrationId . '/cancel', [], true);
    }

    /**
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    private function get(string $path): array
    {
        return $this->request('GET', $path, null, false);
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    private function request(string $method, string $path, ?array $body = null, bool $withAuth = false): array
    {
        try {
            $baseUrl = rtrim((string) Config::get('events.api_base_url', ''), '/');
            if ($baseUrl === '') {
                return [
                    'ok' => false,
                    'status' => 0,
                    'data' => null,
                    'error' => 'EVENTS_URL / Events API base URL is not configured',
                    'code' => 'config_error',
                ];
            }

            $url = $baseUrl . $path;
            $cookie = $withAuth ? \App\Support\Session::getAdminCookie() : '';

            if (function_exists('curl_init')) {
                return $this->decodeResponse($this->requestViaCurl($url, $method, $body, $cookie));
            }

            return $this->decodeResponse($this->requestViaStream($url, $method, $body, $cookie));
        } catch (\Throwable $e) {
            error_log('[EventsApiClient] ' . $e->getMessage());

            return [
                'ok' => false,
                'status' => 0,
                'data' => null,
                'error' => 'Events API request failed',
                'code' => 'transport_error',
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

        $headers = ['Accept: application/json'];
        if ($cookie !== '') {
            $headers[] = 'Cookie: ' . $cookie;
        }
        $payload = null;
        if ($body !== null) {
            $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $headers[] = 'Content-Type: application/json';
        }

        $responseHeaders = [];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $header) use (&$responseHeaders): int {
                $responseHeaders[] = $header;

                return strlen($header);
            },
            CURLOPT_TIMEOUT => 15,
        ]);
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $respBody = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($errno !== 0) {
            throw new \RuntimeException('curl error: ' . $err);
        }

        self::$lastResponseHeaders = $responseHeaders;

        return ['status' => $status, 'body' => $respBody, 'headers' => $responseHeaders];
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array{status: int, body: string|false, headers: list<string>}
     */
    private function requestViaStream(string $url, string $method, ?array $body, string $cookie): array
    {
        $header = "Accept: application/json\r\n";
        if ($cookie !== '') {
            $header .= 'Cookie: ' . $cookie . "\r\n";
        }
        $content = null;
        if ($body !== null) {
            $content = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $header .= "Content-Type: application/json\r\n";
        }

        $opts = [
            'method' => $method,
            'header' => $header,
            'ignore_errors' => true,
            'timeout' => 15,
        ];
        if ($content !== null) {
            $opts['content'] = $content;
        }

        $context = stream_context_create(['http' => $opts]);
        $respBody = file_get_contents($url, false, $context);
        $status = 0;
        $headers = $http_response_header ?? [];
        foreach ($headers as $line) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $line, $m)) {
                $status = (int) $m[1];
                break;
            }
        }
        self::$lastResponseHeaders = $headers;

        return ['status' => $status, 'body' => $respBody, 'headers' => $headers];
    }

    /**
     * @param array{status: int, body: string|false, headers: list<string>} $raw
     * @return array{ok: bool, status: int, data: array<string, mixed>|null, error: string|null, code?: string}
     */
    private function decodeResponse(array $raw): array
    {
        $status = (int) ($raw['status'] ?? 0);
        $body = $raw['body'] ?? false;
        if (!is_string($body) || $body === '') {
            error_log('[EventsApiClient] Empty response status=' . $status);

            return [
                'ok' => false,
                'status' => $status,
                'data' => null,
                'error' => 'Tomt svar fra Events API',
                'code' => 'empty_response',
            ];
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            error_log('[EventsApiClient] Invalid JSON status=' . $status . ' body=' . substr($body, 0, 200));

            return [
                'ok' => false,
                'status' => $status,
                'data' => null,
                'error' => 'Ugyldig JSON fra Events API',
                'code' => 'invalid_json',
            ];
        }

        if ($status >= 200 && $status < 300) {
            $data = $decoded['data'] ?? $decoded;
            if (!is_array($data)) {
                $data = [];
            }

            return [
                'ok' => true,
                'status' => $status,
                'data' => $data,
                'error' => null,
            ];
        }

        $error = $decoded['error'] ?? null;
        $message = 'Events API-feil';
        $code = 'api_error';
        if (is_array($error)) {
            $message = (string) ($error['message'] ?? $message);
            $code = (string) ($error['code'] ?? $code);
        } elseif (is_string($error) && $error !== '') {
            $message = $error;
        }

        error_log('[EventsApiClient] HTTP ' . $status . ' code=' . $code . ' message=' . $message);

        return [
            'ok' => false,
            'status' => $status,
            'data' => null,
            'error' => $message,
            'code' => $code,
        ];
    }
}
