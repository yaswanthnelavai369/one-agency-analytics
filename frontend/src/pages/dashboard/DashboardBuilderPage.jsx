import { useEffect, useMemo, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import GridLayout from 'react-grid-layout';
import 'react-grid-layout/css/styles.css';
import 'react-resizable/css/styles.css';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import Button from '@mui/material/Button';
import IconButton from '@mui/material/IconButton';
import Tooltip from '@mui/material/Tooltip';
import Dialog from '@mui/material/Dialog';
import DialogTitle from '@mui/material/DialogTitle';
import DialogContent from '@mui/material/DialogContent';
import Tabs from '@mui/material/Tabs';
import Tab from '@mui/material/Tab';
import List from '@mui/material/List';
import ListItemButton from '@mui/material/ListItemButton';
import ListItemText from '@mui/material/ListItemText';
import Chip from '@mui/material/Chip';
import CircularProgress from '@mui/material/CircularProgress';
import Box from '@mui/material/Box';
import AddRoundedIcon from '@mui/icons-material/AddRounded';
import SaveRoundedIcon from '@mui/icons-material/SaveRounded';
import RestartAltRoundedIcon from '@mui/icons-material/RestartAltRounded';
import CloseRoundedIcon from '@mui/icons-material/CloseRounded';
import { useTheme } from '@mui/material/styles';
import GlassCard from '../../components/ui/GlassCard';
import WidgetRenderer from '../../components/ui/WidgetRenderer';
import { dashboardsApi } from '../../api/dashboards';

const GRID_COLS = 12;
const ROW_HEIGHT = 64;

function AddWidgetDialog({ open, onClose, catalogue, onAdd, adding }) {
  const categories = Object.keys(catalogue || {});
  const [tab, setTab] = useState(0);

  return (
    <Dialog open={open} onClose={onClose} fullWidth maxWidth="sm">
      <DialogTitle sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        Add widget
        <IconButton onClick={onClose} size="small">
          <CloseRoundedIcon fontSize="small" />
        </IconButton>
      </DialogTitle>
      <DialogContent sx={{ p: 0 }}>
        <Tabs value={tab} onChange={(_, v) => setTab(v)} variant="scrollable" scrollButtons="auto" sx={{ px: 2 }}>
          {categories.map((cat) => (
            <Tab key={cat} label={cat} sx={{ textTransform: 'capitalize' }} />
          ))}
        </Tabs>
        <List sx={{ maxHeight: 360, overflowY: 'auto' }}>
          {(catalogue[categories[tab]] || []).map((widget) => (
            <ListItemButton key={widget.type} disabled={adding} onClick={() => onAdd(widget.type)}>
              <ListItemText primary={widget.label} secondary={widget.kind} />
            </ListItemButton>
          ))}
        </List>
      </DialogContent>
    </Dialog>
  );
}

/**
 * @param {number|null} clientId - null for an agency-level dashboard, or a client id
 *   for a per-client dashboard. Layouts are always scoped to the current user
 *   (their personal view) unless marked shared.
 */
export default function DashboardBuilderPage({ clientId = null }) {
  const theme = useTheme();
  const queryClient = useQueryClient();
  const [dialogOpen, setDialogOpen] = useState(false);
  const [gridWidth, setGridWidth] = useState(1200);
  const [pendingLayout, setPendingLayout] = useState(null); // uncommitted drag/resize state
  const [isDirty, setIsDirty] = useState(false);

  useEffect(() => {
    const el = document.getElementById('dashboard-grid-container');
    if (!el) return;
    const observer = new ResizeObserver(([entry]) => setGridWidth(entry.contentRect.width));
    observer.observe(el);
    return () => observer.disconnect();
  }, []);

  const { data: layoutsData, isLoading } = useQuery({
    queryKey: ['dashboards', clientId],
    queryFn: () => dashboardsApi.list(clientId),
  });

  const { data: catalogueData } = useQuery({
    queryKey: ['widget-catalogue'],
    queryFn: () => dashboardsApi.widgetCatalogue(),
  });
  const catalogue = catalogueData?.data ?? {};
  const catalogueFlat = useMemo(
    () => Object.values(catalogue).flat().reduce((acc, w) => ({ ...acc, [w.type]: w }), {}),
    [catalogue]
  );

  // For this shell: work with the user's first visible layout for this scope,
  // creating one with sensible defaults on first visit.
  const layout = layoutsData?.data?.[0];

  const createDefaultMutation = useMutation({
    mutationFn: () =>
      dashboardsApi.create({ name: 'My Dashboard', client_id: clientId, is_default: true, with_default_widgets: true }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['dashboards', clientId] }),
  });

  useEffect(() => {
    if (!isLoading && layoutsData && layoutsData.data.length === 0 && !createDefaultMutation.isPending) {
      createDefaultMutation.mutate();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isLoading, layoutsData]);

  const addWidgetMutation = useMutation({
    mutationFn: (widgetType) => dashboardsApi.addWidget(layout.id, widgetType),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['dashboards', clientId] });
      setDialogOpen(false);
    },
  });

  const removeWidgetMutation = useMutation({
    mutationFn: (widgetId) => dashboardsApi.removeWidget(layout.id, widgetId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['dashboards', clientId] }),
  });

  const savePositionsMutation = useMutation({
    mutationFn: (positions) => dashboardsApi.savePositions(layout.id, positions),
    onSuccess: () => {
      setIsDirty(false);
      queryClient.invalidateQueries({ queryKey: ['dashboards', clientId] });
    },
  });

  const resetMutation = useMutation({
    mutationFn: () => dashboardsApi.reset(layout.id),
    onSuccess: () => {
      setIsDirty(false);
      queryClient.invalidateQueries({ queryKey: ['dashboards', clientId] });
    },
  });

  if (isLoading || !layout) {
    return (
      <Stack alignItems="center" py={8}>
        <CircularProgress size={28} />
      </Stack>
    );
  }

  const gridLayout = (pendingLayout || layout.widgets).map((w) => ({
    i: String(w.id),
    x: w.x,
    y: w.y,
    w: w.w,
    h: w.h,
  }));

  const handleSave = () => {
    const source = pendingLayout || gridLayout;
    savePositionsMutation.mutate(source.map((w) => ({ id: Number(w.i), x: w.x, y: w.y, w: w.w, h: w.h })));
  };

  return (
    <Stack spacing={3}>
      <Stack direction={{ xs: 'column', sm: 'row' }} justifyContent="space-between" alignItems={{ sm: 'center' }} spacing={2}>
        <Stack spacing={0.5}>
          <Typography variant="h4">{layout.name}</Typography>
          <Typography variant="body2" color="text.secondary">
            Drag to rearrange, drag the corner to resize
          </Typography>
        </Stack>
        <Stack direction="row" spacing={1}>
          {isDirty && (
            <Chip label="Unsaved changes" size="small" color="warning" variant="outlined" sx={{ alignSelf: 'center' }} />
          )}
          <Tooltip title="Reset to default widgets">
            <IconButton onClick={() => resetMutation.mutate()} disabled={resetMutation.isPending}>
              <RestartAltRoundedIcon fontSize="small" />
            </IconButton>
          </Tooltip>
          <Button startIcon={<AddRoundedIcon />} onClick={() => setDialogOpen(true)}>
            Add widget
          </Button>
          <Button
            variant="contained"
            startIcon={<SaveRoundedIcon />}
            disabled={!isDirty || savePositionsMutation.isPending}
            onClick={handleSave}
          >
            Save layout
          </Button>
        </Stack>
      </Stack>

      <Box id="dashboard-grid-container">
        {layout.widgets.length === 0 ? (
          <GlassCard sx={{ textAlign: 'center', py: 8 }}>
            <Typography color="text.secondary">No widgets yet. Add one to get started.</Typography>
          </GlassCard>
        ) : (
          <GridLayout
            className="layout"
            layout={gridLayout}
            cols={GRID_COLS}
            rowHeight={ROW_HEIGHT}
            width={gridWidth}
            margin={[16, 16]}
            draggableCancel=".widget-no-drag"
            onLayoutChange={(next) => {
              setPendingLayout(next);
              setIsDirty(true);
            }}
          >
            {layout.widgets.map((widget) => (
              <div key={widget.id}>
                <Box sx={{ position: 'relative', height: '100%' }}>
                  <Tooltip title="Remove widget">
                    <IconButton
                      size="small"
                      className="widget-no-drag"
                      onClick={() => removeWidgetMutation.mutate(widget.id)}
                      sx={{
                        position: 'absolute',
                        top: 6,
                        right: 6,
                        zIndex: 2,
                        backgroundColor: theme.palette.background.paper,
                        '&:hover': { backgroundColor: theme.palette.error.main, color: '#fff' },
                      }}
                    >
                      <CloseRoundedIcon sx={{ fontSize: 14 }} />
                    </IconButton>
                  </Tooltip>
                  <WidgetRenderer widgetType={widget.widget_type} meta={catalogueFlat[widget.widget_type]} />
                </Box>
              </div>
            ))}
          </GridLayout>
        )}
      </Box>

      <AddWidgetDialog
        open={dialogOpen}
        onClose={() => setDialogOpen(false)}
        catalogue={catalogue}
        onAdd={(type) => addWidgetMutation.mutate(type)}
        adding={addWidgetMutation.isPending}
      />
    </Stack>
  );
}
