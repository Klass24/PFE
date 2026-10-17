import { useState, useEffect, useRef } from 'react';
import {
  Box, Button, Typography, Dialog, DialogTitle, DialogContent, DialogActions,
  TextField, Select, MenuItem, FormControl, InputLabel, Alert, Chip,
  CircularProgress, IconButton, Tooltip, ToggleButton, ToggleButtonGroup,
} from '@mui/material';
import { DataGrid, type GridColDef } from '@mui/x-data-grid';
import AddIcon from '@mui/icons-material/Add';
import DeleteIcon from '@mui/icons-material/Delete';
import WarningAmberIcon from '@mui/icons-material/WarningAmber';
import DirectionsCarIcon from '@mui/icons-material/DirectionsCar';
import PersonIcon from '@mui/icons-material/Person';
import UploadFileIcon from '@mui/icons-material/UploadFile';
import FileDownloadIcon from '@mui/icons-material/FileDownload';
import { useForm, Controller } from 'react-hook-form';
import { useSnackbar } from 'notistack';
import { useDocuments, useCreateDocument, useDeleteDocument } from '@/hooks/useDocuments';
import { useVehicles } from '@/hooks/useVehicles';
import { useDrivers } from '@/hooks/useDrivers';
import { useExport } from '@/hooks/useExport';
import { useImport } from '@/hooks/useImport';
import type { Document, CreateDocumentDto, DocumentableType, Vehicle, Driver } from '@/types';

// ── Types de documents par entité ─────────────────────────────────────────────
const VEHICLE_TYPES: Record<string, string> = {
  registration:       'Carte grise',
  insurance:          'Assurance',
  technical_control:  'Contrôle technique',
  other:              'Autre',
};

const DRIVER_TYPES: Record<string, string> = {
  license: 'Permis de conduire',
  other:   'Autre',
};

const ALL_TYPE_LABELS: Record<string, string> = { ...VEHICLE_TYPES, ...DRIVER_TYPES };

