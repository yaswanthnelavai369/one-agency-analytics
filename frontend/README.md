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
    dashboard/         Overview (KPI grid), placeholder pages for Insights/Reports/etc.
    clients/           Clients list (live-wired to backend via React Query)
  routes/             AppRoutes, ProtectedRoute
```

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

- Wire `/dashboard/insights`, `/reports`, `/integrations`, `/settings` (currently placeholders)
- Replace placeholder Overview KPIs with real data once an integration module exists
- Drag-and-drop dashboard builder
- Code-split the bundle (currently a single ~570kB chunk — fine for a shell, worth splitting
  once more pages/charting libraries are added)
