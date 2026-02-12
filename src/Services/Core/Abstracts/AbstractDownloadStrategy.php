<?php

namespace Elaitech\Import\Services\Core\Abstracts;

use App\Models\Product;
use Elaitech\Import\Services\Core\Exceptions\InvalidImageException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Psr\Log\LoggerInterface;

/**
 * Abstract class AbstractDownloadStrategy
 *
 * Provides an abstract implementation for downloading and saving images.
 */
abstract class AbstractDownloadStrategy
{
    protected Client $client;

    protected LoggerInterface $logger;

    /**
     * AbstractDownloadStrategy constructor.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->client = $this->getClientInstance();
    }

    /**
     * Saves the downloaded image to storage.
     *
     * @param  string  $url  The URL of the image.
     * @param  Product  $product  The ID of the vehicle.
     * @param  string  $imageContent  The binary content of the image.
     * @return string The path to the saved image.
     *
     * @throws InvalidImageException If the downloaded file is not a valid image.
     */
    protected function saveImage(string $url, Product $product, string $imageContent): string
    {
        $imageInfo = getimagesizefromstring($imageContent);
        if ($imageInfo === false) {
            $this->logger->error('Downloaded file is not recognized as a valid image', [
                'url' => $url,
                'received_content' => substr($imageContent, 0, 500),
            ]);
            throw new InvalidImageException("Downloaded file is not a valid image: $url");
        }
        $url = explode('?', $url)[0];
        $extension = pathinfo($url, PATHINFO_EXTENSION);
        if (empty($extension)) {
            $extension = $this->mimeToExtension($imageInfo['mime']);
        }
        $imageName = pathinfo($url, PATHINFO_FILENAME).".$extension";
        $imagePath = "import/$product->id/$imageName";
        Storage::disk('local')->put($imagePath, $imageContent);

        return config('filesystems.disks.local.root').'/'.$imagePath;
    }

    /**
     * Validates the given URL.
     *
     * @param  string  $url  The URL to validate.
     * @return bool True if the URL is valid, false otherwise.
     */
    protected function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Converts a MIME type to a file extension.
     *
     * @param  string  $mime  The MIME type.
     * @return string The corresponding file extension.
     */
    private function mimeToExtension(string $mime): string
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
        ];

        return $mimeMap[$mime] ?? 'jpg';
    }

    /**
     * Create an instance of the Guzzle HTTP client with specific options.
     */
    private function getClientInstance(): Client
    {
        $config = [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko)',
            ],
            'allow_redirects' => true, // Follows redirects
            'timeout' => 60, // Total timeout for the request
            'connect_timeout' => 10, // Timeout to establish a connection
            'http_errors' => false, // Disables exceptions for 4xx and 5xx responses
            'verify' => true, // Ensures SSL certificate verification
            'curl' => [
                CURLOPT_SSL_CIPHER_LIST => 'DEFAULT:@SECLEVEL=1', // Legacy SSL cipher support
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
            ],
        ];

        return new Client($config);
    }

    /**
     * Downloads images from the given URLs and saves them.
     *
     * @param  array  $urls  The list of URLs to download.
     * @param  Product  $product  The ID of the product.
     * @return array An array of paths to the saved images.
     */
    abstract public function download(array $urls, Product $product): array;
}
