/**
 * Composable untuk menentukan warna & label berdasarkan skor KPI.
 * Mendukung dua sistem scoring:
 *   1. Legacy: skor 0–5 (dari task-based KpiCalculatorService)
 *   2. Baru:   achievement_rate % (dari kpi_results / kpi_reports)
 */
export function useKpiColor() {
    // ── Legacy 0-5 scoring ──────────────────────────────────────────────────
    function getColorClass(score) {
        if (score >= 4) return 'bg-emerald-500';
        if (score >= 3) return 'bg-blue-500';
        if (score >= 2) return 'bg-amber-500';
        return 'bg-red-500';
    }

    function getTextColorClass(score) {
        if (score >= 4) return 'text-emerald-600';
        if (score >= 3) return 'text-blue-600';
        if (score >= 2) return 'text-amber-600';
        return 'text-red-600';
    }

    /** Predikat dari skor 0–5 (dipakai di existing dashboards) */
    function getPredikat(score) {
        if (score >= 5)   return { label: 'Baik Sekali', color: 'success' };
        if (score >= 4)   return { label: 'Baik',        color: 'success' };
        if (score >= 3)   return { label: 'Cukup',       color: 'warning' };
        if (score >= 2)   return { label: 'Kurang',      color: 'warning' };
        return            { label: 'Buruk',              color: 'danger'  };
    }

    // ── New percentage-based scoring ─────────────────────────────────────────
    /**
     * Predikat dari achievement_rate (%).
     * <50%  → Bad     → danger
     * 50-80 → Average → warning
     * 80-100→ Good    → success
     * >100  → Excellent → info
     */
    function getPredikatPercentage(pct) {
        if (pct === null || pct === undefined) return { label: '-', color: 'default', scoreLabel: null };
        const p = Number(pct);
        if (p > 100) return { label: 'Excellent', color: 'info',    scoreLabel: 'excellent' };
        if (p >= 80) return { label: 'Good',      color: 'success', scoreLabel: 'good'      };
        if (p >= 50) return { label: 'Average',   color: 'warning', scoreLabel: 'average'   };
        return             { label: 'Bad',        color: 'danger',  scoreLabel: 'bad'        };
    }

    /** CSS classes untuk badge predikat percentage */
    function getBadgeClass(scoreLabel) {
        const map = {
            excellent: 'badge-info',
            good:      'badge-success',
            average:   'badge-warning',
            bad:       'badge-danger',
        };
        return map[scoreLabel] ?? 'badge-neutral';
    }

    /** Progress bar color class from achievement_rate */
    function getProgressClass(pct) {
        const p = Number(pct ?? 0);
        if (p > 100) return 'bg-blue-500';
        if (p >= 80) return 'bg-emerald-500';
        if (p >= 50) return 'bg-amber-500';
        return 'bg-red-500';
    }

    /** Format percentage to display string */
    function formatPct(val, decimals = 1) {
        if (val === null || val === undefined) return '-';
        return Number(val).toFixed(decimals) + '%';
    }

    return {
        getColorClass,
        getTextColorClass,
        getPredikat,
        getPredikatPercentage,
        getBadgeClass,
        getProgressClass,
        formatPct,
    };
}
