import Paper from '@mui/material/Paper';
import { styled } from '@mui/material/styles';
import { glass } from '../../theme/tokens';

/**
 * The base glass surface. Every KPI card, panel, and modal in the dashboard
 * composes on top of this rather than reaching for MUI's Paper directly, so
 * the glass treatment (blur, border, elevation) stays consistent everywhere.
 */
const GlassCard = styled(Paper, {
  shouldForwardProp: (prop) => prop !== 'interactive',
})(({ theme, interactive }) => {
  const isDark = theme.palette.mode === 'dark';

  return {
    position: 'relative',
    borderRadius: glass.radius,
    padding: theme.spacing(3),
    backgroundColor: isDark
      ? 'rgba(255, 255, 255, 0.06)'
      : 'rgba(255, 255, 255, 0.55)',
    backdropFilter: `blur(${glass.blur})`,
    WebkitBackdropFilter: `blur(${glass.blur})`,
    border: `1px solid ${isDark ? 'rgba(255,255,255,0.12)' : 'rgba(255,255,255,0.7)'}`,
    boxShadow: isDark
      ? '0 8px 32px rgba(0, 0, 0, 0.35)'
      : '0 8px 32px rgba(22, 26, 43, 0.08)',
    transition: 'transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease',
    ...(interactive && {
      cursor: 'pointer',
      '&:hover': {
        transform: 'translateY(-2px)',
        borderColor: theme.palette.primary.main,
        boxShadow: isDark
          ? `0 12px 40px rgba(0, 0, 0, 0.45)`
          : `0 12px 40px rgba(22, 26, 43, 0.12)`,
      },
      '&:focus-visible': {
        outline: `2px solid ${theme.palette.primary.main}`,
        outlineOffset: 2,
      },
    }),
  };
});

export default GlassCard;
