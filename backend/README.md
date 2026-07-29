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
  pattern — `AnomalyDetectorInterface` + `AnomalyEngine` registry. Seven detectors (Traffic,
  Conversion, Revenue, SEO, Ads, Integration Health, Goal Deadline) cover most of the spec's
  anomaly types (traffic/conversion/revenue drop or spike, CTR drop, ranking loss, high CPC/CPA,
  campaign failure, API failure, missing tracking codes, goals falling behind pace) using
  transparent percent-deviation-from-baseline math (`AnomalyMath`) rather than a black-box
  model. Each anomaly carries plain-language possible causes and recommended fixes. Runs
  nightly via `DetectAnomaliesJob`/`anomalies:detect`, or on demand; deduped per
  client/type/metric/goal/day. Critical-severity anomalies dispatch a real notification (see
  below) instead of just sitting in the feed.
- **Goal tracking** (`app/Goals/`): fifth application of the pluggable pattern for the
  suggested-template catalogue (`GoalCatalogue`, same idiom as `WidgetCatalogue`/`QuickPrompts`).
  Goals are either auto-tracked (linked to an `analytics_metrics` key, summed cumulatively for
  running totals like Leads/Visitors or read as the latest snapshot for rates like CTR/ROAS) or
  manual (progress entered by hand). `GoalMath` computes achievement rate, expected-progress-by-
  now, pace status, and a simple linear-projection completion date — same transparent-formula
  philosophy as `ScoreMath`/`AnomalyMath`. Deadline alerts are fulfilled by reusing the anomaly
  pipeline (`GoalDeadlineDetector`) rather than a second, parallel alerting mechanism — a goal
  falling behind shows up in the same Alerts feed as a traffic drop. `goal_progress` stores
  daily snapshots for the trend chart / historical comparison.
- **Notification dispatch** (`app/Notifications/`): sixth application of the pluggable
  pattern — `NotificationChannelInterface` + `NotificationManager` registry. Six real
  implementations: Email (works out of the box via Laravel's mailer), Slack/Discord/Microsoft
  Teams (agency-configured webhook URL — broadcast channels, one message per channel regardless
  of team size), and SMS/WhatsApp (real Twilio API calls, need `TWILIO_*` credentials in
  `.env` to actually send — the code path is real, not stubbed, it just needs an account).
  `NotificationService::notifyForAnomaly()` determines recipients (the agency's Owner/Manager
  team on every enabled channel, plus that client's own portal users by email — spec: Client
  role "Receives Notifications") and logs every attempt to `notification_logs`, success or
  failure. Wired into `AnomalyDetectionService`: critical-severity anomalies dispatch
  `SendAnomalyNotificationJob` automatically; warning/info stay feed-only to avoid alert
  fatigue. Browser Push isn't built — it needs a service-worker subscription flow that's a
  distinct unit of frontend work, noted as a clear next step rather than faked.
- **Agency↔client chat** (`app/Services/Chat/`): one thread per client, shared by the whole
  agency team and the whole client-portal side (not per-user, since this is "the agency talking
  to the client," not one team member's private thread). Read state is tracked per-side
  (`chat_threads.agency_last_read_at`/`client_last_read_at`) rather than per-message receipts,
  keeping it simple while still supporting unread counts. Delivery is polling-based (5s
  interval on the frontend), not WebSocket — `laravel/reverb` is already in `composer.json`
  but unwired; upgrading this to real-time push is a clean next step that wouldn't touch the
  data model.
- **Client portal** (`app/Http/Controllers/Api/V1/Client/` + `EnsureClientAccess`): a Client
  role gets a narrower, dedicated `/client/*` route surface — no `{client}` route param to
  guess or tamper with, since scoping comes entirely from the authenticated user's own
  `client_id`. Reuses the same services agency routes use (`DashboardService`,
  `HealthScoreService`, `IntegrationService`, `AIChatService`, `AnomalyDetectionService`,
  `GoalService`, `ChatService`) rather than duplicating logic, so a fix or feature in one place
  benefits both surfaces. Now covers every spec'd Client ability: view own dashboard (read-only
  — `DashboardService::clientFacingLayout()` auto-provisions one from an agency team member's
  shared layout, or defaults, the first time a client logs in), connect/disconnect own
  integrations, view Health Score, create and view (not edit/delete) Goals, ask the AI
  assistant, view (read-only) alerts, and chat with the agency.

## What's not built yet

- React frontend for Reports (Settings currently only covers notification channel config —
  branding/team/billing settings aren't built; agency: Overview/Clients/Health Score/Alerts/
  Goals/Chat/Integrations/AI Insights/Notifications are live; portal: Overview/Health
  Score/Goals/Integrations/Ask AI/Chat/Alerts are live)
- More integration connectors (Google Ads, Meta Ads, LinkedIn, TikTok, CRMs, ...)
- Browser Push notifications (needs a service-worker subscription flow — a distinct unit of
  frontend work; Email/Slack/Discord/Teams/SMS/WhatsApp are all real and working)
- Real-time chat delivery (currently 5s polling; `laravel/reverb` is already a dependency but
  not wired up for broadcasting)
- Billing, scheduled reports

## Getting it running

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

php artisan serve
php artisan queue:work       # needed for SyncIntegrationDataJob / ComputeHealthScoreJob / DetectAnomaliesJob
php artisan schedule:work    # needed for the hourly/daily integration sync + nightly health scores/anomalies
```

Requires: PHP 8.2+, MySQL 8+, Redis (or set `CACHE_STORE`/`SESSION_DRIVER`/`QUEUE_CONNECTION`
to `database`/`file` for a Redis-free local setup). Set `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET`
for the GA4/Search Console integrations, and `ANTHROPIC_API_KEY` for the AI Chat Assistant, in `.env`.

### Test logins

Seeded by `DemoDataSeeder` (called from `DatabaseSeeder`) — all under one demo agency
("Search29 Agency") with one demo client ("Acme Corporation"), so the full RBAC matrix can be
exercised without manually creating accounts. **All passwords: `ChangeMe!12345`** — change or
remove this seeder before any real deployment.

| Role | Email | Notes |
|---|---|---|
| Master Admin | `admin@search29.ai` | Global role — see the `X-Agency-ID` note below |
| Agency Owner | `agency@search29.ai` | Full access to "Search29 Agency" |
| Manager (team member) | `manager@search29.ai` | Most permissions, no billing/team-remove |
| Analyst (team member) | `analyst@search29.ai` | View + export + AI chat, no create/edit |
| Viewer (team member) | `viewer@search29.ai` | Read-only |
| Client portal | `client@search29.ai` | Scoped to only "Acme Corporation" via the real `/client/*` routes |

Master Admin note: since its role is global (not tied to one agency), agency-scoped endpoints
need an `X-Agency-ID` header (or `agency_id` param) to say which agency you're acting on behalf
of — otherwise it defaults to the platform's first agency.

If you already have this running locally and just pulled the new seeder, you don't need to
re-migrate — just run `php artisan db:seed --class=DemoDataSeeder` to add the new test logins.

## Suggested next step

Real-time chat delivery (wiring up the already-installed `laravel/reverb` instead of polling),
Browser Push notifications (needs a service-worker subscription flow), or the Reports module
(scheduled/exportable, white-labeled).
