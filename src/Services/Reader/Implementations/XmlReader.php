<?php

declare(strict_types=1);

namespace Elaitech\Import\Services\Reader\Implementations;

use Elaitech\Import\Services\Core\DTOs\OptionDefinition;
use Elaitech\Import\Services\Core\Exceptions\ReaderException;
use Elaitech\Import\Services\Reader\Abstracts\AbstractReader;

final class XmlReader extends AbstractReader
{
    protected function doRead(string $contents, array $options): array
    {
        try {
            $array = self::convert($contents, $options);
        } catch (\Exception $e) {
            throw ReaderException::parsingFailed('XML', $e->getMessage());
        }

        return $array;
    }

    public static function convert(string $xml, array $options): array
    {
        $array = self::xmlStringToArray($xml, $options);

        if (! $options['keep_root'] && array_key_exists('@root', $array)) {
            unset($array['@root']);
        }

        return self::emptyArraysToNull($array);
    }

    public function getOptionDefinitions(): array
    {
        return [
            'keep_root' => new OptionDefinition(
                type: 'boolean',
                default: false,
                description: 'Keep root element in output'
            ),
            'entry_point' => new OptionDefinition(
                type: 'string',
                default: '',
                description: 'Dot notation path to extract data from (e.g., "inventory.listing")'
            ),
        ];
    }

    private static function xmlStringToArray(string $xmlString, array $options): array
    {
        $doc = new \DOMDocument;
        $doc->loadXML($xmlString);

        $xpath = new \DOMXPath($doc);

        if (! empty($options['entry_point'])) {
            $unitNodes = $xpath->query($options['entry_point']);
            $units = [];
            foreach ($unitNodes as $unitNode) {
                $units[] = self::domNodeToArray($unitNode);
            }

            return $units;
        }

        $output = self::domNodeToArray($doc);
        $root = $doc->documentElement;
        $output['@root'] = $root->tagName;

        return $output;
    }

    /**
     * Recursively convert empty arrays to null.
     */
    private static function emptyArraysToNull(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($value === []) {
                    $data[$key] = null;
                } else {
                    $data[$key] = self::emptyArraysToNull($value);
                }
            }
        }

        return $data;
    }

    private static function domNodeToArray(\DOMNode $node): array|string
    {
        $output = [];
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;
            case XML_ELEMENT_NODE:
                for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = self::domNodeToArray($child);
                    if (property_exists($child, 'tagName') && ! empty($child->tagName)) {
                        $t = (string) ($child->tagName ?? '');
                        if (! isset($output[$t])) {
                            $output[$t] = [];
                        }
                        $output[$t][] = $v;
                    } elseif ($v || $v === '0') {
                        $output = (string) $v;
                    }
                }
                if ($node->attributes->length && ! is_array($output)) { // Has attributes but isn't an array
                    $output = ['@content' => $output]; // Change output into an array.
                }
                if (is_array($output)) {
                    if ($node->attributes->length) {
                        $a = [];
                        foreach ($node->attributes as $attrName => $attrNode) {
                            $a[$attrName] = (string) $attrNode->value;
                        }
                        $output['@attributes'] = $a;
                    }
                    foreach ($output as $t => $v) {
                        if (is_array($v) && count($v) == 1 && $t != '@attributes') {
                            $output[$t] = $v[0];
                        }
                    }
                }
                break;
        }

        return $output;
    }
}
