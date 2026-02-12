<?php

namespace Elaitech\Import\Services\ImageDownloader;

use App\Models\Product;
use Elaitech\Import\Services\Core\Abstracts\AbstractDownloadStrategy;
use Elaitech\Import\Services\Core\Contracts\DownloadStrategyInterface;
use Elaitech\Import\Services\Core\DTOs\ImageMetaDataData;
use Elaitech\Import\Services\Core\DTOs\ImportedImageData;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SerialDownloadStrategy extends AbstractDownloadStrategy implements DownloadStrategyInterface
{
    /**
     * @return ImportedImageData[]
     */
    public function download(array $urls, Product $product): array
    {
        $urls = collect($urls)->filter()->unique()->values();

        $existingMedia = $product
            ->getMedia(Product::MEDIA_COLLECTION)
            ->keyBy(fn (Media $media) => data_get($media->getCustomProperty('meta'), 'url'));

        return $urls->map(function (string $url) use ($product, $existingMedia) {
            if (! $this->isValidUrl($url)) {
                return null;
            }

            try {
                $metadata = $this->fetchImageMetaData($url, $product);
            } catch (Exception $e) {
                $this->logger->error('Failed to fetch image metadata', compact('url', 'product', 'e'));

                return null;
            }

            $media = $existingMedia->get($url);

            if ($media && $media->getCustomProperty('meta.hash') === $metadata->hash) {
                return new ImportedImageData(
                    url: $url,
                    metadata: $metadata,
                    action: 'keep',
                    media_id: $media->id
                );
            }

            return $this->downloadAndStoreImage($url, $product, $media, $metadata);
        })->filter()->values()->all();
    }

    private function downloadAndStoreImage(string $url, Product $product, ?Media $media, ImageMetaDataData $metadata): ImportedImageData
    {
        $action = $media ? 'replace' : 'create';

        $this->logger->info('Downloading image', compact('url', 'product', 'action', 'media', 'metadata'));

        $content = $this->fetchImageContent($url, $product);
        $localUrl = $this->saveImage($url, $product, $content);

        return new ImportedImageData(
            url: $url,
            metadata: $metadata,
            action: $action,
            local_url: $localUrl,
            media_id: $media?->id
        );
    }

    protected function fetchImageContent(string $url, Product $product): string
    {
        return tap($this->tryRequest('get', $url, $product), fn () => null)->getBody()->getContents();
    }

    private function fetchImageMetaData(string $url, Product $product): ImageMetaDataData
    {
        $response = $this->tryRequest('head', $url, $product);

        $headers = collect($response->getHeaders())
            ->map(fn ($v) => $v[0])
            ->mapWithKeys(fn ($v, $k) => [strtolower($k) => $v]);

        $meta = new ImageMetaDataData(
            url: $url,
            hash: '',
            etag: $headers->get('etag'),
            last_modified: $headers->get('last-modified'),
            content_length: isset($headers['content-length']) ? (int) $headers['content-length'] : null,
            content_type: $headers->get('content-type') // temporary, will calculate next
        );

        $meta->hash = md5(serialize($meta));

        return $meta;
    }

    private function tryRequest(string $method, string $url, Product $product)
    {
        try {
            return $this->client->$method($url);
        } catch (RequestException $e) {
            $this->logger->warning('SSL failed, retrying without verification', compact('url', 'product', 'e'));

            return $this->client->$method($url, ['verify' => false]);
        }
    }
}
