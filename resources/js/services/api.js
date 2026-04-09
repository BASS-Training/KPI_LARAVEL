import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || '/api',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
    withCredentials: true,
});

function extractFilename(contentDisposition, fallback) {
    if (!contentDisposition) return fallback;

    const utf8Match = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i);
    if (utf8Match?.[1]) {
        return decodeURIComponent(utf8Match[1]);
    }

    const plainMatch = contentDisposition.match(/filename="?([^"]+)"?/i);
    if (plainMatch?.[1]) {
        return plainMatch[1];
    }

    return fallback;
}

export async function downloadFile(url, options = {}) {
    const {
        params,
        method = 'get',
        data,
        fallbackFilename = 'download',
    } = options;

    let response;

    try {
        response = await api.request({
            url,
            method,
            params,
            data,
            responseType: 'blob',
        });
    } catch (error) {
        const blob = error.response?.data;

        if (blob instanceof Blob && blob.type?.includes('application/json')) {
            try {
                const payload = JSON.parse(await blob.text());
                error.userMessage = payload?.message || error.userMessage;
            } catch {
                // keep the original error message when blob parsing fails
            }
        }

        throw error;
    }

    const blob = response.data instanceof Blob
        ? response.data
        : new Blob([response.data]);

    const filename = extractFilename(
        response.headers?.['content-disposition'],
        fallbackFilename
    );

    const objectUrl = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = objectUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(objectUrl);

    return response;
}

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;

        if (status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }

        if (status === 429) {
            const retryAfter = error.response?.headers?.['retry-after'];
            const menit = retryAfter ? Math.ceil(retryAfter / 60) : '?';
            error.userMessage = `Terlalu banyak percobaan, coba lagi dalam ${menit} menit.`;
        }

        const apiMessage = error.response?.data?.message;
        error.userMessage = error.userMessage || apiMessage || 'Terjadi kesalahan. Coba lagi.';

        return Promise.reject(error);
    },
);

export default api;
