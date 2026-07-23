import Grid from '@mui/material/Grid';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Chip from '@mui/material/Chip';
import KpiCard from '../../components/ui/KpiCard';
import { useAuth } from '../../context/AuthContext';

/**
 * Placeholder metrics — replaced once an integration module (GA4, GSC, Ads, etc.)
 * is wired up to populate real analytics_data. Shape matches what the
 * eventual /agency/dashboard/widgets endpoint will return.
 */
const PLACEHOLDER_KPIS = [
  { label: 'Visitors', value: 48210, format: 'number', deltaPercent: 8.2, trend: [30, 34, 32, 38, 41, 39, 48] },
  { label: 'Conversions', value: 1284, format: 'number', deltaPercent: 4.1, trend: [10, 12, 11, 14, 13, 15, 16] },
  { label: 'Revenue', value: 92450, format: 'currency', deltaPercent: 12.6, trend: [60, 65, 63, 70, 74, 80, 92] },
  { label: 'Ad Spend', value: 18320, format: 'currency', deltaPercent: -2.3, trend: [22, 21, 20, 19, 18, 19, 18] },
  { label: 'ROAS', value: 5.04, format: 'number', deltaPercent: 6.7, trend: [3.8, 4.0, 4.2, 4.5, 4.7, 4.9, 5.0] },
  { label: 'Avg. Session Duration', value: 3.2, format: 'number', deltaPercent: 1.4, trend: [2.8, 2.9, 3.0, 3.0, 3.1, 3.2, 3.2] },
];

export default function OverviewPage() {
  const { user } = useAuth();

  return (
    <Stack spacing={4}>
      <Stack direction={{ xs: 'column', sm: 'row' }} justifyContent="space-between" alignItems={{ sm: 'center' }} spacing={2}>
        <Stack spacing={0.5}>
          <Typography variant="h4">Welcome back{user?.name ? `, ${user.name.split(' ')[0]}` : ''}</Typography>
          <Typography variant="body2" color="text.secondary">
            Here's how your accounts performed over the last 30 days
          </Typography>
        </Stack>
        <Chip label="Sample data — connect an integration to see live metrics" size="small" variant="outlined" />
      </Stack>

      <Grid container spacing={3}>
        {PLACEHOLDER_KPIS.map((kpi) => (
          <Grid item xs={12} sm={6} lg={4} key={kpi.label}>
            <KpiCard {...kpi} />
          </Grid>
        ))}
      </Grid>
    </Stack>
  );
}
