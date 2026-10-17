import { useRef, useState } from 'react';
import {
  Box, Button, Typography, Dialog, DialogTitle, DialogContent, DialogActions,
  TextField, Select, MenuItem, FormControl, InputLabel, Alert, Chip,
  IconButton, Tooltip, Card, CardContent, Grid,
} from '@mui/material';
import { DataGrid, type GridColDef } from '@mui/x-data-grid';
import AddIcon from '@mui/icons-material/Add';
import BuildIcon from '@mui/icons-material/Build';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import WarningIcon from '@mui/icons-material/Warning';
import FileDownloadIcon from '@mui/icons-material/FileDownload';
import FileUploadIcon from '@mui/icons-material/FileUpload';
import { useForm, Controller } from 'react-hook-form';
import { useSnackbar } from 'notistack';
import {
  useMaintenance, useMaintenanceAlerts, useCreateMaintenance,
  useCompleteMaintenance, useDeleteMaintenance,
} from '@/hooks/useMaintenance';
import { useExport } from '@/hooks/useExport';
import { useImport } from '@/hooks/useImport';
import { useVehicles } from '@/hooks/useVehicles';

const TYPE_LABELS: Record<string, string> = {
  oil_change: 'Vidange', tire_rotation: 'Rotation pneus', inspection: 'Inspection',
  engine_service: 'Service moteur', brake_check: 'Freins', filter_change: 'Filtre', other: 'Autre',
};

const STATUS_COLORS: Record<string, 'success' | 'warning' | 'error'> = {
  active: 'success', completed: 'success', overdue: 'error',
};

