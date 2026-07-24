# One Agency Analytics

Enterprise SaaS Marketing Analytics Platform — a white-labelable, multi-tenant
dashboard that lets agencies monitor every client's marketing channels
(GA4, GSC, Google/Meta/LinkedIn/TikTok Ads, CRMs, etc.) from one place, with
AI-driven insights, anomaly detection, and reporting.

## Repository layout

```
backend/    Laravel 11 API — multi-tenant data model, auth + 2FA, RBAC, plus four full
            vertical modules (see backend/README.md). Verified running locally.
frontend/   React + Vite + MUI SPA — glassmorphism design system, auth flows,
            dashboard shell (see frontend/README.md for the design system notes)
```

## Status

🚧 Early build.

- ✅ Backend foundation: multi-tenant data model, auth + 2FA, RBAC — **verified running
  locally** against MySQL (Laravel 11.55 / PHP 8.2+)
- ✅ Frontend shell: design system, auth pages, dashboard layout, live clients page
- ✅ GA4 integration (backend + frontend): real OAuth flow + GA4 Data API sync, built as
  the pluggable template for future connectors (`app/Integrations/`)
- ✅ Dashboard builder (backend + frontend): drag/drop/resize widget grid persisted to the
  backend, widget catalogue covering the full spec'd widget set
- ✅ AI Health Score engine (backend + frontend): 0–100 score per client from 7 pluggable
  category calculators, 90-day trend, rule-based improvement suggestions
- ✅ AI Chat Assistant (backend + frontend): real Anthropic-backed chat, grounded in each
  client's actual metrics + Health Score, with plan-based usage limits
- ⬜ More integration connectors (Search Console, Google Ads, Meta Ads, LinkedIn, TikTok, CRMs, ...)
- ⬜ Automated anomaly detection
- ⬜ Billing, scheduled reports, notifications

