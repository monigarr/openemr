<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Fixed-dimension L2-normalized embedding vectors for guideline chunks and queries (offline, no API).
 *          Used as the dense leg of hybrid retrieval alongside lexical sparse scoring.
 *
 * Usage: Instantiate once; call embed() on query text and on each chunk's concatenated text + keywords.
 *
 * Example:
 *   $e = new DeterministicDenseEmbedder();
 *   $qv = $e->embed('hba1c diabetes');
 *
 * Dependencies: None (pure PHP).
 *
 * Security/PHI: Do not send real PHI through embed(); queries should be clinical topics only.
 * HIPAA: N/A — no PHI
 * FHIR: N/A
 * Accessibility: N/A — non-UI
 * Performance: O(tokens × DIM); corpus is tiny.
 * Stability: Deterministic across PHP versions for same inputs (crc32 + fixed DIM).
 * Legal/compliance: N/A
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Guideline;

final class DeterministicDenseEmbedder
{
    public const DIMENSION = 128;

    /**
     * @return list<float>
     */
    public function embed(string $text): array
    {
        $text = strtolower($text);
        $vec = array_fill(0, self::DIMENSION, 0.0);
        $parts = preg_split('/[^a-z0-9]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts)) {
            return $this->l2Normalize($vec);
        }
        foreach ($parts as $tok) {
            if (!is_string($tok) || strlen($tok) < 2) {
                continue;
            }
            for ($i = 0; $i < self::DIMENSION; $i++) {
                $h = crc32($tok . "\0" . $i);
                $vec[$i] += (($h & 1) === 1) ? 1.0 : -1.0;
            }
        }
        return $this->l2Normalize($vec);
    }

    /**
     * Cosine similarity for L2-normalized vectors equals the dot product.
     *
     * @param list<float> $a
     * @param list<float> $b
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n < 1) {
            return 0.0;
        }
        $sum = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $sum += $a[$i] * $b[$i];
        }
        return max(-1.0, min(1.0, $sum));
    }

    /**
     * @param list<float> $vec
     * @return list<float>
     */
    private function l2Normalize(array $vec): array
    {
        $sumSq = 0.0;
        foreach ($vec as $v) {
            $sumSq += $v * $v;
        }
        if ($sumSq < 1e-12) {
            $out = array_fill(0, self::DIMENSION, 0.0);
            $out[0] = 1.0;
            return $out;
        }
        $inv = 1.0 / sqrt($sumSq);
        $out = [];
        foreach ($vec as $v) {
            $out[] = $v * $inv;
        }
        return $out;
    }
}
