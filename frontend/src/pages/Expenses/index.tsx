import { useState } from 'react';
import {
  Box, Button, Typography, Dialog, DialogTitle, DialogContent, DialogActions,
  TextField, Select, MenuItem, FormControl, InputLabel, Alert, Chip,
  CircularProgress, Paper,
} from '@mui/material';
import { DataGrid, type GridColDef } from '@mui/x-data-grid';
import AddIcon from '@mui/icons-material/Add';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { useForm, Controller } from 'react-hook-form';
import { useSnackbar } from 'notistack';
import { useQuery } from '@tanstack/react-query';
import { missionService } from '@/services/missionService';
import { useExpenses, useCreateExpense } from '@/hooks/useExpenses';
import type { Expense, CreateExpenseDto } from '@/types';

const TYPE_LABELS: Record<string, string> = {
  fuel: 'Carburant', toll: 'Péage', repair: 'Réparation', parking: 'Parking', weighing: 'Pesage', other: 'Autre',
};

const SYNC_LABELS: Record<string, { label: string; color: string }> = {
  synced:  { label: 'Sync.', color: '#22C55E' },
  pending: { label: 'En attente', color: '#F59E0B' },
};

const TYPE_COLORS: Record<string, 'primary' | 'secondary' | 'warning' | 'default' | 'info'> = {
  fuel: 'primary', toll: 'info', repair: 'warning', parking: 'secondary', other: 'default',
};

