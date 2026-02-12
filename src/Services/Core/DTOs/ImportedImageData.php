<?php

namespace Elaitech\Import\Services\Core\DTOs;

use Spatie\LaravelData\Data;

class ImportedImageData extends Data
{
    public function __construct(
        public string $url,
        public ImageMetaDataData $metadata,
        public string $action, // 'keep', 'replace', 'create'
        public ?string $local_url = null,
        public ?int $media_id = null,
    ) {}
}
