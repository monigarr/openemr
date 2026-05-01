<?php

/**
 * SPDX-License-Identifier: GPL-3.0-only
 *
 * @file VerificationGate.php
 *
 * Strips or downgrades LLM statements whose citations do not resolve against tool JSON (dot paths).
 *
 * Module: Clinical Co-Pilot (`oe-module-clinical-copilot`, namespace OpenEMR\Modules\ClinicalCopilot).
 *
 *
 * @author    Monica Peters <monigarr@monigarr.com> GauntletAI.com
 * @version   0.1.0
 * @since     2026-04-30
 *
 * Usage:
 * Pure PHP: invoke `verify($toolData, $parsed)` in unit tests without full OpenEMR bootstrap.
 *
 * Usage example (integrator):
 * Feed the same JSON structure the model was prompted to cite; assert stripped lines in PHPUnit fixtures.
 *
 * @package    OpenEMR\Modules\ClinicalCopilot
 * @subpackage Services
 * @license    https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 * @link       https://www.open-emr.org/wiki/index.php/Developers#Custom_Modules
 * @see        README.md AgentOrchestrator.php
 */

declare(strict_types=1);

namespace OpenEMR\Modules\ClinicalCopilot\Services;

/**
 * Pure PHP — safe to unit test without OpenEMR bootstrap.
 */
final class VerificationGate
{
    /**
     * @param array<string,mixed> $toolData Canonical facts the model may cite (dot-path roots).
     * @param array{statements?:list<array{text?:string,citations?:list<string>}>,uncertainties?:list<string>} $parsed
     * @return array{statements:list<array{text:string,citations:list<string>}>,uncertainties:list<string>,stripped:list<string>}
     */
    public function verify(array $toolData, array $parsed): array
    {
        $outStatements = [];
        $uncertainties = $parsed['uncertainties'] ?? [];
        if (!is_array($uncertainties)) {
            $uncertainties = [];
        }
        $stripped = [];
        $statements = $parsed['statements'] ?? [];
        if (!is_array($statements)) {
            return ['statements' => [], 'uncertainties' => $this->stringList($uncertainties), 'stripped' => ['(no statements array)']];
        }

        foreach ($statements as $row) {
            if (!is_array($row)) {
                continue;
            }
            $text = isset($row['text']) && is_string($row['text']) ? trim($row['text']) : '';
            if ($text === '') {
                continue;
            }
            $citations = $row['citations'] ?? [];
            if (!is_array($citations) || $citations === []) {
                $stripped[] = $text;
                continue;
            }
            $ok = true;
            $cleanCites = [];
            foreach ($citations as $c) {
                if (!is_string($c) || $c === '') {
                    $ok = false;
                    break;
                }
                if (!$this->citationResolves($toolData, $c)) {
                    $ok = false;
                    break;
                }
                $cleanCites[] = $c;
            }
            if ($ok) {
                $outStatements[] = ['text' => $text, 'citations' => $cleanCites];
            } else {
                $stripped[] = $text;
            }
        }

        return [
            'statements' => $outStatements,
            'uncertainties' => $this->stringList($uncertainties),
            'stripped' => $stripped,
        ];
    }

    /**
     * @param list<string> $lines
     */
    public function formatForDisplay(array $verified): string
    {
        $parts = [];
        foreach ($verified['statements'] as $s) {
            $parts[] = $s['text'];
        }
        foreach ($verified['uncertainties'] as $u) {
            $parts[] = '(' . $u . ')';
        }
        if ($verified['stripped'] !== []) {
            $parts[] = '[Unverified claims removed: ' . count($verified['stripped']) . ']';
        }
        return implode("\n\n", array_filter($parts, fn ($p) => $p !== ''));
    }

    /**
     * @param mixed $uncertainties
     * @return list<string>
     */
    private function stringList(mixed $uncertainties): array
    {
        $out = [];
        if (!is_array($uncertainties)) {
            return $out;
        }
        foreach ($uncertainties as $u) {
            if (is_string($u) && $u !== '') {
                $out[] = $u;
            }
        }
        return $out;
    }

    /**
     * Dot-path e.g. "patient.fname" or "allergies.0.title"
     *
     * @param array<string,mixed> $root
     */
    public function citationResolves(array $root, string $path): bool
    {
        $segments = explode('.', $path);
        $cur = $root;
        foreach ($segments as $seg) {
            if ($cur === null) {
                return false;
            }
            if (is_array($cur) && array_is_list($cur)) {
                if (!ctype_digit($seg)) {
                    return false;
                }
                $idx = (int) $seg;
                if (!array_key_exists($idx, $cur)) {
                    return false;
                }
                $cur = $cur[$idx];
                continue;
            }
            if (!is_array($cur) || !array_key_exists($seg, $cur)) {
                return false;
            }
            $cur = $cur[$seg];
        }
        return $cur !== null && (!is_string($cur) || $cur !== '');
    }
}
