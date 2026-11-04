# Keputusan arsitektur

**Pilihan: Laravel MVC monolit (Blade + service layer).**

| Opsi | Penilaian untuk sistem ini |
|---|---|
| **MVC monolit** | Dipilih. Sistem internal, satu kantor, ~4 peran, CRUD + pembuatan dokumen. Satu aplikasi, satu deploy, tanpa Node. Paling mudah dihosting di cPanel/shared hosting dan dirawat staf. |
| Modular (nwidart dll.) | Berlebihan: domain kecil dan saling terkait erat (penugasan → dokumen → verifikasi). Menambah paket & kerumitan tanpa manfaat nyata. Pemisahan logika cukup lewat `app/Services`. |
| API + SPA | Butuh dua deployment (atau build Node), CORS, token auth. Tidak ada klien lain yang memerlukan API. |

Penunjang hosting: sesi & cache berbasis file, queue `sync`, MySQL/MariaDB baik di lokal (Laragon) maupun produksi (cPanel), tanpa worker/Redis. Logika bisnis ada di service (`NomorDokumenService`, `DokumenContextBuilder`, `DocxTemplateFiller`, dst.) sehingga bila kelak perlu API, service dapat dipakai ulang.

Keamanan: kata sandi di-hash (versi lama menyimpan polos di sisi klien), otorisasi peran lewat middleware `role:`, soft delete untuk dokumen.
