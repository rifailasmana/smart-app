# Majar Signature – Nuxt 3 Frontend

## Setup

```bash
cd frontend
npm install
cp .env.example .env   # already done
```

Edit `.env` with your Laravel backend URL:
```
NUXT_PUBLIC_API_BASE=http://localhost:8000
```

## Development

Start the Laravel backend first:
```bash
# In project root
php artisan serve          # runs on :8000
```

Then start Nuxt:
```bash
cd frontend
npm run dev               # runs on http://localhost:3000
```

## Build for Production

```bash
cd frontend
npm run build
node .output/server/index.mjs
```

## Architecture

| Layer | Technology |
|-------|-----------|
| Framework | Nuxt 3 (Vue 3 Composition API) |
| State | Pinia (`stores/auth.ts`) |
| Styling | Tailwind CSS + Bootstrap Icons |
| API | Laravel 10 + Sanctum token auth |
| Auth flow | `POST /api/auth/login` → token stored in localStorage |

## Route Map

| URL | Layout | Who |
|-----|--------|-----|
| `/login` | default | all |
| `/terminal/kasir` | terminal | kasir role |
| `/terminal/waiter` | terminal | waiter role |
| `/terminal/kitchen` | terminal | kitchen role |
| `/dashboard/owner` | dashboard | owner |
| `/dashboard/manager` | dashboard | manager |
| `/dashboard/hrd` | dashboard | hrd |
| `/dashboard/inventory` | dashboard | inventory |
| `/dashboard/admin` | dashboard | admin |

## Auth Store Roles → Routes

```ts
kasir    → /terminal/kasir
waiter   → /terminal/waiter
kitchen  → /terminal/kitchen
owner    → /dashboard/owner
manager  → /dashboard/manager
hrd      → /dashboard/hrd
inventory → /dashboard/inventory
admin    → /dashboard/admin
```