export default function MaintenancePage() {
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState(10);
  const [openForm, setOpenForm] = useState(false);
  const [completeDialog, setCompleteDialog] = useState<string | null>(null);
  const [typeFilter, setTypeFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [vehicleFilter, setVehicleFilter] = useState('');
  const [overdueOnly, setOverdueOnly] = useState('');
  const importInputRef = useRef<HTMLInputElement>(null);
  const { enqueueSnackbar } = useSnackbar();

  const { data, isLoading, isError } = useMaintenance({
    page: page + 1,
    per_page: pageSize,
    ...(typeFilter ? { maintenance_type: typeFilter } : {}),
    ...(statusFilter ? { status: statusFilter } : {}),
    ...(vehicleFilter ? { vehicle_search: vehicleFilter } : {}),
    ...(overdueOnly ? { is_overdue: overdueOnly } : {}),
  });
  const { data: alerts } = useMaintenanceAlerts();
  const createMut = useCreateMaintenance();
  const completeMut = useCompleteMaintenance();
  const deleteMut = useDeleteMaintenance();
  const exportMutation = useExport();
  const importMutation = useImport();
  const { data: vehiclesData } = useVehicles({ per_page: '200' });
  const vehiclesList = vehiclesData?.data ?? [];

  const { control, handleSubmit, reset } = useForm();
  const { control: completeControl, handleSubmit: handleCompleteSubmit, reset: resetComplete } = useForm();

  const columns: GridColDef[] = [
    { field: 'maintenance_type', headerName: 'Type', width: 150, renderCell: (p) => TYPE_LABELS[p.value] || p.value },
    { field: 'vehicle', headerName: 'Véhicule', width: 160, valueGetter: (_, row) => row.vehicle?.plate_number || '-' },
    { field: 'next_due_date', headerName: 'Prochaine échéance', width: 150 },
    { field: 'next_due_km', headerName: 'Prochain km', width: 130, valueFormatter: (v) => v ? `${v} km` : '-' },
    { field: 'status', headerName: 'Statut', width: 120, renderCell: (p) => <Chip label={p.value} size="small" color={STATUS_COLORS[p.value] || 'default'} /> },
    { field: 'is_overdue', headerName: 'En retard', width: 100, renderCell: (p) => p.value ? <WarningIcon color="error" /> : <CheckCircleIcon color="success" /> },
    {
      field: 'actions', headerName: 'Actions', width: 150, sortable: false,
      renderCell: (p) => (
        <Box>
          <Tooltip title="Compléter">
            <IconButton size="small" color="primary" onClick={() => setCompleteDialog(p.row.uuid)}>
              <BuildIcon />
            </IconButton>
          </Tooltip>
          <Tooltip title="Supprimer">
            <IconButton size="small" color="error" onClick={() => handleDelete(p.row.uuid)}>
              <WarningIcon />
            </IconButton>
          </Tooltip>
        </Box>
      ),
    },
  ];

  const onCreateSubmit = (formData: any) => {
    createMut.mutate(formData, {
      onSuccess: () => { enqueueSnackbar('Planification créée', { variant: 'success' }); setOpenForm(false); reset(); },
      onError: () => enqueueSnackbar('Erreur lors de la création', { variant: 'error' }),
    });
  };

  const onCompleteSubmit = (formData: any) => {
    if (!completeDialog) return;
    completeMut.mutate({ uuid: completeDialog, data: formData }, {
      onSuccess: () => { enqueueSnackbar('Maintenance enregistrée', { variant: 'success' }); setCompleteDialog(null); resetComplete(); },
      onError: () => enqueueSnackbar('Erreur', { variant: 'error' }),
    });
  };

  const handleDelete = (uuid: string) => {
    if (!confirm('Supprimer cette planification ?')) return;
    deleteMut.mutate(uuid, {
      onSuccess: () => enqueueSnackbar('Supprimé', { variant: 'success' }),
    });
  };

  const handleExport = async () => {
    try {
      await exportMutation.mutateAsync({
        resource: 'maintenance',
        params: {
          ...(typeFilter ? { maintenance_type: typeFilter } : {}),
          ...(statusFilter ? { status: statusFilter } : {}),
          ...(vehicleFilter ? { vehicle_search: vehicleFilter } : {}),
        },
      });
      enqueueSnackbar('Export maintenance lancé.', { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'export.', { variant: 'error' });
    }
  };

  const handleImportFile = async (file: File) => {
    try {
      const res = await importMutation.mutateAsync({ resource: 'maintenance', file });
      enqueueSnackbar(`${res.imported} maintenance(s) importée(s).`, { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'import.', { variant: 'error' });
    }
  };

  if (isError) return <Alert severity="error">Erreur de chargement</Alert>;

  const rows = data?.data || [];
  const total = data?.data?.meta?.total || 0;

  return (
    <Box>
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3} flexWrap="wrap" gap={1}>
        <Typography variant="h5">Maintenance Prédictive</Typography>
        <Box sx={{ display: 'flex', gap: 1, alignItems: 'center', flexWrap: 'wrap' }}>
          <FormControl size="small" sx={{ minWidth: 160 }}>
            <InputLabel>Type</InputLabel>
            <Select label="Type" value={typeFilter} onChange={e => { setTypeFilter(e.target.value); setPage(0); }}>
              <MenuItem value="">Tous</MenuItem>
              {Object.entries(TYPE_LABELS).map(([k, v]) => <MenuItem key={k} value={k}>{v}</MenuItem>)}
            </Select>
          </FormControl>
          <FormControl size="small" sx={{ minWidth: 120 }}>
            <InputLabel>Statut</InputLabel>
            <Select label="Statut" value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(0); }}>
              <MenuItem value="">Tous</MenuItem>
              <MenuItem value="active">Actif</MenuItem>
              <MenuItem value="completed">Complété</MenuItem>
              <MenuItem value="overdue">En retard</MenuItem>
            </Select>
          </FormControl>
          <TextField size="small" label="Véhicule (UUID/plaque)" value={vehicleFilter}
            onChange={e => { setVehicleFilter(e.target.value); setPage(0); }} sx={{ width: 190 }} />
          <FormControl size="small" sx={{ minWidth: 120 }}>
            <InputLabel>Retard</InputLabel>
            <Select label="Retard" value={overdueOnly} onChange={e => { setOverdueOnly(e.target.value); setPage(0); }}>
              <MenuItem value="">Tous</MenuItem>
              <MenuItem value="true">Seulement en retard</MenuItem>
            </Select>
          </FormControl>
          <input
            ref={importInputRef}
            type="file"
            hidden
            accept=".csv,.xlsx,.xls"
            onChange={e => {
              const file = e.target.files?.[0];
              if (file) void handleImportFile(file);
              e.target.value = '';
            }}
          />
          <Button variant="outlined" startIcon={<FileUploadIcon />} onClick={() => importInputRef.current?.click()}>
            Import
          </Button>
          <Button variant="outlined" startIcon={<FileDownloadIcon />} onClick={handleExport}>
            Export
          </Button>
          <Button startIcon={<AddIcon />} variant="contained" onClick={() => setOpenForm(true)}>
            Planifier
          </Button>
        </Box>
      </Box>

      {/* Alerts summary */}
      {alerts && (
        <Grid container spacing={2} mb={3}>
          <Grid item xs={12} md={6}>
            <Card sx={{ borderLeft: '4px solid #EF4444' }}>
              <CardContent>
                <Typography variant="h6" color="error">{alerts.overdue?.length || 0} En retard</Typography>
                <Typography variant="body2">Maintenances dépassant la date/km prévu</Typography>
              </CardContent>
            </Card>
          </Grid>
          <Grid item xs={12} md={6}>
            <Card sx={{ borderLeft: '4px solid #F59E0B' }}>
              <CardContent>
                <Typography variant="h6" color="warning.main">{alerts.due_soon?.length || 0} À venir (30j)</Typography>
                <Typography variant="body2">Maintenances prévues dans les 30 prochains jours</Typography>
              </CardContent>
            </Card>
          </Grid>
        </Grid>
      )}

      <DataGrid
        rows={rows} columns={columns} loading={isLoading}
        getRowId={(r) => r.uuid}
        paginationMode="server" rowCount={total}
        paginationModel={{ page, pageSize }}
        onPaginationModelChange={(m) => { setPage(m.page); setPageSize(m.pageSize); }}
        pageSizeOptions={[10, 25, 50]}
        autoHeight disableRowSelectionOnClick
      />

      {/* Create Dialog */}
      <Dialog open={openForm} onClose={() => setOpenForm(false)} maxWidth="sm" fullWidth>
        <form onSubmit={handleSubmit(onCreateSubmit)}>
          <DialogTitle>Planifier une maintenance</DialogTitle>
          <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
            <Controller name="vehicle_uuid" control={control} defaultValue="" rules={{ required: true }} render={({ field }) => (
              <FormControl fullWidth required>
                <InputLabel>Véhicule</InputLabel>
                <Select {...field} label="Véhicule">
                  <MenuItem value="">— Sélectionner —</MenuItem>
                  {vehiclesList.map((v) => (
                    <MenuItem key={v.uuid} value={v.uuid}>
                      {v.plate_number} — {v.brand} {v.model}
                    </MenuItem>
                  ))}
                </Select>
              </FormControl>
            )} />
            <Controller name="maintenance_type" control={control} defaultValue="oil_change" render={({ field }) => (
              <FormControl fullWidth>
                <InputLabel>Type</InputLabel>
                <Select {...field} label="Type">
                  {Object.entries(TYPE_LABELS).map(([k, v]) => <MenuItem key={k} value={k}>{v}</MenuItem>)}
                </Select>
              </FormControl>
            )} />
            <Controller name="frequency_km" control={control} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Fréquence (km)" type="number" fullWidth />
            )} />
            <Controller name="frequency_months" control={control} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Fréquence (mois)" type="number" fullWidth />
            )} />
            <Controller name="last_service_date" control={control} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Dernier service" type="date" InputLabelProps={{ shrink: true }} fullWidth />
            )} />
            <Controller name="last_service_km" control={control} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Km au dernier service" type="number" fullWidth />
            )} />
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setOpenForm(false)}>Annuler</Button>
            <Button type="submit" variant="contained" disabled={createMut.isPending}>Créer</Button>
          </DialogActions>
        </form>
      </Dialog>

      {/* Complete Service Dialog */}
      <Dialog open={!!completeDialog} onClose={() => setCompleteDialog(null)} maxWidth="sm" fullWidth>
        <form onSubmit={handleCompleteSubmit(onCompleteSubmit)}>
          <DialogTitle>Enregistrer un service</DialogTitle>
          <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
            <Controller name="service_date" control={completeControl} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Date du service" type="date" InputLabelProps={{ shrink: true }} required fullWidth />
            )} />
            <Controller name="service_km" control={completeControl} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Kilométrage" type="number" required fullWidth />
            )} />
            <Controller name="cost" control={completeControl} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Coût (€)" type="number" fullWidth />
            )} />
            <Controller name="provider" control={completeControl} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Prestataire" fullWidth />
            )} />
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setCompleteDialog(null)}>Annuler</Button>
            <Button type="submit" variant="contained" disabled={completeMut.isPending}>Enregistrer</Button>
          </DialogActions>
        </form>
      </Dialog>
    </Box>
  );
}
