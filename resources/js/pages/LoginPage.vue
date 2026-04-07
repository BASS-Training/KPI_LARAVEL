<script setup>
import { ref, reactive } from 'vue';
import { useAuthStore } from '@/stores/auth';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Alert from '@/components/ui/Alert.vue';

const auth = useAuthStore();

const form = reactive({ nip: '', nama: '' });
const errors = reactive({ nip: '', nama: '' });
const apiError = ref('');

function validate() {
    let valid = true;
    errors.nip = '';
    errors.nama = '';

    if (!form.nip.trim()) {
        errors.nip = 'NIP wajib diisi.';
        valid = false;
    }
    if (!form.nama.trim()) {
        errors.nama = 'Nama lengkap wajib diisi.';
        valid = false;
    }
    return valid;
}

async function handleLogin() {
    apiError.value = '';
    if (!validate()) return;

    try {
        await auth.login(form.nip.trim(), form.nama.trim());
    } catch (err) {
        const errData = err.response?.data;
        const attemptsLeft = errData?.errors?.attempts_left;
        const base = errData?.message || 'NIP atau nama tidak valid.';
        apiError.value = attemptsLeft !== undefined ? `${base} Sisa ${attemptsLeft} percobaan.` : base;
    }
}
</script>

<template>
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
        <div class="absolute inset-0">
            <div class="absolute left-[-8%] top-[-10%] h-64 w-64 rounded-full bg-sky-200/35 blur-3xl"></div>
            <div class="absolute bottom-[-8%] right-[-4%] h-80 w-80 rounded-full bg-amber-200/35 blur-3xl"></div>
        </div>

        <div class="relative grid w-full max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_8px_40px_rgba(15,23,42,0.12)] lg:grid-cols-[1fr_1fr]">
            <section class="hidden bg-[linear-gradient(145deg,#0f172a,#1e3a5f)] px-8 py-10 text-white lg:flex lg:flex-col lg:justify-between">
                <div>
                    <div class="page-hero-meta !border-white/15 !bg-white/10">Dashboard Monitoring KPI</div>
                    <h1 class="mt-6 max-w-md text-4xl font-bold leading-tight">
                        Monitoring KPI pegawai yang rapi, jelas, dan siap dipakai operasional.
                    </h1>
                    <p class="mt-4 max-w-lg text-sm leading-7 text-white/72">
                        Panel kinerja untuk pegawai, HR, dan direktur dengan tampilan yang formal, bersih, dan fokus pada monitoring performa.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-xl border border-white/10 bg-white/10 p-4">
                        <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Monitoring</div>
                        <div class="mt-2 text-2xl font-bold">24/7</div>
                        <p class="mt-2 text-xs leading-5 text-white/70">Pantau progres pekerjaan dan kualitas penyelesaian dari satu panel.</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/10 p-4">
                        <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Penilaian</div>
                        <div class="mt-2 text-2xl font-bold">KPI</div>
                        <p class="mt-2 text-xs leading-5 text-white/70">Komponen penilaian konsisten untuk kebutuhan HR dan evaluasi.</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/10 p-4">
                        <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Akses</div>
                        <div class="mt-2 text-2xl font-bold">Role</div>
                        <p class="mt-2 text-xs leading-5 text-white/70">Pegawai, HR, dan direktur masuk lewat panel yang sama.</p>
                    </div>
                </div>
            </section>

            <section class="flex items-center px-5 py-8 sm:px-8 lg:px-10">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-8">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">PT. BASS Training Center &amp; Consultant</p>
                        <h2 class="mt-3 text-3xl font-bold text-slate-900">Masuk ke Dashboard KPI</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Gunakan NIP dan nama lengkap yang sudah terdaftar.</p>
                    </div>

                    <Alert v-if="apiError" variant="danger" class="mb-4">
                        {{ apiError }}
                    </Alert>

                    <form @submit.prevent="handleLogin" class="space-y-5">
                        <div>
                            <label class="form-label">NIP / Kode Pegawai</label>
                            <Input
                                v-model="form.nip"
                                class="!rounded-xl !border-slate-200 !px-4 !py-3"
                                placeholder="Contoh: BASS-HR-01-2026"
                                maxlength="30"
                                :disabled="auth.isLoading"
                            />
                            <p v-if="errors.nip" class="mt-1 text-xs text-red-500">{{ errors.nip }}</p>
                        </div>

                        <div>
                            <label class="form-label">Nama Lengkap</label>
                            <Input
                                v-model="form.nama"
                                class="!rounded-xl !border-slate-200 !px-4 !py-3"
                                placeholder="Nama sesuai data pegawai"
                                :disabled="auth.isLoading"
                            />
                            <p v-if="errors.nama" class="mt-1 text-xs text-red-500">{{ errors.nama }}</p>
                        </div>

                        <Button type="submit" class="!w-full !justify-center !py-3 text-[15px]" :disabled="auth.isLoading">
                            <svg
                                v-if="auth.isLoading"
                                class="h-4 w-4 animate-spin"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48 2.83-2.83"/>
                            </svg>
                            {{ auth.isLoading ? 'Memverifikasi...' : 'Login' }}
                        </Button>
                    </form>

                    <div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                        <div class="font-semibold text-slate-700">Akses bantuan</div>
                        <p class="mt-1 leading-6">Jika gagal login, cek kembali kecocokan NIP dan nama pegawai pada data master.</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
