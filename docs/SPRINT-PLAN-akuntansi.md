# Sprint Plan — Aplikasi Akuntansi Personal & Bisnis (Manifestasi)

> Bagian dari satu set dokumen: `PRD-akuntansi.md`, `DATABASE-SCHEMA-akuntansi.md`, `SPRINT-PLAN-akuntansi.md` (dokumen ini), `AGENT-BUILD-SPEC-akuntansi.md`.
> Sprint di sini urutannya sama dengan bagian **"Urutan Eksekusi"** di `AGENT-BUILD-SPEC-akuntansi.md`, dipecah lebih detail jadi task per sprint. Setiap sprint diasumsikan bisa dikerjakan AI agent dalam beberapa sesi, dengan **definition of done** yang jelas sebelum lanjut ke sprint berikutnya.

Cara pakai: kerjakan satu sprint sampai selesai (semua checklist tercentang) sebelum minta agent lanjut ke sprint berikutnya. Kalau ada perubahan scope di tengah jalan, update juga `PRD-akuntansi.md` / `DATABASE-SCHEMA-akuntansi.md` supaya tiga dokumen tetap sinkron.

**Status keseluruhan (29 Agu 2026):** Sprint **0–8 selesai** di codebase. Semua checklist di bawah sudah tercentang setelah verifikasi artefak (migration, service, controller, halaman React, test).

---

## Sprint 0 — Project Setup & Auth

**Tujuan:** Project bisa dijalankan lokal, user bisa register/login.

- [x] Install Laravel + Inertia + React + Tailwind starter kit (Breeze/Fortify React variant)
- [x] Setup koneksi database MySQL, `.env` terkonfigurasi
- [x] Jalankan migration bawaan Laravel (users, sessions, dst)
- [x] Halaman login/register/logout berfungsi
- [x] Struktur folder awal sesuai bagian 6 `AGENT-BUILD-SPEC-akuntansi.md`

**Definition of Done:** User bisa register, login, logout, dan masuk ke dashboard kosong.

---

## Sprint 1 — Entity & Multi-User Dasar

**Tujuan:** Konsep entity dan role per entity berjalan, isolasi data mulai ditegakkan.

- [x] Migration `entities`, `entity_user` (lihat `DATABASE-SCHEMA-akuntansi.md` bagian 1)
- [x] Seeder: entity `Personal` & `Manifestasi`, user Owner ter-assign role `owner` di keduanya
- [x] Model `Entity`, relasi `User::entities()` via pivot
- [x] Middleware/Policy `EnsureEntityAccess` — cek user punya akses ke entity yang diminta, dan entity `Personal` hanya bisa diakses role `owner`
- [x] Komponen **Entity Switcher** di UI (dropdown pindah entity aktif)
- [x] Fitur invite anggota tim via email, assign role (`member`/`viewer`) khusus entity `Manifestasi`

**Definition of Done:** Login sebagai Owner bisa switch antara Personal/Manifestasi. Login sebagai user yang di-invite (role `member`) tidak bisa melihat entity Personal sama sekali (dicoba lewat UI dan lewat manipulasi request langsung ke endpoint).

---

## Sprint 2 — Core Accounting (Double-Entry Engine)

**Tujuan:** Transaksi income/expense/transfer bisa dicatat dengan benar secara double-entry, lewat form simpel.

- [x] Migration `accounts`, `categories`, `transactions`, `transaction_entries` (lihat `DATABASE-SCHEMA-akuntansi.md` bagian 2)
- [x] Seeder chart of accounts & kategori default per entity
- [x] `TransactionService`: fungsi untuk generate `transaction_entries` otomatis dari input simpel (income/expense/transfer)
- [x] Validasi backend: `SUM(debit) = SUM(kredit)` per transaksi, dibungkus `DB::transaction()`
- [x] Form input transaksi di React (user pilih akun sumber, kategori, jumlah, tanggal, deskripsi — tanpa istilah debit/kredit)
- [x] Halaman/menu terpisah untuk **jurnal manual/adjustment** (khusus role `owner`), input debit/kredit langsung
- [x] Fitur transfer antar akun dalam satu entity
- [x] Fitur transfer antar entity (`inter_entity_transfer`, misal owner draw)
- [x] Fitur recurring transaction (sederhana: simpan template + jadwal, generate transaksi baru sesuai periode)
- [x] Upload attachment (struk/invoice) ke transaksi

**Definition of Done:** Transaksi income/expense bisa dibuat dari form simpel dan otomatis balance di `transaction_entries`. Jurnal manual hanya bisa diakses Owner. Attachment bisa diunggah dan dilihat kembali.

---

## Sprint 3 — Dashboard Dasar per Entity

**Tujuan:** User bisa melihat ringkasan keuangan begitu masuk aplikasi.

- [x] Ringkasan saldo semua akun untuk entity aktif
- [x] Grafik pemasukan/pengeluaran (mingguan/bulanan)
- [x] Widget berbeda untuk mode Personal (progress budget) vs Manifestasi (project aktif + status piutang) — versi awal boleh statis dulu, disempurnakan di Sprint 5/7

