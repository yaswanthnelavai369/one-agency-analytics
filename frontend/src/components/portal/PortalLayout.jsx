import { useQuery } from '@tanstack/react-query';
import Box from '@mui/material/Box';
import AppBar from '@mui/material/AppBar';
import Toolbar from '@mui/material/Toolbar';
import Tabs from '@mui/material/Tabs';
import Tab from '@mui/material/Tab';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import IconButton from '@mui/material/IconButton';
import Container from '@mui/material/Container';
import CircularProgress from '@mui/material/CircularProgress';
import LogoutRoundedIcon from '@mui/icons-material/LogoutRounded';
import LightModeRoundedIcon from '@mui/icons-material/LightModeRounded';
import DarkModeRoundedIcon from '@mui/icons-material/DarkModeRounded';
import BrightnessAutoRoundedIcon from '@mui/icons-material/BrightnessAutoRounded';
import { Outlet, NavLink, useLocation, Navigate } from 'react-router-dom';
import { useTheme } from '@mui/material/styles';
import AuroraBackground from '../ui/AuroraBackground';
import { useAuth } from '../../context/AuthContext';
import { useThemeMode } from '../../context/ThemeModeContext';
import { portalApi } from '../../api/portal';

const TABS = [
  { label: 'Overview', to: '/portal' },
  { label: 'Health Score', to: '/portal/health-score' },
  { label: 'Integrations', to: '/portal/integrations' },
  { label: 'Ask AI', to: '/portal/ai-chat' },
  { label: 'Alerts', to: '/portal/alerts' },
];

const MODE_CYCLE = ['light', 'dark', 'auto'];
const MODE_ICON = { light: LightModeRoundedIcon, dark: DarkModeRoundedIcon, auto: BrightnessAutoRoundedIcon };

export default function PortalLayout() {
  const theme = useTheme();
  const { user, logout } = useAuth();
  const { mode, setThemeMode } = useThemeMode();
  const location = useLocation();

  const { data, isLoading } = useQuery({ queryKey: ['portal-me'], queryFn: () => portalApi.me() });

  if (user?.user_type !== 'client') {
    return <Navigate to="/dashboard" replace />;
  }

  if (isLoading) {
    return (
      <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: '100vh' }}>
        <CircularProgress size={28} />
      </Box>
    );
  }

  const branding = data?.agency_branding;
  const cycleTheme = () => setThemeMode(MODE_CYCLE[(MODE_CYCLE.indexOf(mode) + 1) % MODE_CYCLE.length]);
  const ModeIcon = MODE_ICON[mode];
  const activeTab =
    TABS.find((t) => t.to === location.pathname)?.to ||
    TABS.find((t) => location.pathname.startsWith(t.to) && t.to !== '/portal')?.to ||
    '/portal';

  return (
    <Box sx={{ minHeight: '100vh' }}>
      <AuroraBackground variant="ambient" />

      <AppBar
        position="sticky"
        elevation={0}
        sx={{
          backgroundColor: theme.palette.mode === 'dark' ? 'rgba(14,18,36,0.7)' : 'rgba(245,246,250,0.7)',
          backdropFilter: 'blur(20px)',
          color: 'text.primary',
          borderBottom: `1px solid ${theme.palette.divider}`,
        }}
      >
        <Toolbar sx={{ justifyContent: 'space-between', gap: 2 }}>
          <Stack direction="row" spacing={1.5} alignItems="center">
            {branding?.logo ? (
              <Box component="img" src={branding.logo} alt={branding.name} sx={{ height: 26 }} />
            ) : (
              <Box
                sx={{
                  width: 26,
                  height: 26,
                  borderRadius: '7px',
                  background: `linear-gradient(135deg, ${branding?.primary_color || '#4C6FFF'}, ${branding?.secondary_color || '#17B8A6'})`,
                }}
              />
            )}
            <Typography variant="h6">{branding?.name || 'Client Portal'}</Typography>
          </Stack>

          <Stack direction="row" spacing={1} alignItems="center">
            <Typography variant="body2" color="text.secondary" sx={{ display: { xs: 'none', sm: 'block' } }}>
              {user?.name}
            </Typography>
            <IconButton size="small" onClick={cycleTheme}>
              <ModeIcon fontSize="small" />
            </IconButton>
            <IconButton size="small" onClick={logout}>
              <LogoutRoundedIcon fontSize="small" />
            </IconButton>
          </Stack>
        </Toolbar>

        <Tabs value={activeTab} sx={{ px: 2 }}>
          {TABS.map((tab) => (
            <Tab key={tab.to} label={tab.label} value={tab.to} component={NavLink} to={tab.to} />
          ))}
        </Tabs>
      </AppBar>

      <Container maxWidth="lg" sx={{ py: 4 }}>
        <Outlet />
      </Container>

      {!branding?.hide_platform_branding && (
        <Box sx={{ textAlign: 'center', py: 3, opacity: 0.6 }}>
          <Typography variant="caption">Powered by Search29</Typography>
        </Box>
      )}
    </Box>
  );
}
