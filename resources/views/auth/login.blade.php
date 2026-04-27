@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="relative flex min-h-screen items-center justify-center overflow-hidden px-5 py-10">
    <div class="absolute inset-0">
        <div class="absolute left-[-8%] top-[-8%] h-56 w-56 rounded-full bg-sky-200/35 blur-3xl"></div>
        <div class="absolute bottom-[-6%] right-[-4%] h-72 w-72 rounded-full bg-amber-200/35 blur-3xl"></div>
    </div>

    <div class="relative grid w-full max-w-6xl overflow-hidden rounded-[32px] border border-slate-200 bg-white shadow-[0_30px_90px_-55px_rgba(26,58,92,0.65)] lg:grid-cols-[1.08fr_0.92fr]">
        <section class="hidden bg-[linear-gradient(145deg,rgba(26,58,92,0.98),rgba(37,99,168,0.92))] px-8 py-10 text-white lg:flex lg:flex-col lg:justify-between">
            <div>
                <div class="page-hero-meta !border-white/15 !bg-white/10">Dashboard Monitoring KPI</div>
                <h1 class="mt-6 max-w-md text-4xl font-bold leading-tight">Pemantauan KPI pegawai yang rapi, jelas, dan siap dipakai operasional.</h1>
                <p class="mt-4 max-w-lg text-sm leading-7 text-white/72">
                    Tampilan disusun mengikuti karakter dashboard perusahaan: formal, bersih, dan fokus pada monitoring performa serta kualitas pekerjaan.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-white/12 bg-white/10 p-4">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Monitoring</div>
                    <div class="mt-2 text-2xl font-bold">24/7</div>
                    <p class="mt-2 text-xs leading-5 text-white/70">Pantau progres pekerjaan dan kualitas penyelesaian dari satu panel.</p>
                </div>
                <div class="rounded-2xl border border-white/12 bg-white/10 p-4">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Penilaian</div>
                    <div class="mt-2 text-2xl font-bold">KPI</div>
                    <p class="mt-2 text-xs leading-5 text-white/70">Komponen penilaian dibuat konsisten untuk kebutuhan HR dan evaluasi.</p>
                </div>
                <div class="rounded-2xl border border-white/12 bg-white/10 p-4">
                    <div class="text-[11px] uppercase tracking-[0.18em] text-white/60">Akses</div>
                    <div class="mt-2 text-2xl font-bold">Role</div>
                    <p class="mt-2 text-xs leading-5 text-white/70">Pegawai dan HR masuk lewat antarmuka yang sama dengan hak akses terpisah.</p>
                </div>
            </div>
        </section>

        <section class="flex items-center px-5 py-8 sm:px-8 lg:px-10">
            <div class="mx-auto w-full max-w-md">
                <div class="mb-8">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">PT. BASS Training Center &amp; Consultant</p>
                    <h2 class="mt-3 text-3xl font-bold text-slate-900">Masuk ke Dashboard KPI</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Gunakan NIP dan nama lengkap yang sudah terdaftar pada data pegawai.</p>
                </div>

                @if($errors->any())
                    <div class="alert mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
                    @csrf
                    <div>
                        <label for="nip" class="form-label">NIP / Kode Pegawai</label>
                        <input
                            id="nip"
                            type="text"
                            name="nip"
                            class="form-input"
                            placeholder="Contoh: XXXX-XX-XX-XXXX"
                            required
                            autofocus
                        >
                    </div>

                    <div>
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            class="form-input"
                            placeholder="Nama sesuai data pegawai"
                            required
                        >
                    </div>

                    <button type="submit" class="btn-primary flex w-full items-center justify-center gap-2 py-3 text-[15px]">
                        <span>Login</span>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14"/>
                            <path d="m13 5 7 7-7 7"/>
                        </svg>
                    </button>
                </form>

                <div class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-500">
                    <div class="font-semibold text-slate-700">Akses bantuan</div>
                    <p class="mt-1 leading-6">Jika gagal login, cek kembali kecocokan NIP dan nama pegawai pada data master HR.</p>
                </div>

                <p class="mt-6 text-center text-xs text-slate-400">Copyright 2026 PT. BASS Training Center &amp; Consultant</p>
            </div>
        </section>
    </div>
</div>
@endsection
