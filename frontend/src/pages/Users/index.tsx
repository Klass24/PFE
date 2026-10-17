import { useRef, useState } from 'react';
import {
  Box, Button, Typography, Dialog, DialogTitle, DialogContent, DialogActions,
  TextField, Select, MenuItem, FormControl, InputLabel, Alert, Chip,
  CircularProgress, IconButton, Tooltip, Switch, FormControlLabel,
} from '@mui/material';
import { DataGrid, type GridColDef } from '@mui/x-data-grid';
import AddIcon from '@mui/icons-material/Add';
import DeleteIcon from '@mui/icons-material/Delete';
import EditIcon from '@mui/icons-material/Edit';
import FileDownloadIcon from '@mui/icons-material/FileDownload';
import FileUploadIcon from '@mui/icons-material/FileUpload';
import { useForm, Controller } from 'react-hook-form';
import { useSnackbar } from 'notistack';
import { useUsers, useCreateUser, useUpdateUser, useDeleteUser } from '@/hooks/useUsers';
import { useExport } from '@/hooks/useExport';
import { useImport } from '@/hooks/useImport';
import { useAuthStore } from '@/store/authStore';
import type { User, CreateUserDto, UserRole } from '@/types';

const ROLE_LABELS: Record<UserRole, string> = {
  admin: 'Administrateur',
  manager: 'Gestionnaire',
  driver: 'Chauffeur',
};

const ROLE_COLORS: Record<UserRole, 'error' | 'warning' | 'info'> = {
  admin: 'error',
  manager: 'warning',
  driver: 'info',
};

