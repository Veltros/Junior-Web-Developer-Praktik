@extends('layouts.app')

@section('title', 'Edit Profil Saya - The Book Atelier')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-muted">Beranda</a></li>
            <li class="breadcrumb-item text-muted">Akun Saya</li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Edit Profil</li>
        </ol>
    </nav>

    <!-- Header Judul -->
    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold font-serif mb-1">
                <i class="bi bi-person-gear text-maroon me-2"></i>Edit Profil Akun
            </h1>
            <p class="text-muted small mb-0">Kelola informasi pribadi, kontak telepon, dan alamat pengiriman Anda</p>
        </div>
        <a href="{{ route('home') }}" class="btn btn-outline-dark btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda
        </a>
    </div>

    <!-- Alert Notifikasi Flash -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4 p-3 d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill fs-5 text-success"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4 p-3" role="alert">
            <div class="d-flex align-items-center gap-2 mb-2 fw-semibold">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <span>Terdapat kesalahan pada formulir yang Anda kirimkan:</span>
            </div>
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4 mb-5">
        <!-- Kolom Kiri: Kartu Ringkasan Akun (Lebar 4 Kolom) -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                <div class="card-body p-4 text-center">
                    <!-- Avatar Lingkaran Inisial -->
                    <div class="bg-maroon bg-opacity-10 text-maroon rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 shadow-sm font-serif fw-bold" style="width: 80px; height: 80px; font-size: 2rem;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>

                    <h5 class="fw-bold mb-1 text-dark font-serif">{{ $user->name }}</h5>
                    <p class="text-muted small mb-2">{{ $user->email }}</p>

                    @if ($user->role === 'admin')
                        <span class="badge bg-warning text-dark px-3 py-1 rounded-pill small fw-semibold">
                            <i class="bi bi-shield-check me-1"></i>Administrator
                        </span>
                    @else
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill small fw-semibold">
                            <i class="bi bi-patch-check-fill me-1"></i>Pelanggan Terdaftar
                        </span>
                    @endif

                    <hr class="my-4">

                    <div class="text-start d-flex flex-column gap-3 small text-secondary">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted"><i class="bi bi-calendar3 me-2 text-maroon"></i>Terdaftar Sejak:</span>
                            <strong class="text-dark">{{ $user->created_at->format('d M Y') }}</strong>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted"><i class="bi bi-bag-check me-2 text-maroon"></i>Total Pesanan:</span>
                            <span class="badge bg-light text-dark border px-2 py-1">{{ $user->orders()->count() }} Pesanan</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted"><i class="bi bi-chat-dots me-2 text-maroon"></i>Pesan Concierge:</span>
                            <span class="badge bg-light text-dark border px-2 py-1">{{ $user->messages()->count() }} Chat</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kartu Kebijakan Email Terkunci -->
            <div class="card border-0 shadow-sm rounded-4 bg-light p-3">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-shield-lock-fill text-maroon fs-4"></i>
                    <div class="small">
                        <strong class="text-dark d-block mb-1">Proteksi Keamanan Email</strong>
                        <p class="text-muted mb-0 lh-sm" style="font-size: 0.8rem;">
                            Alamat email Anda terhubung secara permanen dengan riwayat invoice pesanan dan verifikasi autentikasi, sehingga **tidak dapat diubah**.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Formulir Edit Profil (Lebar 8 Kolom) -->
        <div class="col-lg-8">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <!-- Card 1: Data Identitas & Alamat -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold font-serif mb-3 pb-2 border-bottom text-dark">
                        <i class="bi bi-person-vcard text-maroon me-2"></i>Informasi Pribadi &amp; Kontak
                    </h5>

                    <!-- Form Email (LOCKED / READONLY / TIDAK DAPAT DIUBAH) -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">
                            Alamat Email <span class="badge bg-secondary-subtle text-secondary ms-1 small"><i class="bi bi-lock-fill me-1"></i>Terkunci Permanen</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted">
                                <i class="bi bi-envelope-at"></i>
                            </span>
                            <input type="email" class="form-control bg-light border-start-0 text-muted" value="{{ $user->email }}" readonly disabled style="cursor: not-allowed;">
                        </div>
                        <div class="form-text text-muted" style="font-size: 0.8rem;">
                            <i class="bi bi-info-circle me-1"></i>Email tidak dapat diedit demi alasan keamanan dan integritas transaksi akun Anda.
                        </div>
                    </div>

                    <!-- Field Nama Lengkap -->
                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-secondary mb-1">
                            Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-person"></i>
                            </span>
                            <input type="text" id="name" name="name" class="form-control border-start-0 @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" placeholder="Masukkan nama lengkap Anda" required>
                        </div>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Form Nomor Telepon -->
                    <div class="mb-3">
                        <label for="phone" class="form-label small fw-semibold text-secondary mb-1">
                            Nomor Telepon / WhatsApp
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-telephone"></i>
                            </span>
                            <input type="tel" id="phone" name="phone" class="form-control border-start-0 @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" placeholder="Contoh: 081234567890">
                        </div>
                        <div class="form-text text-muted" style="font-size: 0.8rem;">
                            Digunakan untuk konfirmasi resi kurir dan status pengiriman pesanan Anda.
                        </div>
                        @error('phone')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Form Alamat Pengiriman Utama -->
                    <div class="mb-2">
                        <label for="address" class="form-label small fw-semibold text-secondary mb-1">
                            Alamat Pengiriman Utama
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted align-items-start pt-2">
                                <i class="bi bi-geo-alt"></i>
                            </span>
                            <textarea id="address" name="address" rows="3" class="form-control border-start-0 @error('address') is-invalid @enderror" placeholder="Tuliskan alamat lengkap pengiriman (Nama jalan, nomor rumah, RT/RW, kelurahan/kecamatan, dan kota/kabupaten)...">{{ old('address', $user->address) }}</textarea>
                        </div>
                        <div class="form-text text-muted" style="font-size: 0.8rem;">
                            Alamat ini akan otomatis terpilih saat Anda melakukan checkout buku.
                        </div>
                        @error('address')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Card 2: Keamanan Akun / Ganti Kata Sandi (Opsional) -->
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                    <h5 class="fw-bold font-serif mb-1 pb-2 border-bottom text-dark d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-key text-maroon me-2"></i>Ganti Kata Sandi</span>
                        <span class="badge bg-light text-muted border small fw-normal">Opsional</span>
                    </h5>
                    <p class="text-muted small mb-3">Kosongkan kolom di bawah jika Anda tidak ingin mengubah kata sandi.</p>

                    <!-- Kata Sandi Saat Ini -->
                    <div class="mb-3">
                        <label for="current_password" class="form-label small fw-semibold text-secondary mb-1">
                            Kata Sandi Saat Ini
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="bi bi-shield-lock"></i>
                            </span>
                            <input type="password" id="current_password" name="current_password" class="form-control border-start-0 @error('current_password') is-invalid @enderror" placeholder="Masukkan kata sandi lama Anda">
                        </div>
                        @error('current_password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3">
                        <!-- Kata Sandi Baru -->
                        <div class="col-md-6">
                            <label for="password" class="form-label small fw-semibold text-secondary mb-1">
                                Kata Sandi Baru
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" id="password" name="password" class="form-control border-start-0 @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter">
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Konfirmasi Kata Sandi Baru -->
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label small fw-semibold text-secondary mb-1">
                                Ulangi Kata Sandi Baru
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control border-start-0" placeholder="Ketik ulang kata sandi baru">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="d-flex align-items-center gap-3">
                    <button type="submit" class="btn btn-atelier-primary px-4 py-2 rounded-pill fw-semibold shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-check2-circle fs-5"></i>
                        <span>Simpan Perubahan Profil</span>
                    </button>
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-semibold">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
