import { useEffect, useRef, useState } from 'react';
import Box from '@mui/material/Box';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import TrendingUpRoundedIcon from '@mui/icons-material/TrendingUpRounded';
import TrendingDownRoundedIcon from '@mui/icons-material/TrendingDownRounded';
import { useTheme } from '@mui/material/styles';
import GlassCard from './GlassCard';
import { semanticColors, typography } from '../../theme/tokens';

/** Counts up from 0 to `value` on mount/change — respects reduced motion. */
function useCountUp(value, durationMs = 700) {
  const [display, setDisplay] = useState(0);
  const frameRef = useRef();

  useEffect(() => {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) {
      setDisplay(value);
      return;
    }

    const start = performance.now();
    const from = 0;

    function tick(now) {
      const progress = Math.min((now - start) / durationMs, 1);
      const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
      setDisplay(from + (value - from) * eased);
      if (progress < 1) frameRef.current = requestAnimationFrame(tick);
    }

    frameRef.current = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(frameRef.current);
  }, [value, durationMs]);

  return display;
}

/** Minimal inline sparkline — swapped for ApexCharts once real time-series data is wired up. */
function SignalLine({ points, color }) {
  if (!points || points.length < 2) return null;

  const width = 120;
  const height = 32;
  const max = Math.max(...points);
  const min = Math.min(...points);
  const range = max - min || 1;

  const path = points
    .map((p, i) => {
      const x = (i / (points.length - 1)) * width;
      const y = height - ((p - min) / range) * height;
      return `${i === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`;
    })
    .join(' ');

  return (
    <svg width={width} height={height} viewBox={`0 0 ${width} ${height}`} aria-hidden="true">
      <path d={path} fill="none" stroke={color} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

/**
 * @param {string} label - e.g. "Organic Traffic"
 * @param {number} value - current metric value
 * @param {string} [format] - 'number' | 'currency' | 'percent'
 * @param {number} [deltaPercent] - period-over-period change, e.g. 12.4 or -3.1
 * @param {number[]} [trend] - recent values for the signal line
 */
export default function KpiCard({ label, value, format = 'number', deltaPercent, trend }) {
  const theme = useTheme();
  const animatedValue = useCountUp(value);

  const formatted = (() => {
    if (format === 'currency') {
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(animatedValue);
    }
    if (format === 'percent') {
      return `${animatedValue.toFixed(1)}%`;
    }
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(animatedValue);
  })();

  const isPositive = (deltaPercent ?? 0) >= 0;
  const deltaColor = isPositive ? semanticColors.positive : semanticColors.critical;
  const lineColor = theme.palette.mode === 'dark' ? theme.palette.primary.light : theme.palette.primary.main;

  return (
    <GlassCard interactive tabIndex={0} role="group" aria-label={label}>
      <Stack spacing={1.5}>
        <Typography
          variant="overline"
          sx={{ color: 'text.secondary', fontFamily: typography.mono, fontSize: 11 }}
        >
          {label}
        </Typography>

        <Stack direction="row" alignItems="flex-end" justifyContent="space-between">
          <Typography variant="h3" component="p" sx={{ lineHeight: 1 }}>
            {formatted}
          </Typography>
          {trend && <SignalLine points={trend} color={lineColor} />}
        </Stack>

        {typeof deltaPercent === 'number' && (
          <Stack direction="row" alignItems="center" spacing={0.5}>
            <Box
              component={isPositive ? TrendingUpRoundedIcon : TrendingDownRoundedIcon}
              sx={{ fontSize: 18, color: deltaColor }}
            />
            <Typography variant="body2" sx={{ color: deltaColor, fontWeight: 600 }}>
              {isPositive ? '+' : ''}
              {deltaPercent.toFixed(1)}%
            </Typography>
            <Typography variant="body2" sx={{ color: 'text.secondary' }}>
              vs last period
            </Typography>
          </Stack>
        )}
      </Stack>
    </GlassCard>
  );
}
