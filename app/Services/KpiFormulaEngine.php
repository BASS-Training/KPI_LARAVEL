<?php

namespace App\Services;

use App\Models\KpiIndicator;

/**
 * Safe formula engine for KPI score calculation.
 *
 * Supported formula types (stored as JSON on kpi_indicators.formula):
 *
 *  percentage   (default) — proportional: (actual/target) * weight
 *  conditional            — full weight if achieved, else proportional
 *  threshold              — step scoring via thresholds array
 *  zero_penalty           — full weight if actual == 0 (zero violations)
 *  flat                   — fixed multiplier of weight
 *
 * Formula JSON examples:
 *  {"type":"percentage"}
 *  {"type":"conditional"}
 *  {"type":"threshold","thresholds":[{"min_pct":100,"score_pct":100},{"min_pct":90,"score_pct":80},...]}
 *  {"type":"zero_penalty"}
 *  {"type":"flat","score":0.8}
 *
 * Note: eval() is never used. All logic is handled via pure PHP math + match.
 */
final class KpiFormulaEngine
{
    /**
     * Evaluate the indicator score.
     *
     * @return float Score in range [0, weight]
     */
    public function evaluate(KpiIndicator $indicator, float $actualValue, float $targetValue): float
    {
        $formula = is_array($indicator->formula) ? $indicator->formula : [];
        $type    = $formula['type'] ?? 'percentage';
        $weight  = max(0.0, (float) $indicator->weight);

        if ($weight <= 0) {
            return 0.0;
        }

        $raw = match ($type) {
            'conditional'  => $this->calcConditional($actualValue, $targetValue, $weight),
            'threshold'    => $this->calcThreshold($actualValue, $targetValue, $weight, $formula),
            'zero_penalty' => $this->calcZeroPenalty($actualValue, $weight),
            'flat'         => $this->calcFlat($weight, $formula),
            default        => $this->calcPercentage($actualValue, $targetValue, $weight),
        };

        return round(min($weight, max(0.0, $raw)), 2);
    }

    /**
     * Compute achievement ratio for display (0–1) based on the formula result.
     */
    public function achievementRatio(KpiIndicator $indicator, float $actualValue, float $targetValue): float
    {
        $weight = (float) $indicator->weight;

        if ($weight <= 0) {
            return 0.0;
        }

        $score = $this->evaluate($indicator, $actualValue, $targetValue);

        return round(min(1.0, $score / $weight), 4);
    }

    // ─── Private calculators ────────────────────────────────────────────────

    /** (actual / target) × weight */
    private function calcPercentage(float $actual, float $target, float $weight): float
    {
        if ($target <= 0) {
            return 0.0;
        }

        return ($actual / $target) * $weight;
    }

    /** Full weight if actual ≥ target, else proportional */
    private function calcConditional(float $actual, float $target, float $weight): float
    {
        if ($target <= 0) {
            return $actual > 0 ? $weight : 0.0;
        }

        return $actual >= $target ? $weight : ($actual / $target) * $weight;
    }

    /**
     * Step-based scoring.
     *
     * thresholds: [{"min_pct": 100, "score_pct": 100}, {"min_pct": 90, "score_pct": 80}, ...]
     * Sorted descending by min_pct; first match wins.
     */
    private function calcThreshold(float $actual, float $target, float $weight, array $formula): float
    {
        $thresholds = $formula['thresholds'] ?? [];

        if (empty($thresholds)) {
            return $this->calcPercentage($actual, $target, $weight);
        }

        $pct = $target > 0 ? ($actual / $target) * 100.0 : 0.0;

        usort($thresholds, static fn ($a, $b) => ($b['min_pct'] ?? 0) <=> ($a['min_pct'] ?? 0));

        foreach ($thresholds as $step) {
            if ($pct >= ($step['min_pct'] ?? 0)) {
                return $weight * ((float) ($step['score_pct'] ?? 0) / 100.0);
            }
        }

        return 0.0;
    }

    /** Full weight if violations (actual) = 0, otherwise 0 */
    private function calcZeroPenalty(float $actual, float $weight): float
    {
        return $actual <= 0 ? $weight : 0.0;
    }

    /** Fixed multiplier: weight × formula["score"] (default 1.0) */
    private function calcFlat(float $weight, array $formula): float
    {
        return $weight * min(1.0, max(0.0, (float) ($formula['score'] ?? 1.0)));
    }

    // ─── Default threshold presets ──────────────────────────────────────────

    /** Standard 5-tier threshold used by many company KPIs */
    public static function defaultThresholds(): array
    {
        return [
            ['min_pct' => 100, 'score_pct' => 100],
            ['min_pct' => 90,  'score_pct' => 80],
            ['min_pct' => 70,  'score_pct' => 60],
            ['min_pct' => 50,  'score_pct' => 40],
            ['min_pct' => 0,   'score_pct' => 0],
        ];
    }
}
