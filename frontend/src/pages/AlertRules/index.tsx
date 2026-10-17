import { useState } from 'react';
import {
  Box, Button, Typography, Dialog, DialogTitle, DialogContent, DialogActions,
  TextField, Select, MenuItem, FormControl, InputLabel, Chip,
  Switch, Card, CardContent, Grid, IconButton,
} from '@mui/material';
import { DataGrid, type GridColDef } from '@mui/x-data-grid';
import AddIcon from '@mui/icons-material/Add';
import DeleteIcon from '@mui/icons-material/Delete';
import NotificationsActiveIcon from '@mui/icons-material/NotificationsActive';
import { useForm, Controller } from 'react-hook-form';
import { useSnackbar } from 'notistack';
import { useAlertRules, useCreateAlertRule, useUpdateAlertRule, useDeleteAlertRule } from '@/hooks/useAlertRules';

const TRIGGER_LABELS: Record<string, string> = {
  mission_completed: 'Mission terminée',
  expense_created: 'Dépense créée',
  incident_reported: 'Incident signalé',
  document_expiring: 'Document expirant',
  fuel_anomaly: 'Anomalie carburant',
  geofence_breach: 'Sortie de zone',
  maintenance_due: 'Maintenance due',
  speed_exceeded: 'Excès de vitesse',
};

const PRIORITY_COLORS: Record<string, 'error' | 'warning' | 'default'> = {
  high: 'error', medium: 'warning', low: 'default',
};

