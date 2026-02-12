<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Downloader\Implementations;

use Elaitech\Import\Services\Core\DTOs\DownloadRequestData;
use Elaitech\Import\Services\Core\DTOs\DownloadResultData;
use Elaitech\Import\Services\Core\DTOs\OptionDefinition;
use Elaitech\Import\Services\Core\Exceptions\DownloaderException;
use Elaitech\Import\Services\Downloader\Abstracts\AbstractDownloader;
use Illuminate\Http\Client\Factory as HttpClient;
use Psr\Log\LoggerInterface;

final class HttpDownloader extends AbstractDownloader
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly LoggerInterface $logger,
    ) {}

    protected function doDownload(DownloadRequestData $request, array $options): DownloadResultData
    {
        $url = $options['source'] ?? '';

        try {
            $client = $this->buildHttpClient($options);
            $response = $this->makeRequest($client, $url, $options);
            $response->throw();

            $contents = (string) $response->body();
            $mimeType = (string) ($response->header('Content-Type') ?? 'application/octet-stream');

            $filename = $request->preferredFilename
                ?? $this->guessFilenameFromHeaders($response->header('Content-Disposition'))
                ?? basename(parse_url($url, PHP_URL_PATH) ?: 'download');

            $this->logger->info('HTTP download completed', [
                'url' => $url,
                'filename' => $filename,
                'size' => strlen($contents),
            ]);

            return new DownloadResultData(
                success: true,
                fileSize: (string) strlen($contents),
                filename: $filename,
                mimeType: $mimeType,
                contents: $contents,
            );
        } catch (\Exception $e) {
            throw DownloaderException::downloadFailed('HTTP', $e->getMessage());
        }
    }

    public function getOptionDefinitions(): array
    {
        return [
            'source' => new OptionDefinition(
                type: 'string',
                default: null,
                description: 'Source URL'
            ),
            'headers' => new OptionDefinition(
                type: 'array',
                default: [],
                description: 'HTTP headers'
            ),
            'timeout' => new OptionDefinition(
                type: 'integer',
                default: 30,
                description: 'Request timeout in seconds',
                minValue: 1,
                maxValue: 300
            ),
            'verify_ssl' => new OptionDefinition(
                type: 'boolean',
                default: true,
                description: 'Verify SSL certificates'
            ),
            'method' => new OptionDefinition(
                type: 'string',
                default: 'GET',
                description: 'HTTP method',
                allowedValues: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD']
            ),
            'accept' => new OptionDefinition(
                type: 'string',
                default: null,
                description: 'Accept header'
            ),
            'user_agent' => new OptionDefinition(
                type: 'string',
                default: null,
                description: 'User-Agent header'
            ),
            'bearer_token' => new OptionDefinition(
                type: 'string',
                default: null,
                description: 'Bearer token for authentication'
            ),
            'basic_auth' => new OptionDefinition(
                type: 'array',
                default: null,
                description: 'Basic auth credentials [username, password]'
            ),
            'query' => new OptionDefinition(
                type: 'array',
                default: [],
                description: 'Query parameters'
            ),
            'follow_redirects' => new OptionDefinition(
                type: 'boolean',
                default: true,
                description: 'Follow HTTP redirects'
            ),
            'retry' => new OptionDefinition(
                type: 'array',
                default: ['times' => 0, 'sleep' => 0],
                description: 'Retry configuration'
            ),
            'body' => new OptionDefinition(
                type: 'array',
                default: null,
                description: 'Request body configuration'
            ),
        ];
    }

    private function buildHttpClient(array $options): \Illuminate\Http\Client\PendingRequest
    {
        $client = $this->http->timeout($options['timeout'])->withHeaders($options['headers']);

        if ($options['accept']) {
            $client = $client->accept($options['accept']);
        }

        if ($options['user_agent']) {
            $client = $client->withHeaders(['User-Agent' => $options['user_agent']]);
        }

        if ($options['bearer_token']) {
            $client = $client->withToken($options['bearer_token']);
        }

        if (is_array($options['basic_auth'])) {
            $client = $client->withBasicAuth($options['basic_auth'][0] ?? '', $options['basic_auth'][1] ?? '');
        }

        if ($options['follow_redirects'] === false) {
            $client = $client->withoutRedirecting();
        }

        if (($options['retry']['times'] ?? 0) > 0) {
            $client = $client->retry((int) $options['retry']['times'], (int) ($options['retry']['sleep'] ?? 0));
        }

        if (! $options['verify_ssl']) {
            $client = $client->withoutVerifying();
        }

        if (! empty($options['query'])) {
            $client = $client->withQueryParameters($options['query']);
        }

        return $client;
    }

    private function makeRequest(\Illuminate\Http\Client\PendingRequest $client, string $url, array $options)
    {
        $method = strtoupper($options['method']);
        $body = $options['body'];

        if ($body === null) {
            return $client->{$method}($url);
        }

        return $this->sendWithBody($client, $method, $url, $body);
    }

    private function sendWithBody(\Illuminate\Http\Client\PendingRequest $client, string $method, string $url, array $body)
    {
        $type = $body['type'] ?? 'none';
        $data = $body['data'] ?? null;

        return match ($type) {
            'json' => $client->{$method}($url, is_string($data) ? json_decode($data, true) : (array) $data),
            'form' => $client->{$method}($url, (array) $data),
            'raw' => $client->withHeaders(
                array_filter([
                    'Content-Type' => $body['content_type'] ?? null,
                ])
            )->send($method, $url, ['body' => (string) $data]),
            default => $client->{$method}($url),
        };
    }
}
