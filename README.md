# SPK-BAST Generator (Laravel)

Sistem pembuat dokumen **SPK** dan **BAST** BPS Kota Subulussalam, dibangun ulang dari versi lama (HTML + Google Sheets) menjadi aplikasi Laravel.

## Alur dokumen
Mapping petugas → SPK → verifikasi PPK → nomor BAST (PPL–PML, PPL–SM, PML–SM, ENTRI–SM) → BAST SM–PPK.
Dokumen Word dibuat dengan mengisi template `.docx` asli BPS (`resources/doc-templates/docx`); pratinjau dan cetak memakai template HTML.

## Peran
| Peran | Akses |
|---|---|
| ADMIN | semua, termasuk pengguna & parameter |
| KATIM | master data, penugasan, dokumen |
| PPK | verifikasi dokumen |
| PENGGUNA | melihat dasbor & dokumen |

## Arsitektur (singkat)
Laravel 13, MVC + Blade + lapisan service. Tanpa API/SPA, tanpa paket modul, tanpa build Node — sehingga cukup satu `composer install` dan cocok untuk shared hosting/cPanel. Detail alasan ada di `docs/ARSITEKTUR.md`.

## Menjalankan lokal (Laragon / MySQL)
Default `.env.example` sudah MySQL: `127.0.0.1:3306`, user `root`, password kosong (bawaan Laragon). Pastikan PHP Laragon ≥ 8.3 (Menu → PHP → Version).
```bash
composer install
cp .env.example .env          # lalu atur APP_ENV=local, APP_DEBUG=true
# buat database kosong `spk_bast_generator` (Laragon: Menu → MySQL → HeidiSQL/phpMyAdmin,
# atau: mysql -uroot -e "CREATE DATABASE spk_bast_generator CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
php artisan key:generate
php artisan migrate --seed    # akun awal: lihat database/seeders/data/users.json, password awal 123456
php artisan spk:import-excel /path/DB_SPK_BAST.xlsx   # opsional: impor data lama
php artisan serve
```
Kebutuhan: PHP ≥ 8.3 dengan ekstensi `zip`, `mbstring`, `xml`, `gd` (untuk PhpSpreadsheet), `pdo_mysql`.

### Akun awal
Seeder membuat 11 akun (admin, PPK, KATIM per tim, staf) dari `database/seeders/data/users.json`. **Semua memakai password awal `123456`** — minta tiap pengguna segera menggantinya lewat menu Profil. Seeder tidak menimpa akun yang sudah ada.

## Deploy ke cPanel
1. Pastikan PHP 8.3+ di MultiPHP Manager.
2. Unggah proyek ke luar `public_html` (mis. `~/spk-bast`), lalu arahkan *document root* domain/subdomain ke folder `public`.
3. Jalankan `composer install --no-dev --optimize-autoloader` (Terminal cPanel) atau unggah `vendor` dari lokal.
4. Salin `.env.example` → `.env`, isi `APP_URL`, `DB_*` (MySQL); `php artisan key:generate`. Contoh lengkap untuk hosting ada di `.env.hosting.example`.
5. `php artisan migrate --force --seed`, lalu `php artisan config:cache route:cache view:cache`.
6. Pastikan `storage` dan `bootstrap/cache` dapat ditulis.

## Sinkron langsung dari Google Sheets
Selama Apps Script lama masih aktif, data bisa ditarik tanpa unduh `.xlsx`:
```bash
# isi SHEETS_URL di .env dengan URL /exec Apps Script (sama dengan APPS_SCRIPT_URL di public/js/config.js lama)
php artisan spk:sync-sheets                 # data master: parameter, pegawai, mitra, kegiatan (aman diulang)
php artisan spk:sync-sheets --dry-run       # lihat hasil tanpa menyimpan
php artisan spk:sync-sheets --only=penugasan,dokumen,sm-ppk --fresh   # sekali saja, mengosongkan dokumen lama dulu
```
Data master di-*update-or-create* sehingga aman dijalankan berkali-kali (mis. terjadwal). Penugasan & dokumen selalu dibuat baru, karena itu hanya ditarik bila diminta dan sebaiknya dengan `--fresh`. Arah sinkron satu arah: Sheets → aplikasi.
Bila Apps Script ditolak, pastikan deployment diatur *Who has access: Anyone*.

## Impor data lama
`php artisan spk:import-excel "D:\\path\\DB_SPK_BAST.xlsx" [--dry-run] [--fresh] [--only=pegawai,mitra,...]`
Akun pengguna lama tidak diimpor (kata sandi lama polos); buat ulang di menu Pengguna. Impor SPK_BAST bersifat *best-effort* — baris tanpa petugas yang dikenali dilewati dan dilaporkan.

## Pengujian
`php artisan test`
