import { useMemo } from 'react';
import { ThemeProvider, CssBaseline } from '@mui/material';
import { BrowserRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ThemeModeProvider, useThemeMode } from './context/ThemeModeContext';
import { AuthProvider, useAuth } from './context/AuthContext';
import { createAppTheme } from './theme/createAppTheme';
import AppRoutes from './routes/AppRoutes';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: { retry: 1, refetchOnWindowFocus: false },
  },
});

/** Applies the resolved theme mode, with the current user's agency brand colors when available. */
function ThemedApp() {
  const { resolvedMode } = useThemeMode();
  const { user } = useAuth();

  const theme = useMemo(
    () =>
      createAppTheme(resolvedMode, {
        primaryColor: user?.agency?.branding?.primary_color,
        secondaryColor: user?.agency?.branding?.secondary_color,
        fontFamily: user?.agency?.branding?.font_family,
      }),
    [resolvedMode, user]
  );

  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />
      <AppRoutes />
    </ThemeProvider>
  );
}

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <BrowserRouter>
        <ThemeModeProvider>
          <AuthProvider>
            <ThemedApp />
          </AuthProvider>
        </ThemeModeProvider>
      </BrowserRouter>
    </QueryClientProvider>
  );
}
