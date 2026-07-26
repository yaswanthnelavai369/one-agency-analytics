# One Agency Analytics

Enterprise SaaS Marketing Analytics Platform — a white-labelable, multi-tenant
dashboard that lets agencies monitor every client's marketing channels
(GA4, GSC, Google/Meta/LinkedIn/TikTok Ads, CRMs, etc.) from one place, with
AI-driven insights, anomaly detection, and reporting.

## Repository layout

```
backend/    Laravel 11 API — multi-tenant data model, auth + 2FA, RBAC, plus six full
            vertical modules + a dedicated client-portal route surface (see backend/README.md).
            Verified running locally.
frontend/   React + Vite + MUI SPA — glassmorphism design system, agency dashboard +
            a separate client portal (see frontend/README.md for the design system notes)
```

## Status

🚧 Early build.

- ✅ Backend foundation: multi-tenant data model, auth + 2FA, RBAC — **verified running
  locally** against MySQL (Laravel 11.55 / PHP 8.2+)
- ✅ Frontend shell: design system, auth pages, dashboard layout, live clients page
- ✅ GA4 + Search Console integrations (backend + frontend): real OAuth flows + API sync for
  both, proving out the pluggable connector template (`app/Integrations/`) with two connectors
- ✅ Dashboard builder (backend + frontend): drag/drop/resize widget grid persisted to the
  backend, widget catalogue covering the full spec'd widget set
- ✅ AI Health Score engine (backend + frontend): 0–100 score per client from 7 pluggable
  category calculators, 90-day trend, rule-based improvement suggestions
- ✅ AI Chat Assistant (backend + frontend): real Anthropic-backed chat, grounded in each
  client's actual metrics + Health Score, with plan-based usage limits
- ✅ Automated anomaly detection (backend + frontend): 7 detectors watching traffic,
  conversions, revenue, SEO, ads, integration health, and goal deadlines, with plain-language
  causes/fixes
- ✅ Goal tracking (backend + frontend): auto-tracked or manual goals, pace/forecast math,
  deadline alerts fed straight into the anomaly/alerts pipeline
- ✅ Client portal (backend + frontend): a properly-scoped `/client/*`/`/portal` surface —
  own dashboard, Health Score, Goals (create + view), integrations, AI chat, and alerts —
  separate from the agency dashboard, with real tenant isolation via `EnsureClientAccess`
- ⬜ More integration connectors (Google Ads, Meta Ads, LinkedIn, TikTok, CRMs, ...)
- ⬜ Notification dispatch (email/WhatsApp/push) for anomalies
- ⬜ Agency↔client chat
- ⬜ Billing, scheduled reports

