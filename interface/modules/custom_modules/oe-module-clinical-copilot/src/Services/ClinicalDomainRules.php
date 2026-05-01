<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * Post-citation domain constraints (dosing, imperatives, definitive diagnosis tone).
 *
 * @package OpenEMR\Modules\ClinicalCopilot
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

final class ClinicalDomainRules
{
    private const POLYPHARMACY_THRESHOLD = 12;

    /**
     * @param array{statements:list<array{text:string,citations:list<string>}>,uncertainties:list<string>,stripped:list<string>} $verified
     * @param array<string,mixed> $mergedToolData
     * @return array{statements:list<array{text:string,citations:list<string>}>,uncertainties:list<string>,stripped:list<string>,domain_stripped:list<string>}
     */
    public function apply(array $verified, array $mergedToolData): array
    {
        $domainStripped = [];
        $medCount = $this->countMedications($mergedToolData);
        $outStatements = [];
        foreach ($verified['statements'] as $st) {
            $text = $st['text'];
            if ($this->violatesDosingOrImperative($text)) {
                $domainStripped[] = $text;
                continue;
            }
            if ($this->looksLikeDefinitiveNewDiagnosis($text)) {
                $domainStripped[] = $text;
                continue;
            }
            if ($medCount >= self::POLYPHARMACY_THRESHOLD && $this->looksLikeMedicationInstructionWithoutStrongCitation($text, $st['citations'])) {
                $domainStripped[] = $text;
                continue;
            }
            $outStatements[] = $st;
        }
        $unc = $verified['uncertainties'];
        if ($medCount >= self::POLYPHARMACY_THRESHOLD) {
            $unc[] = 'Polypharmacy context: verify all medications and adherence with the patient.';
        }
        return [
            'statements' => $outStatements,
            'uncertainties' => $unc,
            'stripped' => $verified['stripped'],
            'domain_stripped' => $domainStripped,
        ];
    }

    private function countMedications(array $mergedToolData): int
    {
        $chart = $mergedToolData['chart_lists'] ?? null;
        if (!is_array($chart)) {
            return 0;
        }
        $m = $chart['medications'] ?? [];
        return is_array($m) ? count($m) : 0;
    }

    /**
     * @param list<string> $citations
     */
    private function looksLikeMedicationInstructionWithoutStrongCitation(string $text, array $citations): bool
    {
        if (!$this->containsMedicationActionLanguage($text)) {
            return false;
        }
        foreach ($citations as $c) {
            if (is_string($c) && str_contains($c, 'medications')) {
                return false;
            }
        }
        return true;
    }

    private function containsMedicationActionLanguage(string $text): bool
    {
        $t = strtolower($text);
        foreach (['start ', 'stop ', 'increase ', 'decrease ', 'titrate', 'twice daily', 'qd', 'bid', 'tid', 'mg/kg'] as $p) {
            if (str_contains($t, $p)) {
                return true;
            }
        }
        return (bool) preg_match('/\b\d+\s*(mg|mcg|g)\b/i', $t);
    }

    private function violatesDosingOrImperative(string $text): bool
    {
        $t = strtolower($text);
        if (preg_match('/\b(take|give|prescribe|order)\b.+\b(mg|mcg|units)\b/i', $t)) {
            return true;
        }
        if (preg_match('/\b\d+\s*(mg|mcg)\s*(daily|twice|three times|every)/i', $t)) {
            return true;
        }
        foreach (['start the patient on', 'stop the', 'discontinue the', 'increase the dose', 'decrease the dose'] as $p) {
            if (str_contains($t, $p)) {
                return true;
            }
        }
        return false;
    }

    private function looksLikeDefinitiveNewDiagnosis(string $text): bool
    {
        $t = $text;
        if (preg_match('/\b(you have|patient has|definitive diagnosis|new diagnosis of)\b/i', $t)) {
            return true;
        }
        return false;
    }
}
