import { useState } from 'react';
import {
  Box, Typography, Chip, Alert, CircularProgress,
  TextField, FormControl, InputLabel, Select, MenuItem,
} from '@mui/material';
import { DataGrid, type GridColDef } from '@mui/x-data-grid';
import { useAuditLogs } from '@/hooks/useAuditLogs';

const ACTION_COLORS: Record<string, 'success' | 'warning' | 'error'> = {
  created: 'success',
  updated: 'warning',
  deleted: 'error',
};

const ACTION_LABELS: Record<string, string> = {
  created: 'Création',
  updated: 'Modification',
  deleted: 'Suppression',
};

export default function AuditPage() {
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState(10);
  const [action, setAction] = useState('');
  const [search, setSearch] = useState('');

  const params: Record<string, string> = { page: String(page + 1), per_page: String(pageSize) };
  if (action) params.action = action;
  if (search) params.auditable_type = search;

  const { data, isLoading, isError } = useAuditLogs(params);

  const columns: GridColDef[] = [
    {
      field: 'action', headerName: 'Action', width: 130,
      renderCell: (params) => (
        <Chip label={ACTION_LABELS[params.value] ?? params.value} color={ACTION_COLORS[params.value] ?? 'default'} size="small" />
      ),
    },
    { field: 'auditable_type', headerName: 'Entité', width: 130 },
    {
      field: 'user', headerName: 'Par', width: 150,
      valueGetter: (_v: unknown, row: { user?: { name: string } }) => row.user?.name ?? '—',
    },
    { field: 'ip_address', headerName: 'IP', width: 130 },
    {
      field: 'created_at', headerName: 'Date', width: 170,
      valueFormatter: (v: string) => v ? new Date(v).toLocaleString('fr-FR') : '',
    },
    {
      field: 'new_values', headerName: 'Détails', flex: 1,
      valueFormatter: (v: Record<string, unknown> | null) => v ? Object.keys(v).join(', ') : '—',
    },
  ];

  if (isError) return <Alert severity="error">Impossible de charger les logs d'audit.</Alert>;

  return (
    <Box>
      <Typography variant="h5" fontWeight="bold" sx={{ mb: 2 }}>Journal d'audit</Typography>

      <Box sx={{ display: 'flex', gap: 2, mb: 2 }}>
        <FormControl size="small" sx={{ minWidth: 150 }}>
          <InputLabel>Action</InputLabel>
          <Select value={action} label="Action" onChange={(e) => { setAction(e.target.value); setPage(0); }}>
            <MenuItem value="">Toutes</MenuItem>
            <MenuItem value="created">Création</MenuItem>
            <MenuItem value="updated">Modification</MenuItem>
            <MenuItem value="deleted">Suppression</MenuItem>
          </Select>
        </FormControl>
        <TextField
          size="small"
          label="Filtrer par entité"
          value={search}
          onChange={(e) => { setSearch(e.target.value); setPage(0); }}
          placeholder="Vehicle, Mission..."
        />
      </Box>

      {isLoading ? <CircularProgress /> : (
        <DataGrid
          rows={data?.data ?? []}
          columns={columns}
          getRowId={(r) => r.uuid}
          paginationMode="server"
          rowCount={data?.meta?.total ?? 0}
          paginationModel={{ page, pageSize }}
          onPaginationModelChange={(m) => { setPage(m.page); setPageSize(m.pageSize); }}
          pageSizeOptions={[10, 25, 50, 100]}
          autoHeight
          disableRowSelectionOnClick
        />
      )}
    </Box>
  );
}
