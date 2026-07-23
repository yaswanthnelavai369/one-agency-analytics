# One Agency Analytics

Enterprise SaaS Marketing Analytics Platform — a white-labelable, multi-tenant
dashboard that lets agencies monitor every client's marketing channels
(GA4, GSC, Google/Meta/LinkedIn/TikTok Ads, CRMs, etc.) from one place, with
AI-driven insights, anomaly detection, and reporting.

## Repository layout

```
backend/    Laravel 12 API — multi-tenant data model, auth + 2FA, RBAC
            (see backend/README.md for architecture notes and setup)
frontend/   React + Vite + MUI SPA — glassmorphism design system, auth flows,
            dashboard shell (see frontend/README.md for the design system notes)
```

## Status

🚧 Early build.

- ✅ Backend foundation: multi-tenant data model, auth + 2FA, RBAC
- ✅ Frontend shell: design system, auth pages, dashboard layout, live clients page
- ✅ GA4 integration (backend + frontend): real OAuth flow + GA4 Data API sync, built as
  the pluggable template for future connectors (`app/Integrations/`)
- ✅ Dashboard builder (backend + frontend): drag/drop/resize widget grid persisted to the
  backend, widget catalogue covering the full spec'd widget set
- ⬜ More integration connectors (Search Console, Google Ads, Meta Ads, LinkedIn, TikTok, CRMs, ...)
- ⬜ AI Health Score, AI chat assistant, anomaly detection
- ⬜ Billing, scheduled reports, notifications

