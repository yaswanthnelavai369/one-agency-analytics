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

## What's deliberately NOT here yet

This is the foundation only, per your last message. Not built out (next milestones):
- React frontend
- Integration modules (GA4, GSC, Meta Ads, etc.)
- Dashboard builder, widgets, AI Health Score, AI chat, anomaly detection
- Billing, reports, notifications

## Getting it running

This environment can't reach `packagist.org` to run `composer install`, so the code here hasn't
been executed/tested against a live Laravel bootstrap. To actually run it:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed   # creates admin@search29.ai / ChangeMe!12345 — change immediately
php artisan serve
```

Requires: PHP 8.3+, MySQL 8+, Redis. Composer packages referenced (`laravel/sanctum`,
`spatie/laravel-permission`, `pragmarx/google2fa-laravel`, etc.) are declared in `composer.json`.

## Suggested next step

Build the **React frontend shell** (MUI + glassmorphism theme, routing, auth flows) next so there's
a UI to exercise these APIs, or pick one integration module (e.g. Google Analytics 4) to build
end-to-end as the template for the other ~25 connectors.
