import { useState } from 'react';
import {
  Box, Typography, Chip, Alert,
  IconButton, Tooltip,
} from '@mui/material';
import { DataGrid, type GridColDef } from '@mui/x-data-grid';
import BlockIcon from '@mui/icons-material/Block';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import { useSnackbar } from 'notistack';
import { useAdminCompanies, useSuspendCompany, useActivateCompany } from '@/hooks/useAdmin';

const STATUS_COLORS: Record<string, 'success' | 'error'> = {
  active: 'success',
  suspended: 'error',
};

export default function AdminCompaniesPage() {
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState(10);
  const { enqueueSnackbar } = useSnackbar();

  const { data, isLoading, isError } = useAdminCompanies({ page: String(page + 1), per_page: String(pageSize) });
  const suspendMutation = useSuspendCompany();
  const activateMutation = useActivateCompany();

  const handleSuspend = async (uuid: string) => {
    if (!window.confirm("Suspendre cette entreprise ? Tous ses utilisateurs seront bloqués.")) return;
    try {
      await suspendMutation.mutateAsync(uuid);
      enqueueSnackbar('Entreprise suspendue.', { variant: 'warning' });
    } catch {
      enqueueSnackbar('Erreur.', { variant: 'error' });
    }
  };

  const handleActivate = async (uuid: string) => {
    try {
      await activateMutation.mutateAsync(uuid);
      enqueueSnackbar('Entreprise activée.', { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur.', { variant: 'error' });
    }
  };

  const columns: GridColDef[] = [
    { field: 'name', headerName: 'Entreprise', flex: 1 },
    {
      field: 'plan', headerName: 'Plan', width: 120,
      renderCell: (params) => <Chip label={params.value} size="small" />,
    },
    {
      field: 'status', headerName: 'Statut', width: 120,
      renderCell: (params) => (
        <Chip label={params.value === 'active' ? 'Actif' : 'Suspendu'} color={STATUS_COLORS[params.value] ?? 'default'} size="small" />
      ),
    },
    { field: 'users_count', headerName: 'Utilisateurs', width: 120 },
    { field: 'vehicles_count', headerName: 'Véhicules', width: 110 },
    { field: 'created_at', headerName: 'Créé le', width: 130, valueFormatter: (v: string) => new Date(v).toLocaleDateString('fr-FR') },
    {
      field: 'actions', headerName: 'Actions', width: 130, sortable: false,
      renderCell: (params) => (
        <Box>
          {params.row.status === 'active' ? (
            <Tooltip title="Suspendre">
              <IconButton size="small" color="error" onClick={() => handleSuspend(params.row.uuid)}><BlockIcon fontSize="small" /></IconButton>
            </Tooltip>
          ) : (
            <Tooltip title="Activer">
              <IconButton size="small" color="success" onClick={() => handleActivate(params.row.uuid)}><CheckCircleIcon fontSize="small" /></IconButton>
            </Tooltip>
          )}
        </Box>
      ),
    },
  ];

  if (isError) return <Alert severity="error">Impossible de charger les entreprises.</Alert>;

  return (
    <Box>
      <Typography variant="h5" fontWeight="bold" sx={{ mb: 2 }}>Gestion des entreprises</Typography>
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
    </Box>
  );
}
