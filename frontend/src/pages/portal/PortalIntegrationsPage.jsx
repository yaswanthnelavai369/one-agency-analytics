import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Grid from '@mui/material/Grid';
import Chip from '@mui/material/Chip';
import Button from '@mui/material/Button';
import Alert from '@mui/material/Alert';
import CircularProgress from '@mui/material/CircularProgress';
import LinkRoundedIcon from '@mui/icons-material/LinkRounded';
import LinkOffRoundedIcon from '@mui/icons-material/LinkOffRounded';
import GlassCard from '../../components/ui/GlassCard';
import { portalApi } from '../../api/portal';

const STATUS_COLOR = { connected: 'success', error: 'error', pending: 'warning', disconnected: 'default' };

export default function PortalIntegrationsPage() {
  const queryClient = useQueryClient();
  const [searchParams, setSearchParams] = useSearchParams();
  const [banner, setBanner] = useState(null);

  useEffect(() => {
    const connected = searchParams.get('connected');
    const error = searchParams.get('error');
    if (connected) setBanner({ severity: 'success', message: `Connected ${connected.replaceAll('_', ' ')}.` });
    if (error) setBanner({ severity: 'error', message: error });
    if (connected || error) {
      setSearchParams({}, { replace: true });
      queryClient.invalidateQueries({ queryKey: ['portal-integrations'] });
    }
  }, [searchParams, setSearchParams, queryClient]);

  const { data: catalogueData } = useQuery({ queryKey: ['portal-integrations-catalogue'], queryFn: () => portalApi.integrationsCatalogue() });
  const catalogue = catalogueData?.data ?? [];

  const { data: connectedData, isLoading } = useQuery({ queryKey: ['portal-integrations'], queryFn: () => portalApi.integrations() });
  const connected = connectedData?.data ?? [];
  const connectedByProvider = Object.fromEntries(connected.map((i) => [i.provider, i]));

  const connectMutation = useMutation({
    mutationFn: (provider) => portalApi.connectIntegration(provider),
    onSuccess: (result) => {
      window.location.href = result.auth_url;
    },
  });

  const disconnectMutation = useMutation({
    mutationFn: (integrationId) => portalApi.disconnectIntegration(integrationId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['portal-integrations'] }),
  });

  return (
    <Stack spacing={4}>
      <Stack spacing={0.5}>
        <Typography variant="h4">Integrations</Typography>
        <Typography variant="body2" color="text.secondary">
          Connect your own marketing accounts so your agency can see the full picture
        </Typography>
      </Stack>

      {banner && (
        <Alert severity={banner.severity} onClose={() => setBanner(null)}>
          {banner.message}
        </Alert>
      )}

      {isLoading ? (
        <Stack alignItems="center" py={6}>
          <CircularProgress size={24} />
        </Stack>
      ) : (
        <Grid container spacing={3}>
          {catalogue.map((provider) => {
            const integration = connectedByProvider[provider.key];
            const isConnected = integration?.status === 'connected';

            return (
              <Grid item xs={12} sm={6} lg={4} key={provider.key}>
                <GlassCard>
                  <Stack spacing={1.5}>
                    <Stack direction="row" justifyContent="space-between" alignItems="flex-start">
                      <Typography variant="h6">{provider.name}</Typography>
                      {integration && <Chip label={integration.status} size="small" color={STATUS_COLOR[integration.status]} />}
                    </Stack>
                    {integration?.display_name && (
                      <Typography variant="body2" color="text.secondary">
                        {integration.display_name}
                      </Typography>
                    )}
                    <Stack direction="row" spacing={1}>
                      {!isConnected ? (
                        <Button size="small" variant="contained" startIcon={<LinkRoundedIcon />} disabled={connectMutation.isPending} onClick={() => connectMutation.mutate(provider.key)}>
                          Connect
                        </Button>
                      ) : (
                        <Button size="small" color="error" startIcon={<LinkOffRoundedIcon />} disabled={disconnectMutation.isPending} onClick={() => disconnectMutation.mutate(integration.id)}>
                          Disconnect
                        </Button>
                      )}
                    </Stack>
                  </Stack>
                </GlassCard>
              </Grid>
            );
          })}
        </Grid>
      )}
    </Stack>
  );
}
