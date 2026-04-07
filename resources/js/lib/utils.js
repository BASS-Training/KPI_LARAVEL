import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Gabungkan class Tailwind dengan aman (mencegah konflik kelas)
 */
export function cn(...inputs) {
    return twMerge(clsx(inputs));
}
