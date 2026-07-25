import { useEffect, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Button from '@mui/material/Button';
import CircularProgress from '@mui/material/CircularProgress';
import FormControl from '@mui/material/FormControl';
import InputLabel from '@mui/material/InputLabel';
import Select from '@mui/material/Select';
import MenuItem from '@mui/material/MenuItem';
import RefreshRoundedIcon from '@mui/icons-material/RefreshRounded';
import HealthScoreDisplay from '../../components/ui/HealthScoreDisplay';
import { healthScoreApi } from '../../api/healthScore';
import { clientsApi } from '../../api/clients';

export default function HealthScorePage() {
  const queryClient = useQueryClient();
  const [selectedClientId, setSelectedClientId] = useState('');

  const { data: clientsData } = useQuery({ queryKey: ['clients'], queryFn: () => clientsApi.list() });
  const clients = clientsData?.data ?? [];

  useEffect(() => {
    if (!selectedClientId && clients.length > 0) setSelectedClientId(clients[0].id);
  }, [clients, selectedClientId]);

  const { data, isLoading } = useQuery({
    queryKey: ['health-score', selectedClientId],
    queryFn: () => healthScoreApi.get(selectedClientId),
    enabled: Boolean(selectedClientId),
  });

  const recalculateMutation = useMutation({
    mutationFn: () => healthScoreApi.recalculate(selectedClientId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['health-score', selectedClientId] }),
  });

  return (
    <Stack spacing={4}>
      <Stack direction={{ xs: 'column', sm: 'row' }} justifyContent="space-between" alignItems={{ sm: 'center' }} spacing={2}>
        <Stack spacing={0.5}>
          <Typography variant="h4">AI Health Score</Typography>
          <Typography variant="body2" color="text.secondary">
            A single 0–100 read on how a client's marketing is performing overall
          </Typography>
        </Stack>

        <Stack direction="row" spacing={1} alignItems="center">
          <FormControl size="small" sx={{ minWidth: 220 }}>
            <InputLabel id="hs-client-select-label">Client</InputLabel>
            <Select
              labelId="hs-client-select-label"
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
          <Button
            startIcon={<RefreshRoundedIcon />}
            onClick={() => recalculateMutation.mutate()}
            disabled={!selectedClientId || recalculateMutation.isPending}
          >
            Recalculate
          </Button>
        </Stack>
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
