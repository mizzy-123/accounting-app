# AI Agent Build Spec — Aplikasi Akuntansi Personal & Bisnis (Manifestasi)

> Bagian dari satu set dokumen yang saling terhubung:
> - `PRD-akuntansi.md` — apa yang dibangun dan kenapa (user stories, requirement)
> - `DATABASE-SCHEMA-akuntansi.md` — struktur tabel & kolom detail (source of truth skema DB)
> - `SPRINT-PLAN-akuntansi.md` — checklist eksekusi per sprint, versi lebih detail dari bagian 8 di dokumen ini
> - `AGENT-BUILD-SPEC-akuntansi.md` (dokumen ini) — instruksi teknis ringkas untuk AI coding agent
>
> **Cara pakai dokumen ini:** Ini adalah instruksi eksekusi untuk AI coding agent (Cursor, Antigravity, Claude Code, dll). Taruh keempat file ini di root project, lalu minta agent membaca dokumen ini dulu untuk konteks teknis, lalu mengeksekusi sesuai checklist per sprint di `SPRINT-PLAN-akuntansi.md`. Untuk detail kolom/tipe data, agent harus rujuk ke `DATABASE-SCHEMA-akuntansi.md`, bukan menebak dari skema ringkas di bagian 5 dokumen ini. Agent boleh bertanya klarifikasi di awal, tapi setelah itu diharapkan langsung membuat struktur project, migration, model, controller, dan halaman React — bukan cuma memberi saran.

---

## 1. Tujuan Project

Bangun aplikasi web akuntansi yang bisa mencatat:
1. Keuangan **pribadi** milik owner.
2. Keuangan **bisnis** (entity "Manifestasi", termasuk project & client bisnis tersebut).

Dalam **satu aplikasi, satu login**, tapi data kedua sisi **terisolasi total** secara backend (bukan cuma disembunyikan di UI). Pencatatan menggunakan prinsip **double-entry accounting**, tapi user awam (termasuk tim non-owner) mengisi form yang simpel — sistem yang generate debit/kredit di belakang layar.

---

## 2. Tech Stack (wajib diikuti, jangan ganti tanpa konfirmasi)

| Layer | Teknologi |
|---|---|
| Backend framework | Laravel (versi LTS/stable terbaru) |
| Frontend bridge | Inertia.js |
| Frontend UI | React (functional components + hooks) |
| Styling | Tailwind CSS |
| Database | MySQL |
| Auth | Laravel Breeze/Fortify + Inertia starter kit (React variant) |
| Autorisasi | Laravel Policies + Gates (bukan cek role manual di controller) |

Agent **wajib** cek versi Laravel & paket terbaru yang kompatibel saat instalasi, jangan asumsi versi dari training data.

---

## 3. Konsep Inti yang Harus Dipahami Sebelum Coding

### 3.1 Multi-Entity
- "Entity" = buku besar terpisah. Minimal ada 2 entity awal: `Personal` dan `Manifestasi` (tipe: `personal` / `business`).
- Semua data transaksional (accounts, categories, transactions, projects, clients, budgets) **harus** punya `entity_id` dan di-scope lewat query, bukan filter di frontend.
- Buat **Laravel Global Scope atau Policy** yang otomatis membatasi query berdasarkan entity yang sedang aktif + akses user ke entity tersebut. Ini bagian paling kritikal — jangan sampai bisa ditembus lewat manipulasi request.

### 3.2 Double-Entry, tapi UX Simpel
- Tabel `transactions` (header) + `transaction_entries` (baris debit/kredit) — lihat skema di bagian 5.
- Untuk transaksi biasa (income/expense/transfer), buat **service/helper class** (misal `TransactionService`) yang menerima input simpel dari form ("dari akun apa", "ke kategori apa", "berapa") lalu otomatis generate 2 `transaction_entries` yang balance (total debit = total kredit).
- Jurnal manual (input debit/kredit langsung) hanya untuk role Owner, dipisah ke form/menu berbeda ("Jurnal Koreksi/Advance").
- Validasi wajib di level backend: setiap transaksi yang disimpan, total debit harus sama dengan total kredit sebelum commit ke DB (gunakan DB transaction/wrap dalam `DB::transaction()`).

### 3.3 Role & Permission
- Role per entity (bukan role global user), disimpan di pivot table `entity_user`:
  - `owner` — akses penuh semua entity termasuk Personal.
  - `member` — akses hanya entity bisnis yang di-assign, bisa input transaksi tapi tidak bisa hapus/edit yang sudah `approved`.
  - `viewer` — read-only laporan saja.
- Entity `Personal` **tidak boleh muncul sama sekali** di UI maupun API response untuk user selain owner.

### 3.4 Approval Flow
- Status transaksi: `draft` → `pending_approval` → `approved` (locked, tidak bisa diedit lagi kecuali dibuat jurnal koreksi baru).
- Hanya Owner yang bisa approve/lock.

### 3.5 Audit Trail
- Setiap create/update/delete pada `transactions` dan `transaction_entries` dicatat ke tabel `audit_logs` (siapa, kapan, perubahan apa). Gunakan model observer/event listener, jangan taruh logika ini manual di tiap controller.

---

## 4. Modul & Fitur (scope penuh, lihat urutan build di bagian 8)

