# Search29 Analytics AI — Backend Foundation

This is the **foundation layer** of the platform: multi-tenant data model, auth (incl. 2FA),
and RBAC. It's built to Laravel 12 conventions but authored as source files here (no `composer
install` was run in this environment — see "Getting it running" below).

## What's included

```
app/
  Models/                    User, Agency, Client, Project, TeamMember, Plan, Role, Permission, AuditLog
  Repositories/
    Contracts/                Interfaces (dependency-inverted — services depend on these, not Eloquent)
    Eloquent/                 Concrete implementations
  Services/
    Auth/                      AuthService (register/login/tokens), TwoFactorService (Google2FA)
    RBAC/                       RBACService (role/permission provisioning + checks)
    Agency/                     AgencyService (agency creation, branding, custom domain)
    Client/                     ClientService (client creation with plan-limit enforcement)
  Http/
    Controllers/Api/V1/        Auth, Admin (Master Admin), Agency (tenant-scoped)
    Middleware/                 CheckPermission, EnsureAgencyOwnsResource
    Requests/ Resources/        Form validation + API response shaping
  Providers/
    RepositoryServiceProvider.php   Binds every repository interface -> implementation

database/
  migrations/                 users, agencies, clients, projects, team_members, plans,
                               RBAC tables (Spatie-compatible), audit_logs, activity_logs
  seeders/                    PlanSeeder, RolePermissionSeeder (creates Master Admin), DatabaseSeeder

routes/api.php                 Versioned (/api/v1), permission- and tenant-guarded
bootstrap/app.php, providers.php
config/permission.php
```

## Architecture decisions

- **Repository Pattern + Service Layer**: Controllers never touch Eloquent directly. They call
  Services, which call Repository *interfaces*. Swapping MySQL for something else, or adding
  caching, only touches the `Eloquent/` implementations.
- **Multi-tenancy**: every tenant-owned table carries `agency_id`. The `EnsureAgencyOwnsResource`
  middleware blocks cross-tenant access at the HTTP layer; repositories additionally scope queries
  by `agency_id` so a bug in one layer doesn't expose another tenant's data.
- **RBAC**: built on `spatie/laravel-permission` with its **teams** feature, using `agency_id` as
  the team key. This means the same role name (e.g. "Manager") can carry different permissions per
  agency, while Master Admin is a single global role (`agency_id = null`). `RBACService` centralizes
  provisioning so every new agency gets a consistent starting role set (Agency Owner, Manager,
  Analyst, Viewer) without duplicating logic.
- **Auth**: Sanctum personal-access tokens (works for both SPA and mobile). Google2FA-based 2FA
  with recovery codes. Google/Microsoft OAuth hooks are stubbed in `routes/api.php` — wire up
  `laravel/socialite` there when you build the social-login module.

## What's included (updated)

Beyond the auth/RBAC foundation described above, this now also includes:

- **Integrations module** (`app/Integrations/`): a pluggable connector architecture.
  `IntegrationProviderInterface` is the contract every connector implements;
  `IntegrationManager` is the registry mapping provider keys to classes. **Google Analytics 4**
  is built out end-to-end as the template — real OAuth2 flow + GA4 Data API `runReport` calls
  (see `GoogleAnalytics4Provider`). Adding the next connector (Search Console, Meta Ads, ...) is
  one new class + one registry line; no schema or controller changes needed.
  `IntegrationService` orchestrates connect/callback/disconnect/sync, `SyncIntegrationDataJob`
  is the queued job the scheduler dispatches on each integration's `sync_frequency`, and
  `analytics_metrics` is the generic time-series table every provider writes into (so dashboard
  widgets stay provider-agnostic).
- **Dashboard builder** (`app/Dashboard/WidgetCatalogue.php` + `DashboardService`): layouts
  (`dashboard_layouts`) hold widgets (`dashboard_widgets`) with grid position/size, matching
  `react-grid-layout`'s `{x, y, w, h}` shape 1:1. `WidgetCatalogue` is a single registry of every
  widget type (covering the full KPI/list/health-score set from the spec) — adding a widget type
  is one array entry, no migration. `DashboardController` exposes CRUD for layouts, add/remove
  widget, and a bulk `positions` endpoint for saving after a drag/resize session.

- **AI Health Score engine** (`app/HealthScore/`): a 0–100 score per client, computed from
  7 pluggable category calculators (Growth, SEO, Ads, Social, Website, Lead, Revenue), each
  implementing `ScoreCalculatorInterface` — same registry pattern as Integrations and
  Dashboard widgets, so adding a category (e.g. once Core Web Vitals data exists) is one class.
  `ScoreMath` centralizes the normalization formulas (documented, linear-clamp based — no
  black-box model) and re-normalizes weights when a category has no data yet, so a client with
  only GA4 connected still gets a meaningful score from what's actually available. Each
  calculator also emits plain-language improvement suggestions when its score is low.
  `HealthScoreService` computes-and-stores one row/day in `health_scores` (powering both
  "today's score" and 90-day trend/historical-comparison views), `ComputeHealthScoreJob` +
  the `health-scores:compute` scheduled command run this nightly after integration syncs.

## What's deliberately NOT here yet

This is the foundation only, per your last message. This is the foundation plus three full vertical modules. Not yet built (next milestones):
- React frontend for the remaining screens (Insights, Reports, Settings)
- More integration connectors (Search Console, Google Ads, Meta Ads, LinkedIn, TikTok, CRMs, ...)
- AI chat assistant, automated anomaly detection (Health Score's suggestions are rule-based
  today — an LLM call could generate more personalized recommendations on top of the same
  category signals)
- Billing, scheduled reports, notifications

## Getting it running

This code is the **application layer** (app/, database/migrations, database/seeders,
routes/api.php, routes/console.php, config/permission.php, config/services.php,
bootstrap/app.php, bootstrap/providers.php) — not a full Laravel skeleton. There's no
`artisan`, `public/index.php`, `config/app.php`, etc. here, since those are Laravel's own
boilerplate and this environment can't reach packagist.org to generate them.

To run it for real:

```bash
composer create-project laravel/laravel:^12.0 search29-analytics
cd search29-analytics
# Copy this repo's app/, database/, routes/, config/permission.php, config/services.php,
# bootstrap/app.php, and bootstrap/providers.php over the freshly generated skeleton's.
composer require laravel/sanctum spatie/laravel-permission pragmarx/google2fa-laravel \
  predis/predis barryvdh/laravel-dompdf maatwebsite/excel darkaonline/l5-swagger laravel/socialite

cp .env.example .env
# Add one line to the generated config/app.php: 'frontend_url' => env('FRONTEND_URL'),
# (used by IntegrationController's OAuth callback redirect)

php artisan key:generate
php artisan migrate --seed   # creates admin@search29.ai / ChangeMe!12345 — change immediately
php artisan serve
php artisan queue:work       # needed for SyncIntegrationDataJob
php artisan schedule:work    # needed for the hourly/daily integration sync cadence
```

Requires: PHP 8.3+, MySQL 8+, Redis. Set `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET` in `.env`
(from a Google Cloud OAuth client with the Analytics Data API + Admin API enabled) for the
GA4 integration to work end-to-end.

## Suggested next step

Build the **React frontend shell** (MUI + glassmorphism theme, routing, auth flows) next so there's
a UI to exercise these APIs, or pick one integration module (e.g. Google Analytics 4) to build
end-to-end as the template for the other ~25 connectors.
