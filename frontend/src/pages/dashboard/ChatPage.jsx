import { useEffect, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import FormControl from '@mui/material/FormControl';
import InputLabel from '@mui/material/InputLabel';
import Select from '@mui/material/Select';
import MenuItem from '@mui/material/MenuItem';
import ChatThread from '../../components/ui/ChatThread';
import { chatApi } from '../../api/chat';
import { clientsApi } from '../../api/clients';

const POLL_INTERVAL_MS = 5000;

export default function ChatPage() {
  const queryClient = useQueryClient();
  const [selectedClientId, setSelectedClientId] = useState('');

  const { data: clientsData } = useQuery({ queryKey: ['clients'], queryFn: () => clientsApi.list() });
  const clients = clientsData?.data ?? [];

  useEffect(() => {
    if (!selectedClientId && clients.length > 0) setSelectedClientId(clients[0].id);
  }, [clients, selectedClientId]);

  const { data, isLoading } = useQuery({
    queryKey: ['chat', selectedClientId],
    queryFn: () => chatApi.getThread(selectedClientId),
    enabled: Boolean(selectedClientId),
    refetchInterval: POLL_INTERVAL_MS,
  });

  const sendMutation = useMutation({
    mutationFn: (message) => chatApi.sendMessage(selectedClientId, message),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['chat', selectedClientId] }),
  });

  return (
    <Stack spacing={3}>
      <Stack direction={{ xs: 'column', sm: 'row' }} justifyContent="space-between" alignItems={{ sm: 'center' }} spacing={2}>
        <Stack spacing={0.5}>
          <Typography variant="h4">Chat</Typography>
          <Typography variant="body2" color="text.secondary">
            Message clients directly — replies show up on their portal
          </Typography>
        </Stack>

        <FormControl size="small" sx={{ minWidth: 220 }}>
          <InputLabel id="chat-client-select-label">Client</InputLabel>
          <Select
            labelId="chat-client-select-label"
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

      <ChatThread
        thread={data?.data}
        mySide="agency"
        onSend={(message) => sendMutation.mutate(message)}
        isLoading={isLoading}
        isSending={sendMutation.isPending}
        emptyStateText="No messages with this client yet — say hello."
      />
    </Stack>
  );
}
