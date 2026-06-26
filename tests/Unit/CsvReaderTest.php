<?php

declare(strict_types=1);

namespace Elaitech\Import\Tests\Unit;

use Elaitech\Import\Services\Reader\Implementations\CsvReader;
use PHPUnit\Framework\TestCase;

class CsvReaderTest extends TestCase
{
    private function csv(): string
    {
        return implode("\n", [
            'id|name',
            '1|Alpha',
            '2|Beta',
            '3|Gamma',
        ]);
    }

    public function test_parses_pipe_delimited_with_headers(): void
    {
        $rows = (new CsvReader)->read($this->csv(), ['delimiter' => '|']);

        $this->assertCount(3, $rows);
        $this->assertSame(['id', 'name'], array_keys($rows[0]));
        $this->assertSame('Alpha', $rows[0]['name']);
    }

    public function test_max_rows_caps_output_and_stops_early(): void
    {
        $rows = (new CsvReader)->read($this->csv(), ['delimiter' => '|', 'max_rows' => 2]);

        $this->assertCount(2, $rows);
        // Columns are still discoverable from the capped result (wizard use case).
        $this->assertSame(['id', 'name'], array_keys($rows[0]));
    }

    public function test_tab_delimiter_literal_is_translated_to_a_real_tab(): void
    {
        $tabCsv = "a\tb\n1\t2";
        $rows = (new CsvReader)->read($tabCsv, ['delimiter' => '\t']);

        $this->assertCount(1, $rows);
        $this->assertSame(['a' => '1', 'b' => '2'], $rows[0]);
    }
}
