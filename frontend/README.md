# Search29 Frontend

React + Vite + MUI SPA for the Search29 Analytics AI dashboard.

## Design system

- **Palette** ("Signal & Growth"): signal blue (`#4C6FFF`) represents the converged
  data stream from every channel; growth teal (`#17B8A6`) represents positive movement.
  Alerts (positive/warning/critical) use their own semantic colors so anomaly/health-score
  UI never borrows the brand color. Agencies can override primary/secondary via white-label
  branding — see `theme/createAppTheme.js`.
- **Type**: Space Grotesk (display/headings/KPI numbers) + Inter (body/UI) + IBM Plex Mono
  (timestamps, data values).
- **Signature element**: `AuroraBackground` — a slow gradient-mesh drift behind every glass
  surface, standing in for multiple marketing channels converging into one read.
- **Glass surfaces**: all panels compose on `components/ui/GlassCard.jsx` so the blur/border/
  elevation treatment stays consistent everywhere.

## Structure

```
src/
  theme/            Design tokens + MUI theme factory (light/dark/auto, white-label overrides)
  context/           ThemeModeContext (light/dark/auto), AuthContext (session, login, 2FA)
  api/                Axios client + endpoint wrappers for the Laravel backend
  components/
    ui/               GlassCard, KpiCard (animated + sparkline), AuroraBackground, LogoMark
    layout/           AuthLayout, DashboardLayout, Sidebar, Topbar
  pages/
    auth/             Login, Register, 2FA challenge
    dashboard/         DashboardBuilderPage (drag/drop/resize grid, react-grid-layout),
                        IntegrationsPage (live OAuth connect flow), placeholders for
                        Insights/Reports/Settings
    clients/           Clients list (live-wired to backend via React Query)
  routes/             AppRoutes, ProtectedRoute
```

## Dashboard builder

`DashboardBuilderPage` renders a `react-grid-layout` grid backed by the backend's
`dashboard_layouts`/`dashboard_widgets` tables:
- Drag to rearrange, resize by the corner handle — changes are held in local state and
  committed to the backend only when "Save layout" is clicked (bulk `PUT .../widgets/positions`)
- "Add widget" opens a catalogue picker (`/agency/dashboards/widget-catalogue`), grouped by
  category, driven entirely by the backend's `WidgetCatalogue` registry
- `WidgetRenderer` maps a widget's `kind` (`kpi` | `list` | `health_score`) to its component —
  currently deterministic placeholder data per widget until real metrics are wired up
- "Reset" restores the default widget set

## Integrations

`IntegrationsPage` is wired to the real backend OAuth flow: pick a client, click Connect on a
provider, get redirected to Google's consent screen, land back on this page via the backend's
callback route (`?connected=...` or `?error=...`), then Sync now / Disconnect per connected
integration.

## AI Health Score

`HealthScorePage` renders the live backend score: an animated 0–100 ring gauge, a 7-category
breakdown (Growth/SEO/Ads/Social/Website/Leads/Revenue), a 90-day trend line (Recharts), and
the rule-based improvement suggestions the backend generates per category. "Recalculate" forces
an on-demand recompute rather than waiting for the nightly job.

## Running it

```bash
npm install
cp .env.example .env   # point VITE_API_BASE_URL at your backend
npm run dev
```

Requires the `backend/` API running (see `../backend/README.md`) for login, registration, and
the clients list to actually work — the Overview page currently shows placeholder KPI data until
an integration module (GA4, etc.) is wired up to feed real metrics.

## What's next

- Wire `/dashboard/insights`, `/reports`, `/settings` (currently placeholders)
- Replace WidgetRenderer's placeholder data with real queries against `analytics_metrics`
  and the Health Score API once metrics are flowing in from a synced integration
- More integration providers (Search Console, Google Ads, Meta Ads, ...) — the Integrations
  page already renders whatever the backend's catalogue returns, so no frontend change needed
  when a new provider is added backend-side
- Code-split the bundle (currently a single ~1MB chunk — recharts + react-grid-layout are the
  main contributors; worth lazy-loading per-route)
