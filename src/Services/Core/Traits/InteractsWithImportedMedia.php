<?php

namespace Elaitech\Import\Services\Core\Traits;

use Elaitech\Import\Services\Core\DTOs\ImportedImageData;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait InteractsWithImportedMedia
{
    /** @param ImportedImageData[] $images */
    public function syncImportedMedia(array $images, string $collection): void
    {
        $images = collect($images);

        $protectedIds = $images
            ->whereIn('action', ['keep', 'replace'])
            ->pluck('media_id')
            ->filter()
            ->all();

        // 1️⃣ Delete media not present in import
        $this->getMedia($collection)
            ->reject(fn (Media $media) => in_array($media->id, $protectedIds, true))
            ->each->delete();

        // 2️⃣ Replace modified images
        $images
            ->where('action', 'replace')
            ->each(fn (ImportedImageData $image) => $this->storeMedia($image, $collection, true));

        // 3️⃣ Create new images
        $images
            ->where('action', 'create')
            ->each(fn (ImportedImageData $image) => $this->storeMedia($image, $collection));
    }

    private function storeMedia(
        ImportedImageData $image,
        string $collection,
        bool $deleteOld = false
    ): void {
        if ($deleteOld && $image->media_id) {
            optional(Media::find($image->media_id))->delete();
        }

        $this->addMedia($image->local_url)
            ->withCustomProperties([
                'source' => 'import',
                'meta' => $image->metadata,
            ])
            ->toMediaCollection($collection);
    }
}
