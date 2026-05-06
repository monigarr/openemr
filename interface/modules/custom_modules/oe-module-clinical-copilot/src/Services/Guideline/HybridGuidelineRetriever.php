<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Keyword + lexical overlap retrieval over bundled guideline corpus, then rerank.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Guideline;

final class HybridGuidelineRetriever
{
    private const SPARSE_WEIGHT = 0.45;

    private const DENSE_WEIGHT = 0.55;

    public function __construct(
        private readonly GuidelineChunkRepository $repository,
        private readonly RerankerInterface $reranker,
        private readonly DeterministicDenseEmbedder $denseEmbedder = new DeterministicDenseEmbedder(),
    ) {
    }

    /**
     * @return array{chunks:list<array<string,mixed>>,query:string,retrieval_ms:float,hybrid_note?:string}
     */
    public function retrieve(string $query, int $candidateLimit = 5, int $finalLimit = 3): array
    {
        $t0 = microtime(true);
        $q = strtolower(trim($query));
        if ($q === '') {
            return ['chunks' => [], 'query' => $query, 'retrieval_ms' => (microtime(true) - $t0) * 1000];
        }
        $tokens = self::tokens($q);
        $queryVec = $this->denseEmbedder->embed($q);
        $scored = [];
        $maxSparse = 0.0;
        $maxDense = 0.0;
        foreach ($this->repository->allChunks() as $chunk) {
            $hay = strtolower($chunk['text'] . ' ' . implode(' ', $chunk['keywords']));
            $kw = self::tokenOverlapScore($tokens, self::tokens($hay));
            $overlap = self::bigramOverlap($q, $hay);
            $sparse = $kw * 2.0 + $overlap;
            $chunkVec = $this->denseEmbedder->embed($hay);
            $dense = $this->denseEmbedder->cosineSimilarity($queryVec, $chunkVec);
            if ($sparse > $maxSparse) {
                $maxSparse = $sparse;
            }
            if ($dense > $maxDense) {
                $maxDense = $dense;
            }
            $row = $chunk;
            $row['retrieval_sparse'] = $sparse;
            $row['retrieval_dense'] = $dense;
            $scored[] = $row;
        }
        $normS = $maxSparse > 1e-9 ? $maxSparse : 1.0;
        $normD = $maxDense > 1e-9 ? $maxDense : 1.0;
        foreach ($scored as &$row) {
            $sN = ($row['retrieval_sparse'] ?? 0.0) / $normS;
            $dN = ($row['retrieval_dense'] ?? 0.0) / $normD;
            $row['retrieval_score'] = self::SPARSE_WEIGHT * $sN + self::DENSE_WEIGHT * $dN;
            unset($row['retrieval_sparse'], $row['retrieval_dense']);
        }
        unset($row);
        usort($scored, static fn ($a, $b) => ($b['retrieval_score'] <=> $a['retrieval_score']));
        $candidates = array_slice($scored, 0, max(1, min($candidateLimit, count($scored))));
        foreach ($candidates as &$c) {
            unset($c['retrieval_score']);
        }
        unset($c);
        $reranked = $this->reranker->rerank($query, $candidates);
        $final = array_slice($reranked, 0, max(1, $finalLimit));
        return [
            'chunks' => $final,
            'query' => $query,
            'retrieval_ms' => (microtime(true) - $t0) * 1000,
            'hybrid_note' => 'sparse_lexical+dense_deterministic',
        ];
    }

    /**
     * @return list<string>
     */
    private static function tokens(string $s): array
    {
        $parts = preg_split('/[^a-z0-9]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts)) {
            return [];
        }
        $out = [];
        foreach ($parts as $p) {
            if (is_string($p) && strlen($p) > 1) {
                $out[] = $p;
            }
        }
        return $out;
    }

    /**
     * @param list<string> $queryTok
     * @param list<string> $docTok
     */
    private static function tokenOverlapScore(array $queryTok, array $docTok): float
    {
        if ($queryTok === [] || $docTok === []) {
            return 0.0;
        }
        $set = array_fill_keys($docTok, true);
        $hit = 0;
        foreach ($queryTok as $t) {
            if (isset($set[$t])) {
                $hit++;
            }
        }
        return $hit / max(1, count($queryTok));
    }

    private static function bigramOverlap(string $a, string $b): float
    {
        $ba = self::bigrams($a);
        $bb = self::bigrams($b);
        if ($ba === [] || $bb === []) {
            return 0.0;
        }
        $set = array_fill_keys($bb, true);
        $hit = 0;
        foreach ($ba as $g) {
            if (isset($set[$g])) {
                $hit++;
            }
        }
        return $hit / max(1, count($ba));
    }

    /**
     * @return list<string>
     */
    private static function bigrams(string $s): array
    {
        $s = preg_replace('/[^a-z0-9]+/', '', $s) ?? '';
        if (strlen($s) < 2) {
            return [];
        }
        $out = [];
        $len = strlen($s);
        for ($i = 0; $i < $len - 1; $i++) {
            $out[] = substr($s, $i, 2);
        }
        return $out;
    }
}
