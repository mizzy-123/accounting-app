# Database Schema — Aplikasi Akuntansi Personal & Bisnis (Manifestasi)

> Bagian dari satu set dokumen: `PRD-akuntansi.md`, `DATABASE-SCHEMA-akuntansi.md` (dokumen ini), `SPRINT-PLAN-akuntansi.md`, `AGENT-BUILD-SPEC-akuntansi.md`.
> Nama tabel, kolom, dan enum di sini adalah **acuan tunggal (source of truth)**. Kalau agent membuat migration Laravel, ikuti persis nama/tipe di sini kecuali ada alasan teknis kuat — dan kalau berubah, update dokumen ini juga.

Database: **MySQL**. Semua tabel pakai `id` **UUID** (`char(36)`) sebagai primary key — bukan bigint auto-increment. Laravel trait `HasUuids` dipakai di semua Eloquent model untuk generate UUID otomatis. Semua FK ke tabel lain juga pakai `char(36)` UUID. Plus `created_at`/`updated_at` (timestamps Laravel standar) kecuali disebutkan lain.

---

## 1. Entity & User Management

### `entities`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | uuid (char 36) PK | auto-generated via HasUuids |
| name | varchar(255) | contoh: "Personal", "Manifestasi" |
| type | enum('personal','business') | |
| created_at, updated_at | timestamp | |

### `users`
Tabel standar Laravel auth, dengan **UUID** sebagai PK: `id` uuid (char 36), `name`, `email`, `password`, `email_verified_at`, `remember_token`, timestamps. Trait `HasUuids` aktif.

### `entity_user` (pivot)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | bigint OK — pivot internal, tidak ter-expose ke URL |
| entity_id | uuid FK → entities.id | |
| user_id | uuid FK → users.id | |
| role | enum('owner','member','viewer') | role spesifik per entity |
| created_at, updated_at | timestamp | |

Index: unique (`entity_id`, `user_id`).

---

## 2. Core Accounting

### `accounts`
Chart of accounts, per entity.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| entity_id | bigint FK → entities.id | |
| name | varchar(255) | contoh: "Kas", "Bank BCA", "Piutang Client" |
| type | enum('asset','liability','equity','revenue','expense') | |
| is_active | boolean default true | |
| created_at, updated_at | timestamp | |

### `categories`
Kategori transaksi (untuk pelaporan & auto-categorization), per entity.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| entity_id | bigint FK → entities.id | |
| name | varchar(255) | contoh: "Transport", "Gaji", "Fee Project" |
| type | enum('income','expense') | |
| created_at, updated_at | timestamp | |

### `transactions` (header)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| entity_id | bigint FK → entities.id | |
| project_id | bigint FK → projects.id, nullable | hanya diisi untuk entity bisnis |
| client_id | bigint FK → clients.id, nullable | |
| category_id | bigint FK → categories.id, nullable | kategori utama transaksi (untuk laporan cepat) |
| date | date | tanggal transaksi |
| description | text | |
| type | enum('income','expense','transfer','inter_entity_transfer','adjustment') | jenis transaksi, menentukan bagaimana entries di-generate |
| status | enum('draft','pending_approval','approved') | default `draft` |
| created_by | bigint FK → users.id | |
| approved_by | bigint FK → users.id, nullable | |
| approved_at | timestamp, nullable | |
| created_at, updated_at | timestamp | |

### `transaction_entries` (baris debit/kredit)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| transaction_id | bigint FK → transactions.id | |
| account_id | bigint FK → accounts.id | |
| debit | decimal(15,2) default 0 | |
| kredit | decimal(15,2) default 0 | |
| created_at, updated_at | timestamp | |

**Aturan wajib (divalidasi di service layer, bukan cuma di DB):**
- Untuk satu baris `transaction_entries`, hanya salah satu dari `debit`/`kredit` yang boleh > 0.
- Untuk satu `transaction_id`, `SUM(debit) = SUM(kredit)` sebelum status bisa berubah dari `draft`.
- Transaksi `inter_entity_transfer` menghasilkan entries di dua `entity_id` berbeda yang saling terhubung lewat `description`/referensi yang sama (lihat `SPRINT-PLAN-akuntansi.md` fase Core Accounting untuk detail implementasi).

---

## 3. Project & Client (khusus entity bisnis)

