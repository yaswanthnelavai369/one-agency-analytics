import { useState } from 'react';
import Dialog from '@mui/material/Dialog';
import DialogTitle from '@mui/material/DialogTitle';
import DialogContent from '@mui/material/DialogContent';
import DialogActions from '@mui/material/DialogActions';
import Stack from '@mui/material/Stack';
import Button from '@mui/material/Button';
import TextField from '@mui/material/TextField';
import Alert from '@mui/material/Alert';
import FormControl from '@mui/material/FormControl';
import InputLabel from '@mui/material/InputLabel';
import Select from '@mui/material/Select';
import MenuItem from '@mui/material/MenuItem';
import FormHelperText from '@mui/material/FormHelperText';

/**
 * @param {array} catalogue - GoalCatalogue::all() shape
 * @param {function} onCreate - (payload) => Promise
 */
export default function CreateGoalDialog({ open, onClose, catalogue, onCreate, isSubmitting, error }) {
  const [templateKey, setTemplateKey] = useState('custom');
  const [name, setName] = useState('');
  const [target, setTarget] = useState('');
  const [deadline, setDeadline] = useState('');

  const template = catalogue.find((t) => t.key === templateKey);

  const handleTemplateChange = (key) => {
    setTemplateKey(key);
    const t = catalogue.find((c) => c.key === key);
    if (t) {
      setName(t.label);
      setTarget(t.suggested_target ?? '');
    }
  };

  const handleSubmit = () => {
    onCreate({
      name,
      metric: template?.metric ?? null,
      tracking_mode: template?.tracking_mode ?? 'manual',
      target_value: Number(target),
      format: template?.format ?? 'number',
      deadline: deadline || null,
    }).then(() => {
      setTemplateKey('custom');
      setName('');
      setTarget('');
      setDeadline('');
    });
  };

  return (
    <Dialog open={open} onClose={onClose} fullWidth maxWidth="xs">
      <DialogTitle>New goal</DialogTitle>
      <DialogContent>
        <Stack spacing={2} sx={{ mt: 1 }}>
          {error && <Alert severity="error">{error}</Alert>}

          <FormControl size="small" fullWidth>
            <InputLabel id="goal-template-label">Template</InputLabel>
            <Select
              labelId="goal-template-label"
              label="Template"
              value={templateKey}
              onChange={(e) => handleTemplateChange(e.target.value)}
            >
              {catalogue.map((t) => (
                <MenuItem key={t.key} value={t.key}>
                  {t.label}
                </MenuItem>
              ))}
            </Select>
            {template?.note && <FormHelperText>{template.note}</FormHelperText>}
          </FormControl>

          <TextField label="Goal name" value={name} onChange={(e) => setName(e.target.value)} required fullWidth />
          <TextField
            label="Target"
            type="number"
            value={target}
            onChange={(e) => setTarget(e.target.value)}
            required
            fullWidth
          />
          <TextField
            label="Deadline (optional)"
            type="date"
            value={deadline}
            onChange={(e) => setDeadline(e.target.value)}
            fullWidth
            InputLabelProps={{ shrink: true }}
          />
        </Stack>
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose}>Cancel</Button>
        <Button variant="contained" disabled={!name || !target || isSubmitting} onClick={handleSubmit}>
          {isSubmitting ? 'Creating…' : 'Create goal'}
        </Button>
      </DialogActions>
    </Dialog>
  );
}
