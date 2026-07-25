import { Navigate } from 'react-router-dom';
import Box from '@mui/material/Box';
import CircularProgress from '@mui/material/CircularProgress';
import { useAuth } from '../context/AuthContext';

export default function PortalProtectedRoute({ children }) {
  const { status, user } = useAuth();

  if (status === 'loading') {
    return (
      <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minHeight: '100vh' }}>
        <CircularProgress size={28} />
      </Box>
    );
  }

  if (status === 'guest') {
    return <Navigate to="/login" replace />;
  }

  if (user?.user_type !== 'client') {
    return <Navigate to="/dashboard" replace />;
  }

  return children;
}
