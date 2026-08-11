# 🐞 Issue & Bug Tracker

A full-stack, real-time issue and project management platform built for engineering teams. It streamlines the lifecycle of bugs and feature requests — from creation, triage, and assignment through resolution, with live collaboration over WebSockets, role-based access control, file attachments, threaded comments, and an administrative dashboard.

---

## 📑 Table of Contents

- [🐞 Issue \& Bug Tracker](#-issue--bug-tracker)
  - [📑 Table of Contents](#-table-of-contents)
  - [🔭 Overview](#-overview)
  - [✨ Key Features](#-key-features)
    - [Project \& Issue Management](#project--issue-management)
    - [Authentication \& Authorization](#authentication--authorization)
    - [Real-Time Collaboration](#real-time-collaboration)
    - [Dashboards \& Auditability](#dashboards--auditability)
    - [Frontend Experience](#frontend-experience)
  - [🧱 Tech Stack](#-tech-stack)
  - [🏛 System Architecture](#-system-architecture)
  - [📁 Repository Structure](#-repository-structure)
  - [✅ Prerequisites](#-prerequisites)
  - [⚙️ Installation \& Local Setup](#️-installation--local-setup)
    - [1. Clone the repository](#1-clone-the-repository)
    - [2. Backend setup](#2-backend-setup)
    - [3. Frontend setup](#3-frontend-setup)
  - [🔐 Environment Configuration](#-environment-configuration)
    - [`backend/.env` (key entries)](#backendenv-key-entries)
    - [`frontend/.env`](#frontendenv)
  - [▶️ Running the Application](#️-running-the-application)
    - [Production build](#production-build)
  - [📡 API Reference](#-api-reference)
    - [Authentication](#authentication)
    - [Users \& Profile](#users--profile)
    - [Projects \& Members](#projects--members)
    - [Issues](#issues)
    - [Comments](#comments)
    - [Attachments](#attachments)
    - [Dashboard, Notifications \& Activity](#dashboard-notifications--activity)
    - [Example request](#example-request)
  - [🧬 Domain Model](#-domain-model)
  - [🔄 Real-Time Events](#-real-time-events)
  - [🛣 Roadmap](#-roadmap)
  - [🤝 Contributing](#-contributing)
  - [📄 License](#-license)
  - [👤 Author](#-author)

---

## 🔭 Overview

The **Issue & Bug Tracker** is a self-hosted alternative to commercial tools such as Jira, Linear, and Bugzilla. It is designed around three guiding principles:

1. **Clarity** — every issue carries structured metadata (type, severity, priority, status) so triage is unambiguous.
2. **Collaboration** — comments, attachments, mentions, and notifications are first-class concepts, not afterthoughts.
3. **Insight** — every state transition is recorded as an auditable activity log, surfaced through a dashboard for managers.

The application is composed of a **Laravel 13 REST API** and a **Vue 3 single-page application**. Real-time updates are delivered via **Laravel Reverb** (WebSocket server) using the official [Laravel Echo](https://laravel.com/docs/broadcasting) client.

---

## ✨ Key Features

### Project & Issue Management
- Full CRUD lifecycle for **projects**, **issues**, **comments**, and **attachments**.
- Structured issue metadata via PHP 8.1+ backed enums:
  `IssueType` (Bug, Feature, Task, Improvement), `IssueStatus`, `Priority`, `Severity`.
- Business actions distinct from generic updates: `assign`, `changeStatus`, `reopen`, `close`.
- Project membership with role-aware access control (admin, manager, developer, reporter).
- Soft deletes and Eloquent API Resources for consistent JSON shaping.

### Authentication & Authorization
- Token-based authentication using **Laravel Sanctum**.
- Admin-only user management (create, update, deactivate) with role/status enums.
- Per-user profile management (`GET /user/profile`, `PUT /user/profile`).

### Real-Time Collaboration
- **Laravel Reverb** WebSocket server with private and presence channels.
- Live broadcasts for: `IssueCreated`, `IssueUpdated`, `IssueAssigned`, `IssueStatusChanged`, `CommentCreated`, `AttachmentUploaded`, `AttachmentDeleted`, `NotificationCreated`, `ActivityLogged`.
- In-app inbox with unread badge, mark-as-read, and mark-all-as-read.

### Dashboards & Auditability
- Aggregated dashboard endpoint with project, issue, and activity statistics.
- Full **activity log** of every domain mutation, exposed via `GET /activity-logs`.
- Centralized `ActivityLogService` writes one row per business event with actor, target, and payload.

### Frontend Experience
- Responsive SPA built on **Vue 3** (Composition API), **Vite 6**, and **TailAdmin Vue**.
- Rich component set: charts (ApexCharts), drag-and-drop Kanban (`vue-kanban`, `vuedraggable`), calendar (FullCalendar), data tables, file uploader (Dropzone).
- **Pinia** stores, **Vue Router 4** with route guards, **SweetAlert2** confirmations, **VueUse** utilities, dark mode.

---

## 🧱 Tech Stack

| Layer            | Technology                                                                                     |
| ---------------- | ---------------------------------------------------------------------------------------------- |
| Backend          | PHP 8.3 · Laravel 13 · Laravel Sanctum 4 · Laravel Reverb 1 · PHPUnit 12                        |
| Frontend         | Vue 3.5 · Vite 6 · Vue Router 4 · Pinia 2 · Tailwind CSS 4 · TypeScript · ApexCharts · PrimeVue |
| Realtime         | Laravel Echo · Pusher protocol over WebSockets (Reverb)                                        |
| Database         | SQLite (default) · MySQL/PostgreSQL supported                                                  |
| Auth             | Laravel Sanctum bearer tokens                                                                  |
| Tooling          | Composer · npm · ESLint · Prettier · Laravel Pint                                              |

---

## 🏛 System Architecture

```
                ┌───────────────────────────┐
                │      Vue 3 SPA (Vite)     │
                │  TailAdmin · Pinia · Axios│
                └──────┬───────────┬────────┘
                       │ HTTPS     │ WebSocket (Reverb)
                       │           ▼
┌──────────────────────────┐   ┌──────────────────────┐
│  Laravel REST API        │   │  Laravel Reverb      │
│  Sanctum Auth · Services │◄──┤  Broadcast Server    │
│  Eloquent ORM · Events   │   └──────────────────────┘
└──────────┬───────────────┘
           ▼
      ┌─────────────┐
      │  Database   │   SQLite / MySQL / PostgreSQL
      └─────────────┘
```

- The API is **stateless** and uses bearer tokens; each request to a protected route passes through `auth:sanctum`.
- **Service classes** (`IssueService`, `ProjectService`, `CommentService`, `NotificationService`, `ActivityLogService`, `AttachmentService`, `ProjectMemberService`, `UserService`) own the business logic; controllers remain thin.
- **Events** are dispatched from services and broadcast on private channels; the frontend subscribes via `laravel-echo`.

---

## 📁 Repository Structure

```
issue-bug-tracker/
├── backend/                 # Laravel 13 API
│   ├── app/
│   │   ├── Enums/           # PHP backed enums (IssueType, Priority, etc.)
│   │   ├── Events/          # Broadcastable domain events
│   │   ├── Http/
│   │   │   ├── Controllers/Api/
│   │   │   ├── Requests/    # Form requests (validation)
│   │   │   └── Resources/   # JSON resources
│   │   ├── Models/          # Eloquent models
│   │   ├── Notifications/   # SystemNotification
│   │   └── Services/        # Business logic layer
│   ├── database/            # Migrations, seeders, factories
│   ├── routes/
│   │   ├── api.php          # All API endpoints
│   │   └── channels.php     # Broadcast channel auth
│   ├── config/
│   └── public/              # Web root (index.php)
│
├── frontend/                # Vue 3 SPA
│   ├── src/
│   │   ├── views/           # Admin / Auth / Pages / Tables / Charts / Forms
│   │   ├── components/      # Reusable UI (issues, comments, attachments, …)
│   │   ├── stores/          # Pinia stores
│   │   ├── services/        # Axios API + realtime (Echo) wrappers
│   │   ├── router/          # Vue Router 4 with guards
│   │   └── composables/     # Reusable composition functions
│   └── vite.config.js
│
└── readme.md                # You are here
```

---

## ✅ Prerequisites

Before installing, make sure your machine has:

| Tool   | Version           | Notes                                  |
| ------ | ----------------- | -------------------------------------- |
| PHP    | **≥ 8.3**         | With `pdo_sqlite` / `pdo_mysql`        |
| Composer | **≥ 2.x**      |                                        |
| Node   | **≥ 18.x** (20 LTS recommended) | npm ships with Node |
| SQLite | bundled with PHP  | default; swap for MySQL/Postgres if needed |
| Git    | latest            |                                        |

> **Tip (Windows):** install PHP via [Herd](https://herd.laravel.com) or [Laragon](https://laragon.org) for the smoothest setup.

---

## ⚙️ Installation & Local Setup

### 1. Clone the repository

```bash
git clone https://github.com/alsakib748/issue-bug-tracker.git
cd issue-bug-tracker
```

### 2. Backend setup

```bash
cd backend

# Install PHP dependencies
composer install

# Create environment file and generate application key
cp .env.example .env
php artisan key:generate

# Create SQLite database file and run migrations
touch database/database.sqlite
php artisan migrate --seed

# (Optional) install JS dependencies if you plan to build frontend assets here
npm install
```

### 3. Frontend setup

```bash
cd ../frontend

# Install JS dependencies
npm install
```

---

## 🔐 Environment Configuration

### `backend/.env` (key entries)

```dotenv
APP_NAME="Issue Bug Tracker"
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite          # or mysql / pgsql

SESSION_DRIVER=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=reverb   # enables Reverb broadcasting

# Laravel Reverb (WebSocket server)
REVERB_APP_ID=issue-tracker
REVERB_APP_KEY=pdhydnsdcbmqawzt5zse
REVERB_APP_SECRET=<generate-a-strong-secret>
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# Frontend origin for CORS / Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:5173
FRONTEND_URL=http://localhost:5173
```

### `frontend/.env`

```dotenv
VITE_API_URL=http://localhost:8000/api
VITE_BACKEND_URL=http://localhost:8000

VITE_REVERB_APP_KEY=pdhydnsdcbmqawzt5zse
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

> Regenerate `REVERB_APP_SECRET` with `php artisan reverb:install` and never commit secrets to source control.

---

## ▶️ Running the Application

You will need **four** terminal windows during development.

| # | Terminal | Command                          | Purpose                         |
| - | -------- | -------------------------------- | ------------------------------- |
| 1 | backend  | `php artisan serve`              | HTTP API on `:8000`             |
| 2 | backend  | `php artisan queue:listen`       | Process queued jobs             |
| 3 | backend  | `php artisan reverb:start`       | WebSocket server on `:8080`     |
| 4 | frontend | `npm run dev`                    | Vite dev server on `:5173`      |

Open **<http://localhost:5173>** in your browser. The default admin seeded by migrations can be overridden in `database/seeders/`.

### Production build

```bash
# Backend
cd backend
php artisan config:cache
php artisan route:cache
php artisan optimize

# Frontend
cd ../frontend
npm run build       # outputs to dist/
```

---

## 📡 API Reference

All routes are prefixed with `/api` and require a `Bearer` token unless noted. See [`backend/routes/api.php`](backend/routes/api.php) for the canonical definition.

### Authentication

| Method | Endpoint      | Auth | Description                  |
| ------ | ------------- | ---- | ---------------------------- |
| POST   | `/login`      | —    | Issue a Sanctum token        |
| POST   | `/logout`     | ✔    | Revoke the current token     |
| GET    | `/user`       | ✔    | Authenticated user details   |

### Users & Profile

| Method | Endpoint           | Description              |
| ------ | ------------------ | ------------------------ |
| GET    | `/users`           | List users (admin)       |
| POST   | `/users`           | Create user (admin)      |
| GET    | `/users/{user}`    | Show user                |
| PUT    | `/users/{user}`    | Update user              |
| DELETE | `/users/{user}`    | Delete user              |
| GET    | `/user/profile`    | Current user's profile   |
| PUT    | `/user/profile`    | Update own profile       |

### Projects & Members

| Method | Endpoint                                  | Description                |
| ------ | ----------------------------------------- | -------------------------- |
| GET    | `/projects`                               | List projects              |
| POST   | `/projects`                               | Create project             |
| GET    | `/projects/{project}`                     | Show project               |
| PUT    | `/projects/{project}`                     | Update project             |
| DELETE | `/projects/{project}`                     | Delete project             |
| GET    | `/projects/{project}/members`             | List members               |
| POST   | `/projects/{project}/members`             | Add member                 |
| DELETE | `/projects/{project}/members/{user}`      | Remove member              |
| GET    | `/projects/{project}/members/available`   | Eligible candidates        |

### Issues

| Method | Endpoint                          | Description                       |
| ------ | --------------------------------- | --------------------------------- |
| GET    | `/issues`                         | List / filter issues              |
| POST   | `/issues`                         | Create issue                      |
| GET    | `/issues/{issue}`                 | Show issue                        |
| PUT    | `/issues/{issue}`                 | Update issue                      |
| DELETE | `/issues/{issue}`                 | Delete issue                      |
| PATCH  | `/issues/{issue}/assign`          | Assign / reassign                 |
| PATCH  | `/issues/{issue}/status`          | Change status                     |
| PATCH  | `/issues/{issue}/reopen`          | Reopen closed issue               |
| PATCH  | `/issues/{issue}/close`           | Close issue                       |

### Comments

| Method | Endpoint                          | Description         |
| ------ | --------------------------------- | ------------------- |
| GET    | `/issues/{issue}/comments`        | List comments       |
| POST   | `/issues/{issue}/comments`        | Add comment         |
| PUT    | `/comments/{comment}`             | Edit comment        |
| DELETE | `/comments/{comment}`             | Delete comment      |

### Attachments

| Method | Endpoint                                       | Description        |
| ------ | ---------------------------------------------- | ------------------ |
| GET    | `/issues/{issue}/attachments`                  | List attachments   |
| POST   | `/issues/{issue}/attachments`                  | Upload file        |
| GET    | `/attachments/{attachment}/download`           | Download file      |
| DELETE | `/attachments/{attachment}`                    | Remove attachment  |

### Dashboard, Notifications & Activity

| Method | Endpoint                              | Description                       |
| ------ | ------------------------------------- | --------------------------------- |
| GET    | `/dashboard`                          | Aggregated stats                  |
| GET    | `/notifications`                      | List notifications                |
| PATCH  | `/notifications/{id}/read`            | Mark as read                      |
| PATCH  | `/notifications/read-all`             | Mark all as read                  |
| DELETE | `/notifications/{id}`                 | Delete notification               |
| GET    | `/activity-logs`                      | Audit log                         |
| GET    | `/activity-logs/{activityLog}`        | Single log entry                  |

### Example request

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

---

## 🧬 Domain Model

| Entity         | Purpose                                                        |
| -------------- | -------------------------------------------------------------- |
| `User`         | Account, role (`UserRole`), status (`UserStatus`)              |
| `Project`      | Container for issues, status (`ProjectStatus`), members        |
| `ProjectMember`| Join table — user ↔ project with role                          |
| `Issue`        | Type, status, priority, severity; belongs to project & assignee |
| `Comment`      | Threaded discussion on an issue                                |
| `Attachment`   | File metadata; uploaded against an issue                       |
| `Notification` | Per-user inbox entries generated by system events              |
| `ActivityLog`  | Append-only audit trail of every domain mutation               |

---

## 🔄 Real-Time Events

All events are broadcast on the private channel `issues.{id}` (or `App.Models.User.{id}` for personal notifications) via Laravel Reverb.

| Event                 | Trigger                                  |
| --------------------- | ---------------------------------------- |
| `IssueCreated`        | A new issue is filed                      |
| `IssueUpdated`        | Issue fields change                      |
| `IssueAssigned`       | Assignee changes                         |
| `IssueStatusChanged`  | Status transitions (incl. reopen/close)  |
| `CommentCreated`      | A comment is posted                      |
| `AttachmentUploaded`  | A file is attached                       |
| `AttachmentDeleted`   | A file is removed                        |
| `NotificationCreated` | A notification is generated              |
| `ActivityLogged`      | Any audited action occurred              |

Channel authorization lives in [`backend/routes/channels.php`](backend/routes/channels.php) and gates every subscription to authenticated participants.

---

## 🛣 Roadmap

- [ ] Email & push notifications
- [ ] Issue templates and bulk import
- [ ] Sprint / milestone planning view
- [ ] Saved filters and personal dashboards
- [ ] Webhook integrations (Slack, Discord)
- [ ] Rate-limited public API keys for integrations
- [ ] Docker Compose for one-command deployment

---

## 🤝 Contributing

1. Fork the repository.
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit your changes with conventional messages.
4. Push the branch and open a Pull Request.

Please run before submitting:

```bash
# Backend
cd backend && composer pint && php artisan test

# Frontend
cd frontend && npm run lint && npm run build
```

---

## 📄 License

This project is released under the **MIT License**. See [`LICENSE`](LICENSE) for details.

---

## 👤 Author

**alsakib748** — [github.com/alsakib748](https://github.com/alsakib748)

Built with Laravel 13, Vue 3, and a great deal of coffee ☕.
