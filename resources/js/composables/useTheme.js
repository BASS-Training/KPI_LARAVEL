import { ref } from 'vue';

// Singleton dark state shared across the whole app ─────────────────────────
const stored = localStorage.getItem('theme');
const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
const isDark = ref(stored === 'dark' || (!stored && prefersDark));

function applyTheme() {
    document.documentElement.classList.toggle('dark', isDark.value);
}

// Apply immediately on module load
applyTheme();

export function useTheme() {
    function toggle() {
        isDark.value = !isDark.value;
        localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
        applyTheme();
    }

    return { isDark, toggle };
}