export default function UsersPage() {
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState(10);
  const [openForm, setOpenForm] = useState(false);
  const [editUser, setEditUser] = useState<User | null>(null);
  const [nameFilter, setNameFilter] = useState('');
  const [emailFilter, setEmailFilter] = useState('');
  const [roleFilter, setRoleFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const importInputRef = useRef<HTMLInputElement>(null);
  const { enqueueSnackbar } = useSnackbar();
  const authUser = useAuthStore((s) => s.user);
  const isSuperAdmin = authUser?.role === 'super_admin';

  const { data, isLoading, isError } = useUsers({
    page: String(page + 1),
    per_page: String(pageSize),
    ...(nameFilter ? { name: nameFilter } : {}),
    ...(emailFilter ? { email: emailFilter } : {}),
    ...(roleFilter ? { role: roleFilter } : {}),
    ...(statusFilter ? { status: statusFilter } : {}),
  });
  const createMutation = useCreateUser();
  const updateMutation = useUpdateUser();
  const deleteMutation = useDeleteUser();
  const exportMutation = useExport();
  const importMutation = useImport();

  const { register, handleSubmit, reset, control, formState: { errors } } = useForm<CreateUserDto>();

  const handleOpen = (user?: User) => {
    if (user) {
      setEditUser(user);
      reset({ name: user.name, email: user.email, role: user.role, is_active: user.is_active });
    } else {
      setEditUser(null);
      reset({ name: '', email: '', password: '', password_confirmation: '', role: 'driver', is_active: true });
    }
    setOpenForm(true);
  };

  const handleClose = () => {
    setOpenForm(false);
    reset({});
    setEditUser(null);
  };

  const onSubmit = async (formData: CreateUserDto) => {
    try {
      if (editUser) {
        await updateMutation.mutateAsync({ uuid: editUser.uuid, data: formData });
        enqueueSnackbar('Utilisateur mis à jour.', { variant: 'success' });
      } else {
        await createMutation.mutateAsync(formData);
        enqueueSnackbar('Utilisateur créé.', { variant: 'success' });
      }
      handleClose();
    } catch (err: unknown) {
      enqueueSnackbar(typeof err === 'string' ? err : 'Erreur lors de la sauvegarde.', { variant: 'error' });
    }
  };

  const handleDelete = async (uuid: string) => {
    if (!window.confirm('Supprimer cet utilisateur ?')) return;
    try {
      await deleteMutation.mutateAsync(uuid);
      enqueueSnackbar('Utilisateur supprimé.', { variant: 'success' });
    } catch (err: unknown) {
      enqueueSnackbar(typeof err === 'string' ? err : 'Erreur lors de la suppression.', { variant: 'error' });
    }
  };

  const handleExport = async () => {
    try {
      await exportMutation.mutateAsync({
        resource: 'users',
        params: {
          ...(nameFilter ? { name: nameFilter } : {}),
          ...(emailFilter ? { email: emailFilter } : {}),
          ...(roleFilter ? { role: roleFilter } : {}),
          ...(statusFilter ? { status: statusFilter } : {}),
        },
      });
      enqueueSnackbar('Export des utilisateurs lancé.', { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'export.', { variant: 'error' });
    }
  };

  const handleImportFile = async (file: File) => {
    try {
      const res = await importMutation.mutateAsync({ resource: 'users', file });
      enqueueSnackbar(`${res.imported} utilisateur(s) importé(s).`, { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'import.', { variant: 'error' });
    }
  };

  const columns: GridColDef[] = [
    { field: 'name', headerName: 'Nom', flex: 1 },
    { field: 'email', headerName: 'Email', flex: 1.2 },
    {
      field: 'role', headerName: 'Rôle', width: 140,
      renderCell: (params) => (
        <Chip label={ROLE_LABELS[params.value as UserRole] ?? params.value} color={ROLE_COLORS[params.value as UserRole] ?? 'default'} size="small" />
      ),
    },
    ...(isSuperAdmin
      ? [{ field: 'company_name', headerName: 'Entreprise', flex: 1 } as GridColDef]
      : []),
    {
      field: 'is_active', headerName: 'Statut', width: 110,
      renderCell: (params) => (
        <Chip label={params.value ? 'Actif' : 'Inactif'} color={params.value ? 'success' : 'default'} size="small" />
      ),
    },
    { field: 'created_at', headerName: 'Créé le', width: 130, valueFormatter: (v: string) => new Date(v).toLocaleDateString('fr-FR') },
    ...(!isSuperAdmin
      ? [{
          field: 'actions', headerName: 'Actions', width: 120, sortable: false,
          renderCell: (params: any) => (
            <Box>
              <Tooltip title="Modifier">
                <IconButton size="small" onClick={() => handleOpen(params.row)}><EditIcon fontSize="small" /></IconButton>
              </Tooltip>
              <Tooltip title="Supprimer">
                <IconButton size="small" color="error" onClick={() => handleDelete(params.row.uuid)}><DeleteIcon fontSize="small" /></IconButton>
              </Tooltip>
            </Box>
          ),
        } as GridColDef]
      : []),
  ];

  if (isError) return <Alert severity="error">Impossible de charger les utilisateurs.</Alert>;

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2, flexWrap: 'wrap', gap: 1 }}>
        <Typography variant="h5" fontWeight="bold">Utilisateurs</Typography>
        <Box sx={{ display: 'flex', gap: 1, alignItems: 'center', flexWrap: 'wrap' }}>
          <TextField size="small" label="Nom" value={nameFilter}
            onChange={e => { setNameFilter(e.target.value); setPage(0); }} sx={{ width: 130 }} />
          <TextField size="small" label="Email" value={emailFilter}
            onChange={e => { setEmailFilter(e.target.value); setPage(0); }} sx={{ width: 170 }} />
          <FormControl size="small" sx={{ minWidth: 130 }}>
            <InputLabel>Rôle</InputLabel>
            <Select label="Rôle" value={roleFilter} onChange={e => { setRoleFilter(e.target.value); setPage(0); }}>
              <MenuItem value="">Tous</MenuItem>
              <MenuItem value="admin">Administrateur</MenuItem>
              <MenuItem value="manager">Gestionnaire</MenuItem>
              <MenuItem value="driver">Chauffeur</MenuItem>
            </Select>
          </FormControl>
          <FormControl size="small" sx={{ minWidth: 120 }}>
            <InputLabel>Statut</InputLabel>
            <Select label="Statut" value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(0); }}>
              <MenuItem value="">Tous</MenuItem>
              <MenuItem value="active">Actif</MenuItem>
              <MenuItem value="inactive">Inactif</MenuItem>
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
          {!isSuperAdmin && (
            <Button variant="contained" startIcon={<AddIcon />} onClick={() => handleOpen()}>
              Ajouter un utilisateur
            </Button>
          )}
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
        <DialogTitle>{editUser ? "Modifier l'utilisateur" : 'Nouvel utilisateur'}</DialogTitle>
        <Box component="form" onSubmit={handleSubmit(onSubmit)}>
          <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            <TextField label="Nom" fullWidth {...register('name', { required: true })} error={!!errors.name} helperText={errors.name && 'Le nom est obligatoire.'} />
            <TextField label="Email" type="email" fullWidth {...register('email', { required: true })} error={!!errors.email} helperText={errors.email && "L'email est obligatoire."} />
            <TextField
              label="Mot de passe"
              type="password"
              fullWidth
              {...register('password', { required: !editUser, minLength: 8 })}
              error={!!errors.password}
              helperText={editUser ? 'Laisser vide pour ne pas changer' : 'Min. 8 caractères'}
            />
            <TextField label="Confirmer le mot de passe" type="password" fullWidth {...register('password_confirmation')} />
            <FormControl fullWidth required>
              <InputLabel>Rôle</InputLabel>
              <Controller
                name="role"
                control={control}
                rules={{ required: true }}
                render={({ field }) => (
                  <Select label="Rôle" {...field}>
                    <MenuItem value="admin">Administrateur</MenuItem>
                    <MenuItem value="manager">Gestionnaire</MenuItem>
                    <MenuItem value="driver">Chauffeur</MenuItem>
                  </Select>
                )}
              />
            </FormControl>
            {editUser && (
              <Controller
                name="is_active"
                control={control}
                render={({ field }) => (
                  <FormControlLabel
                    control={<Switch checked={!!field.value} onChange={field.onChange} />}
                    label="Compte actif"
                  />
                )}
              />
            )}
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
