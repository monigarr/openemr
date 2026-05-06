<?php

/**
 * @version 0.1.0
 * @date 2026-05-06
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Select document extraction pipeline from environment (`stub` default, `gemini` when key present).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Document;

final class DocumentExtractionPipelineFactory
{
    public static function createDefault(): DocumentExtractionPipelineInterface
    {
        $modeRaw = getenv('CLINICAL_COPILOT_EXTRACTION_PIPELINE');
        $mode = is_string($modeRaw) ? strtolower(trim($modeRaw)) : 'stub';
        if ($mode === 'gemini') {
            $key = getenv('CLINICAL_COPILOT_GEMINI_API_KEY');
            if (is_string($key) && $key !== '') {
                $modelRaw = getenv('CLINICAL_COPILOT_GEMINI_MODEL');
                $model = is_string($modelRaw) && $modelRaw !== '' ? $modelRaw : 'gemini-1.5-flash';
                return new GeminiFlashDocumentExtractionPipeline($key, $model);
            }
        }
        return new StubDocumentExtractionPipeline();
    }
}
