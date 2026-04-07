/**
 * Composable untuk menentukan warna berdasarkan skor KPI
 * Dipakai di KpiBarRow dan tempat lain yang perlu warna semantik KPI
 */
export function useKpiColor() {
    /**
     * Kembalikan kelas Tailwind berdasarkan skor (0–5)
     */
    function getColorClass(score) {
        if (score >= 4) return 'bg-green-500';
        if (score >= 3) return 'bg-blue-500';
        if (score >= 2) return 'bg-amber-500';
        return 'bg-red-500';
    }

    function getTextColorClass(score) {
        if (score >= 4) return 'text-green-600';
        if (score >= 3) return 'text-blue-600';
        if (score >= 2) return 'text-amber-600';
        return 'text-red-600';
    }

    /**
     * Kembalikan label predikat berdasarkan nilai KPI (0–100)
     */
    function getPredikat(nilai) {
        if (nilai >= 90) return { label: 'Baik Sekali', color: 'success' };
        if (nilai >= 75) return { label: 'Baik', color: 'success' };
        if (nilai >= 60) return { label: 'Cukup', color: 'warning' };
        return { label: 'Buruk', color: 'danger' };
    }

    return { getColorClass, getTextColorClass, getPredikat };
}
