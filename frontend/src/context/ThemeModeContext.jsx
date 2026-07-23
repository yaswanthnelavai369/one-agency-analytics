import { createContext, useContext, useEffect, useMemo, useState, useCallback } from 'react';

const ThemeModeContext = createContext(null);

const STORAGE_KEY = 'search29:theme-mode'; // stores 'light' | 'dark' | 'auto'

function getSystemPrefersDark() {
  return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
}

export function ThemeModeProvider({ children }) {
  const [mode, setMode] = useState(() => localStorage.getItem(STORAGE_KEY) || 'auto');
  const [systemPrefersDark, setSystemPrefersDark] = useState(getSystemPrefersDark);

  useEffect(() => {
    const mql = window.matchMedia('(prefers-color-scheme: dark)');
    const handler = (e) => setSystemPrefersDark(e.matches);
    mql.addEventListener('change', handler);
    return () => mql.removeEventListener('change', handler);
  }, []);

  const setThemeMode = useCallback((next) => {
    setMode(next);
    localStorage.setItem(STORAGE_KEY, next);
  }, []);

  const resolvedMode = mode === 'auto' ? (systemPrefersDark ? 'dark' : 'light') : mode;

  const value = useMemo(
    () => ({ mode, resolvedMode, setThemeMode }),
    [mode, resolvedMode, setThemeMode]
  );

  return <ThemeModeContext.Provider value={value}>{children}</ThemeModeContext.Provider>;
}

export function useThemeMode() {
  const ctx = useContext(ThemeModeContext);
  if (!ctx) throw new Error('useThemeMode must be used within ThemeModeProvider');
  return ctx;
}
