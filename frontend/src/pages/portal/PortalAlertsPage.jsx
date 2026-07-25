import { useQuery } from '@tanstack/react-query';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Chip from '@mui/material/Chip';
import CircularProgress from '@mui/material/CircularProgress';
import WarningRoundedIcon from '@mui/icons-material/WarningRounded';
import ErrorRoundedIcon from '@mui/icons-material/ErrorRounded';
import InfoRoundedIcon from '@mui/icons-material/InfoRounded';
import GlassCard from '../../components/ui/GlassCard';
import { portalApi } from '../../api/portal';
import { semanticColors } from '../../theme/tokens';

const SEVERITY_CONFIG = {
  critical: { color: semanticColors.critical, icon: ErrorRoundedIcon, label: 'Critical' },
  warning: { color: semanticColors.warning, icon: WarningRoundedIcon, label: 'Warning' },
  info: { color: semanticColors.info, icon: InfoRoundedIcon, label: 'Info' },
};

const TYPE_LABELS = {
  traffic_drop: 'Traffic Drop',
  traffic_spike: 'Traffic Spike',
  conversion_drop: 'Conversion Drop',
  revenue_loss: 'Revenue Loss',
  ctr_drop: 'CTR Drop',
  ranking_loss: 'Ranking Loss',
  high_cpc: 'High CPC',
  high_cpa: 'High CPA',
  campaign_failure: 'Campaign Failure',
  api_failure: 'API Failure',
  missing_tracking_codes: 'Missing Tracking Codes',
};

export default function PortalAlertsPage() {
  const { data, isLoading } = useQuery({ queryKey: ['portal-alerts'], queryFn: () => portalApi.alerts() });
  const alerts = data?.data ?? [];

  return (
    <Stack spacing={3}>
      <Stack spacing={0.5}>
        <Typography variant="h4">Alerts</Typography>
        <Typography variant="body2" color="text.secondary">
          Automatically detected changes worth knowing about
        </Typography>
      </Stack>

      {isLoading && (
        <Stack alignItems="center" py={8}>
          <CircularProgress size={28} />
        </Stack>
      )}

      {!isLoading && alerts.length === 0 && (
        <GlassCard sx={{ textAlign: 'center', py: 8 }}>
          <Typography color="text.secondary">No open alerts — everything looks healthy.</Typography>
        </GlassCard>
      )}

      <Stack spacing={2}>
        {alerts.map((a) => {
          const config = SEVERITY_CONFIG[a.severity] || SEVERITY_CONFIG.info;
          const Icon = config.icon;

          return (
            <GlassCard key={a.id} sx={{ borderLeft: `3px solid ${config.color}` }}>
              <Stack direction="row" spacing={1.5} alignItems="flex-start">
                <Icon sx={{ color: config.color, fontSize: 22, mt: 0.25 }} />
                <Stack spacing={0.25}>
                  <Stack direction="row" spacing={1} alignItems="center">
                    <Typography variant="subtitle2">{TYPE_LABELS[a.type] || a.type}</Typography>
                    <Chip label={config.label} size="small" sx={{ backgroundColor: config.color, color: '#fff', height: 20 }} />
                  </Stack>
                  <Typography variant="body2" color="text.secondary">
                    {a.message}
                  </Typography>
                  <Typography variant="caption" color="text.secondary">
                    Detected {a.detected_date}
                  </Typography>
                </Stack>
              </Stack>
            </GlassCard>
          );
        })}
      </Stack>
    </Stack>
  );
}
