import { Routes, Route, Navigate } from 'react-router-dom';
import LoginPage from '../pages/auth/LoginPage';
import RegisterPage from '../pages/auth/RegisterPage';
import TwoFactorPage from '../pages/auth/TwoFactorPage';
import DashboardLayout from '../components/layout/DashboardLayout';
import DashboardBuilderPage from '../pages/dashboard/DashboardBuilderPage';
import ClientsListPage from '../pages/clients/ClientsListPage';
import HealthScorePage from '../pages/dashboard/HealthScorePage';
import AlertsPage from '../pages/dashboard/AlertsPage';
import GoalsPage from '../pages/dashboard/GoalsPage';
import AIChatPage from '../pages/dashboard/AIChatPage';
import IntegrationsPage from '../pages/dashboard/IntegrationsPage';
import ComingSoonPage from '../pages/dashboard/ComingSoonPage';
import ProtectedRoute from './ProtectedRoute';
import PortalProtectedRoute from './PortalProtectedRoute';
import PortalLayout from '../components/portal/PortalLayout';
import PortalOverviewPage from '../pages/portal/PortalOverviewPage';
import PortalHealthScorePage from '../pages/portal/PortalHealthScorePage';
import PortalGoalsPage from '../pages/portal/PortalGoalsPage';
import PortalIntegrationsPage from '../pages/portal/PortalIntegrationsPage';
import PortalAIChatPage from '../pages/portal/PortalAIChatPage';
import PortalAlertsPage from '../pages/portal/PortalAlertsPage';

export default function AppRoutes() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />
      <Route path="/two-factor" element={<TwoFactorPage />} />

      {/* Agency-side dashboard */}
      <Route
        path="/dashboard"
        element={
          <ProtectedRoute>
            <DashboardLayout />
          </ProtectedRoute>
        }
      >
        <Route index element={<DashboardBuilderPage />} />
        <Route path="clients" element={<ClientsListPage />} />
        <Route path="health-score" element={<HealthScorePage />} />
        <Route path="alerts" element={<AlertsPage />} />
        <Route path="goals" element={<GoalsPage />} />
        <Route path="insights" element={<AIChatPage />} />
        <Route
          path="reports"
          element={<ComingSoonPage title="Reports" description="Scheduled, white-labeled PDF/Excel reports land here." />}
        />
        <Route path="integrations" element={<IntegrationsPage />} />
        <Route
          path="settings"
          element={<ComingSoonPage title="Settings" description="Branding, team, billing, and account settings." />}
        />
      </Route>

      {/* Client portal — a narrower surface, scoped entirely to one client */}
      <Route
        path="/portal"
        element={
          <PortalProtectedRoute>
            <PortalLayout />
          </PortalProtectedRoute>
        }
      >
        <Route index element={<PortalOverviewPage />} />
        <Route path="health-score" element={<PortalHealthScorePage />} />
        <Route path="goals" element={<PortalGoalsPage />} />
        <Route path="integrations" element={<PortalIntegrationsPage />} />
        <Route path="ai-chat" element={<PortalAIChatPage />} />
        <Route path="alerts" element={<PortalAlertsPage />} />
      </Route>

      <Route path="/" element={<Navigate to="/dashboard" replace />} />
      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  );
}
