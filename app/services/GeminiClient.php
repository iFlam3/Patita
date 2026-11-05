<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Cliente mínimo de la API de Gemini (generateContent) con cURL.
 * La clave vive solo en el servidor (.env: GEMINI_API_KEY) y viaja en la
 * cabecera x-goog-api-key, nunca en la URL ni hacia el navegador.
 */
final class GeminiClient
{
    private const FALLBACK_MODEL = 'gemini-flash-latest';
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    public function __construct(
        private string $apiKey,
        private string $model,
        private int $timeout = 12, // dos intentos caben en el límite de 30 s de PHP en hosting gratuito
    ) {
    }

    public static function fromConfig(): self
    {
        $c = (array) config('app.chat');

        return new self((string) $c['api_key'], (string) $c['model']);
    }

    /**
     * @param string                                          $system  Instrucciones del sistema.
     * @param list<array{role: 'user'|'model', text: string}> $history Conversación, la última entrada es del usuario.
     */
    public function reply(string $system, array $history): string
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY no está configurada.');
        }

        $payload = [
            'system_instruction' => ['parts' => [['text' => $system]]],
            'contents' => array_map(
                static fn (array $m): array => ['role' => $m['role'], 'parts' => [['text' => $m['text']]]],
                $history
            ),
            'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 600],
        ];

        // Si el modelo principal está saturado (503) o sin cuota (429), se intenta con el otro modelo.
        foreach (array_unique([$this->model, self::FALLBACK_MODEL]) as $model) {
            [$status, $data, $error] = $this->request($model, $payload);
            if ($status !== 503 && $status !== 429) {
                break;
            }
        }

        if ($data === null && $status === 0) {
            throw new RuntimeException('Sin conexión con Gemini: ' . $error);
        }
        if ($status !== 200 || !is_array($data)) {
            $detail = is_array($data) ? (string) ($data['error']['message'] ?? '') : '';
            throw new RuntimeException('Gemini respondió HTTP ' . $status . ($detail !== '' ? ': ' . $detail : ''));
        }

        $text = '';
        foreach ((array) ($data['candidates'][0]['content']['parts'] ?? []) as $part) {
            $text .= (string) ($part['text'] ?? '');
        }

        return trim($text);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{0: int, 1: mixed, 2: string} Estado HTTP, JSON decodificado y error de cURL.
     */
    private function request(string $model, array $payload): array
    {
        $ch = curl_init(sprintf(self::ENDPOINT, rawurlencode($model)));
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-goog-api-key: ' . $this->apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [$body === false ? 0 : $status, $body === false ? null : json_decode((string) $body, true), $error];
    }
}
