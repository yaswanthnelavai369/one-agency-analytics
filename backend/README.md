# Search29 Analytics AI — Backend

Multi-tenant Laravel API: auth/2FA/RBAC foundation, plus four full vertical modules
(Integrations, Dashboard Builder, AI Health Score, AI Chat Assistant).

**Verified running locally on Laravel 11.55 / PHP 8.2+** (see `composer.lock`) — this repo
now contains the full Laravel skeleton (`artisan`, `config/app.php`, etc. — `vendor/` itself is
excluded via `.gitignore`, but `composer.lock` is committed), not just the application layer.

## What's included

```
app/
  Models/                    User, Agency, Client, Project, TeamMember, Plan, Role, Permission,
                              AuditLog, Integration, OauthToken, AnalyticsMetric, DashboardLayout,
                              DashboardWidget, HealthScore, AIConversation, AIMessage, AIUsageLog
  Repositories/
    Contracts/                Interfaces (dependency-inverted — services depend on these, not Eloquent)
    Eloquent/                 Concrete implementations
  Services/
    Auth/                      AuthService (register/login/tokens), TwoFactorService (Google2FA)
    RBAC/                       RBACService (role/permission provisioning + checks)
    Agency/, Client/            Agency/client creation with plan-limit enforcement
    Integration/                Connect/callback/disconnect/sync orchestration
    Dashboard/                  Layout + widget CRUD, bulk position save
    HealthScore/                Compute-and-store, trend/history
    AI/                         Chat orchestration (context + provider + usage limit + persistence)
  Integrations/                Pluggable connector architecture (see below)
  Dashboard/WidgetCatalogue.php Registry of every dashboard widget type
  HealthScore/                  Pluggable scoring-category architecture (see below)
  AI/                            Pluggable AI-provider architecture (see below)
  Http/
    Controllers/Api/V1/        Auth, Admin (Master Admin), Agency (tenant-scoped)
    Middleware/                 CheckPermission, EnsureAgencyOwnsResource
    Requests/ Resources/        Form validation + API response shaping
  Jobs/                         SyncIntegrationDataJob, ComputeHealthScoreJob
  Console/Commands/             Scheduled dispatch commands
  Providers/
    RepositoryServiceProvider.php   Binds every repository interface -> implementation

database/
  migrations/                 Full schema — see "Modules" below for what each covers
  seeders/                    PlanSeeder, RolePermissionSeeder (Master Admin + demo agency/client)

routes/api.php                 Versioned (/api/v1), permission- and tenant-guarded
routes/console.php              Scheduled jobs (integration sync, health score computation)
```

## Architecture decisions

- **Repository Pattern + Service Layer**: Controllers never touch Eloquent directly. They call
  Services, which call Repository *interfaces*.
- **Multi-tenancy**: every tenant-owned table carries `agency_id`. `EnsureAgencyOwnsResource`
  blocks cross-tenant access at the HTTP layer; repositories additionally scope queries by
  `agency_id`. Master Admin's role is intentionally global (`agency_id = null`) — to let it
  still exercise agency-scoped endpoints (spec: "View Every Dashboard", "Login As Any User"),
  the middleware resolves which agency it's "looking at" from an `X-Agency-ID` header (or
  `agency_id` param), defaulting to the platform's first agency.
- **RBAC**: `spatie/laravel-permission` with its **teams** feature, using `agency_id` as the
  team key — the same role name (e.g. "Manager") carries different permissions per agency.
  `RBACService` provisions a consistent starting role set (Owner/Manager/Analyst/Viewer) for
  every new agency.
- **Auth**: Sanctum tokens, Google2FA-based 2FA with recovery codes. Google/Microsoft OAuth
  hooks are stubbed — wire up `laravel/socialite` for the social-login module.
- **Pluggable module pattern, used three times**: Integrations, Health Score categories, and
  AI providers all follow the same shape — an interface + a registry class. Adding a new
  connector/category/provider is one class + one registry line, never a controller or schema
  change. This is deliberate: it's the same architectural answer to three different "the spec
  lists 20+ of these and more will come later" problems.

## Modules

