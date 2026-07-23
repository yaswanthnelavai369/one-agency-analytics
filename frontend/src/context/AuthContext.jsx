import { createContext, useContext, useEffect, useMemo, useState, useCallback } from 'react';
import { authApi } from '../api/auth';
import { storeToken, clearToken, getToken } from '../api/client';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [status, setStatus] = useState('loading'); // 'loading' | 'authenticated' | 'guest'

  const loadSession = useCallback(async () => {
    if (!getToken()) {
      setStatus('guest');
      return;
    }
    try {
      const me = await authApi.me();
      setUser(me);
      setStatus('authenticated');
    } catch {
      clearToken();
      setUser(null);
      setStatus('guest');
    }
  }, []);

  useEffect(() => {
    loadSession();
    const onUnauthorized = () => {
      setUser(null);
      setStatus('guest');
    };
    window.addEventListener('search29:unauthorized', onUnauthorized);
    return () => window.removeEventListener('search29:unauthorized', onUnauthorized);
  }, [loadSession]);

  const login = useCallback(async (credentials) => {
    const result = await authApi.login(credentials);

    if (result.two_factor_required) {
      // Caller (LoginPage) routes to the 2FA challenge screen with this token.
      return { twoFactorRequired: true, challengeToken: result.challenge_token };
    }

    storeToken(result.token);
    setUser(result.user);
    setStatus('authenticated');
    return { twoFactorRequired: false };
  }, []);

  const verifyTwoFactor = useCallback(async ({ challengeToken, code }) => {
    const result = await authApi.verifyTwoFactor({ challenge_token: challengeToken, code });
    storeToken(result.token);
    setUser(result.user);
    setStatus('authenticated');
  }, []);

  const register = useCallback(async (data) => {
    const result = await authApi.register(data);
    storeToken(result.token);
    setUser(result.user);
    setStatus('authenticated');
    return result;
  }, []);

  const logout = useCallback(async () => {
    try {
      await authApi.logout();
    } finally {
      clearToken();
      setUser(null);
      setStatus('guest');
    }
  }, []);

  const value = useMemo(
    () => ({ user, status, login, verifyTwoFactor, register, logout, refresh: loadSession }),
    [user, status, login, verifyTwoFactor, register, logout, loadSession]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
