import { useQuery } from '@tanstack/react-query';
import Grid from '@mui/material/Grid';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import CircularProgress from '@mui/material/CircularProgress';
import GlassCard from '../../components/ui/GlassCard';
import WidgetRenderer from '../../components/ui/WidgetRenderer';
import { portalApi } from '../../api/portal';

export default function PortalOverviewPage() {
  const { data, isLoading } = useQuery({ queryKey: ['portal-dashboard'], queryFn: () => portalApi.dashboard() });
  const layout = data?.data;

  if (isLoading) {
    return (
      <Stack alignItems="center" py={8}>
        <CircularProgress size={28} />
      </Stack>
    );
  }

  const widgets = (layout?.widgets ?? []).filter((w) => !w.hidden);

  return (
    <Stack spacing={3}>
      <Typography variant="h4">{layout?.name || 'Overview'}</Typography>

      {widgets.length === 0 ? (
        <GlassCard sx={{ textAlign: 'center', py: 8 }}>
          <Typography color="text.secondary">Your dashboard hasn't been set up yet — check back soon.</Typography>
        </GlassCard>
      ) : (
        <Grid container spacing={3}>
          {widgets.map((w) => (
            <Grid item xs={12} sm={6} lg={4} key={w.id}>
              <WidgetRenderer widgetType={w.widget_type} meta={w} />
            </Grid>
          ))}
        </Grid>
      )}
    </Stack>
  );
}
