<?php

namespace App\Services\Insights;

use Illuminate\Validation\ValidationException;

class InsightValidator
{
    /**
     * Validate and clamp the structured AI output against the evidence package.
     *
     * The schema already guarantees the shape, but every value is re-checked
     * so a misbehaving model cannot smuggle in invented metrics or oversized
     * payloads. Evidence identifiers must exist in the supplied metrics.
     *
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $evidence
     * @return array{summary: string, findings: list<array<string, mixed>>, recommendations: list<array<string, mixed>>, limitations: list<string>}
     *
     * @throws ValidationException
     */
    public function validate(array $result, array $evidence): array
    {
        $summary = $result['summary'] ?? null;

        if (! is_string($summary) || trim($summary) === '') {
            throw ValidationException::withMessages([
                'summary' => 'The AI did not return a usable summary.',
            ]);
        }

        $summary = $this->clamp($summary, 2000);

        $allowedEvidence = $this->flattenEvidenceKeys($evidence);

        $findings = [];

        foreach ((array) ($result['findings'] ?? []) as $finding) {
            if (! is_array($finding)) {
                continue;
            }

            $title = is_string($finding['title'] ?? null) ? $this->clamp($finding['title'], 200) : '';
            $interpretation = is_string($finding['interpretation'] ?? null)
                ? $this->clamp($finding['interpretation'], 1000)
                : '';

            $confidence = $finding['confidence'] ?? 'low';

            if (! in_array($confidence, ['low', 'medium', 'high'], true)) {
                $confidence = 'low';
            }

            $cited = [];

            foreach ((array) ($finding['evidence'] ?? []) as $metric) {
                if (is_string($metric) && in_array($metric, $allowedEvidence, true)) {
                    $cited[] = $metric;
                }
            }

            if ($title !== '' && $interpretation !== '') {
                $findings[] = [
                    'title' => $title,
                    'evidence' => array_values(array_unique($cited)),
                    'interpretation' => $interpretation,
                    'confidence' => $confidence,
                ];
            }

            if (count($findings) >= 10) {
                break;
            }
        }

        $recommendations = [];

        foreach ((array) ($result['recommendations'] ?? []) as $recommendation) {
            if (! is_array($recommendation)) {
                continue;
            }

            $title = is_string($recommendation['title'] ?? null)
                ? $this->clamp($recommendation['title'], 200)
                : '';
            $reason = is_string($recommendation['reason'] ?? null)
                ? $this->clamp($recommendation['reason'], 500)
                : '';
            $actionType = is_string($recommendation['action_type'] ?? null)
                ? $this->clamp($recommendation['action_type'], 60)
                : 'human_review';

            if ($title !== '' && $reason !== '') {
                $recommendations[] = [
                    'title' => $title,
                    'reason' => $reason,
                    'action_type' => $actionType,
                ];
            }

            if (count($recommendations) >= 5) {
                break;
            }
        }

        $limitations = array_values(array_filter(array_map(
            fn ($limitation) => is_string($limitation) ? $this->clamp($limitation, 500) : null,
            (array) ($result['limitations'] ?? []),
        )));

        return [
            'summary' => $summary,
            'findings' => $findings,
            'recommendations' => $recommendations,
            'limitations' => array_slice($limitations, 0, 5),
        ];
    }

    /**
     * Flatten every metric identifier in the evidence so citations can be
     * checked against the actual evidence keys (both dotted and leaf forms).
     *
     * @param  array<string, mixed>  $evidence
     * @return list<string>
     */
    protected function flattenEvidenceKeys(array $evidence, string $prefix = ''): array
    {
        $keys = [];

        foreach ($evidence as $key => $value) {
            $full = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $keys = array_merge($keys, $this->flattenEvidenceKeys($value, $full));
            } else {
                $keys[] = $full;
                $keys[] = (string) $key;
            }
        }

        return array_values(array_unique($keys));
    }

    protected function clamp(string $value, int $limit): string
    {
        return mb_substr(trim($value), 0, $limit);
    }
}
