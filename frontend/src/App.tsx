import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { useMemo } from 'react';
import { CssBaseline, ThemeProvider } from '@mui/material';
import { useAuthStore } from './store/authStore';
import { useThemeStore } from './store/themeStore';
import { createFleetControlTheme } from './theme';
import AppLayout from './components/common/Layout/AppLayout';
import LoginPage from './pages/Auth/Login';
import RegisterPage from './pages/Auth/Register';
import FleetControlLanding from './pages/Landing';
import DashboardPage from './pages/Dashboard';
import VehiclesPage from './pages/Vehicles';
import DriversPage from './pages/Drivers';
import MissionsPage from './pages/Missions';
import ExpensesPage from './pages/Expenses';
import IncidentsPage from './pages/Incidents';
import DocumentsPage from './pages/Documents';
import NotificationsPage from './pages/Notifications';
import ExportPage from './pages/Export';
import UsersPage from './pages/Users';
import SettingsPage from './pages/Settings';
import AuditPage from './pages/Audit';
import AdminDashboardPage from './pages/Admin/Dashboard';
import AdminCompaniesPage from './pages/Admin/Companies';
import AdminPlansPage from './pages/Admin/Plans';
import AdminSubscriptionsPage from './pages/Admin/Subscriptions';
import PermissionsPage from './pages/Permissions';
import MaintenancePage from './pages/Maintenance';
import FuelMonitoringPage from './pages/FuelMonitoring';
import TrackingPage from './pages/Tracking';
import AlertRulesPage from './pages/AlertRules';
import PricingPage from './pages/Pricing';
import ContactPage from './pages/Contact';
import AboutPage from './pages/About';
import FAQPage from './pages/FAQ';
import LegalPage from './pages/Legal';

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const isTokenExpired = useAuthStore((s) => s.isTokenExpired);
  const logout = useAuthStore((s) => s.logout);

  if (!isAuthenticated || isTokenExpired()) {
    logout();
    return <Navigate to="/login" replace />;
  }
  return <>{children}</>;
}

function AdminRoute({ children }: { children: React.ReactNode }) {
  const user = useAuthStore((s) => s.user);
  if (user?.role !== 'super_admin') {
    return <Navigate to="/dashboard" replace />;
  }
  return <>{children}</>;
}

function ManagerRoute({ children }: { children: React.ReactNode }) {
  const user = useAuthStore((s) => s.user);
  if (!user || !['admin', 'manager', 'super_admin'].includes(user.role)) {
    return <Navigate to="/dashboard" replace />;
  }
  return <>{children}</>;
}

function DefaultRedirect() {
  const user = useAuthStore((s) => s.user);
  return <Navigate to={user?.role === 'super_admin' ? '/admin/dashboard' : '/dashboard'} replace />;
}

export default function App() {
  const mode = useThemeStore((s) => s.mode);

  // Use the FleetControl design system theme
  const theme = useMemo(() => createFleetControlTheme(mode), [mode]);

  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />
      <BrowserRouter>
        <Routes>
          {/* Public routes */}
          <Route path="/landing" element={<FleetControlLanding />} />
          <Route path="/contact" element={<ContactPage />} />
          <Route path="/a-propos" element={<AboutPage />} />
          <Route path="/faq" element={<FAQPage />} />
          <Route path="/mentions-legales" element={<LegalPage />} />
          <Route path="/login" element={<LoginPage />} />
          <Route path="/register" element={<RegisterPage />} />

          {/* Protected routes */}
          <Route
            path="/"
            element={
              <ProtectedRoute>
                <AppLayout />
              </ProtectedRoute>
            }
          >
            <Route index element={<DefaultRedirect />} />
            <Route path="dashboard"     element={<DashboardPage />} />
            <Route path="vehicles"      element={<VehiclesPage />} />
            <Route path="drivers"       element={<DriversPage />} />
            <Route path="missions"      element={<MissionsPage />} />
            <Route path="expenses"      element={<ExpensesPage />} />
            <Route path="incidents"     element={<IncidentsPage />} />
            <Route path="documents"     element={<DocumentsPage />} />
            <Route path="notifications" element={<NotificationsPage />} />
            <Route path="export"        element={<ExportPage />} />
            <Route path="maintenance"   element={<ManagerRoute><MaintenancePage /></ManagerRoute>} />
            <Route path="fuel"          element={<ManagerRoute><FuelMonitoringPage /></ManagerRoute>} />
            <Route path="tracking"      element={<TrackingPage />} />
            <Route path="alert-rules"   element={<ManagerRoute><AlertRulesPage /></ManagerRoute>} />
            <Route path="users"         element={<ManagerRoute><UsersPage /></ManagerRoute>} />
            <Route path="settings"      element={<SettingsPage />} />
            <Route path="pricing"       element={<ManagerRoute><PricingPage /></ManagerRoute>} />
            <Route path="audit"         element={<ManagerRoute><AuditPage /></ManagerRoute>} />
            <Route path="permissions"  element={<ManagerRoute><PermissionsPage /></ManagerRoute>} />
            {/* Super Admin */}
            <Route path="admin/dashboard"  element={<AdminRoute><AdminDashboardPage /></AdminRoute>} />
            <Route path="admin/companies"  element={<AdminRoute><AdminCompaniesPage /></AdminRoute>} />
            <Route path="admin/plans"      element={<AdminRoute><AdminPlansPage /></AdminRoute>} />
            <Route path="admin/subscriptions" element={<AdminRoute><AdminSubscriptionsPage /></AdminRoute>} />
          </Route>
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </BrowserRouter>
    </ThemeProvider>
  );
}
