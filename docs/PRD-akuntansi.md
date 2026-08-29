# PRD — Aplikasi Akuntansi Personal & Bisnis (Manifestasi)

> Dokumen ini adalah bagian dari satu set dokumen yang saling terhubung:
> - `PRD-akuntansi.md` (dokumen ini) — apa yang dibangun dan kenapa
> - `DATABASE-SCHEMA-akuntansi.md` — struktur data detail
> - `SPRINT-PLAN-akuntansi.md` — urutan eksekusi per sprint
> - `AGENT-BUILD-SPEC-akuntansi.md` — instruksi teknis untuk AI coding agent
>
> Semua istilah (entity, role, status transaksi, nama tabel) di dokumen ini **wajib sama** dengan tiga dokumen lainnya. Kalau AI agent perlu mengubah salah satu istilah/struktur, update juga di tiga dokumen lain supaya tidak nyeleneh.

---

## 1. Latar Belakang & Masalah

Owner butuh satu tempat untuk mencatat:
1. Keuangan **pribadi**.
2. Keuangan **bisnis** (entity "Manifestasi"), yang punya kompleksitas tambahan: banyak project & client, ada tim yang input data, dan butuh laporan yang bisa dipertanggungjawabkan (neraca, laba-rugi).

Masalah yang mau diselesaikan:
- Pencatatan manual (spreadsheet) rawan campur aduk antara uang pribadi dan bisnis.
- Tidak ada kontrol siapa yang boleh input/edit data begitu ada tim.
- Laporan keuangan bisnis (neraca, laba-rugi) sulit dihasilkan akurat dari pencatatan single-entry biasa.
- Tidak ada jejak audit kalau ada kesalahan input atau butuh investigasi transaksi lama.

## 2. Tujuan Produk

- Satu aplikasi, satu login, untuk mencatat dua "dunia" keuangan yang terpisah datanya: `Personal` dan `Manifestasi`.
- Pencatatan berbasis **double-entry accounting** di backend, tapi form input tetap sesederhana mungkin untuk user.
- Mendukung kolaborasi tim di sisi bisnis tanpa mengorbankan privasi data personal.
- Menghasilkan laporan keuangan yang bisa diandalkan (cash flow, laba-rugi, neraca, profitabilitas per project).

## 3. Target Pengguna & Persona

| Persona | Deskripsi | Kebutuhan Utama |
|---|---|---|
| **Owner** | Pemilik akun, akses penuh ke Personal + Manifestasi | Lihat gambaran keuangan lengkap, approve transaksi, kelola tim, buat laporan |
| **Team Member (Manifestasi)** | Staf/kolaborator bisnis | Input transaksi harian, lihat project yang di-assign, tidak bisa lihat data Personal |
| **Viewer** | Pihak yang cuma perlu lihat laporan (misal partner/investor) | Akses read-only ke laporan bisnis tertentu |

## 4. Konsep Produk Inti

### 4.1 Multi-Entity
Setiap data (akun, kategori, transaksi, project, client) terikat ke satu **entity** (`Personal` atau `Manifestasi`). User bisa punya role berbeda di tiap entity. Entity `Personal` **tidak pernah** terlihat oleh siapa pun selain Owner.

### 4.2 Double-Entry, UX Simpel
Backend mencatat setiap transaksi sebagai minimal 2 entri (debit & kredit) agar laporan neraca akurat. User biasa tetap mengisi form sederhana ("dari akun apa, ke kategori apa, berapa") — sistem yang menerjemahkan ke debit/kredit.

### 4.3 Role & Approval
Role per entity: `owner`, `member`, `viewer`. Transaksi punya status `draft` → `pending_approval` → `approved` (locked). Hanya Owner yang bisa approve.

### 4.4 Audit Trail
Setiap perubahan data transaksi tercatat: siapa, kapan, apa yang berubah.

## 5. User Stories & Functional Requirements

### Epic A — Autentikasi & Entity
- Sebagai Owner, saya bisa login dan melihat entity switcher untuk pindah antara Personal dan Manifestasi.
- Sebagai Owner, saya bisa invite anggota tim lewat email dan assign role (`member`/`viewer`) khusus untuk entity Manifestasi.
- Sebagai Team Member, saya login dan hanya melihat entity Manifestasi (Personal tidak muncul sama sekali, termasuk di API response).

