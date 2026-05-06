<?php

/**
 * CLI: Emit deterministic dense-embedding manifest for bundled guideline corpus (PRD hybrid RAG audit).
 *
 * Usage (repo root):
 *   php interface/modules/custom_modules/oe-module-clinical-copilot/resources/guidelines/build_dense_manifest.php
 *
 * Writes `corpus_dense_manifest.json` next to corpus.json with per-chunk vector checksums (no PHI).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

$root = dirname(__DIR__, 6);
require_once $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use OpenEMR\Modules\ClinicalCopilot\Services\Guideline\DeterministicDenseEmbedder;
use OpenEMR\Modules\ClinicalCopilot\Services\Guideline\GuidelineChunkRepository;

$repo = new GuidelineChunkRepository();
$embedder = new DeterministicDenseEmbedder();
$rows = [];
foreach ($repo->allChunks() as $chunk) {
    $hay = strtolower($chunk['text'] . ' ' . implode(' ', $chunk['keywords']));
    $vec = $embedder->embed($hay);
    $checksum = hash('sha256', implode(',', array_map(static fn (float $f): string => sprintf('%.8f', $f), $vec)));
    $rows[] = [
        'chunk_id' => $chunk['chunk_id'],
        'embedding_dim' => DeterministicDenseEmbedder::DIMENSION,
        'vector_sha256' => $checksum,
    ];
}
$manifest = [
    'version' => 1,
    'generated_at' => gmdate('c'),
    'embedder' => 'DeterministicDenseEmbedder',
    'chunks' => $rows,
];
$out = __DIR__ . DIRECTORY_SEPARATOR . 'corpus_dense_manifest.json';
file_put_contents($out, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "Wrote {$out}\n";
