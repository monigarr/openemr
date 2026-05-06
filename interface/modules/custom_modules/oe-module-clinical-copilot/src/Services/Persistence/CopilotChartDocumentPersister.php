<?php

/**
 * @version 0.1.0
 * @date 2026-05-05
 * @author Monica Peters <monica.peters@gfachallenger.gauntletai.com>
 *
 * Purpose: Optional persistence of copilot PDF uploads into OpenEMR `documents` via legacy `addNewDocument`.
 *
 * Usage: Enable with env `CLINICAL_COPILOT_PERSIST_UPLOADS=1`. Requires full OpenEMR bootstrap (e.g. copilot_request.php).
 *
 * Security/PHI: Same ACL/session as the request; copies file before ingest so extraction temp path remains valid.
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services\Persistence;

use OpenEMR\Core\OEGlobalsBag;

final class CopilotChartDocumentPersister
{
    public static function isEnabled(): bool
    {
        $v = getenv('CLINICAL_COPILOT_PERSIST_UPLOADS');
        return is_string($v) && ($v === '1' || strtolower($v) === 'true');
    }

    /**
     * @return array{ok:bool,doc_id?:int,error?:string}
     */
    public function persistPatientPdf(int $pid, string $sourceAbsolutePath, string $originalFilename): array
    {
        if ($pid < 1 || !is_readable($sourceAbsolutePath)) {
            return ['ok' => false, 'error' => 'invalid_input'];
        }
        $copyPdf = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ccop_chart_' . bin2hex(random_bytes(8)) . '.pdf';
        if (!@copy($sourceAbsolutePath, $copyPdf)) {
            return ['ok' => false, 'error' => 'copy_failed'];
        }
        $size = filesize($copyPdf);
        if ($size === false || $size < 1) {
            @unlink($copyPdf);
            return ['ok' => false, 'error' => 'empty_file'];
        }
        $projectDir = OEGlobalsBag::getInstance()->getProjectDir();
        require_once $projectDir . DIRECTORY_SEPARATOR . 'library' . DIRECTORY_SEPARATOR . 'documents.php';
        $categoryId = '1';
        if (function_exists('document_category_to_id')) {
            $lookups = ['Clinical', 'Patient Records', 'Categories'];
            foreach ($lookups as $title) {
                $cid = document_category_to_id($title);
                if ($cid !== false && $cid > 0) {
                    $categoryId = (string) $cid;
                    break;
                }
            }
        }
        $res = addNewDocument(
            $originalFilename,
            'application/pdf',
            $copyPdf,
            0,
            $size,
            '',
            (string) $pid,
            $categoryId,
            '',
            '1',
            false
        );
        if (is_file($copyPdf)) {
            @unlink($copyPdf);
        }
        if ($res === false || !is_array($res)) {
            return ['ok' => false, 'error' => 'add_new_document_failed'];
        }
        $docId = isset($res['doc_id']) ? (int) $res['doc_id'] : 0;
        if ($docId < 1) {
            return ['ok' => false, 'error' => 'missing_doc_id'];
        }
        return ['ok' => true, 'doc_id' => $docId];
    }
}
