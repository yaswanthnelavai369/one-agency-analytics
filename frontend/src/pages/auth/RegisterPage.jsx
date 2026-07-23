import { useState } from 'react';
import { useNavigate, Link as RouterLink } from 'react-router-dom';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import TextField from '@mui/material/TextField';
import Button from '@mui/material/Button';
import Alert from '@mui/material/Alert';
import Link from '@mui/material/Link';
import AuthLayout from '../../components/layout/AuthLayout';
import { useAuth } from '../../context/AuthContext';

export default function RegisterPage() {
  const { register } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    agency_name: '',
  });
  const [error, setError] = useState(null);
  const [submitting, setSubmitting] = useState(false);

  const handleChange = (field) => (e) => setForm((f) => ({ ...f, [field]: e.target.value }));

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await register(form);
      navigate('/dashboard');
    } catch (err) {
      const messages = err.response?.data?.errors;
      const firstMessage = messages ? Object.values(messages)[0]?.[0] : null;
      setError(firstMessage || err.response?.data?.message || 'Unable to create your account.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <AuthLayout>
      <Stack spacing={3} component="form" onSubmit={handleSubmit}>
        <Stack spacing={0.5}>
          <Typography variant="h4">Start your free trial</Typography>
          <Typography variant="body2" color="text.secondary">
            14 days, no credit card required
          </Typography>
        </Stack>

        {error && <Alert severity="error">{error}</Alert>}

        <TextField label="Your name" value={form.name} onChange={handleChange('name')} required fullWidth />
        <TextField label="Agency name" value={form.agency_name} onChange={handleChange('agency_name')} required fullWidth />
        <TextField label="Email" type="email" value={form.email} onChange={handleChange('email')} required fullWidth autoComplete="email" />
        <TextField
          label="Password"
          type="password"
          value={form.password}
          onChange={handleChange('password')}
          required
          fullWidth
          autoComplete="new-password"
          helperText="At least 10 characters, mixed case, numbers, and symbols"
        />
        <TextField
          label="Confirm password"
          type="password"
          value={form.password_confirmation}
          onChange={handleChange('password_confirmation')}
          required
          fullWidth
          autoComplete="new-password"
        />

        <Button type="submit" variant="contained" size="large" disabled={submitting} fullWidth>
          {submitting ? 'Creating your workspace…' : 'Create account'}
        </Button>

        <Typography variant="body2" align="center" color="text.secondary">
          Already have an account?{' '}
          <Link component={RouterLink} to="/login">
            Sign in
          </Link>
        </Typography>
      </Stack>
    </AuthLayout>
  );
}
