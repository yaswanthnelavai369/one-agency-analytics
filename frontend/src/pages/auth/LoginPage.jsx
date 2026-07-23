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

export default function LoginPage() {
  const { login } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({ email: '', password: '' });
  const [error, setError] = useState(null);
  const [submitting, setSubmitting] = useState(false);

  const handleChange = (field) => (e) => setForm((f) => ({ ...f, [field]: e.target.value }));

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      const result = await login(form);
      if (result.twoFactorRequired) {
        navigate('/two-factor', { state: { challengeToken: result.challengeToken } });
      } else {
        navigate('/dashboard');
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Unable to sign in. Check your credentials and try again.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <AuthLayout>
      <Stack spacing={3} component="form" onSubmit={handleSubmit}>
        <Stack spacing={0.5}>
          <Typography variant="h4">Welcome back</Typography>
          <Typography variant="body2" color="text.secondary">
            Sign in to your agency dashboard
          </Typography>
        </Stack>

        {error && <Alert severity="error">{error}</Alert>}

        <TextField
          label="Email"
          type="email"
          value={form.email}
          onChange={handleChange('email')}
          required
          fullWidth
          autoComplete="email"
        />
        <TextField
          label="Password"
          type="password"
          value={form.password}
          onChange={handleChange('password')}
          required
          fullWidth
          autoComplete="current-password"
        />

        <Button type="submit" variant="contained" size="large" disabled={submitting} fullWidth>
          {submitting ? 'Signing in…' : 'Sign in'}
        </Button>

        <Typography variant="body2" align="center" color="text.secondary">
          Don&apos;t have an account?{' '}
          <Link component={RouterLink} to="/register">
            Create one
          </Link>
        </Typography>
      </Stack>
    </AuthLayout>
  );
}
