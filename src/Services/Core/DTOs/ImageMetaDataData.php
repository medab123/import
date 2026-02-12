<?php

namespace Elaitech\Import\Services\Core\DTOs;

use Spatie\LaravelData\Data;

class ImageMetaDataData extends Data
{
    public function __construct(
        public string $url,
        public string $hash,
        public ?string $etag = null,
        public ?string $last_modified = null,
        public ?int $content_length = null,
        public ?string $content_type = null,
    ) {}
}
