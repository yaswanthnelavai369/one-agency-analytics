import Box from '@mui/material/Box';
import Container from '@mui/material/Container';
import Stack from '@mui/material/Stack';
import AuroraBackground from '../ui/AuroraBackground';
import GlassCard from '../ui/GlassCard';
import LogoMark from '../ui/LogoMark';

export default function AuthLayout({ children }) {
  return (
    <Box sx={{ minHeight: '100vh', display: 'flex', alignItems: 'center' }}>
      <AuroraBackground variant="hero" />
      <Container maxWidth="sm">
        <Stack spacing={4} alignItems="center">
          <LogoMark />
          <GlassCard sx={{ width: '100%', p: { xs: 3, sm: 5 } }}>{children}</GlassCard>
        </Stack>
      </Container>
    </Box>
  );
}