// ── Page principale ───────────────────────────────────────────────────────────
export default function DocumentsPage() {
  const [page, setPage] = useState(0);
  const [openForm, setOpenForm] = useState(false);
  const [entityType, setEntityType] = useState<DocumentableType>('vehicle');
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [pageSize, setPageSize] = useState(10);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [entityFilter, setEntityFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const importListInputRef = useRef<HTMLInputElement>(null);
  const { enqueueSnackbar } = useSnackbar();

  const queryParams: Record<string, string> = { page: String(page + 1), per_page: String(pageSize) };
  if (dateFrom) queryParams.from = dateFrom;
  if (dateTo)   queryParams.to   = dateTo;
  if (typeFilter) queryParams.type = typeFilter;
  if (entityFilter) queryParams.documentable_type = entityFilter;
  if (statusFilter) queryParams.status = statusFilter;
  const { data, isLoading, isError } = useDocuments(queryParams);
  const { data: vehiclesData } = useVehicles({ per_page: '100' });
  const { data: driversData }  = useDrivers({ per_page: '100' });
  const createMutation = useCreateDocument();
  const deleteMutation = useDeleteDocument();
  const exportMutation = useExport();
  const importMutation = useImport();

  const { register, handleSubmit, reset, control, setValue, formState: { errors } } = useForm<CreateDocumentDto>({
    defaultValues: { documentable_type: 'vehicle' },
  });

  useEffect(() => {
    setValue('documentable_type', entityType);
    setValue('documentable_uuid', '');
    setValue('type', entityType === 'vehicle' ? 'registration' : 'license');
  }, [entityType, setValue]);

  const handleClose = () => {
    setOpenForm(false);
    setEntityType('vehicle');
    setSelectedFile(null);
    reset({ documentable_type: 'vehicle' });
  };

  const onSubmit = async (formData: CreateDocumentDto) => {
    if (!selectedFile) {
      enqueueSnackbar('Veuillez sélectionner un fichier.', { variant: 'warning' });
      return;
    }
    try {
      await createMutation.mutateAsync({ ...formData, file: selectedFile });
      enqueueSnackbar('Document enregistré.', { variant: 'success' });
      handleClose();
    } catch (err: unknown) {
      enqueueSnackbar(typeof err === 'string' ? err : 'Erreur lors de l\'enregistrement.', { variant: 'error' });
    }
  };

  const handleDelete = async (uuid: string) => {
    if (!window.confirm('Supprimer ce document ?')) return;
    try {
      await deleteMutation.mutateAsync(uuid);
      enqueueSnackbar('Document supprimé.', { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de la suppression.', { variant: 'error' });
    }
  };

  const handleExport = async () => {
    try {
      await exportMutation.mutateAsync({
        resource: 'documents',
        params: {
          ...(dateFrom ? { from: dateFrom } : {}),
          ...(dateTo ? { to: dateTo } : {}),
          ...(typeFilter ? { type: typeFilter } : {}),
          ...(entityFilter ? { documentable_type: entityFilter } : {}),
          ...(statusFilter ? { status: statusFilter } : {}),
        },
      });
      enqueueSnackbar('Export des documents lancé.', { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'export.', { variant: 'error' });
    }
  };

  const handleListImport = async (file: File) => {
    try {
      const res = await importMutation.mutateAsync({ resource: 'documents', file });
      enqueueSnackbar(`${res.imported} document(s) importé(s).`, { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'import.', { variant: 'error' });
    }
  };

  const vehicles: Vehicle[] = vehiclesData?.data ?? [];
  const drivers: Driver[]   = driversData?.data  ?? [];
  const typeOptions = entityType === 'vehicle' ? VEHICLE_TYPES : DRIVER_TYPES;

  const columns: GridColDef[] = [
    {
      field: 'documentable_type', headerName: 'Entité', width: 120,
      renderCell: (params) => {
        const isVehicle = String(params.value).includes('Vehicle') || params.value === 'vehicle';
        return (
          <Chip
            size="small"
            icon={isVehicle ? <DirectionsCarIcon sx={{ fontSize: 14 }} /> : <PersonIcon sx={{ fontSize: 14 }} />}
            label={isVehicle ? 'Véhicule' : 'Chauffeur'}
            sx={{
              fontSize: 11,
              bgcolor: isVehicle ? 'rgba(37,99,235,0.1)' : 'rgba(34,197,94,0.1)',
              color:   isVehicle ? '#2563EB' : '#22C55E',
            }}
          />
        );
      },
    },
    { field: 'type', headerName: 'Type', width: 170, valueFormatter: (v) => ALL_TYPE_LABELS[v] ?? v },
    {
      field: 'issue_date', headerName: 'Délivrance', width: 120,
      valueFormatter: (v) => v ? new Date(v).toLocaleDateString('fr-FR') : '—',
    },
    {
      field: 'expiry_date', headerName: 'Expiration', width: 140,
      renderCell: (params) => {
        const expired: boolean  = params.row.is_expired;
        const expiring: boolean = params.row.is_expiring_soon;
        return (
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 0.5, color: expired ? 'error.main' : expiring ? 'warning.main' : 'inherit' }}>
            {(expired || expiring) && <WarningAmberIcon fontSize="small" />}
            {params.value ? new Date(params.value).toLocaleDateString('fr-FR') : '—'}
          </Box>
        );
      },
    },
    {
      field: 'status', headerName: 'Statut', width: 140,
      renderCell: (params) => {
        const doc: Document = params.row;
        if (doc.is_expired)      return <Chip label="Expiré"        color="error"   size="small" />;
        if (doc.is_expiring_soon) return <Chip label="Expire bientôt" color="warning" size="small" />;
        return <Chip label="Valide" color="success" size="small" />;
      },
    },
    {
      field: 'alert_sent', headerName: 'Alerte', width: 90,
      renderCell: (params) => params.value
        ? <Chip label="Envoyée" size="small" sx={{ bgcolor: '#e8f5e9', color: '#2e7d32', fontSize: 11 }} />
        : <Chip label="—"       size="small" sx={{ bgcolor: '#f5f5f5', color: '#aaa',    fontSize: 11 }} />,
    },
    {
      field: 'actions', headerName: '', width: 70, sortable: false,
      renderCell: (params) => (
        <Tooltip title="Supprimer">
          <IconButton size="small" color="error" onClick={() => handleDelete(params.row.uuid)}>
            <DeleteIcon fontSize="small" />
          </IconButton>
        </Tooltip>
      ),
    },
  ];

  const expiringCount = (data?.data ?? []).filter((d: Document) => d.is_expiring_soon || d.is_expired).length;

  if (isError) return <Alert severity="error">Impossible de charger les documents.</Alert>;

  return (
    <Box>
      {/* En-tête */}
      <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2, flexWrap: 'wrap', gap: 1 }}>
        <Typography variant="h5" fontWeight="bold">Documents</Typography>
        <Box sx={{ display: 'flex', gap: 1, alignItems: 'center' }}>
          <FormControl size="small" sx={{ minWidth: 150 }}>
            <InputLabel>Type</InputLabel>
            <Select label="Type" value={typeFilter} onChange={e => { setTypeFilter(e.target.value); setPage(0); }}>
              <MenuItem value="">Tous</MenuItem>
              <MenuItem value="registration">Carte grise</MenuItem>
              <MenuItem value="insurance">Assurance</MenuItem>
              <MenuItem value="technical_control">Contrôle technique</MenuItem>
              <MenuItem value="license">Permis</MenuItem>
              <MenuItem value="other">Autre</MenuItem>
            </Select>
          </FormControl>
          <FormControl size="small" sx={{ minWidth: 120 }}>
            <InputLabel>Entité</InputLabel>
            <Select label="Entité" value={entityFilter} onChange={e => { setEntityFilter(e.target.value); setPage(0); }}>
              <MenuItem value="">Toutes</MenuItem>
              <MenuItem value="vehicle">Véhicule</MenuItem>
              <MenuItem value="driver">Chauffeur</MenuItem>
            </Select>
          </FormControl>
          <FormControl size="small" sx={{ minWidth: 130 }}>
            <InputLabel>Statut</InputLabel>
            <Select label="Statut" value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(0); }}>
              <MenuItem value="">Tous</MenuItem>
              <MenuItem value="expired">Expiré</MenuItem>
              <MenuItem value="expiring">Expire bientôt</MenuItem>
              <MenuItem value="valid">Valide</MenuItem>
            </Select>
          </FormControl>
          <TextField size="small" type="date" label="Du" value={dateFrom}
            onChange={e => { setDateFrom(e.target.value); setPage(0); }}
            InputLabelProps={{ shrink: true }} sx={{ width: 160 }} />
          <TextField size="small" type="date" label="Au" value={dateTo}
            onChange={e => { setDateTo(e.target.value); setPage(0); }}
            InputLabelProps={{ shrink: true }} sx={{ width: 160 }} />
          <input
            ref={importListInputRef}
            type="file"
            hidden
            accept=".csv,.xlsx,.xls"
            onChange={e => {
              const file = e.target.files?.[0];
              if (file) void handleListImport(file);
              e.target.value = '';
            }}
          />
          <Button variant="outlined" startIcon={<UploadFileIcon />} onClick={() => importListInputRef.current?.click()}>
            Import
          </Button>
          <Button variant="outlined" startIcon={<FileDownloadIcon />} onClick={handleExport}>
            Export
          </Button>
          <Button variant="contained" startIcon={<AddIcon />} onClick={() => setOpenForm(true)}>
            Nouveau document
          </Button>
        </Box>
      </Box>

      {expiringCount > 0 && (
        <Alert severity="warning" sx={{ mb: 2 }}>
          {expiringCount} document(s) expiré(s) ou expirant bientôt.
        </Alert>
      )}

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
        getRowClassName={(p) => (p.row.is_expired ? 'row-expired' : p.row.is_expiring_soon ? 'row-expiring' : '')}
        sx={{
          '& .row-expired':  { bgcolor: 'rgba(217,83,79,0.06)',  '&:hover': { bgcolor: 'rgba(217,83,79,0.10)' } },
          '& .row-expiring': { bgcolor: 'rgba(240,173,78,0.06)', '&:hover': { bgcolor: 'rgba(240,173,78,0.10)' } },
        }}
      />

      {/* ── Formulaire ── */}
      <Dialog open={openForm} onClose={handleClose} maxWidth="sm" fullWidth>
        <DialogTitle>Nouveau document</DialogTitle>
        <Box component="form" onSubmit={handleSubmit(onSubmit)}>
          <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2.5 }}>

            {/* 1. Entité */}
            <Box>
              <Typography variant="caption" color="text.secondary" fontWeight={600} sx={{ mb: 1, display: 'block' }}>
                Associé à
              </Typography>
              <ToggleButtonGroup
                exclusive value={entityType}
                onChange={(_, v) => { if (v) setEntityType(v as DocumentableType); }}
                size="small" fullWidth
              >
                <ToggleButton value="vehicle" sx={{ gap: 0.5, fontSize: 13 }}>
                  <DirectionsCarIcon fontSize="small" /> Véhicule
                </ToggleButton>
                <ToggleButton value="driver" sx={{ gap: 0.5, fontSize: 13 }}>
                  <PersonIcon fontSize="small" /> Chauffeur
                </ToggleButton>
              </ToggleButtonGroup>
            </Box>

            {/* 2. Sélection véhicule ou chauffeur */}
            <FormControl fullWidth required error={!!errors.documentable_uuid}>
              <InputLabel>{entityType === 'vehicle' ? 'Véhicule' : 'Chauffeur'}</InputLabel>
              <Controller
                name="documentable_uuid"
                control={control}
                rules={{ required: 'Ce champ est requis' }}
                render={({ field }) => (
                  <Select label={entityType === 'vehicle' ? 'Véhicule' : 'Chauffeur'} {...field}>
                    {entityType === 'vehicle'
                      ? vehicles.map((v) => (
                          <MenuItem key={v.uuid} value={v.uuid}>
                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                              <DirectionsCarIcon fontSize="small" sx={{ color: '#2563EB' }} />
                              <span><strong>{v.plate_number}</strong> — {v.brand} {v.model}</span>
                            </Box>
                          </MenuItem>
                        ))
                      : drivers.map((d) => (
                          <MenuItem key={d.uuid} value={d.uuid}>
                            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                              <PersonIcon fontSize="small" sx={{ color: '#22C55E' }} />
                              <span><strong>{d.user?.name ?? '—'}</strong> — {d.license_number}</span>
                            </Box>
                          </MenuItem>
                        ))
                    }
                  </Select>
                )}
              />
            </FormControl>

            {/* 3. Type de document */}
            <FormControl fullWidth required error={!!errors.type}>
              <InputLabel>Type de document</InputLabel>
              <Controller
                name="type"
                control={control}
                rules={{ required: 'Ce champ est requis' }}
                render={({ field }) => (
                  <Select label="Type de document" {...field}>
                    {Object.entries(typeOptions).map(([v, l]) => (
                      <MenuItem key={v} value={v}>{l}</MenuItem>
                    ))}
                  </Select>
                )}
              />
            </FormControl>

            {/* 4. Fichier */}
            <Box>
              <Typography variant="caption" color="text.secondary" fontWeight={600} sx={{ mb: 1, display: 'block' }}>
                Fichier (PDF, JPG, PNG — max 10 Mo) *
              </Typography>
              <input
                ref={fileInputRef}
                type="file"
                accept=".pdf,.jpg,.jpeg,.png"
                style={{ display: 'none' }}
                onChange={(e) => setSelectedFile(e.target.files?.[0] ?? null)}
              />
              <Button
                variant="outlined"
                startIcon={<UploadFileIcon />}
                onClick={() => fileInputRef.current?.click()}
                fullWidth
                sx={{ justifyContent: 'flex-start', textTransform: 'none', color: selectedFile ? 'success.main' : 'text.secondary', borderColor: selectedFile ? 'success.main' : 'divider' }}
              >
                {selectedFile ? selectedFile.name : 'Choisir un fichier…'}
              </Button>
            </Box>

            {/* 5. Dates */}
            <Box sx={{ display: 'flex', gap: 2 }}>
              <TextField label="Date de délivrance" type="date" fullWidth
                InputLabelProps={{ shrink: true }} {...register('issue_date')} />
              <TextField label="Date d'expiration" type="date" fullWidth
                InputLabelProps={{ shrink: true }} {...register('expiry_date')} />
            </Box>

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

