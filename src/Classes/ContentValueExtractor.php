<?php

namespace Codebuster\GptBundle\Classes;

final class ContentValueExtractor
{
    private const MAX_DEPTH = 20;

    public static function extract($value): string
    {
        $parts = [];

        self::collect($value, $parts, 0);

        return implode(' ', $parts);
    }

    private static function collect($value, array &$parts, int $depth): void
    {
        if ($depth > self::MAX_DEPTH) {
            return;
        }

        if (is_string($value)) {
            $deserialized = self::deserialize($value);

            if ($deserialized !== $value) {
                self::collect($deserialized, $parts, $depth + 1);

                return;
            }

            $text = self::normalize($value);

            if ($text !== '') {
                $parts[] = $text;
            }

            return;
        }

        if (is_int($value) || is_float($value)) {
            $parts[] = (string) $value;

            return;
        }

        if (!is_array($value)) {
            return;
        }

        // Contao headlines contain a presentational unit and the actual value.
        if (array_key_exists('unit', $value) && array_key_exists('value', $value)) {
            self::collect($value['value'], $parts, $depth + 1);

            return;
        }

        foreach ($value as $item) {
            self::collect($item, $parts, $depth + 1);
        }
    }

    private static function deserialize(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '' || !preg_match('/^(?:N;|[aObisdC]:)/', $trimmed)) {
            return $value;
        }

        $deserialized = @unserialize($trimmed, ['allowed_classes' => false]);

        if ($deserialized === false && $trimmed !== 'b:0;') {
            return $value;
        }

        return $deserialized;
    }

    private static function normalize(string $value): string
    {
        if (!preg_match('//u', $value)) {
            return '';
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);
        $value = preg_replace('/[\s\x{00A0}]+/u', ' ', $value)
            ?? preg_replace('/\s+/', ' ', $value)
            ?? $value;

        return trim($value);
    }
}
