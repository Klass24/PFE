import { useState } from 'react';
import {
  Box, Typography, Chip, Tab, Tabs, Alert,
} from '@mui/material';
import { DataGrid, type GridColDef } from '@mui/x-data-grid';
import { useAdminSubscriptions, useAdminPayments } from '@/hooks/useAdmin';

const SUB_COLS: GridColDef[] = [
  { field: 'company_name', headerName: 'Entreprise', flex: 1 },
  {
    field: 'plan', headerName: 'Plan', width: 120,
    renderCell: (p) => <Chip label={p.value} size="small" />,
  },
  {
    field: 'billing_status', headerName: 'Statut', width: 120,
    renderCell: (p) => (
      <Chip label={p.value} size="small"
        color={p.value === 'active' ? 'success' : p.value === 'cancelled' ? 'error' : 'warning'} />
    ),
  },
  {
    field: 'payment_gateway', headerName: 'Paiement', width: 120,
    renderCell: (p) => p.value ? <Chip label={p.value} size="small" variant="outlined" /> : <Typography variant="body2" color="text.secondary">Manuel</Typography>,
  },
  {
    field: 'amount', headerName: 'Montant', width: 110,
    renderCell: (p) => `${p.value ?? 0}€`,
  },
  { field: 'billing_cycle', headerName: 'Cycle', width: 100 },
  {
    field: 'start_date', headerName: 'Début', width: 120,
    valueFormatter: (v: string) => v ? new Date(v).toLocaleDateString('fr-FR') : '-',
  },
  {
    field: 'end_date', headerName: 'Fin', width: 120,
    valueFormatter: (v: string) => v ? new Date(v).toLocaleDateString('fr-FR') : '-',
  },
];

const PAY_COLS: GridColDef[] = [
  {
    field: 'company', headerName: 'Entreprise', flex: 1,
    valueGetter: (_v: unknown, row: Record<string, unknown>) => (row.company as Record<string, string>)?.name ?? '-',
  },
  {
    field: 'gateway', headerName: 'Passerelle', width: 110,
    renderCell: (p) => <Chip label={p.value} size="small" variant="outlined" />,
  },
  {
    field: 'amount', headerName: 'Montant', width: 110,
    renderCell: (p) => `${p.value}€`,
  },
  {
    field: 'status', headerName: 'Statut', width: 120,
    renderCell: (p) => (
      <Chip label={p.value} size="small"
        color={p.value === 'succeeded' ? 'success' : p.value === 'failed' ? 'error' : 'warning'} />
    ),
  },
  { field: 'payment_method', headerName: 'Méthode', width: 100 },
  { field: 'description', headerName: 'Description', flex: 1 },
  {
    field: 'paid_at', headerName: 'Payé le', width: 140,
    valueFormatter: (v: string) => v ? new Date(v).toLocaleString('fr-FR') : '-',
  },
];

export default function AdminSubscriptionsPage() {
  const [tab, setTab] = useState(0);
  const [subPage, setSubPage] = useState(0);
  const [payPage, setPayPage] = useState(0);

  const { data: subsData, isLoading: subsLoading, isError: subsError } = useAdminSubscriptions({
    page: String(subPage + 1),
  });
  const { data: paysData, isLoading: paysLoading, isError: paysError } = useAdminPayments({
    page: String(payPage + 1),
  });

  return (
    <Box>
      <Typography variant="h5" fontWeight="bold" sx={{ mb: 2 }}>Abonnements & Paiements</Typography>

      <Tabs value={tab} onChange={(_, v) => setTab(v)} sx={{ mb: 3 }}>
        <Tab label="Abonnements" />
        <Tab label="Paiements" />
      </Tabs>

      {tab === 0 && (
        <>
          {subsError && <Alert severity="error" sx={{ mb: 2 }}>Erreur de chargement.</Alert>}
          <DataGrid
            rows={subsData?.data ?? []}
            columns={SUB_COLS}
            getRowId={(r) => r.id}
            loading={subsLoading}
            paginationMode="server"
            rowCount={subsData?.meta?.total ?? 0}
            paginationModel={{ page: subPage, pageSize: 15 }}
            onPaginationModelChange={(m) => setSubPage(m.page)}
            pageSizeOptions={[15]}
            autoHeight
            disableRowSelectionOnClick
          />
        </>
      )}

      {tab === 1 && (
        <>
          {paysError && <Alert severity="error" sx={{ mb: 2 }}>Erreur de chargement.</Alert>}
          <DataGrid
            rows={paysData?.data ?? []}
            columns={PAY_COLS}
            getRowId={(r) => r.uuid}
            loading={paysLoading}
            paginationMode="server"
            rowCount={paysData?.meta?.total ?? 0}
            paginationModel={{ page: payPage, pageSize: 15 }}
            onPaginationModelChange={(m) => setPayPage(m.page)}
            pageSizeOptions={[15]}
            autoHeight
            disableRowSelectionOnClick
          />
        </>
      )}
    </Box>
  );
}