export default function ExpensesPage() {
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState(10);
  const [openForm, setOpenForm] = useState(false);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const { enqueueSnackbar } = useSnackbar();

  const queryParams: Record<string, string> = { page: String(page + 1), per_page: String(pageSize) };
  if (dateFrom) queryParams.from = dateFrom;
  if (dateTo)   queryParams.to   = dateTo;
  const { data, isLoading, isError } = useExpenses(queryParams);
  const { data: missionsData } = useQuery({
    queryKey: ['missions-list'],
    queryFn: () => missionService.getAll(),
  });
  const createMutation = useCreateExpense();

  const { register, handleSubmit, reset, control, formState: { errors } } = useForm<CreateExpenseDto>();

  const handleClose = () => { setOpenForm(false); reset({}); };

  const onSubmit = async (formData: CreateExpenseDto) => {
    try {
      const { mission_uuid, ...rest } = formData;
      await createMutation.mutateAsync({ missionUuid: mission_uuid, data: rest });
      enqueueSnackbar('Dépense enregistrée.', { variant: 'success' });
      handleClose();
    } catch (err: unknown) {
      enqueueSnackbar(typeof err === 'string' ? err : 'Erreur lors de l\'enregistrement.', { variant: 'error' });
    }
  };

  // Aggregate chart data by type
  const chartData = Object.entries(TYPE_LABELS).map(([type, label]) => ({
    name: label,
    total: (data?.data ?? []).filter((e: Expense) => e.type === type).reduce((s: number, e: Expense) => s + Number(e.amount), 0),
  }));

  const columns: GridColDef[] = [
    {
      field: 'type', headerName: 'Type', width: 140,
      renderCell: (params) => (
        <Chip label={TYPE_LABELS[params.value] ?? params.value} color={TYPE_COLORS[params.value] ?? 'default'} size="small" />
      ),
    },
    {
      field: 'amount', headerName: 'Montant', width: 120,
      valueFormatter: (v) => `${Number(v).toFixed(2)} XAF`,
    },
    { field: 'liters', headerName: 'Litres', width: 90, valueFormatter: (v) => v ? `${v} L` : '—' },
    { field: 'mileage', headerName: 'Kilométrage', width: 120, valueFormatter: (v) => v ? `${v} km` : '—' },
    {
      field: 'sync_status', headerName: 'Sync', width: 110,
      renderCell: (params) => {
        const s = SYNC_LABELS[params.value] ?? { label: params.value, color: '#888' };
        return <Chip size="small" label={s.label} sx={{ bgcolor: s.color + '20', color: s.color, fontWeight: 600, fontSize: 11, height: 20 }} />;
      },
    },
    {
      field: 'created_at', headerName: 'Date', width: 140,
      valueFormatter: (v) => new Date(v).toLocaleDateString('fr-FR'),
    },
  ];

  if (isError) return <Alert severity="error">Impossible de charger les dépenses.</Alert>;

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2, flexWrap: 'wrap', gap: 1 }}>
        <Typography variant="h5" fontWeight="bold">Dépenses</Typography>
        <Box sx={{ display: 'flex', gap: 1, alignItems: 'center' }}>
          <TextField size="small" type="date" label="Du" value={dateFrom}
            onChange={e => { setDateFrom(e.target.value); setPage(0); }}
            InputLabelProps={{ shrink: true }} sx={{ width: 160 }} />
          <TextField size="small" type="date" label="Au" value={dateTo}
            onChange={e => { setDateTo(e.target.value); setPage(0); }}
            InputLabelProps={{ shrink: true }} sx={{ width: 160 }} />
          <Button variant="contained" startIcon={<AddIcon />} onClick={() => setOpenForm(true)}>
            Nouvelle dépense
          </Button>
        </Box>
      </Box>

      <Paper sx={{ p: 2, mb: 3 }}>
        <Typography variant="subtitle1" fontWeight="bold" mb={1}>Répartition par type</Typography>
        <ResponsiveContainer width="100%" height={220}>
          <BarChart data={chartData}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis />
            <Tooltip formatter={(v: number) => `${v.toFixed(2)} XAF`} />
            <Bar dataKey="total" fill="#1976d2" radius={[4, 4, 0, 0]} />
          </BarChart>
        </ResponsiveContainer>
      </Paper>

      <DataGrid
        rows={data?.data ?? []}
        columns={columns}
        getRowId={(r) => r.uuid}
        loading={isLoading}
        paginationMode="server"
        rowCount={data?.meta?.total ?? 0}
        paginationModel={{ page, pageSize }}
        onPaginationModelChange={(m) => { setPage(m.page); setPageSize(m.pageSize); }}
        pageSizeOptions={[10, 25, 50, 100]}
        autoHeight
        disableRowSelectionOnClick
      />

      <Dialog open={openForm} onClose={handleClose} maxWidth="sm" fullWidth>
        <DialogTitle>Nouvelle dépense</DialogTitle>
        <Box component="form" onSubmit={handleSubmit(onSubmit)}>
          <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            <FormControl fullWidth required>
              <InputLabel>Mission</InputLabel>
              <Controller
                name="mission_uuid"
                control={control}
                rules={{ required: true }}
                render={({ field }) => (
                  <Select label="Mission" {...field}>
                    {(missionsData?.data ?? []).map((m: { uuid: string; origin: string; destination: string }) => (
                      <MenuItem key={m.uuid} value={m.uuid}>{m.origin} → {m.destination}</MenuItem>
                    ))}
                  </Select>
                )}
              />
            </FormControl>
            <FormControl fullWidth>
              <InputLabel>Type</InputLabel>
              <Controller
                name="type"
                control={control}
                rules={{ required: true }}
                render={({ field }) => (
                  <Select label="Type" {...field}>
                    {Object.entries(TYPE_LABELS).map(([v, l]) => <MenuItem key={v} value={v}>{l}</MenuItem>)}
                  </Select>
                )}
              />
            </FormControl>
            <TextField label="Montant (XAF)" type="number" inputProps={{ step: '0.01', min: 0 }} fullWidth
              {...register('amount', { required: true })} error={!!errors.amount} />
            <TextField label="Litres (si carburant)" type="number" inputProps={{ step: '0.01', min: 0 }} fullWidth
              {...register('liters')} />
            <TextField label="Kilométrage" type="number" fullWidth {...register('mileage')} />
          </DialogContent>
          <DialogActions>
            <Button onClick={handleClose}>Annuler</Button>
            <Button type="submit" variant="contained" disabled={createMutation.isPending}>
              {createMutation.isPending ? <CircularProgress size={20} /> : 'Enregistrer'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>
    </Box>
  );
}
