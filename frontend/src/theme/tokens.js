/**
 * Design tokens for Search29 Analytics AI.
 *
 * Palette concept: "Signal & Growth" — a marketing analytics platform pulls
 * many channels (search, social, ads, email) into one converged read. The
 * primary (signal blue) represents that converged data stream; the secondary
 * (growth teal) represents positive movement. Alerts get their own semantic
 * colors so anomaly/health-score UI never has to borrow the brand color.
 *
 * Every agency can override primary/secondary via white-label branding
 * (see AgencyContext) — these are the platform defaults.
 */

export const brandDefaults = {
  primary: '#4C6FFF', // signal blue
  secondary: '#17B8A6', // growth teal
};

export const semanticColors = {
  positive: '#17B8A6',
  warning: '#FFB020',
  critical: '#FF6B6B',
  info: '#4C6FFF',
};

export const neutrals = {
  light: {
    bg: '#F5F6FA',
    bgElevated: '#FFFFFF',
    text: '#161A2B',
    textMuted: '#5B6178',
    border: 'rgba(22, 26, 43, 0.08)',
    glassSurface: 'rgba(255, 255, 255, 0.55)',
    glassBorder: 'rgba(255, 255, 255, 0.7)',
  },
  dark: {
    bg: '#0E1224',
    bgElevated: '#161B33',
    text: '#EEF0FA',
    textMuted: '#9199B8',
    border: 'rgba(238, 240, 250, 0.08)',
    glassSurface: 'rgba(255, 255, 255, 0.06)',
    glassBorder: 'rgba(255, 255, 255, 0.12)',
  },
};

export const typography = {
  display: '"Space Grotesk", "Inter", sans-serif', // KPI numbers, headings — the platform's characterful face
  body: '"Inter", -apple-system, sans-serif', // dense dashboard UI, forms, tables
  mono: '"IBM Plex Mono", monospace', // timestamps, IDs, precise data values
};

/** Backdrop-filter blur radius used across all glass panels — keep consistent. */
export const glass = {
  blur: '20px',
  radius: 20,
  radiusSm: 14,
};

export const gradients = {
  aurora: (mode) =>
    mode === 'dark'
      ? 'radial-gradient(60% 60% at 15% 10%, rgba(76,111,255,0.28) 0%, rgba(76,111,255,0) 60%), radial-gradient(50% 50% at 90% 20%, rgba(23,184,166,0.22) 0%, rgba(23,184,166,0) 60%), radial-gradient(70% 70% at 50% 100%, rgba(76,111,255,0.12) 0%, rgba(76,111,255,0) 60%)'
      : 'radial-gradient(60% 60% at 15% 10%, rgba(76,111,255,0.16) 0%, rgba(76,111,255,0) 60%), radial-gradient(50% 50% at 90% 20%, rgba(23,184,166,0.14) 0%, rgba(23,184,166,0) 60%), radial-gradient(70% 70% at 50% 100%, rgba(76,111,255,0.08) 0%, rgba(76,111,255,0) 60%)',
};
