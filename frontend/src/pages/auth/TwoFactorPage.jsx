import { useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import TextField from '@mui/material/TextField';
import Button from '@mui/material/Button';
import Alert from '@mui/material/Alert';
import AuthLayout from '../../components/layout/AuthLayout';
import { useAuth } from '../../context/AuthContext';

export default function TwoFactorPage() {
  const { verifyTwoFactor } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const challengeToken = location.state?.challengeToken;

  const [code, setCode] = useState('');
  const [error, setError] = useState(null);
  const [submitting, setSubmitting] = useState(false);

  if (!challengeToken) {
    navigate('/login', { replace: true });
    return null;
  }

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      const user = await verifyTwoFactor({ challengeToken, code });
      navigate(user?.user_type === 'client' ? '/portal' : '/dashboard');
    } catch (err) {
      setError(err.response?.data?.errors?.code?.[0] || 'Invalid code. Try again.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <AuthLayout>
      <Stack spacing={3} component="form" onSubmit={handleSubmit}>
        <Stack spacing={0.5}>
          <Typography variant="h4">Two-factor verification</Typography>
          <Typography variant="body2" color="text.secondary">
            Enter the 6-digit code from your authenticator app, or a recovery code
          </Typography>
        </Stack>

        {error && <Alert severity="error">{error}</Alert>}

        <TextField
          label="Authentication code"
          value={code}
          onChange={(e) => setCode(e.target.value)}
          required
          fullWidth
          autoFocus
          inputProps={{ inputMode: 'numeric', maxLength: 10 }}
        />

        <Button type="submit" variant="contained" size="large" disabled={submitting} fullWidth>
          {submitting ? 'Verifying…' : 'Verify and sign in'}
        </Button>
      </Stack>
    </AuthLayout>
  );
}
