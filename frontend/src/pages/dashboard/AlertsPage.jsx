import { useEffect, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Button from '@mui/material/Button';
import Chip from '@mui/material/Chip';
import CircularProgress from '@mui/material/CircularProgress';
import FormControl from '@mui/material/FormControl';
import InputLabel from '@mui/material/InputLabel';
import Select from '@mui/material/Select';
import MenuItem from '@mui/material/MenuItem';
import Accordion from '@mui/material/Accordion';
import AccordionSummary from '@mui/material/AccordionSummary';
import AccordionDetails from '@mui/material/AccordionDetails';
import List from '@mui/material/List';
import ListItem from '@mui/material/ListItem';
import ListItemText from '@mui/material/ListItemText';
import ExpandMoreRoundedIcon from '@mui/icons-material/ExpandMoreRounded';
import RefreshRoundedIcon from '@mui/icons-material/RefreshRounded';
import CheckRoundedIcon from '@mui/icons-material/CheckRounded';
import DoneAllRoundedIcon from '@mui/icons-material/DoneAllRounded';
import WarningRoundedIcon from '@mui/icons-material/WarningRounded';
import ErrorRoundedIcon from '@mui/icons-material/ErrorRounded';
import InfoRoundedIcon from '@mui/icons-material/InfoRounded';
import GlassCard from '../../components/ui/GlassCard';
import { anomaliesApi } from '../../api/anomalies';
import { clientsApi } from '../../api/clients';
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

function AnomalyCard({ anomaly, onAcknowledge, onResolve, isUpdating }) {
  const config = SEVERITY_CONFIG[anomaly.severity] || SEVERITY_CONFIG.info;
  const Icon = config.icon;

  return (
    <GlassCard sx={{ borderLeft: `3px solid ${config.color}` }}>
      <Stack spacing={1.5}>
        <Stack direction="row" justifyContent="space-between" alignItems="flex-start">
          <Stack direction="row" spacing={1.5} alignItems="flex-start">
            <Icon sx={{ color: config.color, fontSize: 22, mt: 0.25 }} />
            <Stack spacing={0.25}>
              <Stack direction="row" spacing={1} alignItems="center">
                <Typography variant="subtitle2">{TYPE_LABELS[anomaly.type] || anomaly.type}</Typography>
                <Chip label={config.label} size="small" sx={{ backgroundColor: config.color, color: '#fff', height: 20 }} />
                {anomaly.status !== 'open' && (
                  <Chip label={anomaly.status} size="small" variant="outlined" sx={{ height: 20 }} />
                )}
              </Stack>
              <Typography variant="body2" color="text.secondary">
                {anomaly.message}
              </Typography>
              <Typography variant="caption" color="text.secondary">
                Detected {anomaly.detected_date}
              </Typography>
            </Stack>
          </Stack>

          {anomaly.status === 'open' && (
            <Stack direction="row" spacing={0.5}>
              <Button size="small" startIcon={<CheckRoundedIcon />} disabled={isUpdating} onClick={() => onAcknowledge(anomaly.id)}>
                Acknowledge
              </Button>
              <Button size="small" startIcon={<DoneAllRoundedIcon />} disabled={isUpdating} onClick={() => onResolve(anomaly.id)}>
                Resolve
              </Button>
            </Stack>
          )}
          {anomaly.status === 'acknowledged' && (
            <Button size="small" startIcon={<DoneAllRoundedIcon />} disabled={isUpdating} onClick={() => onResolve(anomaly.id)}>
              Resolve
            </Button>
          )}
        </Stack>

        {(anomaly.possible_causes?.length > 0 || anomaly.recommended_fixes?.length > 0) && (
          <Accordion disableGutters sx={{ background: 'transparent', boxShadow: 'none', '&:before': { display: 'none' } }}>
            <AccordionSummary expandIcon={<ExpandMoreRoundedIcon />} sx={{ px: 0, minHeight: 0 }}>
              <Typography variant="caption" color="text.secondary">
                Possible causes & recommended fixes
              </Typography>
            </AccordionSummary>
            <AccordionDetails sx={{ px: 0, pt: 0 }}>
              <Stack spacing={1.5}>
                {anomaly.possible_causes?.length > 0 && (
                  <Stack spacing={0.5}>
                    <Typography variant="caption" sx={{ fontWeight: 600 }}>
                      Possible causes
                    </Typography>
                    <List dense disablePadding>
                      {anomaly.possible_causes.map((c, i) => (
                        <ListItem key={i} disableGutters sx={{ py: 0.25 }}>
                          <ListItemText primary={`• ${c}`} primaryTypographyProps={{ variant: 'body2' }} />
                        </ListItem>
                      ))}
                    </List>
                  </Stack>
                )}
                {anomaly.recommended_fixes?.length > 0 && (
                  <Stack spacing={0.5}>
                    <Typography variant="caption" sx={{ fontWeight: 600 }}>
                      Recommended fixes
                    </Typography>
                    <List dense disablePadding>
                      {anomaly.recommended_fixes.map((f, i) => (
                        <ListItem key={i} disableGutters sx={{ py: 0.25 }}>
                          <ListItemText primary={`• ${f}`} primaryTypographyProps={{ variant: 'body2' }} />
                        </ListItem>
                      ))}
                    </List>
                  </Stack>
                )}
              </Stack>
            </AccordionDetails>
          </Accordion>
        )}
      </Stack>
    </GlassCard>
  );
}

export default function AlertsPage() {
  const queryClient = useQueryClient();
  const [selectedClientId, setSelectedClientId] = useState('');
  const [statusFilter, setStatusFilter] = useState('open');

  const { data: clientsData } = useQuery({ queryKey: ['clients'], queryFn: () => clientsApi.list() });
  const clients = clientsData?.data ?? [];

  useEffect(() => {
    if (!selectedClientId && clients.length > 0) setSelectedClientId(clients[0].id);
  }, [clients, selectedClientId]);

  const { data, isLoading } = useQuery({
    queryKey: ['anomalies', selectedClientId, statusFilter],
    queryFn: () => anomaliesApi.list(selectedClientId, statusFilter || undefined),
    enabled: Boolean(selectedClientId),
  });
  const anomalies = data?.data ?? [];

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['anomalies', selectedClientId] });

  const detectMutation = useMutation({ mutationFn: () => anomaliesApi.detect(selectedClientId), onSuccess: invalidate });
  const acknowledgeMutation = useMutation({
    mutationFn: (id) => anomaliesApi.acknowledge(selectedClientId, id),
    onSuccess: invalidate,
  });
  const resolveMutation = useMutation({ mutationFn: (id) => anomaliesApi.resolve(selectedClientId, id), onSuccess: invalidate });

  const isUpdating = acknowledgeMutation.isPending || resolveMutation.isPending;

  return (
    <Stack spacing={3}>
      <Stack direction={{ xs: 'column', sm: 'row' }} justifyContent="space-between" alignItems={{ sm: 'center' }} spacing={2}>
        <Stack spacing={0.5}>
          <Typography variant="h4">Alerts</Typography>
          <Typography variant="body2" color="text.secondary">
            Automatically detected anomalies across traffic, conversions, ads, SEO, and integrations
          </Typography>
        </Stack>

        <Stack direction="row" spacing={1}>
          <FormControl size="small" sx={{ minWidth: 200 }}>
            <InputLabel id="alerts-client-select-label">Client</InputLabel>
            <Select
              labelId="alerts-client-select-label"
              label="Client"
              value={selectedClientId}
              onChange={(e) => setSelectedClientId(e.target.value)}
            >
              {clients.map((c) => (
                <MenuItem key={c.id} value={c.id}>
                  {c.name}
                </MenuItem>
              ))}
            </Select>
          </FormControl>

          <FormControl size="small" sx={{ minWidth: 140 }}>
            <InputLabel id="alerts-status-label">Status</InputLabel>
            <Select labelId="alerts-status-label" label="Status" value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
              <MenuItem value="open">Open</MenuItem>
              <MenuItem value="acknowledged">Acknowledged</MenuItem>
              <MenuItem value="resolved">Resolved</MenuItem>
              <MenuItem value="">All</MenuItem>
            </Select>
          </FormControl>

          <Button
            startIcon={<RefreshRoundedIcon />}
            onClick={() => detectMutation.mutate()}
            disabled={!selectedClientId || detectMutation.isPending}
          >
            Run detection
          </Button>
        </Stack>
      </Stack>

      {isLoading && (
        <Stack alignItems="center" py={8}>
          <CircularProgress size={28} />
        </Stack>
      )}

      {!isLoading && anomalies.length === 0 && (
        <GlassCard sx={{ textAlign: 'center', py: 8 }}>
          <Typography color="text.secondary">
            {statusFilter === 'open' ? 'No open alerts — everything looks healthy.' : 'No alerts found for this filter.'}
          </Typography>
        </GlassCard>
      )}

      <Stack spacing={2}>
        {anomalies.map((a) => (
          <AnomalyCard
            key={a.id}
            anomaly={a}
            onAcknowledge={(id) => acknowledgeMutation.mutate(id)}
            onResolve={(id) => resolveMutation.mutate(id)}
            isUpdating={isUpdating}
          />
        ))}
      </Stack>
    </Stack>
  );
}
