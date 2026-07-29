import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Switch from '@mui/material/Switch';
import TextField from '@mui/material/TextField';
import Button from '@mui/material/Button';
import Chip from '@mui/material/Chip';
import CircularProgress from '@mui/material/CircularProgress';
import Alert from '@mui/material/Alert';
import GlassCard from '../../components/ui/GlassCard';
import { notificationsApi } from '../../api/notifications';

function ChannelRow({ channel, saved, onSave, onTest, isTesting, testResult }) {
  const [enabled, setEnabled] = useState(saved?.is_enabled ?? false);
  const [config, setConfig] = useState(saved?.config ?? {});
  const dirty = enabled !== (saved?.is_enabled ?? false) || JSON.stringify(config) !== JSON.stringify(saved?.config ?? {});

  return (
    <GlassCard>
      <Stack spacing={1.5}>
        <Stack direction="row" justifyContent="space-between" alignItems="center">
          <Stack spacing={0.25}>
            <Typography variant="subtitle1">{channel.name}</Typography>
            {channel.key === 'email' && (
              <Typography variant="caption" color="text.secondary">
                Always available — uses your configured mail settings
              </Typography>
            )}
            {(channel.key === 'sms' || channel.key === 'whatsapp') && (
              <Typography variant="caption" color="text.secondary">
                Requires Twilio credentials configured on the server
              </Typography>
            )}
          </Stack>
          <Switch checked={enabled} onChange={(e) => setEnabled(e.target.checked)} disabled={channel.key === 'email'} />
        </Stack>

        {channel.required_config_keys.includes('webhook_url') && (
          <TextField
            size="small"
            label="Webhook URL"
            value={config.webhook_url || ''}
            onChange={(e) => setConfig({ ...config, webhook_url: e.target.value })}
            fullWidth
          />
        )}

        {testResult && (
          <Alert severity={testResult.success ? 'success' : 'error'} sx={{ py: 0 }}>
            {testResult.success ? 'Test sent successfully.' : testResult.error}
          </Alert>
        )}

        <Stack direction="row" spacing={1}>
          <Button size="small" variant="contained" disabled={!dirty} onClick={() => onSave(channel.key, { is_enabled: enabled, config })}>
            Save
          </Button>
          <Button size="small" disabled={isTesting || (!enabled && channel.key !== 'email')} onClick={() => onTest(channel.key)}>
            {isTesting ? 'Sending…' : 'Send test'}
          </Button>
        </Stack>
      </Stack>
    </GlassCard>
  );
}

export default function NotificationSettingsPage() {
  const queryClient = useQueryClient();
  const [testResults, setTestResults] = useState({});
  const [testingChannel, setTestingChannel] = useState(null);

  const { data: catalogueData, isLoading: catalogueLoading } = useQuery({
    queryKey: ['notifications-catalogue'],
    queryFn: () => notificationsApi.catalogue(),
  });
  const catalogue = catalogueData?.data ?? [];

  const { data: configData, isLoading: configLoading } = useQuery({
    queryKey: ['notifications-config'],
    queryFn: () => notificationsApi.list(),
  });
  const saved = configData?.data ?? {};

  const updateMutation = useMutation({
    mutationFn: ({ channel, data }) => notificationsApi.update(channel, data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications-config'] }),
  });

  const testMutation = useMutation({
    mutationFn: (channel) => notificationsApi.test(channel),
    onMutate: (channel) => setTestingChannel(channel),
    onSuccess: (result, channel) => setTestResults((prev) => ({ ...prev, [channel]: result })),
    onError: (error, channel) =>
      setTestResults((prev) => ({ ...prev, [channel]: { success: false, error: error.response?.data?.error || 'Test failed.' } })),
    onSettled: () => setTestingChannel(null),
  });

  const isLoading = catalogueLoading || configLoading;

  return (
    <Stack spacing={3}>
      <Stack spacing={0.5}>
        <Typography variant="h4">Notifications</Typography>
        <Typography variant="body2" color="text.secondary">
          Choose how your team hears about critical alerts
        </Typography>
      </Stack>

      {isLoading && (
        <Stack alignItems="center" py={8}>
          <CircularProgress size={28} />
        </Stack>
      )}

      {!isLoading && (
        <Stack spacing={2}>
          {catalogue.map((channel) => (
            <ChannelRow
              key={channel.key}
              channel={channel}
              saved={saved[channel.key]}
              onSave={(key, data) => updateMutation.mutate({ channel: key, data })}
              onTest={(key) => testMutation.mutate(key)}
              isTesting={testingChannel === channel.key}
              testResult={testResults[channel.key]}
            />
          ))}
        </Stack>
      )}

      <Chip label="Critical-severity alerts trigger notifications automatically" size="small" variant="outlined" sx={{ alignSelf: 'flex-start' }} />
    </Stack>
  );
}
