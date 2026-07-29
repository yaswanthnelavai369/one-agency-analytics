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

`HealthScoreDisplay` (shared by both the agency `HealthScorePage` and the portal's
`PortalHealthScorePage`) renders the live backend score: an animated 0–100 ring gauge, a
7-category breakdown, a 90-day trend line (Recharts), and the rule-based improvement
suggestions the backend generates per category. The agency page adds a client selector and a
"Recalculate" button; the portal page is read-only and implicitly scoped to the logged-in
client.

## Alerts

`AlertsPage` (agency) shows detected anomalies with severity color-coding, a status filter
(open/acknowledged/resolved), expandable possible-causes/recommended-fixes, and
acknowledge/resolve actions, plus a manual "Run detection" trigger. The portal's
`PortalAlertsPage` is the same idea but read-only — clients see alerts, agency team members
triage them.

## AI Chat Assistant

`AIChatPage` (agency, client-selectable) and `PortalAIChatPage` (client-scoped) both render a
conversation thread wired to the real Anthropic-backed backend, with the spec's suggested
quick-prompt chips ("Why did traffic drop?", etc.) shown until the first message is sent.

## Goals

`GoalsPage` (agency, full edit rights: add/archive/delete, manual-progress input, "refresh from
data" for auto-tracked goals) and `PortalGoalsPage` (client: create + view only) both use the
shared `GoalCard` (progress bar, pace-status chip, forecast text) and `CreateGoalDialog`
(template picker sourced from the backend's `GoalCatalogue`, so a new suggested template is a
backend-only change). Goals falling behind pace near their deadline show up in the Alerts feed
automatically — no separate "goal alerts" UI needed.

## Chat & Notification Settings

`ChatThread` (shared) renders one message thread — agency's `ChatPage` (client selector, polls
every 5s) and the portal's `PortalChatPage` (implicit own thread) both use it, with `mySide`
('agency' | 'client') controlling bubble alignment. Delivery is polling-based, not WebSocket
(see backend README for the real-time upgrade path). `NotificationSettingsPage` (now living at
`/dashboard/settings`) is a real per-channel enable/config UI wired to the backend's six
notification channels, with a "Send test" button per channel — it's not the full agency
Settings page from the spec (branding/team/billing aren't here), just the notifications slice.

## Client Portal

A second, narrower app surface at `/portal/*` for `user_type: 'client'` logins — separate
layout (`PortalLayout`: topbar + tabs, no sidebar), separate route guard
(`PortalProtectedRoute`, which also redirects non-client users back to `/dashboard`, and
`DashboardLayout` redirects client users the other way to `/portal`). Reuses the same
components as the agency dashboard where the experience matches (`WidgetRenderer`,
`HealthScoreDisplay`, `GoalCard`, `ChatThread`, `GlassCard`) rather than duplicating them —
only the pages that need different scoping or fewer controls (no client selector, no
edit/delete actions) are portal-specific. The topbar reads the agency's white-label branding
(logo, colors, "powered by" visibility) from `/client/me`.

## Running it

```bash
npm install
cp .env.example .env   # point VITE_API_BASE_URL at your backend
npm run dev
```

Requires the `backend/` API running (see `../backend/README.md`) — login routes you to
`/dashboard` (agency roles) or `/portal` (client role) automatically based on the logged-in
user's `user_type`. See the backend README's test-login table for accounts covering every role.

## What's next

- Wire `/dashboard/reports` (currently a placeholder) — the portal has no equivalent yet
  either, since there's no Reports module on the backend
- Full agency Settings (branding, team, billing) — `/dashboard/settings` currently only covers
  notification channel config
- Replace WidgetRenderer's placeholder data with real queries against `analytics_metrics`
  once metrics are flowing in from a synced integration
- More integration providers (Google Ads, Meta Ads, ...) — the Integrations page (both agency
  and portal) already renders whatever the backend's catalogue returns, so no frontend change
  needed when a new provider is added backend-side
- Real-time chat (swap ChatThread's polling for Reverb/Echo once broadcasting is wired up
  backend-side — the component's props wouldn't need to change)
- Code-split the bundle (currently a single ~1.1MB chunk — recharts + react-grid-layout are the
  main contributors; worth lazy-loading per-route, especially splitting portal vs. agency)