export default function AlertRulesPage() {
  const [openForm, setOpenForm] = useState(false);
  const { enqueueSnackbar } = useSnackbar();

  const { data, isLoading } = useAlertRules();
  const createMut = useCreateAlertRule();
  const updateMut = useUpdateAlertRule();
  const deleteMut = useDeleteAlertRule();

  const { control, handleSubmit, reset } = useForm();

  const columns: GridColDef[] = [
    { field: 'name', headerName: 'Nom', width: 200 },
    { field: 'trigger_type', headerName: 'Déclencheur', width: 180, renderCell: (p) => TRIGGER_LABELS[p.value] || p.value },
    { field: 'priority', headerName: 'Priorité', width: 120, renderCell: (p) => <Chip label={p.value} size="small" color={PRIORITY_COLORS[p.value] || 'default'} /> },
    {
      field: 'is_active', headerName: 'Actif', width: 100,
      renderCell: (p) => (
        <Switch checked={p.value} size="small" onChange={() => toggleRule(p.row.uuid, !p.value)} />
      ),
    },
    { field: 'conditions', headerName: 'Conditions', width: 200, valueFormatter: (v) => v ? JSON.stringify(v) : '-' },
    {
      field: 'actions_col', headerName: 'Actions', width: 100, sortable: false,
      renderCell: (p) => (
        <IconButton size="small" color="error" onClick={() => handleDelete(p.row.uuid)}>
          <DeleteIcon />
        </IconButton>
      ),
    },
  ];

  const toggleRule = (uuid: string, isActive: boolean) => {
    updateMut.mutate({ uuid, data: { is_active: isActive } }, {
      onSuccess: () => enqueueSnackbar(isActive ? 'Règle activée' : 'Règle désactivée', { variant: 'success' }),
    });
  };

  const handleDelete = (uuid: string) => {
    if (!confirm('Supprimer cette règle ?')) return;
    deleteMut.mutate(uuid, { onSuccess: () => enqueueSnackbar('Règle supprimée', { variant: 'success' }) });
  };

  const onSubmit = (formData: any) => {
    const payload = {
      ...formData,
      conditions: formData.condition_field && formData.condition_operator
        ? { [formData.condition_field]: { operator: formData.condition_operator, value: Number(formData.condition_value) } }
        : {},
      actions: { notification: { channels: ['dashboard'] } },
    };
    delete payload.condition_field;
    delete payload.condition_operator;
    delete payload.condition_value;

    createMut.mutate(payload, {
      onSuccess: () => { enqueueSnackbar('Règle créée', { variant: 'success' }); setOpenForm(false); reset(); },
      onError: () => enqueueSnackbar('Erreur', { variant: 'error' }),
    });
  };

  const rules = data?.data || [];

  return (
    <Box>
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Typography variant="h5"><NotificationsActiveIcon sx={{ mr: 1, verticalAlign: 'middle' }} />Règles d'Alerte</Typography>
        <Button startIcon={<AddIcon />} variant="contained" onClick={() => setOpenForm(true)}>
          Nouvelle règle
        </Button>
      </Box>

      <Grid container spacing={2} mb={3}>
        <Grid item xs={12} sm={4}>
          <Card><CardContent>
            <Typography variant="body2" color="text.secondary">Règles actives</Typography>
            <Typography variant="h4">{rules.filter((r: any) => r.is_active).length}</Typography>
          </CardContent></Card>
        </Grid>
        <Grid item xs={12} sm={4}>
          <Card><CardContent>
            <Typography variant="body2" color="text.secondary">Total règles</Typography>
            <Typography variant="h4">{rules.length}</Typography>
          </CardContent></Card>
        </Grid>
      </Grid>

      <DataGrid
        rows={rules} columns={columns} loading={isLoading}
        getRowId={(r) => r.uuid}
        autoHeight disableRowSelectionOnClick
        pageSizeOptions={[10, 25]}
      />

      {/* Create Dialog */}
      <Dialog open={openForm} onClose={() => setOpenForm(false)} maxWidth="sm" fullWidth>
        <form onSubmit={handleSubmit(onSubmit)}>
          <DialogTitle>Créer une règle d'alerte</DialogTitle>
          <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
            <Controller name="name" control={control} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Nom de la règle" required fullWidth />
            )} />
            <Controller name="trigger_type" control={control} defaultValue="expense_created" render={({ field }) => (
              <FormControl fullWidth>
                <InputLabel>Déclencheur</InputLabel>
                <Select {...field} label="Déclencheur">
                  {Object.entries(TRIGGER_LABELS).map(([k, v]) => <MenuItem key={k} value={k}>{v}</MenuItem>)}
                </Select>
              </FormControl>
            )} />
            <Controller name="priority" control={control} defaultValue="medium" render={({ field }) => (
              <FormControl fullWidth>
                <InputLabel>Priorité</InputLabel>
                <Select {...field} label="Priorité">
                  <MenuItem value="low">Basse</MenuItem>
                  <MenuItem value="medium">Moyenne</MenuItem>
                  <MenuItem value="high">Haute</MenuItem>
                </Select>
              </FormControl>
            )} />
            <Typography variant="subtitle2" mt={1}>Condition (optionnel)</Typography>
            <Grid container spacing={1}>
              <Grid item xs={4}>
                <Controller name="condition_field" control={control} defaultValue="" render={({ field }) => (
                  <TextField {...field} label="Champ" size="small" fullWidth placeholder="ex: amount" />
                )} />
              </Grid>
              <Grid item xs={4}>
                <Controller name="condition_operator" control={control} defaultValue=">" render={({ field }) => (
                  <FormControl fullWidth size="small">
                    <Select {...field}>
                      <MenuItem value=">">{'>'}</MenuItem>
                      <MenuItem value=">=">{'>='}</MenuItem>
                      <MenuItem value="<">{'<'}</MenuItem>
                      <MenuItem value="==">{'=='}</MenuItem>
                    </Select>
                  </FormControl>
                )} />
              </Grid>
              <Grid item xs={4}>
                <Controller name="condition_value" control={control} defaultValue="" render={({ field }) => (
                  <TextField {...field} label="Valeur" size="small" type="number" fullWidth />
                )} />
              </Grid>
            </Grid>
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setOpenForm(false)}>Annuler</Button>
            <Button type="submit" variant="contained" disabled={createMut.isPending}>Créer</Button>
          </DialogActions>
        </form>
      </Dialog>
    </Box>
  );
}
