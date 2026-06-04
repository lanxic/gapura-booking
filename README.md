# Amartha eTicket

Platform ticketing berbasis web untuk Amartha — full custom, menggantikan GlobalTix untuk full ownership data dan eliminasi biaya komisi.

## Tech Stack

| Layer | Teknologi | Versi |
|---|---|---|
| Storefront | Next.js (App Router + Turbopack) | 16.x |
| Admin Portal | Next.js (App Router + Turbopack) | 16.x |
| UI Styling | Tailwind CSS | v4.x |
| UI Components | shadcn/ui + Lucide React | latest |
| State Management | Zustand | ^5 |
| Data Fetching | TanStack Query | ^5 |
| Form Handling | React Hook Form + Zod | ^7 / ^3 |
| Backend API | Laravel | 12.x |
| Database | MySQL | 8.0 |
| Cache & Queue | Redis | 7.x |
| Auth | tymon/jwt-auth | ^2.1 |
| Storage & CDN | Cloudinary | latest |
| Payment | Midtrans Snap | v2 |
| Email | Resend | latest |
| WhatsApp | Fonnte | latest |
| E-Ticket PDF | barryvdh/dompdf | ^3.0 |
| Package Manager | PNPM Workspaces | ^9 |
| Build System | Turborepo | latest |

## Struktur Monorepo

```
amartha-eticket/
├── apps/
│   ├── web/          # Next.js 16 — Storefront customer (port 3000)
│   ├── admin/        # Next.js 16 — Admin/Supervisor/Kasir/Scanner portal (port 3001)
│   └── api/          # Laravel 12 — REST API (port 8000)
├── packages/
│   └── ui/           # Shared component library (Button, Card, Badge, Input)
├── docker-compose.yml
├── turbo.json
├── pnpm-workspace.yaml
└── package.json
```

## Prasyarat

- Node.js >= 22
- PNPM >= 9 — `npm install -g pnpm`
- PHP >= 8.2
- Composer >= 2
- Docker & Docker Compose (untuk MySQL + Redis)

## Memulai (Development)

### 1. Clone & install dependencies

```bash
git clone <repo-url> amartha-eticket
cd amartha-eticket

# Install semua JS dependencies (web + admin + packages/ui)
pnpm install

# Install PHP dependencies
cd apps/api && composer install && cd ../..
```

### 2. Setup environment

```bash
# Root (untuk Next.js apps)
cp .env.example .env

# Laravel API
cp apps/api/.env.example apps/api/.env
php -r "echo 'APP_KEY=' . 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;" >> apps/api/.env
cd apps/api && php artisan jwt:secret && cd ../..
```

Isi nilai yang diperlukan di `apps/api/.env`:

```env
DB_DATABASE=amartha_eticket
DB_USERNAME=root
DB_PASSWORD=        # sesuai docker-compose

CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=

MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=

RESEND_API_KEY=
FONNTE_TOKEN=
```

### 3. Jalankan infrastruktur (MySQL + Redis)

```bash
docker compose up -d
```

| Service | Port | Keterangan |
|---|---|---|
| MySQL 8 | 3306 | Database utama |
| Redis 7 | 6379 | Cache, queue, seat hold |
| Mailpit | 8025 | Email preview (dev) |

### 4. Migrasi & seed database

```bash
cd apps/api
php artisan migrate
php artisan db:seed
```

Output seeder akan menampilkan akun testing dan booking code yang tersedia.

### 5. Jalankan semua app sekaligus

```bash
# Dari root — jalankan web + admin sekaligus via Turborepo
pnpm dev

# Atau per app
pnpm dev:web    # http://localhost:3000
pnpm dev:admin  # http://localhost:3001
```

```bash
# Laravel API (dari apps/api/)
php artisan serve --port=8000

# Queue worker (terminal terpisah)
php artisan queue:work
```

## Akun Testing

Semua password: `password`

| Role | Email | Akses |
|---|---|---|
| super_admin | superadmin@amartha.test | Full akses + hapus permanen |
| admin | admin@amartha.test | Full operasional |
| supervisor | supervisor@amartha.test | Correction request + activity log |
| kasir | kasir@amartha.test | Collect pelunasan DP |
| scanner | scanner@amartha.test | Scan QR tiket di gate |
| customer | customer@amartha.test | Beli tiket, lihat order |
| customer | customer2@amartha.test | Beli tiket, lihat order |

## Booking Code & Invoice

| Identifier | Format | Contoh | Keterangan |
|---|---|---|---|
| Booking Code | `AMT-XXXXXXXX` | `AMT-FULL0001` | Customer-facing, untuk tracking pesanan |
| Invoice Number | `INV-YYYYMMDD-XXXXXXXX` | `INV-20260604-FL0001` | Dikirim ke Midtrans sebagai `order_id` |

Satu booking code bisa memiliki lebih dari satu invoice (DP + pelunasan, atau retry payment).

## Data Seed untuk Testing

| Booking Code | Status | Keterangan |
|---|---|---|
| `AMT-FULL0001` | `paid` | Full payment, 2 tiket belum scan |
| `AMT-DP300001` | `dp_paid` | DP 30%, sisa Rp 259.000 di kasir |
| `AMT-DP500001` | `paid` | DP 50%, sudah lunas di kasir |
| `AMT-SCN00001` | `paid` | Tiket sudah di-scan |
| `AMT-PND00001` | `pending` | Belum bayar |
| `AMT-EXP00001` | `expired` | Order kadaluarsa |
| `AMT-VOC00001` | `paid` | Pakai voucher `HEMAT50K`, ada retry payment |

Voucher aktif: `WELCOME10` (10%), `HEMAT50K` (Rp 50.000), `SAFARI20` (20%)

## Endpoint API

Base URL: `http://localhost:8000/v1`

| Grup | Prefix | Auth |
|---|---|---|
| Public | `/products`, `/orders`, `/payments` | — |
| Auth | `/auth/{role}/login` | — |
| Customer | `/customer/*` | Bearer (customer) |
| Scanner | `/scanner/*` | Bearer (scanner) |
| Kasir | `/kasir/*` | Bearer (kasir) |
| Supervisor | `/supervisor/*` | Bearer (supervisor) |
| Admin | `/admin/*` | Bearer (admin/super_admin) |
| Health | `/health` | — |

## Build untuk Production

```bash
# Build semua Next.js apps
pnpm build

# Build per app
pnpm build:web
pnpm build:admin

# Laravel — optimize
cd apps/api
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Lisensi

Confidential — Internal Use Only © Amartha eTicket
