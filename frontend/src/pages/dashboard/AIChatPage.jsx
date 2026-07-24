import { useEffect, useRef, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Box from '@mui/material/Box';
import TextField from '@mui/material/TextField';
import IconButton from '@mui/material/IconButton';
import Chip from '@mui/material/Chip';
import Avatar from '@mui/material/Avatar';
import CircularProgress from '@mui/material/CircularProgress';
import Alert from '@mui/material/Alert';
import FormControl from '@mui/material/FormControl';
import InputLabel from '@mui/material/InputLabel';
import Select from '@mui/material/Select';
import MenuItem from '@mui/material/MenuItem';
import SendRoundedIcon from '@mui/icons-material/SendRounded';
import AutoAwesomeRoundedIcon from '@mui/icons-material/AutoAwesomeRounded';
import { useTheme } from '@mui/material/styles';
import GlassCard from '../../components/ui/GlassCard';
import { aiChatApi } from '../../api/aiChat';
import { clientsApi } from '../../api/clients';

function MessageBubble({ role, content }) {
  const theme = useTheme();
  const isUser = role === 'user';

  return (
    <Stack direction="row" spacing={1.5} justifyContent={isUser ? 'flex-end' : 'flex-start'}>
      {!isUser && (
        <Avatar sx={{ width: 28, height: 28, bgcolor: 'primary.main' }}>
          <AutoAwesomeRoundedIcon sx={{ fontSize: 16 }} />
        </Avatar>
      )}
      <Box
        sx={{
          maxWidth: '75%',
          px: 2,
          py: 1.25,
          borderRadius: 3,
          backgroundColor: isUser
            ? 'primary.main'
            : theme.palette.mode === 'dark'
            ? 'rgba(255,255,255,0.06)'
            : 'rgba(255,255,255,0.7)',
          color: isUser ? 'primary.contrastText' : 'text.primary',
          whiteSpace: 'pre-wrap',
        }}
      >
        <Typography variant="body2">{content}</Typography>
      </Box>
    </Stack>
  );
}

export default function AIChatPage() {
  const queryClient = useQueryClient();
  const [selectedClientId, setSelectedClientId] = useState('');
  const [draft, setDraft] = useState('');
  const scrollRef = useRef(null);

  const { data: clientsData } = useQuery({ queryKey: ['clients'], queryFn: () => clientsApi.list() });
  const clients = clientsData?.data ?? [];

  useEffect(() => {
    if (!selectedClientId && clients.length > 0) setSelectedClientId(clients[0].id);
  }, [clients, selectedClientId]);

  const { data: promptsData } = useQuery({ queryKey: ['ai-quick-prompts'], queryFn: () => aiChatApi.quickPrompts() });
  const quickPrompts = Object.values(promptsData?.data ?? {}).flat().slice(0, 6);

  const { data: conversationData, isLoading } = useQuery({
    queryKey: ['ai-chat', selectedClientId],
    queryFn: () => aiChatApi.getConversation(selectedClientId),
    enabled: Boolean(selectedClientId),
  });
  const messages = conversationData?.data?.messages ?? [];

  const sendMutation = useMutation({
    mutationFn: (message) => aiChatApi.sendMessage(selectedClientId, message),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['ai-chat', selectedClientId] });
      setDraft('');
    },
  });

  useEffect(() => {
    scrollRef.current?.scrollTo({ top: scrollRef.current.scrollHeight, behavior: 'smooth' });
  }, [messages.length, sendMutation.isPending]);

  const handleSend = (text) => {
    const message = (text ?? draft).trim();
    if (!message || sendMutation.isPending) return;
    sendMutation.mutate(message);
  };

  return (
    <Stack spacing={3} sx={{ height: '100%' }}>
      <Stack direction={{ xs: 'column', sm: 'row' }} justifyContent="space-between" alignItems={{ sm: 'center' }} spacing={2}>
        <Stack spacing={0.5}>
          <Typography variant="h4">AI Insights</Typography>
          <Typography variant="body2" color="text.secondary">
            Ask questions about this client's marketing performance
          </Typography>
        </Stack>

        <FormControl size="small" sx={{ minWidth: 220 }}>
          <InputLabel id="ai-client-select-label">Client</InputLabel>
          <Select
            labelId="ai-client-select-label"
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

      <GlassCard sx={{ flex: 1, display: 'flex', flexDirection: 'column', minHeight: 480, p: 0, overflow: 'hidden' }}>
        <Box ref={scrollRef} sx={{ flex: 1, overflowY: 'auto', p: 3 }}>
          {isLoading && (
            <Stack alignItems="center" py={6}>
              <CircularProgress size={24} />
            </Stack>
          )}

          {!isLoading && messages.length === 0 && (
            <Stack spacing={2} alignItems="center" justifyContent="center" sx={{ height: '100%', py: 6 }}>
              <AutoAwesomeRoundedIcon sx={{ fontSize: 32, color: 'primary.main' }} />
              <Typography color="text.secondary" align="center">
                Ask anything about this client's data — traffic, campaigns, SEO, or ask for a strategy.
              </Typography>
            </Stack>
          )}

          <Stack spacing={2}>
            {messages.map((m) => (
              <MessageBubble key={m.id} role={m.role} content={m.content} />
            ))}
            {sendMutation.isPending && (
              <Stack direction="row" spacing={1.5} alignItems="center">
                <Avatar sx={{ width: 28, height: 28, bgcolor: 'primary.main' }}>
                  <AutoAwesomeRoundedIcon sx={{ fontSize: 16 }} />
                </Avatar>
                <CircularProgress size={16} />
              </Stack>
            )}
          </Stack>
        </Box>

        {sendMutation.isError && (
          <Box sx={{ px: 2 }}>
            <Alert severity="error" onClose={() => sendMutation.reset()}>
              {sendMutation.error?.response?.data?.errors?.plan?.[0] ||
                sendMutation.error?.response?.data?.message ||
                "Couldn't reach the AI assistant."}
            </Alert>
          </Box>
        )}

        {quickPrompts.length > 0 && messages.length === 0 && (
          <Stack direction="row" spacing={1} flexWrap="wrap" useFlexGap sx={{ px: 2, pb: 1 }}>
            {quickPrompts.map((prompt) => (
              <Chip key={prompt} label={prompt} size="small" onClick={() => handleSend(prompt)} sx={{ mb: 1 }} />
            ))}
          </Stack>
        )}

        <Stack
          direction="row"
          spacing={1}
          sx={{ p: 2, borderTop: (t) => `1px solid ${t.palette.divider}` }}
          component="form"
          onSubmit={(e) => {
            e.preventDefault();
            handleSend();
          }}
        >
          <TextField
            fullWidth
            size="small"
            placeholder="Ask about traffic, campaigns, SEO…"
            value={draft}
            onChange={(e) => setDraft(e.target.value)}
            disabled={!selectedClientId || sendMutation.isPending}
          />
          <IconButton
            type="submit"
            color="primary"
            disabled={!draft.trim() || !selectedClientId || sendMutation.isPending}
          >
            <SendRoundedIcon />
          </IconButton>
        </Stack>
      </GlassCard>
    </Stack>
  );
}