**Definition of Done:** Dashboard menampilkan data real dari transaksi yang sudah diinput, otomatis menyesuaikan saat entity di-switch.

---

## Sprint 4 — Project & Client Management

**Tujuan:** Transaksi bisnis bisa dikaitkan ke client & project.

- [x] Migration `clients`, `projects` (lihat `DATABASE-SCHEMA-akuntansi.md` bagian 3)
- [x] CRUD Client (khusus entity `Manifestasi`)
- [x] CRUD Project (link ke client, budget, tanggal, status)
- [x] Update form transaksi: opsi tag ke project/client (hanya muncul saat entity aktif = Manifestasi)
- [x] Halaman detail project menampilkan transaksi yang ter-tag ke project tsb

**Definition of Done:** Owner/Team Member bisa membuat client & project, transaksi bisa ditag ke project, dan halaman detail project menunjukkan transaksi terkait.

---

## Sprint 5 — Invoice & Piutang Tracker

**Tujuan:** Bisa membuat invoice sederhana dan memantau piutang.

- [x] Migration `invoices` (lihat `DATABASE-SCHEMA-akuntansi.md` bagian 3)
- [x] Form buat invoice (pilih project, isi item, hitung total otomatis)
- [x] Status invoice: draft/sent/paid, update status manual oleh Owner
- [x] Halaman daftar piutang (invoice belum `paid`) dengan indikator jatuh tempo (`due_date`)
- [x] (Opsional) reminder sederhana di dashboard untuk invoice mendekati/lewat jatuh tempo

**Definition of Done:** Invoice bisa dibuat dari sebuah project, status bisa diupdate, dan daftar piutang menampilkan invoice yang belum lunas terurut berdasarkan jatuh tempo.

---

## Sprint 6 — Import CSV & Auto-Categorization

**Tujuan:** Mengurangi input manual dari mutasi bank.

- [x] Migration `bank_import_rules`, `bank_import_batches` (lihat `DATABASE-SCHEMA-akuntansi.md` bagian 5)
- [x] Fitur upload CSV mutasi bank, parsing ke preview sebelum disimpan
- [x] Rule-based matching: deskripsi mengandung keyword tertentu → auto-assign kategori
- [x] Halaman kelola rule (tambah/edit/hapus keyword → kategori)
- [x] Setelah preview dikonfirmasi, generate transaksi (via `TransactionService` yang sama dari Sprint 2, status awal `draft`)

**Definition of Done:** User bisa upload CSV, lihat preview dengan kategori ter-assign otomatis (bisa dikoreksi manual sebelum simpan), lalu transaksi masuk sebagai batch.

---

## Sprint 7 — Laporan Lanjutan & Export

**Tujuan:** Laporan keuangan utama tersedia dan bisa diekspor.

- [x] Migration `budgets` (lihat `DATABASE-SCHEMA-akuntansi.md` bagian 4)
- [x] Laporan Personal: cash flow bulanan, breakdown kategori, progress budget vs actual
- [x] Laporan Manifestasi: laba-rugi, cash flow, **neraca (balance sheet)** — dihitung dari `transaction_entries` per `account.type`
- [x] Laporan profitabilitas per project (revenue vs cost dari transaksi yang ter-tag)
- [x] Export laporan ke PDF dan Excel

**Definition of Done:** Neraca yang dihasilkan balance secara matematis (total asset = total liability + equity). Semua laporan bisa diekspor.

---

## Sprint 8 — Finalisasi Approval Flow & Audit Trail

**Tujuan:** Kontrol multi-user dan jejak audit lengkap di semua modul.

- [x] Pastikan semua endpoint create/update/delete transaksi mencatat ke `audit_logs` (model observer/event listener)
- [x] Halaman daftar transaksi `pending_approval` khusus Owner, dengan aksi approve/lock
- [x] Transaksi `approved` tidak bisa diedit/dihapus langsung dari UI (hanya lewat jurnal koreksi baru)
- [x] Halaman audit log (siapa ubah apa, kapan) — read-only, khusus Owner
- [x] Review keamanan menyeluruh: coba akses entity Personal & endpoint sensitif pakai akun `member`/`viewer` untuk pastikan semua diblokir di backend

**Definition of Done:** Semua transaksi yang di-approve terkunci dari edit langsung, audit log lengkap untuk seluruh histori transaksi, dan tidak ada jalan bagi role non-owner untuk mengakses data Personal.

---

## Ringkasan Urutan (Quick Reference)

| Sprint | Fokus | Terkait Epic PRD | Status |
|---|---|---|---|
| 0 | Setup project & auth | — | ✅ Selesai |
| 1 | Entity & multi-user | Epic A | ✅ Selesai |
| 2 | Core accounting (double-entry) | Epic B | ✅ Selesai |
| 3 | Dashboard dasar | Epic E (sebagian) | ✅ Selesai |
| 4 | Project & client | Epic C | ✅ Selesai |
| 5 | Invoice & piutang | Epic C | ✅ Selesai |
| 6 | Import CSV & automasi | Epic F | ✅ Selesai |
| 7 | Laporan lanjutan & export | Epic E | ✅ Selesai |
| 8 | Approval flow & audit trail final | Epic D | ✅ Selesai |