### Epic B — Core Accounting
- Sebagai user, saya bisa mencatat transaksi income/expense dengan form sederhana tanpa perlu paham debit/kredit.
- Sebagai Owner, saya bisa membuat jurnal koreksi/adjustment manual (debit/kredit langsung) untuk kasus khusus.
- Sebagai user, saya bisa mencatat transfer antar akun dalam satu entity, dan transfer antar entity (owner draw) yang tercatat jelas sebagai jenis khusus, bukan expense biasa.
- Sebagai user, saya bisa set transaksi berulang (recurring) seperti sewa atau langganan.
- Sebagai user, saya bisa upload struk/invoice sebagai lampiran transaksi.

### Epic C — Project & Client (khusus Manifestasi)
- Sebagai Team Member, saya bisa membuat/lihat data client dan project yang di-assign ke saya.
- Sebagai user, saya bisa men-tag transaksi ke sebuah project agar muncul di laporan profitabilitas project tersebut.
- Sebagai Owner, saya bisa membuat invoice sederhana (nomor, item, total, status draft/sent/paid) untuk sebuah project.
- Sebagai Owner, saya bisa melihat daftar piutang dan reminder jatuh tempo.

### Epic D — Approval & Audit
- Sebagai Team Member, transaksi yang saya input berstatus `draft`/`pending_approval` dan tidak bisa saya hapus/edit setelah Owner approve.
- Sebagai Owner, saya bisa melihat daftar transaksi pending untuk di-approve/lock.
- Sebagai Owner, saya bisa melihat audit log semua perubahan transaksi.

### Epic E — Laporan & Dashboard
- Sebagai user, saya bisa melihat dashboard ringkasan saldo, grafik pemasukan/pengeluaran sesuai entity aktif.
- Sebagai Owner, saya bisa generate laporan cash flow, laba-rugi, dan neraca untuk entity Manifestasi.
- Sebagai Owner, saya bisa melihat laporan cash flow dan breakdown kategori untuk entity Personal.
- Sebagai Owner, saya bisa melihat laporan revenue vs cost per project.
- Sebagai user, saya bisa export laporan ke PDF/Excel.

### Epic F — Import & Otomasi
- Sebagai user, saya bisa import mutasi bank dari file CSV.
- Sebagai user, sistem bisa auto-kategorikan transaksi berdasarkan rule sederhana (contoh: deskripsi mengandung "GOJEK" → kategori Transport).

## 6. Non-Functional Requirements

- **Keamanan/isolasi data**: isolasi entity Personal dievaluasi di backend (policy/middleware), bukan hanya UI. Ini prioritas nomor satu.
- **Integritas data**: setiap transaksi harus balance (total debit = total kredit) sebelum tersimpan; dibungkus DB transaction.
- **Audit**: semua create/update/delete pada transaksi tercatat di audit log.
- **Skalabilitas kecil-menengah**: cukup untuk 1 owner + tim kecil (< 20 user), tidak perlu arsitektur microservices.
- **Aksesibilitas UI**: form transaksi harus tetap sederhana meski struktur data di belakang layar double-entry.

## 7. Out of Scope (untuk versi awal)

- Integrasi bank real-time (open banking API) — cukup import CSV manual dulu.
- Multi-currency.
- Aplikasi mobile native (cukup web responsive).
- Payroll/penggajian otomatis.
- Integrasi pajak otomatis (e-Faktur, dsb).

## 8. Metrik Keberhasilan (Sederhana)

- Owner bisa mencatat transaksi personal & bisnis tanpa tercampur, dan bisa lihat laporan neraca/laba-rugi Manifestasi yang balance secara matematis (debit = kredit).
- Tim bisa input transaksi tanpa pernah bisa mengakses data Personal (diverifikasi lewat testing akses/API).
- Owner bisa generate laporan profitabilitas per project dalam hitungan detik dari data yang sudah di-tag.

## 9. Referensi Struktur Data & Eksekusi

Detail tabel dan kolom ada di `DATABASE-SCHEMA-akuntansi.md`. Urutan pengerjaan per sprint ada di `SPRINT-PLAN-akuntansi.md`. Instruksi teknis untuk AI coding agent (tech stack, struktur folder, aturan keamanan) ada di `AGENT-BUILD-SPEC-akuntansi.md`.