### `clients`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | uuid (char 36) PK | auto-generated via HasUuids |
| entity_id | uuid FK → entities.id | cascade delete |
| name | varchar(255) | |
| contact_info | text nullable | email, phone, alamat (free-form) |
| created_at, updated_at | timestamp | |

### `projects`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | uuid (char 36) PK | auto-generated via HasUuids |
| entity_id | uuid FK → entities.id | cascade delete |
| client_id | uuid FK → clients.id nullable | null = proyek tanpa client |
| name | varchar(255) | |
| budget | decimal(15,2) nullable | |
| start_date | date nullable | |
| end_date | date nullable | |
| status | enum('active','completed','cancelled') | default `active` |
| created_at, updated_at | timestamp | |

### `invoices`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | uuid (char 36) PK | Sprint 5 |
| project_id | uuid FK → projects.id | |
| invoice_number | varchar(50) unique | |
| items | json | array item: `{name, qty, price}` |
| total | decimal(15,2) | |
| status | enum('draft','sent','paid') | default `draft` |
| due_date | date nullable | untuk reminder piutang |
| created_at, updated_at | timestamp | |

---

## 4. Budget, Attachment, Audit

### `budgets`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | uuid (char 36) PK | auto-generated via HasUuids |
| entity_id | uuid FK → entities.id | cascade delete |
| category_id | uuid FK → categories.id | restrict delete |
| period | varchar(7) | format `YYYY-MM` |
| amount | decimal(15,2) | |
| created_at, updated_at | timestamp | |

Index: unique (`entity_id`, `category_id`, `period`).

### `attachments`
Polymorphic — bisa dipakai untuk transaksi, invoice, dll.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| attachable_type | varchar(255) | contoh: `App\Models\Transaction` |
| attachable_id | bigint | |
| file_path | varchar(255) | |
| created_at, updated_at | timestamp | |

### `audit_logs`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK → users.id | |
| action | varchar(50) | `created`/`updated`/`deleted`/`approved` |
| model_type | varchar(255) | |
| model_id | bigint | |
| changes | json | before/after diff |
| created_at | timestamp | |

---

## 5. Import & Auto-Categorization

### `bank_import_rules`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| entity_id | bigint FK → entities.id | |
| keyword | varchar(255) | contoh: "GOJEK" |
| category_id | bigint FK → categories.id | |
| created_at, updated_at | timestamp | |

### `bank_import_batches` (opsional, untuk tracking history import CSV)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| entity_id | bigint FK → entities.id | |
| file_name | varchar(255) | |
| imported_by | bigint FK → users.id | |
| total_rows | int | |
| created_at | timestamp | |

---

## 6. Relasi Ringkas (ERD Textual)

```
entities 1---N entity_user N---1 users
entities 1---N accounts
entities 1---N categories
entities 1---N clients
entities 1---N projects (via clients)
entities 1---N transactions
entities 1---N budgets
entities 1---N bank_import_rules

projects 1---N invoices
projects 1---N transactions (nullable link)
clients  1---N projects
clients  1---N transactions (nullable link)

transactions 1---N transaction_entries
accounts     1---N transaction_entries

users 1---N transactions (created_by)
users 1---N transactions (approved_by, nullable)
users 1---N audit_logs

* (polymorphic) 1---N attachments
```

## 7. Index & Constraint Penting

- `entity_user`: unique (`entity_id`, `user_id`)
- `invoices.invoice_number`: unique
- Semua kolom `entity_id` di tabel transaksional: index biasa (dipakai terus untuk scoping query)
- `transaction_entries.transaction_id`: index (untuk agregasi cepat saat validasi balance)
- Foreign key `on delete restrict` untuk relasi yang berkaitan dengan histori keuangan (`accounts`, `transactions`, `transaction_entries`) — jangan pakai `cascade` supaya data keuangan tidak bisa hilang tidak sengaja.

## 8. Seeder Default (dijalankan saat setup awal, lihat Sprint 0 di `SPRINT-PLAN-akuntansi.md`)

- 2 entity: `Personal` (type `personal`), `Manifestasi` (type `business`)
- 1 user Owner dengan role `owner` di kedua entity
- Chart of accounts dasar per entity (Kas, Bank, Piutang, Hutang, Modal, Pendapatan, Beban)
- Kategori dasar (income: "Gaji/Fee", "Lainnya"; expense: "Transport", "Makan", "Utilities", "Operasional")
