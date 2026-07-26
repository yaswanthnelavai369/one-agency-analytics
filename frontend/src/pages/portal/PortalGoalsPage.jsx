import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Button from '@mui/material/Button';
import Grid from '@mui/material/Grid';
import CircularProgress from '@mui/material/CircularProgress';
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import GlassCard from '../../components/ui/GlassCard';
import GoalCard from '../../components/ui/GoalCard';
import CreateGoalDialog from '../../components/ui/CreateGoalDialog';
import { portalApi } from '../../api/portal';

export default function PortalGoalsPage() {
  const queryClient = useQueryClient();
  const [dialogOpen, setDialogOpen] = useState(false);

  const { data: catalogueData } = useQuery({ queryKey: ['portal-goals-catalogue'], queryFn: () => portalApi.goalsCatalogue() });
  const catalogue = catalogueData?.data ?? [];

  const { data, isLoading } = useQuery({ queryKey: ['portal-goals'], queryFn: () => portalApi.goals() });
  const goals = data?.data ?? [];

  const createMutation = useMutation({
    mutationFn: (payload) => portalApi.createGoal(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['portal-goals'] });
      setDialogOpen(false);
    },
  });

  return (
    <Stack spacing={3}>
      <Stack direction={{ xs: 'column', sm: 'row' }} justifyContent="space-between" alignItems={{ sm: 'center' }} spacing={2}>
        <Stack spacing={0.5}>
          <Typography variant="h4">Goals</Typography>
          <Typography variant="body2" color="text.secondary">
            Set and track targets for your marketing performance
          </Typography>
        </Stack>
        <Button variant="contained" startIcon={<AddRoundedIcon />} onClick={() => setDialogOpen(true)}>
          Add goal
        </Button>
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
          <Grid item xs={12} sm={6} key={goal.id}>
            <GoalCard goal={goal} />
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
