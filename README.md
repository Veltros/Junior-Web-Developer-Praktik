# 📚 The Book Atelier - Laravel Edition

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap)](https://getbootstrap.com)

Aplikasi toko buku online modern **The Book Atelier** yang dibangun menggunakan framework **Laravel 11**, **MySQL**, **PHP 8.3**, dan **Bootstrap 5.3**. Dilengkapi arsitektur MVC, sistem transaksi aman atomik, kontrol peran multi-user (Admin & Pelanggan), keranjang belanja hybrid, serta layanan chat pengguna interaktif.

---

## 📸 Tangkapan Layar Halaman Website (Screenshots)

Berikut adalah dokumentasi visual lengkap dari seluruh antarmuka aplikasi **The Book Atelier**:

### 🛍️ 1. Antarmuka Publik & Pelanggan (Customer Interface)

#### 1. Halaman Beranda (Katalog & Kurasi)
![Halaman Beranda](screenshots/01_home_beranda.jpg)

#### 2. Detail Informasi & Sinopsis Buku
![Detail Buku](screenshots/02_detail_buku.png)

#### 3. Keranjang Belanja & Stepper Kuantitas
![Keranjang Belanja](screenshots/03_keranjang_belanja.png)

#### 4. Riwayat Pesanan Pelanggan (Pesanan Saya)
![Riwayat Pesanan](screenshots/04_riwayat_pesanan.png)

#### 5. Layanan Chat & Pesan Pelanggan (Concierge)
![Layanan Chat Concierge](screenshots/05_chat_concierge.png)

#### 6. Halaman Profil Toko (Tentang Kami)
![Tentang Kami](screenshots/06_tentang_kami.jpg)

#### 7. Halaman Masuk ke Akun (Login)
![Form Login](screenshots/07_masuk_login.png)

#### 8. Formulir Pendaftaran Akun Baru (Register)
![Form Registrasi](screenshots/08_registrasi_akun.png)

---

### ⚙️ 2. Panel Manajemen Administrator (Admin Panel)

#### 9. Dasbor Administrator & Ringkasan Statistik
![Dasbor Admin](screenshots/09_admin_dashboard.jpg)

#### 10. Kelola Data Buku & Smart Restock
![Kelola Data Buku](screenshots/10_admin_kelola_buku.jpg)

#### 11. Kelola Kategori Klasifikasi Buku
![Kelola Kategori](screenshots/11_admin_kelola_kategori.jpg)

#### 12. Manajemen & Pembaruan Status Pesanan
![Manajemen Pesanan](screenshots/12_admin_manajemen_pesanan.jpg)

#### 13. Data Pengguna Terdaftar
![Data Pengguna](screenshots/13_admin_data_pengguna.png)

#### 14. Kelola & Balas Pesan Pengguna
![Kelola Pesan Chat](screenshots/14_admin_chat_pesan_pengguna.jpg)

---

## 🚀 Fitur & Arsitektur Sistem

1. **Arsitektur MVC (Model-View-Controller)**:
   - **Models & Relasi Eloquent:** `User`, `Category`, `Book`, `Order`, `OrderItem`, `Message`, `CartItem`.
   - **Controllers Terstruktur:** Pemisahan controller publik (`HomeController`, `BookController`, `CartController`, `OrderController`, dll) dan panel admin (`Admin\BookController`, `Admin\CategoryController`, dll).
   - **Blade Templates:** Komponen modular dengan layout terpadu (`layouts/app.blade.php` dan `layouts/admin.blade.php`).

2. **Integritas & Proteksi Data Transaksi**:
   - Buku dan Kategori yang terhubung dengan riwayat pesanan user **tidak dapat dihapus** (`ON DELETE RESTRICT` pada basis data dan proteksi keamanan di controller).
   - Indikator status pesanan terpadu yang otomatis menyinkronkan status pembayaran menjadi lunas.

3. **Sistem Keranjang & Checkout Transaksional**:
   - Validasi stok sebelum checkout menggunakan *pessimistic locking* (`lockForUpdate`).
   - Kontrol transaksi database aman (`DB::transaction`) untuk memastikan pemotongan stok dan pembuatan nomor pesanan `ORD-YYYYMMDD-XXXXX` berjalan atomik (*all-or-nothing*).
   - Fitur seleksi checkout (ceklis item) dan penyesuaian kuantitas.

4. **Autentikasi & Otorisasi Berbasis Peran (RBAC)**:
   - Sistem login aman dengan hashing kata sandi bawaan Laravel BCRYPT (`Hash::make`).
   - Route Middleware: `auth` untuk transaksi pelanggan dan `admin` untuk panel manajemen.
   - Proteksi respon **HTTP 401 Unauthorized** (jika belum login) dan **HTTP 403 Forbidden** (jika peran tidak berhak), dengan halaman error kustom yang bersih (*minimalist*).

5. **Layanan Chat Dua Arah**:
   - Pengguna dapat mengirimkan pesan ke pengelola toko via obrolan berbalas.
   - Admin dapat membalas pesan secara real-time dan riwayat balasan langsung muncul di akun pengguna.

---

## 🔑 Akun Demo Pengujian

| Peran (Role) | Alamat Email | Kata Sandi | Halaman Akses |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@bookstore.com` | `admin123` | `/admin` |
| **User (Pelanggan)** | `budi@gmail.com` | `user1234` | `/` |

---

## 🌐 Cara Menjalankan di Laragon

1. Pastikan Laragon berjalan (**Start All** Apache & MySQL).
2. Database MySQL: `bookstore_laravel_db` (sudah ter-migrasi dan ter-seeding secara otomatis).
3. Akses via Browser:
   - 👉 **`http://bookstore-laravel.test`**  
   - Atau via localhost:  
     👉 **`http://localhost/bookstore-laravel/public`**  
   - Panel Admin:  
     👉 **`http://localhost/bookstore-laravel/public/admin`**
