# Inventori App — Inventory & POS Management System

A full-stack inventory management and point-of-sale (POS) web application built with **Laravel 13** (REST API backend) and **Vue 3** (SPA frontend). Designed for small-to-medium retail businesses to manage products, transactions, sales, and reports in one place.

---

## Features

| Module | Description |
|---|---|
| **Authentication** | JWT-based login, forced password change on first login, idle auto-logout |
| **Role-Based Access** | `pengelola` (owner/manager) and `kasir` (cashier) roles with strict middleware guards |
| **Product Management** | CRUD products with categories, COGS tracking, and low-stock alerts |
| **Transactions** | Stock-in (purchase) and stock-out flows with full audit trail |
| **Point of Sale (POS)** | Kasir-friendly POS page with cart, receipt printing, and sale history |
| **Invoices / Purchases** | Create and manage supplier invoices with itemized line items |
| **Reports** | Stock summary, export, and profit report with Chart.js visualizations |
| **Notifications** | Real-time push notifications via Firebase FCM to registered devices |
| **Audit Trail** | Every write action is logged — who did what and when |
| **User Management** | Pengelola can create, edit, deactivate, and delete user accounts |

---

## Tech Stack

**Backend**
- PHP 8.3 / Laravel 13
- JWT Auth (`tymon/jwt-auth`)
- Firebase Admin SDK (`kreait/laravel-firebase`) for push notifications
- MySQL 8 (production) / SQLite (local dev)

**Frontend**
- Vue 3 + TypeScript
- Vue Router 5 + Pinia (state management)
- Tailwind CSS 4 + Radix Vue + shadcn-vue components
- Chart.js + vue-chartjs for data visualization
- Firebase JS SDK for FCM (web push)
- Vite 8 build tool

**CI/CD**
- GitLab CI — automated test (`phpunit`) and deploy pipeline
- SSH deploy to production server via GitLab CI variables

---

## Requirements

- PHP >= 8.3
- Composer
- Node.js >= 20
- MySQL 8 (or SQLite for local)
- Firebase project (for push notifications)

---

## Local Development Setup

```bash
# 1. Clone the repo
git clone <repo-url>
cd inventori-app

# 2. One-command setup (install deps, generate keys, migrate)
composer run setup

# 3. Configure environment
cp .env.example .env
# Edit .env: set DB credentials, JWT_SECRET, Firebase keys

# 4. Generate JWT secret
php artisan jwt:secret

# 5. Start all services (Laravel + queue + Vite hot reload)
composer run dev
```

The app will be available at `http://localhost:8000`.

---

## Environment Variables

Key variables to configure in `.env`:

```env
# Database (switch to mysql for production)
DB_CONNECTION=sqlite

# JWT
JWT_SECRET=<generate with: php artisan jwt:secret>

# Firebase (for push notifications)
FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
FIREBASE_PROJECT_ID=your-project-id

# Firebase frontend keys (exposed to Vite)
VITE_FIREBASE_API_KEY=
VITE_FIREBASE_AUTH_DOMAIN=
VITE_FIREBASE_PROJECT_ID=
VITE_FIREBASE_MESSAGING_SENDER_ID=
VITE_FIREBASE_APP_ID=
VITE_FIREBASE_VAPID_KEY=
```

---

## API Endpoints Overview

All routes are prefixed with `/api`. Protected routes require `Authorization: Bearer <token>`.

| Method | Endpoint | Access |
|---|---|---|
| `POST` | `/auth/login` | Public |
| `POST` | `/auth/logout` | Authenticated |
| `GET` | `/products` | Pengelola, Kasir |
| `POST` | `/products` | Pengelola only |
| `GET` | `/products/low-stock` | Pengelola, Kasir |
| `POST` | `/transactions/in` | Pengelola only |
| `POST` | `/transactions/out` | Pengelola, Kasir |
| `POST` | `/sales` | Pengelola, Kasir |
| `GET` | `/reports/profit` | Pengelola only |
| `GET` | `/reports/export` | Pengelola only |
| `GET` | `/audit-logs` | Pengelola only |
| `GET` | `/invoices` | Pengelola only |
| `GET/POST` | `/notifications` | Authenticated |

A full Postman collection is available at [`postman/inventori-pos.postman_collection.json`](postman/inventori-pos.postman_collection.json).

---

## Running Tests

```bash
# PHP unit tests
composer run test

# Frontend unit tests (Vitest)
npm run test

# Watch mode
npm run test:watch
```

---

## Production Deployment

The GitLab CI pipeline automatically:
1. Runs the full test suite on every push
2. On merge to `main`: SSH into the production server, pulls latest code, runs migrations, rebuilds assets, and restarts PHP-FPM + Nginx

Required GitLab CI/CD variables:
- `SSH_PRIVATE_KEY` — base64-encoded ed25519 private key
- `SERVER_IP` — production server IP
- `SERVER_USER` — SSH user
- `GITLAB_TOKEN` — GitLab personal access token for git pull

---

## Project Structure

```
inventori-app/
├── app/
│   ├── Http/Controllers/   # API controllers
│   ├── Http/Middleware/    # JWT, role, active-user guards
│   ├── Models/             # Eloquent models
│   └── Services/           # NotificationService (FCM)
├── database/
│   ├── migrations/         # Schema history
│   └── seeders/            # Dev seed data
├── resources/js/
│   ├── pages/              # Vue page components
│   ├── components/         # Shared UI components
│   ├── stores/             # Pinia stores (auth, cart, dashboard)
│   └── services/           # Axios API service layer
└── routes/api.php          # All API route definitions
```

---

## License

MIT

---

## Credits

**Reisan Adrefa** — Project Work 2025–2026

> Developed as a complete inventory management and POS system for retail business operations.
