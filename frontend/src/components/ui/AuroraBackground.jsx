import Box from '@mui/material/Box';
import { useTheme } from '@mui/material/styles';
import { gradients } from '../../theme/tokens';

/**
 * Signature element for the platform: a slow-drifting gradient mesh behind
 * every glass surface, standing in for the many channels (search, social,
 * ads, email) the product pulls together into one converged read. Respects
 * prefers-reduced-motion by disabling the drift animation entirely.
 */
export default function AuroraBackground({ variant = 'ambient' }) {
  const theme = useTheme();
  const background = gradients.aurora(theme.palette.mode);

  return (
    <Box
      aria-hidden="true"
      sx={{
        position: 'fixed',
        inset: 0,
        zIndex: -1,
        background,
        backgroundColor: theme.palette.background.default,
        backgroundSize: variant === 'hero' ? '160% 160%' : '130% 130%',
        animation: variant === 'hero' ? 'auroraDrift 24s ease-in-out infinite' : 'none',
        '@media (prefers-reduced-motion: reduce)': {
          animation: 'none',
        },
        '@keyframes auroraDrift': {
          '0%, 100%': { backgroundPosition: '0% 0%' },
          '50%': { backgroundPosition: '100% 40%' },
        },
      }}
    />
  );
}
