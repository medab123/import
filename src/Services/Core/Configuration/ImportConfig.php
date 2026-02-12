<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Core\Configuration;

final class ImportConfig
{
    private static ?self $instance = null;

    private array $config = [];

    private function __construct()
    {
        $this->loadDefaultConfig();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->config);
    }

    public function merge(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }

    public function all(): array
    {
        return $this->config;
    }

    private function loadDefaultConfig(): void
    {
        $this->config = [
            'default_timeout' => 30,
            'max_file_size' => 10 * 1024 * 1024, // 10MB
            'allowed_mime_types' => [
                'text/csv',
                'application/json',
                'text/xml',
                'application/xml',
                'text/plain',
            ],
            'cache_ttl' => 3600, // 1 hour
            'retry_attempts' => 3,
            'retry_delay' => 1000, // milliseconds
        ];
    }

    /**
     * Get option definitions for a specific service type.
     */
    public function getOptionDefinitions(string $serviceType): array
    {
        return $this->get("option_definitions.{$serviceType}", []);
    }

    /**
     * Set option definitions for a specific service type.
     */
    public function setOptionDefinitions(string $serviceType, array $definitions): void
    {
        $this->config["option_definitions.{$serviceType}"] = $definitions;
    }

    /**
     * Validate configuration values.
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->get('default_timeout') < 1) {
            $errors[] = 'default_timeout must be greater than 0';
        }

        if ($this->get('max_file_size') < 1) {
            $errors[] = 'max_file_size must be greater than 0';
        }

        if ($this->get('retry_attempts') < 0) {
            $errors[] = 'retry_attempts must be non-negative';
        }

        return $errors;
    }
}
