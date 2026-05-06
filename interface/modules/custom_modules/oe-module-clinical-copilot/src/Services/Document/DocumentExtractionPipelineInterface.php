<?php

/**
 * @version 0.1.0
 * @date 2026-05-06
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Pluggable Week 2 document extraction (stub, Gemini, future VLM).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Document;

interface DocumentExtractionPipelineInterface
{
    /**
     * @param array{doc_type:string,path:string,original_filename:string} $pending
     * @return array{ok:bool,error?:string,lab_rows?:list<array<string,mixed>>,intake?:array<string,mixed>}
     */
    public function process(int $pid, array $pending): array;
}
