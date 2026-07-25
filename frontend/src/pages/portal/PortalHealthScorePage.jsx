import { useQuery } from '@tanstack/react-query';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import CircularProgress from '@mui/material/CircularProgress';
import HealthScoreDisplay from '../../components/ui/HealthScoreDisplay';
import { portalApi } from '../../api/portal';

export default function PortalHealthScorePage() {
  const { data, isLoading } = useQuery({ queryKey: ['portal-health-score'], queryFn: () => portalApi.healthScore() });

  return (
    <Stack spacing={4}>
      <Stack spacing={0.5}>
        <Typography variant="h4">Health Score</Typography>
        <Typography variant="body2" color="text.secondary">
          A 0–100 read on how your marketing is performing overall
        </Typography>
      </Stack>

      {isLoading && (
        <Stack alignItems="center" py={8}>
          <CircularProgress size={28} />
        </Stack>
      )}

      {!isLoading && data?.data && (
        <HealthScoreDisplay score={data.data} trend={data.trend} previousOverall={data.previous_overall_score} />
      )}
    </Stack>
  );
}
