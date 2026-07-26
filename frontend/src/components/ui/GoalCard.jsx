import { useState } from 'react';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import LinearProgress from '@mui/material/LinearProgress';
import Chip from '@mui/material/Chip';
import IconButton from '@mui/material/IconButton';
import Menu from '@mui/material/Menu';
import MenuItem from '@mui/material/MenuItem';
import TextField from '@mui/material/TextField';
import Button from '@mui/material/Button';
import Collapse from '@mui/material/Collapse';
import MoreVertRoundedIcon from '@mui/icons-material/MoreVertRounded';
import GlassCard from './GlassCard';
import { semanticColors } from '../../theme/tokens';

const PACE_CONFIG = {
  achieved: { label: 'Achieved', color: semanticColors.positive },
  on_track: { label: 'On track', color: semanticColors.positive },
  behind: { label: 'Behind pace', color: semanticColors.critical },
  missed: { label: 'Missed', color: semanticColors.critical },
  no_deadline: { label: 'No deadline', color: semanticColors.info },
};

function formatValue(value, format) {
  if (format === 'currency') return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(value);
  if (format === 'percent') return `${value}%`;
  return new Intl.NumberFormat('en-US').format(value);
}

/**
 * @param {object} goal - GoalResource shape
 * @param {function} [onAddProgress] - (value, mode) => void — only manual goals show the input when this is passed
 * @param {function} [onRecompute] - () => void — only auto-tracked goals show this when passed
 * @param {function} [onArchive] - () => void
 * @param {function} [onDelete] - () => void
 */
export default function GoalCard({ goal, onAddProgress, onRecompute, onArchive, onDelete }) {
  const [menuAnchor, setMenuAnchor] = useState(null);
  const [progressInput, setProgressInput] = useState(false);
  const [value, setValue] = useState('');

  const pace = PACE_CONFIG[goal.forecast.pace_status] || PACE_CONFIG.no_deadline;
  const pct = Math.min(100, goal.forecast.achievement_rate);
  const showActions = onArchive || onDelete;

  return (
    <GlassCard>
      <Stack spacing={1.5}>
        <Stack direction="row" justifyContent="space-between" alignItems="flex-start">
          <Stack spacing={0.25}>
            <Typography variant="subtitle1">{goal.name}</Typography>
            <Typography variant="caption" color="text.secondary">
              {goal.is_auto_tracked ? `Auto-tracked · ${goal.metric}` : 'Manual'}
              {goal.deadline && ` · Due ${goal.deadline}`}
            </Typography>
          </Stack>

          <Stack direction="row" spacing={1} alignItems="center">
            <Chip label={pace.label} size="small" sx={{ backgroundColor: pace.color, color: '#fff' }} />
            {showActions && (
              <>
                <IconButton size="small" onClick={(e) => setMenuAnchor(e.currentTarget)}>
                  <MoreVertRoundedIcon fontSize="small" />
                </IconButton>
                <Menu anchorEl={menuAnchor} open={Boolean(menuAnchor)} onClose={() => setMenuAnchor(null)}>
                  {onArchive && (
                    <MenuItem
                      onClick={() => {
                        setMenuAnchor(null);
                        onArchive();
                      }}
                    >
                      Archive
                    </MenuItem>
                  )}
                  {onDelete && (
                    <MenuItem
                      onClick={() => {
                        setMenuAnchor(null);
                        onDelete();
                      }}
                    >
                      Delete
                    </MenuItem>
                  )}
                </Menu>
              </>
            )}
          </Stack>
        </Stack>

        <Stack spacing={0.5}>
          <Stack direction="row" justifyContent="space-between">
            <Typography variant="body2">
              {formatValue(goal.current_value, goal.format)} of {formatValue(goal.target_value, goal.format)}
            </Typography>
            <Typography variant="body2" color="text.secondary">
              {goal.forecast.achievement_rate}%
            </Typography>
          </Stack>
          <LinearProgress
            variant="determinate"
            value={pct}
            sx={{
              height: 8,
              borderRadius: 4,
              backgroundColor: 'action.hover',
              '& .MuiLinearProgress-bar': { backgroundColor: pace.color, borderRadius: 4 },
            }}
          />
        </Stack>

        {goal.deadline && goal.forecast.pace_status !== 'achieved' && (
          <Typography variant="caption" color="text.secondary">
            {goal.forecast.days_remaining} day(s) left · expected {formatValue(goal.forecast.expected_progress, goal.format)} by now
            {goal.forecast.projected_completion_date && ` · projected to hit target ${goal.forecast.projected_completion_date}`}
          </Typography>
        )}

        {onRecompute && goal.is_auto_tracked && (
          <Button size="small" onClick={onRecompute} sx={{ alignSelf: 'flex-start' }}>
            Refresh from data
          </Button>
        )}

        {onAddProgress && !goal.is_auto_tracked && (
          <Stack spacing={1}>
            <Button size="small" onClick={() => setProgressInput((v) => !v)} sx={{ alignSelf: 'flex-start' }}>
              {progressInput ? 'Cancel' : 'Update progress'}
            </Button>
            <Collapse in={progressInput}>
              <Stack direction="row" spacing={1}>
                <TextField
                  size="small"
                  type="number"
                  label="New value"
                  value={value}
                  onChange={(e) => setValue(e.target.value)}
                  className="widget-no-drag"
                />
                <Button
                  size="small"
                  variant="contained"
                  disabled={value === ''}
                  onClick={() => {
                    onAddProgress(Number(value), 'set');
                    setValue('');
                    setProgressInput(false);
                  }}
                >
                  Save
                </Button>
              </Stack>
            </Collapse>
          </Stack>
        )}
      </Stack>
    </GlassCard>
  );
}
