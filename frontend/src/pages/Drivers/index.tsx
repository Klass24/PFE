import { useRef, useState } from 'react';
import {
  Box, Button, Typography, Dialog, DialogTitle, DialogContent, DialogActions,
  TextField, Alert, Chip, IconButton, Tooltip, CircularProgress, FormControl, InputLabel, Select, MenuItem,
  FormControlLabel, Checkbox,
} from '@mui/material';
import { DataGrid, type GridColDef } from '@mui/x-data-grid';
import AddIcon from '@mui/icons-material/Add';
import DeleteIcon from '@mui/icons-material/Delete';
import EditIcon from '@mui/icons-material/Edit';
import WarningAmberIcon from '@mui/icons-material/WarningAmber';
import FileDownloadIcon from '@mui/icons-material/FileDownload';
import FileUploadIcon from '@mui/icons-material/FileUpload';
import { useForm } from 'react-hook-form';
import { useSnackbar } from 'notistack';
import { useDrivers, useCreateDriver, useUpdateDriver, useDeleteDriver } from '@/hooks/useDrivers';
import { useExport } from '@/hooks/useExport';
import { useImport } from '@/hooks/useImport';
import { useAllPermissions } from '@/hooks/usePermissions';
import { useAuthStore } from '@/store/authStore';
import type { Driver } from '@/types';

interface CreateDriverDto {
  license_number: string;
  license_expiry: string;
  phone?: string;
  status?: 'available' | 'on_mission' | 'inactive';
  user_id?: number;
  user?: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    is_active?: boolean;
  };
  permissions?: string[];
}

function isExpiringSoon(dateStr: string | null): boolean {
  if (!dateStr) return false;
  const diff = new Date(dateStr).getTime() - Date.now();
  return diff > 0 && diff < 30 * 24 * 60 * 60 * 1000;
}

const STATUS_COLORS: Record<string, 'success' | 'warning' | 'default'> = {
  available: 'success', on_mission: 'warning', inactive: 'default',
};

const STATUS_LABELS: Record<string, string> = {
  available: 'Disponible', on_mission: 'En mission', inactive: 'Inactif',
};

