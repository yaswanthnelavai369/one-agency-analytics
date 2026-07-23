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
import FormControl from '@mui/material/FormControl';
import InputLabel from '@mui/material/InputLabel';
import Select from '@mui/material/Select';
import MenuItem from '@mui/material/MenuItem';
import LinkRoundedIcon from '@mui/icons-material/LinkRounded';
import SyncRoundedIcon from '@mui/icons-material/SyncRounded';
import LinkOffRoundedIcon from '@mui/icons-material/LinkOffRounded';
import GlassCard from '../../components/ui/GlassCard';
import { integrationsApi } from '../../api/integrations';
import { clientsApi } from '../../api/clients';

const CATEGORY_LABEL = {
  analytics: 'Analytics',
  ads: 'Advertising',
  social: 'Social',
  seo: 'SEO',
  crm: 'CRM',
  ecommerce: 'E-commerce',
  email: 'Email',
};

const STATUS_COLOR = {
  connected: 'success',
  error: 'error',
  pending: 'warning',
  disconnected: 'default',
};

export default function IntegrationsPage() {
  const queryClient = useQueryClient();
  const [searchParams, setSearchParams] = useSearchParams();
  const [selectedClientId, setSelectedClientId] = useState('');
  const [banner, setBanner] = useState(null);

  // Land back here after the OAuth redirect (?connected=google_analytics_4 or ?error=...)
  useEffect(() => {
    const connected = searchParams.get('connected');
    const error = searchParams.get('error');
    if (connected) setBanner({ severity: 'success', message: `Connected ${connected.replaceAll('_', ' ')}.` });
    if (error) setBanner({ severity: 'error', message: error });
    if (connected || error) {
      setSearchParams({}, { replace: true });
      queryClient.invalidateQueries({ queryKey: ['integrations'] });
    }
  }, [searchParams, setSearchParams, queryClient]);

  const { data: clientsData } = useQuery({ queryKey: ['clients'], queryFn: () => clientsApi.list() });
  const clients = clientsData?.data ?? [];

  useEffect(() => {
    if (!selectedClientId && clients.length > 0) setSelectedClientId(clients[0].id);
  }, [clients, selectedClientId]);

  const { data: catalogueData } = useQuery({
    queryKey: ['integrations-catalogue'],
    queryFn: () => integrationsApi.catalogue(),
  });
  const catalogue = catalogueData?.data ?? [];

  const { data: connectedData, isLoading: loadingConnected } = useQuery({
    queryKey: ['integrations', selectedClientId],
    queryFn: () => integrationsApi.list(selectedClientId),
    enabled: Boolean(selectedClientId),
  });
  const connected = connectedData?.data ?? [];
  const connectedByProvider = Object.fromEntries(connected.map((i) => [i.provider, i]));

  const connectMutation = useMutation({
    mutationFn: (provider) => integrationsApi.connect(selectedClientId, provider),
    onSuccess: (result) => {
      window.location.href = result.auth_url; // hand off to the provider's OAuth consent screen
    },
  });

  const syncMutation = useMutation({
    mutationFn: (integrationId) => integrationsApi.syncNow(selectedClientId, integrationId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['integrations', selectedClientId] }),
  });

  const disconnectMutation = useMutation({
    mutationFn: (integrationId) => integrationsApi.disconnect(selectedClientId, integrationId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['integrations', selectedClientId] }),
  });

  return (
    <Stack spacing={4}>
      <Stack direction={{ xs: 'column', sm: 'row' }} justifyContent="space-between" alignItems={{ sm: 'center' }} spacing={2}>
        <Stack spacing={0.5}>
          <Typography variant="h4">Integrations</Typography>
          <Typography variant="body2" color="text.secondary">
            Connect a client's marketing channels to start pulling their data in
          </Typography>
        </Stack>

        <FormControl size="small" sx={{ minWidth: 220 }}>
          <InputLabel id="client-select-label">Client</InputLabel>
          <Select
            labelId="client-select-label"
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
      </Stack>

      {banner && (
        <Alert severity={banner.severity} onClose={() => setBanner(null)}>
          {banner.message}
        </Alert>
      )}

      {!selectedClientId && (
        <GlassCard sx={{ textAlign: 'center', py: 6 }}>
          <Typography color="text.secondary">Add a client first to connect their channels.</Typography>
        </GlassCard>
      )}

      {selectedClientId && loadingConnected && (
        <Stack alignItems="center" py={4}>
          <CircularProgress size={24} />
        </Stack>
      )}

      {selectedClientId && !loadingConnected && (
        <Grid container spacing={3}>
          {catalogue.map((provider) => {
            const integration = connectedByProvider[provider.key];
            const isConnected = integration?.status === 'connected';

            return (
              <Grid item xs={12} sm={6} lg={4} key={provider.key}>
                <GlassCard>
                  <Stack spacing={1.5}>
                    <Stack direction="row" justifyContent="space-between" alignItems="flex-start">
                      <Stack spacing={0.5}>
                        <Typography variant="h6">{provider.name}</Typography>
                        <Chip label={CATEGORY_LABEL[provider.category] || provider.category} size="small" variant="outlined" />
                      </Stack>
                      {integration && (
                        <Chip label={integration.status} size="small" color={STATUS_COLOR[integration.status]} />
                      )}
                    </Stack>

                    {integration?.display_name && (
                      <Typography variant="body2" color="text.secondary">
                        {integration.display_name}
                      </Typography>
                    )}

                    {integration?.last_error && (
                      <Alert severity="error" sx={{ py: 0 }}>
                        {integration.last_error}
                      </Alert>
                    )}

                    <Stack direction="row" spacing={1}>
                      {!isConnected && (
                        <Button
                          size="small"
                          variant="contained"
                          startIcon={<LinkRoundedIcon />}
                          disabled={connectMutation.isPending}
                          onClick={() => connectMutation.mutate(provider.key)}
                        >
                          Connect
                        </Button>
                      )}
                      {isConnected && (
                        <>
                          <Button
                            size="small"
                            startIcon={<SyncRoundedIcon />}
                            disabled={syncMutation.isPending}
                            onClick={() => syncMutation.mutate(integration.id)}
                          >
                            Sync now
                          </Button>
                          <Button
                            size="small"
                            color="error"
                            startIcon={<LinkOffRoundedIcon />}
                            disabled={disconnectMutation.isPending}
                            onClick={() => disconnectMutation.mutate(integration.id)}
                          >
                            Disconnect
                          </Button>
                        </>
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
