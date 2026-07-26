import { useEffect, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Button from '@mui/material/Button';
import Grid from '@mui/material/Grid';
import CircularProgress from '@mui/material/CircularProgress';
import FormControl from '@mui/material/FormControl';
import InputLabel from '@mui/material/InputLabel';
import Select from '@mui/material/Select';
import MenuItem from '@mui/material/MenuItem';
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import GlassCard from '../../components/ui/GlassCard';
import GoalCard from '../../components/ui/GoalCard';
import CreateGoalDialog from '../../components/ui/CreateGoalDialog';
import { goalsApi } from '../../api/goals';
import { clientsApi } from '../../api/clients';

export default function GoalsPage() {
  const queryClient = useQueryClient();
  const [selectedClientId, setSelectedClientId] = useState('');
  const [statusFilter, setStatusFilter] = useState('active');
  const [dialogOpen, setDialogOpen] = useState(false);

  const { data: clientsData } = useQuery({ queryKey: ['clients'], queryFn: () => clientsApi.list() });
  const clients = clientsData?.data ?? [];

  useEffect(() => {
    if (!selectedClientId && clients.length > 0) setSelectedClientId(clients[0].id);
  }, [clients, selectedClientId]);

  const { data: catalogueData } = useQuery({ queryKey: ['goals-catalogue'], queryFn: () => goalsApi.catalogue() });
  const catalogue = catalogueData?.data ?? [];

  const { data, isLoading } = useQuery({
    queryKey: ['goals', selectedClientId, statusFilter],
    queryFn: () => goalsApi.list(selectedClientId, statusFilter || undefined),
    enabled: Boolean(selectedClientId),
  });
  const goals = data?.data ?? [];

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['goals', selectedClientId] });

  const createMutation = useMutation({
    mutationFn: (payload) => goalsApi.create(selectedClientId, payload),
    onSuccess: () => {
      invalidate();
      setDialogOpen(false);
    },
  });
  const progressMutation = useMutation({
    mutationFn: ({ goalId, value, mode }) => goalsApi.addProgress(selectedClientId, goalId, value, mode),
    onSuccess: invalidate,
  });
  const recomputeMutation = useMutation({
    mutationFn: (goalId) => goalsApi.recompute(selectedClientId, goalId),
    onSuccess: invalidate,
  });
  const archiveMutation = useMutation({
    mutationFn: (goalId) => goalsApi.archive(selectedClientId, goalId),
    onSuccess: invalidate,
  });
  const deleteMutation = useMutation({
    mutationFn: (goalId) => goalsApi.remove(selectedClientId, goalId),
    onSuccess: invalidate,
  });

  return (
    <Stack spacing={3}>
      <Stack direction={{ xs: 'column', sm: 'row' }} justifyContent="space-between" alignItems={{ sm: 'center' }} spacing={2}>
        <Stack spacing={0.5}>
          <Typography variant="h4">Goals</Typography>
          <Typography variant="body2" color="text.secondary">
            Track progress toward targets — leads, traffic, revenue, ROAS, and more
          </Typography>
        </Stack>

        <Stack direction="row" spacing={1}>
          <FormControl size="small" sx={{ minWidth: 200 }}>
            <InputLabel id="goals-client-select-label">Client</InputLabel>
            <Select
              labelId="goals-client-select-label"
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
            <InputLabel id="goals-status-label">Status</InputLabel>
            <Select labelId="goals-status-label" label="Status" value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
              <MenuItem value="active">Active</MenuItem>
              <MenuItem value="completed">Completed</MenuItem>
              <MenuItem value="missed">Missed</MenuItem>
              <MenuItem value="archived">Archived</MenuItem>
              <MenuItem value="">All</MenuItem>
            </Select>
          </FormControl>

          <Button variant="contained" startIcon={<AddRoundedIcon />} onClick={() => setDialogOpen(true)} disabled={!selectedClientId}>
            Add goal
          </Button>
        </Stack>
      </Stack>

      {isLoading && (
        <Stack alignItems="center" py={8}>
          <CircularProgress size={28} />
        </Stack>
      )}

      {!isLoading && goals.length === 0 && (
        <GlassCard sx={{ textAlign: 'center', py: 8 }}>
          <Typography color="text.secondary">No goals yet — add one to start tracking progress.</Typography>
        </GlassCard>
      )}

      <Grid container spacing={3}>
        {goals.map((goal) => (
          <Grid item xs={12} sm={6} lg={4} key={goal.id}>
            <GoalCard
              goal={goal}
              onAddProgress={(value, mode) => progressMutation.mutate({ goalId: goal.id, value, mode })}
              onRecompute={() => recomputeMutation.mutate(goal.id)}
              onArchive={() => archiveMutation.mutate(goal.id)}
              onDelete={() => deleteMutation.mutate(goal.id)}
            />
          </Grid>
        ))}
      </Grid>

      <CreateGoalDialog
        open={dialogOpen}
        onClose={() => setDialogOpen(false)}
        catalogue={catalogue}
        onCreate={(payload) => createMutation.mutateAsync(payload)}
        isSubmitting={createMutation.isPending}
        error={createMutation.error?.response?.data?.message}
      />
    </Stack>
  );
}