- **A. Core Accounting** — chart of accounts, transaksi, double-entry engine, transfer antar akun & antar entity (owner draw), recurring transaction, attachment struk/invoice.
- **B. Project & Client Management** (khusus entity bisnis) — master client, master project (link ke client, budget, tanggal, status), tag transaksi ke project → laporan profitabilitas per project, invoice generator sederhana, piutang tracker + reminder jatuh tempo.
- **C. Multi-User & Permission** — invite tim via email, assign role per entity, isolasi data Personal, audit trail, approval flow.
- **D. Laporan** — cash flow bulanan & breakdown kategori (personal), laba-rugi + cash flow + neraca (bisnis umum), revenue vs cost per project (bisnis), export PDF/Excel.
- **E. Dashboard** — ringkasan saldo per entity, grafik pemasukan/pengeluaran, project aktif + status piutang (mode bisnis), budget vs actual (mode personal), entity switcher.
- **F. Import & Automasi** — import CSV mutasi bank, rule-based auto-categorization (contoh: deskripsi mengandung "GOJEK" → kategori Transport).

---

## 5. Skema Database (ringkasan — acuan detail ada di `DATABASE-SCHEMA-akuntansi.md`)

```sql
entities        (id, name, type ENUM('personal','business'), created_at, updated_at)
users           (id, name, email, password, ...)
entity_user     (id, entity_id, user_id, role ENUM('owner','member','viewer'), created_at)

accounts        (id, entity_id, name, type ENUM('asset','liability','equity','revenue','expense'),
                  is_active, created_at, updated_at)
categories      (id, entity_id, name, type ENUM('income','expense'), created_at, updated_at)

clients         (id, entity_id, name, contact_info, created_at, updated_at)
projects        (id, entity_id, client_id, name, budget, start_date, end_date,
                  status ENUM('active','completed','cancelled'), created_at, updated_at)

transactions        (id, entity_id, project_id NULLABLE, client_id NULLABLE,
                      date, description,
                      status ENUM('draft','pending_approval','approved'),
                      created_by, approved_by NULLABLE, created_at, updated_at)
transaction_entries (id, transaction_id, account_id, debit DECIMAL(15,2) DEFAULT 0,
                      kredit DECIMAL(15,2) DEFAULT 0, created_at, updated_at)

invoices        (id, project_id, invoice_number, items JSON, total, 
                  status ENUM('draft','sent','paid'), created_at, updated_at)
budgets         (id, entity_id, category_id, period, amount, created_at, updated_at)
attachments     (id, attachable_type, attachable_id, file_path, created_at, updated_at)
audit_logs      (id, user_id, action, model_type, model_id, changes JSON, created_at)
```

Catatan implementasi:
- Gunakan Laravel migration standar, `foreignId()->constrained()` untuk semua relasi.
- `transaction_entries.debit` dan `kredit` tidak boleh dua-duanya diisi di baris yang sama (validasi di service layer).
- `attachments` pakai polymorphic relation (`attachable_type`, `attachable_id`).

---

## 6. Struktur Folder yang Diharapkan

```
app/
  Models/            (Entity, User, Account, Category, Client, Project,
                       Transaction, TransactionEntry, Invoice, Budget,
                       Attachment, AuditLog)
  Services/          (TransactionService, ReportService, ImportService)
  Policies/          (EntityPolicy, TransactionPolicy, ...)
  Http/
    Controllers/     (per modul, mengikuti resource controller Laravel)
    Middleware/      (EnsureEntityAccess / SetActiveEntity)
resources/js/
  Pages/             (Dashboard, Transactions, Reports, Projects, Clients,
                       Invoices, Settings/Team)
  Components/        (EntitySwitcher, TransactionForm, dst)
  Layouts/
database/migrations/
database/seeders/     (seed: 2 entity default, chart of accounts default, admin user)
```

---

## 7. Aturan Keamanan Non-Negotiable

1. Isolasi data Personal dievaluasi di **Policy/Middleware backend**, bukan hanya disembunyikan di frontend.
2. Semua endpoint API/route yang menyentuh data entity **wajib** lewat middleware yang cek `entity_user` pivot.
3. Transaksi `approved` tidak bisa diedit/dihapus langsung — hanya lewat jurnal koreksi baru.
4. Total debit = total kredit divalidasi di server sebelum commit, dibungkus `DB::transaction()`.

---

## 8. Urutan Eksekusi untuk Agent

> Versi checklist detail per fase (task-level, dengan Definition of Done) ada di `SPRINT-PLAN-akuntansi.md`. Bagian ini hanya ringkasan urutan fase.

Kerjakan bertahap, jangan loncat fase sebelum fase sebelumnya jalan (migration + minimal 1 test/manual check):

1. **Setup project**: `laravel new` + Inertia + React + Tailwind starter kit, konfigurasi `.env`, database MySQL, auth (login/register).
2. **Entity & multi-user dasar**: migration `entities`, `entity_user`, seeder 2 entity awal, entity switcher di UI, middleware pembatas akses entity.
3. **Core accounting**: migration `accounts`, `categories`, `transactions`, `transaction_entries`, `TransactionService` (double-entry otomatis), form transaksi simpel di React.
4. **Dashboard dasar** per entity (saldo, grafik ringkas).
5. **Modul project & client** (khusus entity bisnis) + tagging transaksi ke project.
6. **Invoice & piutang tracker**.
7. **Import CSV & auto-categorization**.
8. **Laporan lanjutan**: neraca, laba-rugi, profitabilitas per project, export PDF/Excel.
9. **Audit trail & approval flow** disempurnakan di semua modul (bisa dicicil sejak fase 3, tapi finalisasi di akhir).

Di setiap fase, agent diharapkan: membuat migration → model → policy → controller → halaman React, lalu melaporkan ringkas apa yang selesai sebelum lanjut fase berikutnya.
