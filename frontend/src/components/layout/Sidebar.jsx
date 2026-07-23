import Box from '@mui/material/Box';
import List from '@mui/material/List';
import ListItemButton from '@mui/material/ListItemButton';
import ListItemIcon from '@mui/material/ListItemIcon';
import ListItemText from '@mui/material/ListItemText';
import Stack from '@mui/material/Stack';
import DashboardRoundedIcon from '@mui/icons-material/DashboardRounded';
import GroupsRoundedIcon from '@mui/icons-material/GroupsRounded';
import InsightsRoundedIcon from '@mui/icons-material/InsightsRounded';
import DescriptionRoundedIcon from '@mui/icons-material/DescriptionRounded';
import ExtensionRoundedIcon from '@mui/icons-material/ExtensionRounded';
import SettingsRoundedIcon from '@mui/icons-material/SettingsRounded';
import { NavLink } from 'react-router-dom';
import { useTheme } from '@mui/material/styles';
import LogoMark from '../ui/LogoMark';

const NAV_ITEMS = [
  { label: 'Overview', to: '/dashboard', icon: DashboardRoundedIcon },
  { label: 'Clients', to: '/dashboard/clients', icon: GroupsRoundedIcon },
  { label: 'AI Insights', to: '/dashboard/insights', icon: InsightsRoundedIcon },
  { label: 'Reports', to: '/dashboard/reports', icon: DescriptionRoundedIcon },
  { label: 'Integrations', to: '/dashboard/integrations', icon: ExtensionRoundedIcon },
  { label: 'Settings', to: '/dashboard/settings', icon: SettingsRoundedIcon },
];

export const SIDEBAR_WIDTH = 240;

export default function Sidebar({ agency }) {
  const theme = useTheme();
  const isDark = theme.palette.mode === 'dark';

  return (
    <Box
      component="nav"
      sx={{
        width: SIDEBAR_WIDTH,
        flexShrink: 0,
        height: '100vh',
        position: 'sticky',
        top: 0,
        p: 2,
        display: { xs: 'none', md: 'flex' },
        flexDirection: 'column',
        borderRight: `1px solid ${theme.palette.divider}`,
        backgroundColor: isDark ? 'rgba(255,255,255,0.02)' : 'rgba(255,255,255,0.4)',
        backdropFilter: 'blur(20px)',
      }}
    >
      <Box sx={{ px: 1, py: 2 }}>
        <LogoMark agency={agency} />
      </Box>

      <List sx={{ flex: 1, mt: 2 }}>
        {NAV_ITEMS.map(({ label, to, icon: Icon }) => (
          <ListItemButton
            key={to}
            component={NavLink}
            to={to}
            end={to === '/dashboard'}
            sx={{
              borderRadius: 2,
              mb: 0.5,
              '&.active': {
                backgroundColor: isDark ? 'rgba(76,111,255,0.16)' : 'rgba(76,111,255,0.1)',
                color: 'primary.main',
                '& .MuiListItemIcon-root': { color: 'primary.main' },
              },
            }}
          >
            <ListItemIcon sx={{ minWidth: 36 }}>
              <Icon fontSize="small" />
            </ListItemIcon>
            <ListItemText primary={label} primaryTypographyProps={{ fontSize: 14, fontWeight: 500 }} />
          </ListItemButton>
        ))}
      </List>

      <Stack sx={{ px: 1, pb: 1 }}>
        {!agency?.hidePlatformBranding && (
          <Box sx={{ fontSize: 11, color: 'text.secondary' }}>Powered by Search29</Box>
        )}
      </Stack>
    </Box>
  );
}
