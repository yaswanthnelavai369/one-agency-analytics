import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Box from '@mui/material/Box';
import LinearProgress from '@mui/material/LinearProgress';
import KpiCard from './KpiCard';
import GlassCard from './GlassCard';
import { semanticColors } from '../../theme/tokens';

// Deterministic placeholder generator so a given widget always looks the same
// across renders (rather than random flicker) until real data is wired up.
function placeholderValue(seed) {
  let hash = 0;
  for (let i = 0; i < seed.length; i++) hash = (hash * 31 + seed.charCodeAt(i)) % 100000;
  return hash;
}

function ListWidget({ label }) {
  const rows = ['Item A', 'Item B', 'Item C', 'Item D'];
  return (
    <GlassCard sx={{ height: '100%' }}>
      <Stack spacing={1.5} sx={{ height: '100%' }}>
        <Typography variant="overline" sx={{ color: 'text.secondary', fontSize: 11 }}>
          {label}
        </Typography>
        <Stack spacing={1} sx={{ flex: 1 }}>
          {rows.map((row, i) => (
            <Stack key={row} direction="row" justifyContent="space-between">
              <Typography variant="body2">{row}</Typography>
              <Typography variant="body2" color="text.secondary">
                {(placeholderValue(label + i) % 500) + 50}
              </Typography>
            </Stack>
          ))}
        </Stack>
      </Stack>
    </GlassCard>
  );
}

function HealthScoreWidget({ label }) {
  const score = 60 + (placeholderValue(label) % 35);
  const color = score >= 80 ? semanticColors.positive : score >= 50 ? semanticColors.warning : semanticColors.critical;

  return (
    <GlassCard sx={{ height: '100%' }}>
      <Stack spacing={2} sx={{ height: '100%' }}>
        <Typography variant="overline" sx={{ color: 'text.secondary', fontSize: 11 }}>
          {label}
        </Typography>
        <Box sx={{ display: 'flex', alignItems: 'baseline', gap: 1 }}>
          <Typography variant="h2" sx={{ color }}>
            {score}
          </Typography>
          <Typography variant="body2" color="text.secondary">
            / 100
          </Typography>
        </Box>
        <LinearProgress
          variant="determinate"
          value={score}
          sx={{
            height: 8,
            borderRadius: 4,
            backgroundColor: 'action.hover',
            '& .MuiLinearProgress-bar': { backgroundColor: color, borderRadius: 4 },
          }}
        />
      </Stack>
    </GlassCard>
  );
}

/**
 * @param {{ widget_type: string, config?: object }} definition - the widget's
 *   catalogue entry as returned by /agency/dashboards/widget-catalogue
 * @param {{ label: string, kind: string, format?: string }} meta - resolved from the catalogue by type
 */
export default function WidgetRenderer({ widgetType, meta }) {
  const label = meta?.label || widgetType;

  if (meta?.kind === 'health_score') {
    return <HealthScoreWidget label={label} />;
  }

  if (meta?.kind === 'list') {
    return <ListWidget label={label} />;
  }

  // Default: 'kpi'
  const base = placeholderValue(widgetType);
  const value = meta?.format === 'percent' ? (base % 100) / 10 : meta?.format === 'currency' ? base * 10 : base;
  const trend = Array.from({ length: 7 }, (_, i) => (base % 50) + i * 3 + ((base + i) % 7));

  return (
    <KpiCard
      label={label}
      value={value}
      format={meta?.format || 'number'}
      deltaPercent={((base % 40) - 15) / 2}
      trend={trend}
    />
  );
}
