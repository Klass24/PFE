import { useRef, useState } from 'react';
import {
  Box, Button, Typography, Dialog, DialogTitle, DialogContent, DialogActions,
  TextField, Select, MenuItem, FormControl, InputLabel, Alert, Chip,
  CircularProgress, IconButton, Tooltip, Autocomplete,
} from '@mui/material';
import { DataGrid, type GridColDef } from '@mui/x-data-grid';
import AddIcon from '@mui/icons-material/Add';
import DeleteIcon from '@mui/icons-material/Delete';
import EditIcon from '@mui/icons-material/Edit';
import FileDownloadIcon from '@mui/icons-material/FileDownload';
import FileUploadIcon from '@mui/icons-material/FileUpload';
import { useForm, Controller } from 'react-hook-form';
import { useSnackbar } from 'notistack';
import {
  useVehicles, useCreateVehicle, useUpdateVehicle, useDeleteVehicle,
  useVehicleBrands, useVehicleModels,
} from '@/hooks/useVehicles';
import { useExport } from '@/hooks/useExport';
import { useImport } from '@/hooks/useImport';
import type { Vehicle, CreateVehicleDto, VehicleType } from '@/types';

const VEHICLE_TYPE_LABELS: Record<VehicleType, string> = {
  car: 'Voiture', truck: 'Camion', van: 'Fourgon', motorcycle: 'Moto', bus: 'Bus', other: 'Autre',
};

const STATUS_COLORS: Record<string, 'success' | 'warning' | 'error' | 'default'> = {
  available: 'success', on_mission: 'warning', maintenance: 'error', inactive: 'default',
};

const STATUS_LABELS: Record<string, string> = {
  available: 'Disponible', on_mission: 'En mission', maintenance: 'Maintenance', inactive: 'Inactif',
};