export default function DriversPage() {
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState(10);
  const [openForm, setOpenForm] = useState(false);
  const [editDriver, setEditDriver] = useState<Driver | null>(null);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [nameFilter, setNameFilter] = useState('');
  const [licenseFilter, setLicenseFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const importInputRef = useRef<HTMLInputElement>(null);
  const { enqueueSnackbar } = useSnackbar();
  const authUser = useAuthStore((s) => s.user);
  const canManagePermissions = authUser?.role === 'admin' || authUser?.role === 'super_admin';

  const queryParams: Record<string, string> = { page: String(page + 1), per_page: String(pageSize) };
  if (dateFrom) queryParams.from = dateFrom;
  if (dateTo)   queryParams.to   = dateTo;
  if (nameFilter) queryParams.name = nameFilter;
  if (licenseFilter) queryParams.license_number = licenseFilter;
  if (statusFilter) queryParams.status = statusFilter;
  const { data, isLoading, isError } = useDrivers(queryParams);
  const createMutation = useCreateDriver();
  const updateMutation = useUpdateDriver();
  const deleteMutation = useDeleteDriver();
  const exportMutation = useExport();
  const importMutation = useImport();
  const { data: permissionsData } = useAllPermissions(canManagePermissions && openForm && !editDriver);
  const [selectedPermissions, setSelectedPermissions] = useState<string[]>([]);

  const { register, handleSubmit, reset, formState: { errors } } = useForm<CreateDriverDto>();

  const handleOpen = (driver?: Driver) => {
    if (driver) {
      setEditDriver(driver);
      reset(driver as unknown as CreateDriverDto);
      setSelectedPermissions([]);
    } else {
      setEditDriver(null);
      reset({
        status: 'available',
        user: { name: '', email: '', password: '', password_confirmation: '', is_active: true },
      } as unknown as CreateDriverDto);
      setSelectedPermissions([]);
    }
    setOpenForm(true);
  };

  const handleClose = () => {
    setOpenForm(false);
    setEditDriver(null);
    setSelectedPermissions([]);
    reset({});
  };

  const onSubmit = async (formData: CreateDriverDto) => {
    try {
      if (editDriver) {
        await updateMutation.mutateAsync({
          uuid: editDriver.uuid,
          data: {
            license_number: formData.license_number,
            license_expiry: formData.license_expiry,
            phone: formData.phone,
            status: formData.status,
          },
        });
        enqueueSnackbar('Chauffeur mis à jour.', { variant: 'success' });
      } else {
        await createMutation.mutateAsync({
          ...formData,
          permissions: canManagePermissions ? selectedPermissions : undefined,
        });
        enqueueSnackbar('Chauffeur créé.', { variant: 'success' });
      }
      handleClose();
    } catch (err: unknown) {
      enqueueSnackbar(typeof err === 'string' ? err : 'Erreur lors de la sauvegarde.', { variant: 'error' });
    }
  };

  const handleDelete = async (uuid: string) => {
    if (!window.confirm('Supprimer ce chauffeur ?')) return;
    try {
      await deleteMutation.mutateAsync(uuid);
      enqueueSnackbar('Chauffeur supprimé.', { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de la suppression.', { variant: 'error' });
    }
  };

  const handleExport = async () => {
    try {
      await exportMutation.mutateAsync({
        resource: 'drivers',
        params: {
          ...(dateFrom ? { from: dateFrom } : {}),
          ...(dateTo ? { to: dateTo } : {}),
          ...(nameFilter ? { name: nameFilter } : {}),
          ...(licenseFilter ? { license_number: licenseFilter } : {}),
          ...(statusFilter ? { status: statusFilter } : {}),
        },
      });
      enqueueSnackbar('Export des chauffeurs lancé.', { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'export.', { variant: 'error' });
    }
  };

  const handleImportFile = async (file: File) => {
    try {
      const res = await importMutation.mutateAsync({ resource: 'drivers', file });
      enqueueSnackbar(`${res.imported} chauffeur(s) importé(s).`, { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'import.', { variant: 'error' });
    }
  };

  const columns: GridColDef[] = [
    { field: 'name', headerName: 'Nom', flex: 1, valueGetter: (_, row: Driver) => row.user?.name ?? '—' },
    { field: 'license_number', headerName: 'N° Permis', flex: 1 },
    {
      field: 'license_expiry', headerName: 'Exp. Permis', width: 160,
      renderCell: (params) => (
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5 }}>
          {isExpiringSoon(params.value) && <WarningAmberIcon fontSize="small" color="warning" />}
          <span>{params.value ? new Date(params.value).toLocaleDateString('fr-FR') : '—'}</span>
        </Box>
      ),
    },
    { field: 'phone', headerName: 'Téléphone', width: 150 },
    {
      field: 'status', headerName: 'Statut', width: 140,
      renderCell: (params) => (
        <Chip label={STATUS_LABELS[params.value] ?? params.value} color={STATUS_COLORS[params.value] ?? 'default'} size="small" />
      ),
    },
    {
      field: 'actions', headerName: 'Actions', width: 120, sortable: false,
      renderCell: (params) => (
        <Box>
          <Tooltip title="Modifier">
            <IconButton size="small" onClick={() => handleOpen(params.row)}><EditIcon fontSize="small" /></IconButton>
          </Tooltip>
          <Tooltip title="Supprimer">
            <IconButton size="small" color="error" onClick={() => handleDelete(params.row.uuid)}><DeleteIcon fontSize="small" /></IconButton>
          </Tooltip>
        </Box>
      ),
    },
  ];

  if (isError) return <Alert severity="error">Impossible de charger les chauffeurs.</Alert>;

  const groupedPermissions = (permissionsData?.data ?? {}) as Record<string, string[]>;
  const allPermissions = Object.values(groupedPermissions).flat();

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2, flexWrap: 'wrap', gap: 1 }}>
        <Typography variant="h5" fontWeight="bold">Chauffeurs</Typography>
        <Box sx={{ display: 'flex', gap: 1, alignItems: 'center' }}>
          <TextField size="small" label="Nom" value={nameFilter}
            onChange={e => { setNameFilter(e.target.value); setPage(0); }} sx={{ width: 150 }} />
          <TextField size="small" label="N° Permis" value={licenseFilter}
            onChange={e => { setLicenseFilter(e.target.value); setPage(0); }} sx={{ width: 140 }} />
          <FormControl size="small" sx={{ minWidth: 140 }}>
            <InputLabel>Statut</InputLabel>
            <Select label="Statut" value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(0); }}>
              <MenuItem value="">Tous</MenuItem>
              <MenuItem value="available">Disponible</MenuItem>
              <MenuItem value="on_mission">En mission</MenuItem>
              <MenuItem value="inactive">Inactif</MenuItem>
            </Select>
          </FormControl>
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
          <Button variant="contained" startIcon={<AddIcon />} onClick={() => handleOpen()}>
            Ajouter un chauffeur
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
        autoHeight
        disableRowSelectionOnClick
      />

      <Dialog open={openForm} onClose={handleClose} maxWidth="sm" fullWidth>
        <DialogTitle>{editDriver ? 'Modifier le chauffeur' : 'Nouveau chauffeur'}</DialogTitle>
        <Box component="form" onSubmit={handleSubmit(onSubmit)}>
          <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            {!editDriver && (
              <>
                <Typography variant="subtitle2" color="text.secondary">Compte utilisateur du chauffeur</Typography>
                <TextField
                  label="Nom"
                  fullWidth
                  {...register('user.name', { required: true })}
                  error={!!errors.user?.name}
                />
                <TextField
                  label="Email"
                  type="email"
                  fullWidth
                  {...register('user.email', { required: true })}
                  error={!!errors.user?.email}
                />
                <TextField
                  label="Mot de passe"
                  type="password"
                  fullWidth
                  {...register('user.password', { required: true, minLength: 8 })}
                  error={!!errors.user?.password}
                />
                <TextField
                  label="Confirmation mot de passe"
                  type="password"
                  fullWidth
                  {...register('user.password_confirmation', { required: true })}
                  error={!!errors.user?.password_confirmation}
                />
                <FormControlLabel
                  control={<Checkbox defaultChecked {...register('user.is_active')} />}
                  label="Compte actif"
                />

                {canManagePermissions && (
                  <FormControl fullWidth>
                    <InputLabel>Permissions personnalisées (optionnel)</InputLabel>
                    <Select
                      multiple
                      label="Permissions personnalisées (optionnel)"
                      value={selectedPermissions}
                      onChange={(e) => setSelectedPermissions(e.target.value as string[])}
                      renderValue={(selected) => (selected as string[]).join(', ')}
                    >
                      {allPermissions.map((p) => (
                        <MenuItem key={p} value={p}>{p}</MenuItem>
                      ))}
                    </Select>
                  </FormControl>
                )}
              </>
            )}

            <TextField label="N° Permis" fullWidth {...register('license_number', { required: true })} error={!!errors.license_number} />
            <TextField label="Expiration du permis" type="date" fullWidth InputLabelProps={{ shrink: true }}
              {...register('license_expiry', { required: true })} error={!!errors.license_expiry} />
            <TextField label="Téléphone" fullWidth {...register('phone')} />
            <FormControl fullWidth>
              <InputLabel>Statut</InputLabel>
              <Select label="Statut" defaultValue="available" {...register('status')}>
                <MenuItem value="available">Disponible</MenuItem>
                <MenuItem value="on_mission">En mission</MenuItem>
                <MenuItem value="inactive">Inactif</MenuItem>
              </Select>
            </FormControl>
          </DialogContent>
          <DialogActions>
            <Button onClick={handleClose}>Annuler</Button>
            <Button type="submit" variant="contained" disabled={createMutation.isPending || updateMutation.isPending}>
              {createMutation.isPending || updateMutation.isPending ? <CircularProgress size={20} /> : 'Enregistrer'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>
    </Box>
  );
}
