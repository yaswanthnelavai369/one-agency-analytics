import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Box from '@mui/material/Box';

/**
 * @param {{ name?: string, logoUrl?: string }} agency - white-label overrides;
 *   falls back to the platform's own name/mark when an agency hasn't set one.
 */
export default function LogoMark({ agency }) {
  const name = agency?.name || 'Search29';

  return (
    <Stack direction="row" spacing={1.25} alignItems="center">
      {agency?.logoUrl ? (
        <Box component="img" src={agency.logoUrl} alt={name} sx={{ height: 28, width: 'auto' }} />
      ) : (
        <Box
          sx={{
            width: 28,
            height: 28,
            borderRadius: '8px',
            background: 'linear-gradient(135deg, #4C6FFF 0%, #17B8A6 100%)',
          }}
        />
      )}
      <Typography variant="h6" component="span" sx={{ letterSpacing: '-0.01em' }}>
        {name}
      </Typography>
    </Stack>
  );
}
