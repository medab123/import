<?php

declare(strict_types=1);

if (! function_exists('arrayDiffRecursiveSpatie')) {
    /**
     * Recursively compare two arrays and return the differences.
     *
     * @param  array  $old  The old array to compare
     * @param  array  $new  The new array to compare
     * @return array Returns an array with 'attributes' (new values) and 'old' (old values) keys
     */
    function arrayDiffRecursiveSpatie(array $old, array $new): array
    {
        $attributes = [];
        $oldValues = [];

        foreach ($new as $key => $newValue) {
            $oldValue = $old[$key] ?? null;

            if (is_array($newValue) && is_array($oldValue)) {
                $diff = arrayDiffRecursiveSpatie($oldValue, $newValue);

                if (! empty($diff['attributes'])) {
                    $attributes[$key] = $diff['attributes'];
                    $oldValues[$key] = $diff['old'];
                }
            } elseif ($newValue !== $oldValue) {
                $attributes[$key] = $newValue;
                $oldValues[$key] = $oldValue;
            }
        }

        // Detect removed keys
        foreach ($old as $key => $oldValue) {
            if (! array_key_exists($key, $new)) {
                $attributes[$key] = null;
                $oldValues[$key] = $oldValue;
            }
        }

        return [
            'attributes' => $attributes,
            'old' => $oldValues,
        ];
    }
}