export default function VehiclesPage() {
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState(10);
  const [openForm, setOpenForm] = useState(false);
  const [editVehicle, setEditVehicle] = useState<Vehicle | null>(null);
  const [selectedBrand, setSelectedBrand] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [plateFilter, setPlateFilter] = useState('');
  const [brandFilter, setBrandFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const importInputRef = useRef<HTMLInputElement>(null);
  const { enqueueSnackbar } = useSnackbar();

  const queryParams: Record<string, string> = { page: String(page + 1), per_page: String(pageSize) };
  if (dateFrom) queryParams.from = dateFrom;
  if (dateTo)   queryParams.to   = dateTo;
  if (plateFilter) queryParams.plate_number = plateFilter;
  if (brandFilter) queryParams.brand = brandFilter;
  if (statusFilter) queryParams.status = statusFilter;
  const { data, isLoading, isError } = useVehicles(queryParams);
  const { data: brandsData, isLoading: brandsLoading } = useVehicleBrands();
  const { data: modelsData } = useVehicleModels(selectedBrand);
  const createMutation = useCreateVehicle();
  const updateMutation = useUpdateVehicle();
  const deleteMutation = useDeleteVehicle();
  const exportMutation = useExport();
  const importMutation = useImport();

  const brands: string[] = brandsData?.data ?? [];
  const models: string[] = modelsData?.data ?? [];

  const { register, handleSubmit, reset, control, setValue, formState: { errors } } = useForm<CreateVehicleDto>();

  const handleOpen = (vehicle?: Vehicle) => {
    if (vehicle) {
      setEditVehicle(vehicle);
      setSelectedBrand(vehicle.brand ?? '');
      reset({
        plate_number: vehicle.plate_number,
        chassis_number: vehicle.chassis_number ?? '',
        type: vehicle.type ?? 'car',
        brand: vehicle.brand,
        model: vehicle.model,
        year: vehicle.year,
        mileage: vehicle.mileage,
        fuel_type: vehicle.fuel_type,
        status: vehicle.status,
      } as CreateVehicleDto);
    } else {
      setEditVehicle(null);
      setSelectedBrand('');
      reset({});
    }
    setOpenForm(true);
  };

  const handleClose = () => {
    setOpenForm(false);
    setSelectedBrand('');
    reset({});
    setEditVehicle(null);
  };

  const onSubmit = async (formData: CreateVehicleDto) => {
    try {
      if (editVehicle) {
        await updateMutation.mutateAsync({ uuid: editVehicle.uuid, data: formData });
        enqueueSnackbar('Véhicule mis à jour.', { variant: 'success' });
      } else {
        await createMutation.mutateAsync(formData);
        enqueueSnackbar('Véhicule créé.', { variant: 'success' });
      }
      handleClose();
    } catch (err: unknown) {
      // 422 with company errors = quota dépassé
      if (err && typeof err === 'object' && 'company' in err) {
        enqueueSnackbar('Quota de véhicules atteint pour votre abonnement.', { variant: 'error' });
      } else {
        enqueueSnackbar(typeof err === 'string' ? err : 'Erreur lors de la sauvegarde.', { variant: 'error' });
      }
    }
  };

  const handleDelete = async (uuid: string) => {
    if (!window.confirm('Supprimer ce véhicule ?')) return;
    try {
      await deleteMutation.mutateAsync(uuid);
      enqueueSnackbar('Véhicule supprimé.', { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de la suppression.', { variant: 'error' });
    }
  };

  const handleExport = async () => {
    try {
      await exportMutation.mutateAsync({
        resource: 'vehicles',
        params: {
          ...(dateFrom ? { from: dateFrom } : {}),
          ...(dateTo ? { to: dateTo } : {}),
          ...(plateFilter ? { plate_number: plateFilter } : {}),
          ...(brandFilter ? { brand: brandFilter } : {}),
          ...(statusFilter ? { status: statusFilter } : {}),
        },
      });
      enqueueSnackbar('Export des véhicules lancé.', { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'export.', { variant: 'error' });
    }
  };

  const handleImportFile = async (file: File) => {
    try {
      const res = await importMutation.mutateAsync({ resource: 'vehicles', file });
      enqueueSnackbar(`${res.imported} véhicule(s) importé(s).`, { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'import.', { variant: 'error' });
    }
  };

  const columns: GridColDef[] = [
    { field: 'plate_number', headerName: 'Immatriculation', flex: 1 },
    { field: 'brand', headerName: 'Marque', flex: 1 },
    { field: 'model', headerName: 'Modèle', flex: 1 },
    { field: 'type', headerName: 'Type', width: 110, valueFormatter: (v: VehicleType) => VEHICLE_TYPE_LABELS[v] ?? v },
    { field: 'year', headerName: 'Année', width: 80 },
    { field: 'mileage', headerName: 'Kilométrage', width: 130, valueFormatter: (v) => `${Number(v).toLocaleString()} km` },
    { field: 'fuel_type', headerName: 'Carburant', width: 110 },
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

  if (isError) return <Alert severity="error">Impossible de charger les véhicules.</Alert>;

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2, flexWrap: 'wrap', gap: 1 }}>
        <Typography variant="h5" fontWeight="bold">Véhicules</Typography>
        <Box sx={{ display: 'flex', gap: 1, alignItems: 'center' }}>
          <TextField size="small" label="Immatriculation" value={plateFilter}
            onChange={e => { setPlateFilter(e.target.value); setPage(0); }} sx={{ width: 160 }} />
          <TextField size="small" label="Marque" value={brandFilter}
            onChange={e => { setBrandFilter(e.target.value); setPage(0); }} sx={{ width: 130 }} />
          <FormControl size="small" sx={{ minWidth: 140 }}>
            <InputLabel>Statut</InputLabel>
            <Select label="Statut" value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(0); }}>
              <MenuItem value="">Tous</MenuItem>
              <MenuItem value="available">Disponible</MenuItem>
              <MenuItem value="on_mission">En mission</MenuItem>
              <MenuItem value="maintenance">Maintenance</MenuItem>
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
            Ajouter un véhicule
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
        <DialogTitle>{editVehicle ? 'Modifier le véhicule' : 'Nouveau véhicule'}</DialogTitle>
        <Box component="form" onSubmit={handleSubmit(onSubmit)}>
          <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            <TextField label="Immatriculation" fullWidth {...register('plate_number', { required: true })} error={!!errors.plate_number} />
            <TextField label="N° Châssis" fullWidth {...register('chassis_number')} />

            {/* ── Marque ──────────────────────────────────────────────────── */}
            <Controller
              name="brand"
              control={control}
              rules={{ required: true }}
              render={({ field }) => (
                <Autocomplete
                  options={brands}
                  loading={brandsLoading}
                  value={field.value ?? null}
                  onChange={(_, val) => {
                    field.onChange(val ?? '');
                    setSelectedBrand(val ?? '');
                    setValue('model', ''); // reset modèle quand marque change
                  }}
                  freeSolo
                  renderInput={(params) => (
                    <TextField
                      {...params}
                      label="Marque *"
                      error={!!errors.brand}
                      InputProps={{
                        ...params.InputProps,
                        endAdornment: (
                          <>
                            {brandsLoading ? <CircularProgress size={14} /> : null}
                            {params.InputProps.endAdornment}
                          </>
                        ),
                      }}
                    />
                  )}
                />
              )}
            />

            {/* ── Modèle (dépend de la marque) ──────────────────────────── */}
            <Controller
              name="model"
              control={control}
              rules={{ required: true }}
              render={({ field }) => (
                <Autocomplete
                  options={models}
                  value={field.value ?? null}
                  onChange={(_, val) => field.onChange(val ?? '')}
                  disabled={!selectedBrand}
                  freeSolo
                  noOptionsText={selectedBrand ? 'Aucun modèle trouvé' : 'Sélectionnez d\'abord une marque'}
                  renderInput={(params) => (
                    <TextField
                      {...params}
                      label="Modèle *"
                      error={!!errors.model}
                      helperText={!selectedBrand ? 'Choisissez une marque pour voir les modèles' : undefined}
                    />
                  )}
                />
              )}
            />
            <FormControl fullWidth required>
              <InputLabel>Type de véhicule</InputLabel>
              <Controller name="type" control={control} rules={{ required: true }}
                render={({ field }) => (
                  <Select label="Type de véhicule" {...field}>
                    {(Object.entries(VEHICLE_TYPE_LABELS) as [VehicleType, string][]).map(([v, l]) => (
                      <MenuItem key={v} value={v}>{l}</MenuItem>
                    ))}
                  </Select>
                )}
              />
            </FormControl>
            <TextField label="Année" type="number" fullWidth {...register('year', { required: true })} error={!!errors.year} />
            <TextField label="Kilométrage" type="number" fullWidth {...register('mileage')} />
            <FormControl fullWidth>
              <InputLabel>Carburant</InputLabel>
              <Controller
                name="fuel_type"
                control={control}
                rules={{ required: true }}
                render={({ field }) => (
                  <Select label="Carburant" {...field}>
                    <MenuItem value="gasoline">Essence</MenuItem>
                    <MenuItem value="diesel">Diesel</MenuItem>
                    <MenuItem value="electric">Électrique</MenuItem>
                    <MenuItem value="hybrid">Hybride</MenuItem>
                  </Select>
                )}
              />
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
