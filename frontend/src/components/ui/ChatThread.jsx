import { useEffect, useRef, useState } from 'react';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Box from '@mui/material/Box';
import TextField from '@mui/material/TextField';
import IconButton from '@mui/material/IconButton';
import Avatar from '@mui/material/Avatar';
import CircularProgress from '@mui/material/CircularProgress';
import SendRoundedIcon from '@mui/icons-material/SendRounded';
import ForumRoundedIcon from '@mui/icons-material/ForumRounded';
import { useTheme } from '@mui/material/styles';
import GlassCard from './GlassCard';

function MessageBubble({ isMine, senderName, content }) {
  const theme = useTheme();

  return (
    <Stack direction="row" spacing={1.5} justifyContent={isMine ? 'flex-end' : 'flex-start'}>
      {!isMine && (
        <Avatar sx={{ width: 28, height: 28, fontSize: 13 }}>{senderName?.[0]?.toUpperCase() || '?'}</Avatar>
      )}
      <Stack spacing={0.25} sx={{ maxWidth: '75%', alignItems: isMine ? 'flex-end' : 'flex-start' }}>
        <Box
          sx={{
            px: 2,
            py: 1.25,
            borderRadius: 3,
            backgroundColor: isMine ? 'primary.main' : theme.palette.mode === 'dark' ? 'rgba(255,255,255,0.06)' : 'rgba(255,255,255,0.7)',
            color: isMine ? 'primary.contrastText' : 'text.primary',
            whiteSpace: 'pre-wrap',
          }}
        >
          <Typography variant="body2">{content}</Typography>
        </Box>
        <Typography variant="caption" color="text.secondary">
          {senderName || 'Unknown'}
        </Typography>
      </Stack>
    </Stack>
  );
}

/**
 * @param {{ messages: array }} thread - ChatThreadResource shape
 * @param {'agency'|'client'} mySide - which side the current user is on, for bubble alignment
 * @param {function} onSend - (message: string) => void
 * @param {boolean} isLoading
 * @param {boolean} isSending
 * @param {string} [emptyStateText]
 */
export default function ChatThread({ thread, mySide, onSend, isLoading, isSending, emptyStateText }) {
  const [draft, setDraft] = useState('');
  const scrollRef = useRef(null);
  const messages = thread?.messages ?? [];

  useEffect(() => {
    scrollRef.current?.scrollTo({ top: scrollRef.current.scrollHeight, behavior: 'smooth' });
  }, [messages.length]);

  const handleSend = (e) => {
    e.preventDefault();
    const message = draft.trim();
    if (!message || isSending) return;
    onSend(message);
    setDraft('');
  };

  return (
    <GlassCard sx={{ display: 'flex', flexDirection: 'column', minHeight: 480, p: 0, overflow: 'hidden' }}>
      <Box ref={scrollRef} sx={{ flex: 1, overflowY: 'auto', p: 3 }}>
        {isLoading && (
          <Stack alignItems="center" py={6}>
            <CircularProgress size={24} />
          </Stack>
        )}

        {!isLoading && messages.length === 0 && (
          <Stack spacing={2} alignItems="center" justifyContent="center" sx={{ height: '100%', py: 6 }}>
            <ForumRoundedIcon sx={{ fontSize: 32, color: 'primary.main' }} />
            <Typography color="text.secondary" align="center">
              {emptyStateText || 'No messages yet — say hello.'}
            </Typography>
          </Stack>
        )}

        <Stack spacing={2}>
          {messages.map((m) => (
            <MessageBubble key={m.id} isMine={m.sender_side === mySide} senderName={m.sender_name} content={m.content} />
          ))}
        </Stack>
      </Box>

      <Stack direction="row" spacing={1} sx={{ p: 2, borderTop: (t) => `1px solid ${t.palette.divider}` }} component="form" onSubmit={handleSend}>
        <TextField
          fullWidth
          size="small"
          placeholder="Type a message…"
          value={draft}
          onChange={(e) => setDraft(e.target.value)}
          disabled={isSending}
        />
        <IconButton type="submit" color="primary" disabled={!draft.trim() || isSending}>
          <SendRoundedIcon />
        </IconButton>
      </Stack>
    </GlassCard>
  );
}
