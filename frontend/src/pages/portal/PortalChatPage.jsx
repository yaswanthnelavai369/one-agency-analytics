import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import ChatThread from '../../components/ui/ChatThread';
import { portalApi } from '../../api/portal';

const POLL_INTERVAL_MS = 5000;

export default function PortalChatPage() {
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ['portal-chat'],
    queryFn: () => portalApi.getChat(),
    refetchInterval: POLL_INTERVAL_MS,
  });

  const sendMutation = useMutation({
    mutationFn: (message) => portalApi.sendChatMessage(message),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['portal-chat'] }),
  });

  return (
    <Stack spacing={3}>
      <Stack spacing={0.5}>
        <Typography variant="h4">Chat</Typography>
        <Typography variant="body2" color="text.secondary">
          Message your agency directly
        </Typography>
      </Stack>

      <ChatThread
        thread={data?.data}
        mySide="client"
        onSend={(message) => sendMutation.mutate(message)}
        isLoading={isLoading}
        isSending={sendMutation.isPending}
        emptyStateText="No messages yet — say hello to your agency."
      />
    </Stack>
  );
}
