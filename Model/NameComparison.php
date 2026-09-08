<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model;

/**
 * Compares the name a consumer declared on the withdrawal form with the name
 * stored on the order. Customer data contains all kinds of spelling variants
 * ("Max Karl-Mustermann", "max karl mustermann", "Mustermann Max"), which must
 * never invalidate a withdrawal, so the comparison only reports a difference
 * when the name parts themselves differ.
 */
class NameComparison
{
    /**
     * True when both names consist of the same parts, ignoring case,
     * punctuation, repeated whitespace, and the order of the parts.
     */
    public function isSame(string $first, string $second): bool
    {
        return $this->normalizeParts($first) === $this->normalizeParts($second);
    }

    /**
     * Reduces a name to a sorted list of lowercase word parts.
     *
     * @return string[]
     */
    private function normalizeParts(string $name): array
    {
        $normalized = preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($name, 'UTF-8'));
        $parts = preg_split('/\s+/', trim((string) $normalized), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        sort($parts);
        return $parts;
    }
}
