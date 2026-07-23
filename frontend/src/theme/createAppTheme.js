import { createTheme } from '@mui/material/styles';
import { brandDefaults, neutrals, typography, glass } from './tokens';

/**
 * @param {'light'|'dark'} mode - resolved mode (auto is resolved to light/dark before this is called)
 * @param {{ primaryColor?: string, secondaryColor?: string, fontFamily?: string }} brandOverrides
 *   Passed from AgencyContext so each white-labeled agency can shift the accent
 *   colors and heading font without touching component code.
 */
export function createAppTheme(mode = 'light', brandOverrides = {}) {
  const palette = neutrals[mode];
  const primary = brandOverrides.primaryColor || brandDefaults.primary;
  const secondary = brandOverrides.secondaryColor || brandDefaults.secondary;
  const displayFont = brandOverrides.fontFamily
    ? `"${brandOverrides.fontFamily}", ${typography.display}`
    : typography.display;

  return createTheme({
    palette: {
      mode,
      primary: { main: primary },
      secondary: { main: secondary },
      background: {
        default: palette.bg,
        paper: palette.bgElevated,
      },
      text: {
        primary: palette.text,
        secondary: palette.textMuted,
      },
      divider: palette.border,
    },
    shape: {
      borderRadius: glass.radiusSm,
    },
    typography: {
      fontFamily: typography.body,
      h1: { fontFamily: displayFont, fontWeight: 700, letterSpacing: '-0.02em' },
      h2: { fontFamily: displayFont, fontWeight: 700, letterSpacing: '-0.02em' },
      h3: { fontFamily: displayFont, fontWeight: 600, letterSpacing: '-0.01em' },
      h4: { fontFamily: displayFont, fontWeight: 600 },
      h5: { fontFamily: displayFont, fontWeight: 600 },
      h6: { fontFamily: displayFont, fontWeight: 600 },
      button: { fontFamily: typography.body, fontWeight: 600, textTransform: 'none' },
      overline: { fontFamily: typography.mono, letterSpacing: '0.08em' },
    },
    components: {
      MuiButton: {
        styleOverrides: {
          root: {
            borderRadius: 12,
            paddingInline: 18,
          },
        },
      },
      MuiPaper: {
        styleOverrides: {
          root: {
            backgroundImage: 'none',
          },
        },
      },
      MuiCssBaseline: {
        styleOverrides: {
          body: {
            transition: 'background-color 200ms ease, color 200ms ease',
          },
        },
      },
    },
  });
}
