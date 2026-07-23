import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogTitle from '@mui/material/DialogTitle';
import DialogContent from '@mui/material/DialogContent';
import DialogActions from '@mui/material/DialogActions';
import TextField from '@mui/material/TextField';
import Chip from '@mui/material/Chip';
import Grid from '@mui/material/Grid';
import Alert from '@mui/material/Alert';
import CircularProgress from '@mui/material/CircularProgress';
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import GlassCard from '../../components/ui/GlassCard';
import { clientsApi } from '../../api/clients';

const STATUS_COLOR = {
  active: 'success',
  onboarding: 'info',
  paused: 'warning',
  archived: 'default',
};

function CreateClientDialog({ open, onClose }) {
  const queryClient = useQueryClient();
  const [name, setName] = useState('');
  const [website, setWebsite] = useState('');

  const mutation = useMutation({
    mutationFn: () => clientsApi.create({ name, website: website || undefined }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['clients'] });
      setName('');
      setWebsite('');
      onClose();
    },
  });

  return (
    <Dialog open={open} onClose={onClose} fullWidth maxWidth="xs">
      <DialogTitle>Add client</DialogTitle>
      <DialogContent>
        <Stack spacing={2} sx={{ mt: 1 }}>
          {mutation.isError && (
            <Alert severity="error">
              {mutation.error?.response?.data?.errors?.plan?.[0] ||
                mutation.error?.response?.data?.message ||
                'Could not create client.'}
            </Alert>
          )}
          <TextField label="Client name" value={name} onChange={(e) => setName(e.target.value)} required autoFocus fullWidth />
          <TextField label="Website (optional)" value={website} onChange={(e) => setWebsite(e.target.value)} fullWidth />
        </Stack>
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose}>Cancel</Button>
        <Button
          variant="contained"
          disabled={!name || mutation.isPending}
          onClick={() => mutation.mutate()}
        >
          {mutation.isPending ? 'Creating…' : 'Create client'}
        </Button>
      </DialogActions>
    </Dialog>
  );
}

export default function ClientsListPage() {
  const [dialogOpen, setDialogOpen] = useState(false);

  const { data, isLoading, isError } = useQuery({
    queryKey: ['clients'],
    queryFn: () => clientsApi.list(),
  });

  const clients = data?.data ?? [];

  return (
    <Stack spacing={4}>
      <Stack direction="row" justifyContent="space-between" alignItems="center">
        <Stack spacing={0.5}>
          <Typography variant="h4">Clients</Typography>
          <Typography variant="body2" color="text.secondary">
            {clients.length} {clients.length === 1 ? 'client' : 'clients'}
          </Typography>
        </Stack>
        <Button variant="contained" startIcon={<AddRoundedIcon />} onClick={() => setDialogOpen(true)}>
          Add client
        </Button>
      </Stack>

      {isLoading && (
        <Stack alignItems="center" py={6}>
          <CircularProgress size={28} />
        </Stack>
      )}

      {isError && <Alert severity="error">Couldn't load clients. Is the backend running?</Alert>}

      {!isLoading && !isError && clients.length === 0 && (
        <GlassCard sx={{ textAlign: 'center', py: 6 }}>
          <Typography variant="h6" gutterBottom>
            No clients yet
          </Typography>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            Add your first client to start connecting their marketing channels.
          </Typography>
          <Button variant="contained" startIcon={<AddRoundedIcon />} onClick={() => setDialogOpen(true)}>
            Add client
          </Button>
        </GlassCard>
      )}

      <Grid container spacing={3}>
        {clients.map((client) => (
          <Grid item xs={12} sm={6} lg={4} key={client.id}>
            <GlassCard interactive>
              <Stack spacing={1.5}>
                <Stack direction="row" justifyContent="space-between" alignItems="flex-start">
                  <Typography variant="h6">{client.name}</Typography>
                  <Chip
                    label={client.status}
                    size="small"
                    color={STATUS_COLOR[client.status] || 'default'}
                    variant="outlined"
                  />
                </Stack>
                {client.website && (
                  <Typography variant="body2" color="text.secondary">
                    {client.website}
                  </Typography>
                )}
              </Stack>
            </GlassCard>
          </Grid>
        ))}
      </Grid>

      <CreateClientDialog open={dialogOpen} onClose={() => setDialogOpen(false)} />
    </Stack>
  );
}
