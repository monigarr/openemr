<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Load static guideline chunks from module `resources/guidelines/corpus.json`.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Guideline;

final class GuidelineChunkRepository
{
    private const RELATIVE_CORPUS = 'resources' . DIRECTORY_SEPARATOR . 'guidelines' . DIRECTORY_SEPARATOR . 'corpus.json';

    /** @var list<array<string,mixed>>|null */
    private ?array $cache = null;

    /**
     * @return list<array{chunk_id:string,source_id:string,title:string,section:string,text:string,keywords:list<string>}>
     */
    public function allChunks(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        $path = $this->moduleRoot() . DIRECTORY_SEPARATOR . self::RELATIVE_CORPUS;
        if (!is_readable($path)) {
            $this->cache = [];
            return $this->cache;
        }
        $raw = file_get_contents($path);
        if ($raw === false) {
            $this->cache = [];
            return $this->cache;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['chunks']) || !is_array($decoded['chunks'])) {
            $this->cache = [];
            return $this->cache;
        }
        $out = [];
        foreach ($decoded['chunks'] as $c) {
            if (!is_array($c)) {
                continue;
            }
            $id = isset($c['chunk_id']) && is_string($c['chunk_id']) ? $c['chunk_id'] : '';
            $sid = isset($c['source_id']) && is_string($c['source_id']) ? $c['source_id'] : '';
            $title = isset($c['title']) && is_string($c['title']) ? $c['title'] : '';
            $section = isset($c['section']) && is_string($c['section']) ? $c['section'] : '';
            $text = isset($c['text']) && is_string($c['text']) ? $c['text'] : '';
            $kw = $c['keywords'] ?? [];
            $kwList = [];
            if (is_array($kw)) {
                foreach ($kw as $k) {
                    if (is_string($k) && $k !== '') {
                        $kwList[] = strtolower($k);
                    }
                }
            }
            if ($id === '' || $text === '') {
                continue;
            }
            $out[] = [
                'chunk_id' => $id,
                'source_id' => $sid,
                'title' => $title,
                'section' => $section,
                'text' => $text,
                'keywords' => $kwList,
            ];
        }
        $this->cache = $out;
        return $this->cache;
    }

    private function moduleRoot(): string
    {
        return dirname(__DIR__, 3);
    }
}
