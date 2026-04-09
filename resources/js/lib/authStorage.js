export function readStoredUser() {
    const rawUser = localStorage.getItem('user');

    if (!rawUser) {
        return null;
    }

    try {
        return JSON.parse(rawUser);
    } catch {
        localStorage.removeItem('user');
        localStorage.removeItem('token');
        return null;
    }
}
