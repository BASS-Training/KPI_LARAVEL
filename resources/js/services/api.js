import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || '/api',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
    withCredentials: true,
});

// ─── Request interceptor ───────────────────────────────────────────────────
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// ─── Response interceptor ─────────────────────────────────────────────────
api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;

        if (status === 401) {
            // Token tidak valid atau kedaluwarsa — bersihkan dan arahkan ke login
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }

        if (status === 429) {
            // Terlalu banyak permintaan
            const retryAfter = error.response?.headers?.['retry-after'];
            const menit = retryAfter ? Math.ceil(retryAfter / 60) : '?';
            error.userMessage = `Terlalu banyak percobaan, coba lagi dalam ${menit} menit.`;
        }

        // Ekstrak pesan error yang ramah pengguna
        const apiMessage = error.response?.data?.message;
        error.userMessage = error.userMessage || apiMessage || 'Terjadi kesalahan. Coba lagi.';

        // Lempar kembali agar bisa ditangkap di store/komponen
        return Promise.reject(error);
    },
);

export default api;
