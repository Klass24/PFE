import { useState } from 'react';
import {
  Box, Button, Typography, Dialog, DialogTitle, DialogContent, DialogActions,
  TextField, Chip, Card, CardContent, Grid, Tab, Tabs,
} from '@mui/material';
import { DataGrid, type GridColDef } from '@mui/x-data-grid';
import AddIcon from '@mui/icons-material/Add';
import LocalGasStationIcon from '@mui/icons-material/LocalGasStation';
import WarningAmberIcon from '@mui/icons-material/WarningAmber';
import { useForm, Controller } from 'react-hook-form';
import { useSnackbar } from 'notistack';
import { useFuelEntries, useCreateFuelEntry, useFuelAnomalies, useFuelAnalytics, useResolveFuelAnomaly } from '@/hooks/useFuel';

const ANOMALY_LABELS: Record<string, string> = {
  excessive_consumption: 'Consommation excessive',
  potential_theft: 'Vol potentiel',
  gauge_malfunction: 'Dysfonctionnement jauge',
};

export default function FuelMonitoringPage() {
  const [tab, setTab] = useState(0);
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState(10);
  const [openForm, setOpenForm] = useState(false);
  const { enqueueSnackbar } = useSnackbar();

  const { data: entriesData, isLoading } = useFuelEntries({ page: page + 1, per_page: pageSize });
  const { data: anomaliesData } = useFuelAnomalies({ status: 'unresolved' });
  const { data: analytics } = useFuelAnalytics();
  const createMut = useCreateFuelEntry();
  const resolveMut = useResolveFuelAnomaly();

  const { control, handleSubmit, reset } = useForm();

  const entryCols: GridColDef[] = [
    { field: 'vehicle', headerName: 'Véhicule', width: 140, valueGetter: (_, row) => row.vehicle?.plate_number || '-' },
    { field: 'liters', headerName: 'Litres', width: 100 },
    { field: 'cost_per_liter', headerName: '€/L', width: 80 },
    { field: 'total_cost', headerName: 'Coût total', width: 110 },
    { field: 'odometer_reading', headerName: 'Compteur', width: 120, valueFormatter: (v) => `${v} km` },
    { field: 'station_name', headerName: 'Station', width: 150 },
    { field: 'recorded_at', headerName: 'Date', width: 160, valueFormatter: (v) => v ? new Date(v).toLocaleDateString('fr-FR') : '-' },
  ];

  const anomalyCols: GridColDef[] = [
    { field: 'vehicle', headerName: 'Véhicule', width: 140, valueGetter: (_, row) => row.vehicle?.plate_number || '-' },
    { field: 'anomaly_type', headerName: 'Type', width: 200, renderCell: (p) => <Chip label={ANOMALY_LABELS[p.value] || p.value} color="error" size="small" /> },
    { field: 'expected_consumption', headerName: 'Attendu (L/100km)', width: 160 },
    { field: 'actual_consumption', headerName: 'Réel (L/100km)', width: 140 },
    { field: 'deviation_percent', headerName: 'Déviation', width: 120, renderCell: (p) => <Typography color="error">+{p.value}%</Typography> },
    {
      field: 'actions', headerName: 'Actions', width: 200, sortable: false,
      renderCell: (p) => (
        <Box>
          <Button size="small" color="success" onClick={() => resolveAnomaly(p.row.uuid, 'false_positive')}>Fausse alerte</Button>
          <Button size="small" color="error" onClick={() => resolveAnomaly(p.row.uuid, 'confirmed')}>Confirmer</Button>
        </Box>
      ),
    },
  ];

  const resolveAnomaly = (uuid: string, status: string) => {
    resolveMut.mutate({ uuid, data: { status } }, {
      onSuccess: () => enqueueSnackbar('Anomalie résolue', { variant: 'success' }),
    });
  };

  const onSubmit = (formData: any) => {
    createMut.mutate(formData, {
      onSuccess: () => { enqueueSnackbar('Consommation enregistrée', { variant: 'success' }); setOpenForm(false); reset(); },
      onError: () => enqueueSnackbar('Erreur', { variant: 'error' }),
    });
  };

  const entries = entriesData?.data || [];
  const anomalies = anomaliesData?.data || [];

  return (
    <Box>
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Typography variant="h5"><LocalGasStationIcon sx={{ mr: 1, verticalAlign: 'middle' }} />Suivi Carburant</Typography>
        <Button startIcon={<AddIcon />} variant="contained" onClick={() => setOpenForm(true)}>Enregistrer</Button>
      </Box>

      {/* KPI Cards */}
      {analytics && (
        <Grid container spacing={2} mb={3}>
          <Grid item xs={12} sm={4}>
            <Card><CardContent>
              <Typography variant="body2" color="text.secondary">Coût total</Typography>
              <Typography variant="h5">{analytics.total_cost?.toFixed(2)} €</Typography>
            </CardContent></Card>
          </Grid>
          <Grid item xs={12} sm={4}>
            <Card><CardContent>
              <Typography variant="body2" color="text.secondary">Litres totaux</Typography>
              <Typography variant="h5">{analytics.total_liters?.toFixed(0)} L</Typography>
            </CardContent></Card>
          </Grid>
          <Grid item xs={12} sm={4}>
            <Card sx={{ borderLeft: '4px solid #EF4444' }}><CardContent>
              <Typography variant="body2" color="text.secondary">Anomalies non résolues</Typography>
              <Typography variant="h5" color="error">{anomalies.length}</Typography>
            </CardContent></Card>
          </Grid>
        </Grid>
      )}

      <Tabs value={tab} onChange={(_, v) => setTab(v)} sx={{ mb: 2 }}>
        <Tab label="Entrées carburant" />
        <Tab label={`Anomalies (${anomalies.length})`} icon={anomalies.length > 0 ? <WarningAmberIcon color="error" /> : undefined} iconPosition="end" />
      </Tabs>

      {tab === 0 && (
        <DataGrid
          rows={entries} columns={entryCols} loading={isLoading}
          getRowId={(r) => r.uuid}
          paginationMode="server" rowCount={entriesData?.data?.meta?.total || 0}
          paginationModel={{ page, pageSize }}
          onPaginationModelChange={(m) => { setPage(m.page); setPageSize(m.pageSize); }}
          pageSizeOptions={[10, 25, 50]} autoHeight disableRowSelectionOnClick
        />
      )}

      {tab === 1 && (
        <DataGrid
          rows={anomalies} columns={anomalyCols}
          getRowId={(r) => r.uuid}
          autoHeight disableRowSelectionOnClick
        />
      )}

      {/* Create Dialog */}
      <Dialog open={openForm} onClose={() => setOpenForm(false)} maxWidth="sm" fullWidth>
        <form onSubmit={handleSubmit(onSubmit)}>
          <DialogTitle>Enregistrer une consommation</DialogTitle>
          <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
            <Controller name="vehicle_uuid" control={control} defaultValue="" render={({ field }) => (
              <TextField {...field} label="UUID Véhicule" required fullWidth />
            )} />
            <Controller name="liters" control={control} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Litres" type="number" required fullWidth />
            )} />
            <Controller name="cost_per_liter" control={control} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Prix / litre (€)" type="number" required fullWidth />
            )} />
            <Controller name="odometer_reading" control={control} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Kilométrage compteur" type="number" required fullWidth />
            )} />
            <Controller name="station_name" control={control} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Station" fullWidth />
            )} />
            <Controller name="recorded_at" control={control} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Date" type="datetime-local" InputLabelProps={{ shrink: true }} fullWidth />
            )} />
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setOpenForm(false)}>Annuler</Button>
            <Button type="submit" variant="contained" disabled={createMut.isPending}>Enregistrer</Button>
          </DialogActions>
        </form>
      </Dialog>
    </Box>
  );
}
