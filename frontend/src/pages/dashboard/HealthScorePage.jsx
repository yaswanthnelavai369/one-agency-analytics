import { useEffect, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { LineChart, Line, XAxis, YAxis, Tooltip as ChartTooltip, ResponsiveContainer, CartesianGrid } from 'recharts';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Grid from '@mui/material/Grid';
import Box from '@mui/material/Box';
import Chip from '@mui/material/Chip';
import Button from '@mui/material/Button';
import LinearProgress from '@mui/material/LinearProgress';
import CircularProgress from '@mui/material/CircularProgress';
import FormControl from '@mui/material/FormControl';
import InputLabel from '@mui/material/InputLabel';
import Select from '@mui/material/Select';
import MenuItem from '@mui/material/MenuItem';
import List from '@mui/material/List';
import ListItem from '@mui/material/ListItem';
import ListItemIcon from '@mui/material/ListItemIcon';
import ListItemText from '@mui/material/ListItemText';
import LightbulbRoundedIcon from '@mui/icons-material/LightbulbRounded';
import RefreshRoundedIcon from '@mui/icons-material/RefreshRounded';
import { useTheme } from '@mui/material/styles';
import GlassCard from '../../components/ui/GlassCard';
import { healthScoreApi } from '../../api/healthScore';
import { clientsApi } from '../../api/clients';
import { semanticColors } from '../../theme/tokens';

const CATEGORY_LABELS = {
  growth: 'Growth',
  seo: 'SEO',
  ads: 'Ads',
  social: 'Social',
  website: 'Website',
  lead: 'Leads',
  revenue: 'Revenue',
};

const BAND_LABEL = {
  excellent: 'Excellent',
  good: 'Good',
  needs_attention: 'Needs attention',
  at_risk: 'At risk',
};

function scoreColor(score) {
  if (score === null || score === undefined) return semanticColors.info;
  if (score >= 80) return semanticColors.positive;
  if (score >= 50) return semanticColors.warning;
  return semanticColors.critical;
}

function ScoreGauge({ score, size = 160 }) {
  const color = scoreColor(score);
  const radius = size / 2 - 10;
  const circumference = 2 * Math.PI * radius;
  const offset = circumference - (Math.max(0, Math.min(100, score)) / 100) * circumference;

  return (
    <Box sx={{ position: 'relative', width: size, height: size }}>
      <svg width={size} height={size} style={{ transform: 'rotate(-90deg)' }}>
        <circle cx={size / 2} cy={size / 2} r={radius} fill="none" stroke="rgba(128,128,128,0.15)" strokeWidth={12} />
        <circle
          cx={size / 2}
          cy={size / 2}
          r={radius}
          fill="none"
          stroke={color}
          strokeWidth={12}
          strokeLinecap="round"
          strokeDasharray={circumference}
          strokeDashoffset={offset}
          style={{ transition: 'stroke-dashoffset 700ms ease' }}
        />
      </svg>
      <Box
        sx={{
          position: 'absolute',
          inset: 0,
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
        }}
      >
        <Typography variant="h2" sx={{ color, lineHeight: 1 }}>
          {score}
        </Typography>
        <Typography variant="body2" color="text.secondary">
          / 100
        </Typography>
      </Box>
    </Box>
  );
}

function CategoryBar({ label, score }) {
  const color = scoreColor(score);
  return (
    <Stack spacing={0.75}>
      <Stack direction="row" justifyContent="space-between">
        <Typography variant="body2">{label}</Typography>
        <Typography variant="body2" sx={{ color, fontWeight: 600 }}>
          {score ?? '—'}
        </Typography>
      </Stack>
      <LinearProgress
        variant="determinate"
        value={score ?? 0}
        sx={{
          height: 6,
          borderRadius: 3,
          backgroundColor: 'action.hover',
          '& .MuiLinearProgress-bar': { backgroundColor: color, borderRadius: 3 },
        }}
      />
    </Stack>
  );
}

export default function HealthScorePage() {
  const theme = useTheme();
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

  const score = data?.data;
  const trend = data?.trend ?? [];
  const previousOverall = data?.previous_overall_score;
  const delta = score && previousOverall != null ? score.overall_score - previousOverall : null;

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

      {!isLoading && score && (
        <>
          <Grid container spacing={3}>
            <Grid item xs={12} md={4}>
              <GlassCard sx={{ height: '100%', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', gap: 2 }}>
                <ScoreGauge score={score.overall_score} />
                <Stack alignItems="center" spacing={0.5}>
                  <Chip label={BAND_LABEL[score.band]} size="small" sx={{ backgroundColor: scoreColor(score.overall_score), color: '#fff' }} />
                  {delta !== null && (
                    <Typography variant="body2" color="text.secondary">
                      {delta >= 0 ? '+' : ''}
                      {delta} vs. 30 days ago
                    </Typography>
                  )}
                </Stack>
              </GlassCard>
            </Grid>

            <Grid item xs={12} md={8}>
              <GlassCard sx={{ height: '100%' }}>
                <Stack spacing={2}>
                  <Typography variant="overline" sx={{ color: 'text.secondary', fontSize: 11 }}>
                    Category breakdown
                  </Typography>
                  <Grid container spacing={2}>
                    {Object.entries(CATEGORY_LABELS).map(([key, label]) => (
                      <Grid item xs={6} sm={4} key={key}>
                        <CategoryBar label={label} score={score.category_scores[key]} />
                      </Grid>
                    ))}
                  </Grid>
                </Stack>
              </GlassCard>
            </Grid>
          </Grid>

          <GlassCard>
            <Stack spacing={2}>
              <Typography variant="overline" sx={{ color: 'text.secondary', fontSize: 11 }}>
                90-day trend
              </Typography>
              <Box sx={{ height: 240 }}>
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={trend}>
                    <CartesianGrid strokeDasharray="3 3" stroke={theme.palette.divider} />
                    <XAxis dataKey="date" tick={{ fontSize: 11 }} stroke={theme.palette.text.secondary} minTickGap={30} />
                    <YAxis domain={[0, 100]} tick={{ fontSize: 11 }} stroke={theme.palette.text.secondary} width={30} />
                    <ChartTooltip
                      contentStyle={{
                        background: theme.palette.background.paper,
                        border: `1px solid ${theme.palette.divider}`,
                        borderRadius: 8,
                      }}
                    />
                    <Line type="monotone" dataKey="overall_score" stroke={theme.palette.primary.main} strokeWidth={2} dot={false} />
                  </LineChart>
                </ResponsiveContainer>
              </Box>
            </Stack>
          </GlassCard>

          <GlassCard>
            <Stack spacing={1.5}>
              <Typography variant="overline" sx={{ color: 'text.secondary', fontSize: 11 }}>
                Improvement suggestions
              </Typography>
              {score.suggestions.length === 0 ? (
                <Typography variant="body2" color="text.secondary">
                  No specific issues detected right now — performance looks healthy across the board.
                </Typography>
              ) : (
                <List dense>
                  {score.suggestions.map((s, i) => (
                    <ListItem key={i} disableGutters>
                      <ListItemIcon sx={{ minWidth: 32 }}>
                        <LightbulbRoundedIcon fontSize="small" sx={{ color: semanticColors.warning }} />
                      </ListItemIcon>
                      <ListItemText primary={s} primaryTypographyProps={{ variant: 'body2' }} />
                    </ListItem>
                  ))}
                </List>
              )}
            </Stack>
          </GlassCard>
        </>
      )}
    </Stack>
  );
}
