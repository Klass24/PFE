import { useRef, useState } from 'react';
import {
  Box, Typography, Dialog, DialogTitle, DialogContent, DialogActions,
  Button, Alert, Chip, FormControl, InputLabel, Select, MenuItem,
} from '@mui/material';
import { DataGrid, type GridColDef } from '@mui/x-data-grid';
import FileDownloadIcon from '@mui/icons-material/FileDownload';
import FileUploadIcon from '@mui/icons-material/FileUpload';
import { useIncidents } from '@/hooks/useIncidents';
import { useExport } from '@/hooks/useExport';
import { useImport } from '@/hooks/useImport';
import TextField from '@mui/material/TextField';
import { useSnackbar } from 'notistack';
import type { Incident } from '@/types';

const TYPE_LABELS: Record<string, string> = {
  accident: 'Accident', breakdown: 'Panne', flat_tire: 'Crevaison',
  traffic: 'Circulation', police_check: 'Contrôle police',
  mechanical_issue: 'Problème mécanique', other: 'Autre',
};

const TYPE_COLORS: Record<string, 'error' | 'warning' | 'info' | 'default'> = {
  accident: 'error', breakdown: 'warning', flat_tire: 'warning',
  traffic: 'info', police_check: 'info', mechanical_issue: 'warning', other: 'default',
};

const SYNC_LABELS: Record<string, { label: string; color: string }> = {
  synced:  { label: 'Sync.',      color: '#22C55E' },
  pending: { label: 'En attente', color: '#F59E0B' },
};

export default function IncidentsPage() {
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState(10);
  const [selected, setSelected] = useState<Incident | null>(null);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [syncFilter, setSyncFilter] = useState('');
  const [descriptionFilter, setDescriptionFilter] = useState('');
  const importInputRef = useRef<HTMLInputElement>(null);
  const { enqueueSnackbar } = useSnackbar();
  const exportMutation = useExport();
  const importMutation = useImport();

  const queryParams: Record<string, string> = { page: String(page + 1), per_page: String(pageSize) };
  if (dateFrom) queryParams.from = dateFrom;
  if (dateTo)   queryParams.to   = dateTo;
  if (typeFilter) queryParams.type = typeFilter;
  if (syncFilter) queryParams.sync_status = syncFilter;
  if (descriptionFilter) queryParams.description = descriptionFilter;
  const { data, isLoading, isError } = useIncidents(queryParams);

  const handleExport = async () => {
    try {
      await exportMutation.mutateAsync({
        resource: 'incidents',
        params: {
          ...(dateFrom ? { from: dateFrom } : {}),
          ...(dateTo ? { to: dateTo } : {}),
          ...(typeFilter ? { type: typeFilter } : {}),
          ...(syncFilter ? { sync_status: syncFilter } : {}),
          ...(descriptionFilter ? { description: descriptionFilter } : {}),
        },
      });
      enqueueSnackbar('Export des incidents lancé.', { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'export.', { variant: 'error' });
    }
  };

  const handleImportFile = async (file: File) => {
    try {
      const res = await importMutation.mutateAsync({ resource: 'incidents', file });
      enqueueSnackbar(`${res.imported} incident(s) importé(s).`, { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'import.', { variant: 'error' });
    }
  };

  const columns: GridColDef[] = [
    {
      field: 'type', headerName: 'Type', width: 160,
      renderCell: (params) => (
        <Chip label={TYPE_LABELS[params.value] ?? params.value} color={TYPE_COLORS[params.value] ?? 'default'} size="small" />
      ),
    },
    { field: 'description', headerName: 'Description', flex: 1 },
    {
      field: 'reported_at', headerName: 'Signalé le', width: 150,
      valueFormatter: (v) => v ? new Date(v).toLocaleString('fr-FR') : '—',
    },
    {
      field: 'sync_status', headerName: 'Sync', width: 110,
      renderCell: (params) => {
        const s = SYNC_LABELS[params.value] ?? { label: params.value ?? '—', color: '#888' };
        return <Chip size="small" label={s.label} sx={{ bgcolor: s.color + '20', color: s.color, fontWeight: 600, fontSize: 11, height: 20 }} />;
      },
    },
    {
      field: 'created_at', headerName: 'Date', width: 140,
      valueFormatter: (v) => new Date(v).toLocaleDateString('fr-FR'),
    },
  ];

  if (isError) return <Alert severity="error">Impossible de charger les incidents.</Alert>;

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2, flexWrap: 'wrap', gap: 1 }}>
        <Typography variant="h5" fontWeight="bold">Incidents</Typography>
        <Box sx={{ display: 'flex', gap: 1, alignItems: 'center' }}>
          <FormControl size="small" sx={{ minWidth: 160 }}>
            <InputLabel>Type</InputLabel>
            <Select label="Type" value={typeFilter} onChange={e => { setTypeFilter(e.target.value); setPage(0); }}>
              <MenuItem value="">Tous</MenuItem>
              <MenuItem value="accident">Accident</MenuItem>
              <MenuItem value="breakdown">Panne</MenuItem>
              <MenuItem value="flat_tire">Crevaison</MenuItem>
              <MenuItem value="traffic">Circulation</MenuItem>
              <MenuItem value="police_check">Contrôle police</MenuItem>
              <MenuItem value="mechanical_issue">Problème mécanique</MenuItem>
              <MenuItem value="other">Autre</MenuItem>
            </Select>
          </FormControl>
          <FormControl size="small" sx={{ minWidth: 130 }}>
            <InputLabel>Sync</InputLabel>
            <Select label="Sync" value={syncFilter} onChange={e => { setSyncFilter(e.target.value); setPage(0); }}>
              <MenuItem value="">Tous</MenuItem>
              <MenuItem value="synced">Sync.</MenuItem>
              <MenuItem value="pending">En attente</MenuItem>
            </Select>
          </FormControl>
          <TextField size="small" label="Description" value={descriptionFilter}
            onChange={e => { setDescriptionFilter(e.target.value); setPage(0); }} sx={{ width: 170 }} />
          <TextField size="small" type="date" label="Du" value={dateFrom}
            onChange={e => { setDateFrom(e.target.value); setPage(0); }}
            InputLabelProps={{ shrink: true }} sx={{ width: 160 }} />
          <TextField size="small" type="date" label="Au" value={dateTo}
            onChange={e => { setDateTo(e.target.value); setPage(0); }}
            InputLabelProps={{ shrink: true }} sx={{ width: 160 }} />
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
        </Box>
      </Box>

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
        onRowClick={(p) => setSelected(p.row as Incident)}
        autoHeight
        disableRowSelectionOnClick
      />

      <Dialog open={!!selected} onClose={() => setSelected(null)} maxWidth="sm" fullWidth>
        <DialogTitle>Détail de l'incident</DialogTitle>
        <DialogContent>
          {selected && (
            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 1.5 }}>
              <Box><strong>Type :</strong> {TYPE_LABELS[selected.type] ?? selected.type}</Box>
              <Box><strong>Description :</strong> {selected.description}</Box>
              {selected.latitude && selected.longitude && (
                <Box>
                  <strong>Localisation :</strong>{' '}
                  <a
                    href={`https://maps.google.com/?q=${selected.latitude},${selected.longitude}`}
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    {selected.latitude}, {selected.longitude}
                  </a>
                </Box>
              )}
              <Box><strong>Date :</strong> {new Date(selected.created_at).toLocaleString('fr-FR')}</Box>
            </Box>
          )}
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setSelected(null)}>Fermer</Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
