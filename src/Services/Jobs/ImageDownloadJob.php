<?php

namespace Elaitech\Import\Services\Jobs;

use App\Models\Product;
use Elaitech\Import\Services\Core\Contracts\DownloadStrategyInterface;
use Elaitech\Import\Services\ImageDownloader\SerialDownloadStrategy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;

/**
 * Class ImageDownloadJob
 *
 * Handles image downloading for vehicles using either parallel or serial strategies.
 */
class ImageDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const string LOG_CHANNEL = 'import-image-download';

    private LoggerInterface $logger;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $productId, private array $urls)
    {
        $this->urls = $this->formatUrls($urls);
        $this->onQueue('import-pipelines-images');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            $this->logger = logger()->channel(self::LOG_CHANNEL);
            $product = Product::find($this->productId)?->load('media');
            if ($product) {
                $strategy = $this->determineStrategy();
                $images = $strategy->download($this->urls, $product);
                $product->syncImportedMedia($images, Product::MEDIA_COLLECTION);
            } else {
                $this->logger->error("Product with id {$this->productId} not found");
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), [$exception->getTrace()]);
        }

    }

    /**
     * Format URLs to replace spaces with URL-encoded characters.
     */
    private function formatUrls(array $urls): array
    {
        return array_map(fn ($url): string => str_replace(' ', '%20', trim($url)), $urls);
    }

    /**
     * Determine the download strategy to use based on the "multiple" flag.
     *
     * @param  bool  $multiple
     */
    private function determineStrategy(): DownloadStrategyInterface
    {
        return new SerialDownloadStrategy($this->logger);
    }
}
