import { useState } from 'react';
import AppBar from '@mui/material/AppBar';
import Toolbar from '@mui/material/Toolbar';
import Stack from '@mui/material/Stack';
import IconButton from '@mui/material/IconButton';
import Tooltip from '@mui/material/Tooltip';
import Menu from '@mui/material/Menu';
import MenuItem from '@mui/material/MenuItem';
import Avatar from '@mui/material/Avatar';
import Badge from '@mui/material/Badge';
import LightModeRoundedIcon from '@mui/icons-material/LightModeRounded';
import DarkModeRoundedIcon from '@mui/icons-material/DarkModeRounded';
import BrightnessAutoRoundedIcon from '@mui/icons-material/BrightnessAutoRounded';
import NotificationsRoundedIcon from '@mui/icons-material/NotificationsRounded';
import { useTheme } from '@mui/material/styles';
import { useThemeMode } from '../../context/ThemeModeContext';
import { useAuth } from '../../context/AuthContext';

const MODE_CYCLE = ['light', 'dark', 'auto'];
const MODE_ICON = {
  light: LightModeRoundedIcon,
  dark: DarkModeRoundedIcon,
  auto: BrightnessAutoRoundedIcon,
};

export default function Topbar() {
  const theme = useTheme();
  const { mode, setThemeMode } = useThemeMode();
  const { user, logout } = useAuth();
  const [anchorEl, setAnchorEl] = useState(null);

  const cycleTheme = () => {
    const next = MODE_CYCLE[(MODE_CYCLE.indexOf(mode) + 1) % MODE_CYCLE.length];
    setThemeMode(next);
  };

  const ModeIcon = MODE_ICON[mode];

  return (
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
      <Toolbar sx={{ justifyContent: 'flex-end', gap: 1 }}>
        <Tooltip title={`Theme: ${mode} (click to cycle)`}>
          <IconButton onClick={cycleTheme} size="small">
            <ModeIcon fontSize="small" />
          </IconButton>
        </Tooltip>

        <Tooltip title="Notifications">
          <IconButton size="small">
            <Badge color="error" variant="dot" invisible>
              <NotificationsRoundedIcon fontSize="small" />
            </Badge>
          </IconButton>
        </Tooltip>

        <Stack direction="row" alignItems="center" spacing={1}>
          <IconButton onClick={(e) => setAnchorEl(e.currentTarget)} size="small" aria-label="Account menu">
            <Avatar sx={{ width: 32, height: 32, fontSize: 14 }} src={user?.avatar}>
              {user?.name?.[0]?.toUpperCase()}
            </Avatar>
          </IconButton>
        </Stack>

        <Menu anchorEl={anchorEl} open={Boolean(anchorEl)} onClose={() => setAnchorEl(null)}>
          <MenuItem disabled>{user?.email}</MenuItem>
          <MenuItem onClick={logout}>Sign out</MenuItem>
        </Menu>
      </Toolbar>
    </AppBar>
  );
}
