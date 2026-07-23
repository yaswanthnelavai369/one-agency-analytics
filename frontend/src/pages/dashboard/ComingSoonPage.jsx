import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import GlassCard from '../../components/ui/GlassCard';

export default function ComingSoonPage({ title, description }) {
  return (
    <Stack spacing={4}>
      <Typography variant="h4">{title}</Typography>
      <GlassCard sx={{ textAlign: 'center', py: 8 }}>
        <Typography variant="h6" gutterBottom>
          Coming soon
        </Typography>
        <Typography variant="body2" color="text.secondary">
          {description}
        </Typography>
      </GlassCard>
    </Stack>
  );
}