- **Integrations** (`app/Integrations/`): `IntegrationProviderInterface` + `IntegrationManager`.
  Two connectors are built out end-to-end: **Google Analytics 4** (real OAuth2 + GA4 Data API
  `runReport`) and **Google Search Console** (real OAuth2 + `searchAnalytics.query`, mapping
  clicks/impressions/ctr/position into the same generic metric keys GA4 uses). Adding the next
  one (Google Ads, Meta Ads, ...) is one new class + one registry line — no schema, controller,
  or frontend change needed (the Integrations page already renders whatever the backend
  catalogue returns). `IntegrationService` orchestrates connect/callback/disconnect/sync,
  `SyncIntegrationDataJob` is the queued job the scheduler dispatches on each integration's
  `sync_frequency`, and `analytics_metrics` is the generic time-series table every provider
  writes into (so dashboard widgets stay provider-agnostic).
- **Dashboard builder** (`app/Dashboard/WidgetCatalogue.php` + `DashboardService`): layouts
  hold widgets with grid position/size matching `react-grid-layout`'s `{x,y,w,h}` shape 1:1.
  `WidgetCatalogue` covers the full KPI/list/health-score widget set from the spec.
- **AI Health Score** (`app/HealthScore/`): 0–100 score per client from 7 pluggable category
  calculators (Growth, SEO, Ads, Social, Website, Lead, Revenue). `ScoreMath` centralizes the
  normalization formulas — documented linear-clamp math, not a black box — and re-normalizes
  weights when a category has no data yet. Each calculator emits plain-language suggestions
  when its score is low. One row/day in `health_scores` powers the trend + historical
  comparison. Computed nightly via `ComputeHealthScoreJob`, or on demand.
- **AI Chat Assistant** (`app/AI/`): `AIProviderInterface` + `AIProviderManager`, with
  `AnthropicProvider` calling the real Anthropic Messages API. `AIContextBuilder` grounds every
  answer in the client's actual metrics + Health Score, so the assistant answers from real
  numbers rather than guessing. Conversations persist per client/user; `ai_usage_logs` tracks
  credit usage against the agency's plan limit. `QuickPrompts` supplies the spec's suggested-
  prompt catalogue (e.g. "Why did traffic drop?").
- **Automated anomaly detection** (`app/Anomaly/`): fourth application of the same pluggable
  pattern — `AnomalyDetectorInterface` + `AnomalyEngine` registry. Six detectors (Traffic,
  Conversion, Revenue, SEO, Ads, Integration Health) cover most of the spec's anomaly types
  (traffic/conversion/revenue drop or spike, CTR drop, ranking loss, high CPC/CPA, campaign
  failure, API failure, missing tracking codes) using transparent percent-deviation-from-
  baseline math (`AnomalyMath`) rather than a black-box model. Each anomaly carries
  plain-language possible causes and recommended fixes. Runs nightly via
  `DetectAnomaliesJob`/`anomalies:detect`, or on demand; deduped per client/type/metric/day.
  Email/WhatsApp/push notification dispatch is a clear extension point (left as a TODO in
  `AnomalyDetectionService`) rather than half-building a notification pipeline now.

## What's not built yet

- React frontend for Reports and Settings (Overview/Clients/Health Score/Alerts/Integrations/AI
  Insights are live)
- More integration connectors (Google Ads, Meta Ads, LinkedIn, TikTok, CRMs, ...)
- Notification dispatch (email/WhatsApp/push) — anomalies are detected and stored, but not yet
  pushed out; see the TODO in `AnomalyDetectionService::run()`
- Billing, scheduled reports

## Getting it running

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
# Seeds: admin@search29.ai / ChangeMe!12345 (Master Admin)
#        agency@search29.ai / ChangeMe!12345 (demo agency owner, with a demo client "Acme Corporation")
# Change both passwords immediately outside of local dev.

php artisan serve
php artisan queue:work       # needed for SyncIntegrationDataJob / ComputeHealthScoreJob
php artisan schedule:work    # needed for the hourly/daily integration sync + nightly health scores
```

Requires: PHP 8.2+, MySQL 8+, Redis (or set `CACHE_STORE`/`SESSION_DRIVER`/`QUEUE_CONNECTION`
to `database`/`file` for a Redis-free local setup). Set `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET`
for the GA4 integration, and `ANTHROPIC_API_KEY` for the AI Chat Assistant, in `.env`.

Master Admin note: since its role is global (not tied to one agency), agency-scoped endpoints
need an `X-Agency-ID` header (or `agency_id` param) to say which agency you're acting on behalf
of — otherwise it defaults to the platform's first agency.

## Suggested next step

Notification dispatch (email/WhatsApp/push) for anomalies now that they're being detected and
stored, another integration connector (Google Ads pairs naturally with the two Google connectors
already built), or the Reports module (scheduled/exportable, white-labeled).
