import { useRef, useState } from 'react';
import {
  Box, Button, Typography, Dialog, DialogTitle, DialogContent, DialogActions,
  TextField, Select, MenuItem, FormControl, InputLabel, Alert, Chip,
  CircularProgress, IconButton, Tooltip, Step, Stepper, StepLabel,
  Checkbox, FormControlLabel, FormGroup, Divider,
} from '@mui/material';
import { DataGrid, type GridColDef } from '@mui/x-data-grid';
import AddIcon from '@mui/icons-material/Add';
import PlayArrowIcon from '@mui/icons-material/PlayArrow';
import CheckIcon from '@mui/icons-material/Check';
import CloseIcon from '@mui/icons-material/Close';
import AssignmentIcon from '@mui/icons-material/Assignment';
import FileDownloadIcon from '@mui/icons-material/FileDownload';
import FileUploadIcon from '@mui/icons-material/FileUpload';
import { useForm, Controller } from 'react-hook-form';
import { useSnackbar } from 'notistack';
import {
  useMissions, useCreateMission, useStartMission, useCompleteMission, useCancelMission,
} from '@/hooks/useMissions';
import { useVehicles } from '@/hooks/useVehicles';
import { useDrivers } from '@/hooks/useDrivers';
import { useChecklist, useSubmitChecklist, useValidateChecklist, useUploadChecklistPhoto } from '@/hooks/useChecklist';
import { useExport } from '@/hooks/useExport';
import { useImport } from '@/hooks/useImport';
import type { Mission, CreateMissionDto, ChecklistItem, Vehicle, Driver } from '@/types';

const STEPS = ['Planifiée', 'En cours', 'Terminée'];

const STATUS_STEP: Record<string, number> = {
  planned: 0, in_progress: 1, completed: 2, cancelled: -1,
};

const STATUS_COLORS: Record<string, 'default' | 'primary' | 'success' | 'error'> = {
  planned: 'default', in_progress: 'primary', completed: 'success', cancelled: 'error',
};

const STATUS_LABELS: Record<string, string> = {
  planned: 'Planifiée', in_progress: 'En cours', completed: 'Terminée', cancelled: 'Annulée',
};

export default function MissionsPage() {
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState(10);
  const [openForm, setOpenForm] = useState(false);
  const [selectedMission, setSelectedMission] = useState<Mission | null>(null);
  const [checklistMission, setChecklistMission] = useState<Mission | null>(null);
  const [checkedItems, setCheckedItems] = useState<Record<number, boolean>>({});
  const [checklistError, setChecklistError] = useState<string | null>(null);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [originFilter, setOriginFilter] = useState('');
  const [destinationFilter, setDestinationFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const importInputRef = useRef<HTMLInputElement>(null);
  const { enqueueSnackbar } = useSnackbar();

  const queryParams: Record<string, string> = { page: String(page + 1), per_page: String(pageSize) };
  if (dateFrom) queryParams.from = dateFrom;
  if (dateTo)   queryParams.to   = dateTo;
  if (originFilter) queryParams.origin = originFilter;
  if (destinationFilter) queryParams.destination = destinationFilter;
  if (statusFilter) queryParams.status = statusFilter;
  const { data, isLoading, isError } = useMissions(queryParams);
  const { data: vehiclesData } = useVehicles({ per_page: '100' });
  const { data: driversData } = useDrivers({ per_page: '100' });
  const createMutation = useCreateMission();
  const startMutation = useStartMission();
  const completeMutation = useCompleteMission();
  const cancelMutation = useCancelMission();
  const exportMutation = useExport();
  const importMutation = useImport();
  const { data: checklist } = useChecklist(checklistMission?.uuid ?? '');
  const submitChecklist = useSubmitChecklist(checklistMission?.uuid ?? '');
  const validateChecklist = useValidateChecklist(checklistMission?.uuid ?? '');
  const uploadPhoto = useUploadChecklistPhoto(checklistMission?.uuid ?? '');
  const [uploadingItem, setUploadingItem] = useState<number | null>(null);

  const { register, handleSubmit, reset, control, formState: { errors } } = useForm<CreateMissionDto>();

  const vehicles = (vehiclesData?.data ?? []) as Vehicle[];
  const drivers = (driversData?.data ?? []) as Driver[];

  const handleClose = () => { setOpenForm(false); setSelectedMission(null); reset({}); };

  const onSubmit = async (formData: CreateMissionDto) => {
    try {
      await createMutation.mutateAsync({
        ...formData,
        // Le backend exige start_date ; on l'aligne sur la date de départ saisie.
        start_date: formData.start_date || formData.planned_departure,
        end_date: formData.end_date || formData.planned_arrival,
      });
      enqueueSnackbar('Mission créée.', { variant: 'success' });
      handleClose();
    } catch (err: unknown) {
      enqueueSnackbar(typeof err === 'string' ? err : 'Erreur lors de la création.', { variant: 'error' });
    }
  };

  const handleAction = async (action: 'start' | 'complete' | 'cancel', uuid: string) => {
    if (action === 'start') {
      const mission = (data?.data ?? []).find((m: Mission) => m.uuid === uuid);
      if (mission) {
        setChecklistMission(mission);
        setCheckedItems({});
        setChecklistError(null);
        return;
      }
    }
    const map = { start: startMutation, complete: completeMutation, cancel: cancelMutation };
    const labels = { start: 'démarrée', complete: 'terminée', cancel: 'annulée' };
    try {
      await map[action].mutateAsync(uuid);
      enqueueSnackbar(`Mission ${labels[action]}.`, { variant: 'success' });
    } catch (err: unknown) {
      enqueueSnackbar(typeof err === 'string' ? err : 'Erreur lors de l\'action.', { variant: 'error' });
    }
  };

  const handleChecklistSubmit = async () => {
    if (!checklistMission) return;
    setChecklistError(null);
    // Vérifier que tous les items obligatoires sont cochés
    const items: ChecklistItem[] = checklist?.items ?? [];
    const required = items.filter((i) => i.is_required ?? true);
    const allChecked = required.every((i) => !!checkedItems[i.id]);
    if (!allChecked) {
      setChecklistError('Veuillez cocher tous les éléments obligatoires avant de démarrer la mission.');
      return;
    }
    try {
      if (items.length > 0) {
        await submitChecklist.mutateAsync();
        await validateChecklist.mutateAsync();
      }
      await startMutation.mutateAsync(checklistMission.uuid);
      enqueueSnackbar('Checklist validée. Mission démarrée.', { variant: 'success' });
      setChecklistMission(null);
    } catch (err: unknown) {
      type ErrResp = { response?: { data?: { data?: { checklist?: string[]; photos?: string[] } } } };
      const data = (err as ErrResp)?.response?.data?.data;
      const msg =
        data?.photos?.[0] ??
        data?.checklist?.[0] ??
        'Erreur lors de la validation de la checklist.';
      setChecklistError(msg);
    }
  };

  const handlePhotoUpload = async (itemId: number, file: File) => {
    setUploadingItem(itemId);
    try {
      await uploadPhoto.mutateAsync({ itemId, file });
    } catch {
      enqueueSnackbar('Erreur lors de l\'upload de la photo.', { variant: 'error' });
    } finally {
      setUploadingItem(null);
    }
  };

  const handleExport = async () => {
    try {
      await exportMutation.mutateAsync({
        resource: 'missions',
        params: {
          ...(dateFrom ? { from: dateFrom } : {}),
          ...(dateTo ? { to: dateTo } : {}),
          ...(originFilter ? { origin: originFilter } : {}),
          ...(destinationFilter ? { destination: destinationFilter } : {}),
          ...(statusFilter ? { status: statusFilter } : {}),
        },
      });
      enqueueSnackbar('Export des missions lancé.', { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'export.', { variant: 'error' });
    }
  };

  const handleImportFile = async (file: File) => {
    try {
      const res = await importMutation.mutateAsync({ resource: 'missions', file });
      enqueueSnackbar(`${res.imported} mission(s) importée(s).`, { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'import.', { variant: 'error' });
    }
  };

  const columns: GridColDef[] = [
    { field: 'origin', headerName: 'Départ', flex: 1 },
    { field: 'destination', headerName: 'Destination', flex: 1 },
    {
      field: 'planned_departure', headerName: 'Départ prévu', width: 150,
      valueFormatter: (v, row) => (v ?? row.start_date) ? new Date((v ?? row.start_date)).toLocaleDateString('fr-FR') : '—',
    },
    {
      field: 'total_cost', headerName: 'Coût total', width: 120,
      valueFormatter: (v) => v ? `${Number(v).toLocaleString('fr-FR')} XAF` : '—',
    },
    {
      field: 'status', headerName: 'Statut', width: 140,
      renderCell: (params) => (
        <Chip label={STATUS_LABELS[params.value] ?? params.value} color={STATUS_COLORS[params.value] ?? 'default'} size="small" />
      ),
    },
    {
      field: 'checklist_status', headerName: 'Checklist', width: 120,
      renderCell: (params) => {
        const m: Mission = params.row;
        if (!m.checklist) return null;
        const colors: Record<string, string> = { draft: '#888', submitted: '#F59E0B', validated: '#22C55E', rejected: '#EF4444' };
        const c = colors[m.checklist.status] ?? '#888';
        return <AssignmentIcon sx={{ color: c, fontSize: 20 }} titleAccess={m.checklist.status} />;
      },
    },
    {
      field: 'actions', headerName: 'Actions', width: 160, sortable: false,
      renderCell: (params) => {
        const status: string = params.row.status;
        return (
          <Box sx={{ display: 'flex', gap: 0.5 }}>
            {status === 'planned' && (
              <Tooltip title="Démarrer">
                <IconButton size="small" color="primary" onClick={() => handleAction('start', params.row.uuid)}>
                  <PlayArrowIcon fontSize="small" />
                </IconButton>
              </Tooltip>
            )}
            {status === 'in_progress' && (
              <Tooltip title="Terminer">
                <IconButton size="small" color="success" onClick={() => handleAction('complete', params.row.uuid)}>
                  <CheckIcon fontSize="small" />
                </IconButton>
              </Tooltip>
            )}
            {(status === 'planned' || status === 'in_progress') && (
              <Tooltip title="Annuler">
                <IconButton size="small" color="error" onClick={() => handleAction('cancel', params.row.uuid)}>
                  <CloseIcon fontSize="small" />
                </IconButton>
              </Tooltip>
            )}
          </Box>
        );
      },
    },
  ];

  if (isError) return <Alert severity="error">Impossible de charger les missions.</Alert>;

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2, flexWrap: 'wrap', gap: 1 }}>
        <Typography variant="h5" fontWeight="bold">Missions</Typography>
        <Box sx={{ display: 'flex', gap: 1, alignItems: 'center' }}>
          <TextField size="small" label="Origine" value={originFilter}
            onChange={e => { setOriginFilter(e.target.value); setPage(0); }} sx={{ width: 140 }} />
          <TextField size="small" label="Destination" value={destinationFilter}
            onChange={e => { setDestinationFilter(e.target.value); setPage(0); }} sx={{ width: 150 }} />
          <FormControl size="small" sx={{ minWidth: 140 }}>
            <InputLabel>Statut</InputLabel>
            <Select label="Statut" value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(0); }}>
              <MenuItem value="">Tous</MenuItem>
              <MenuItem value="planned">Planifiée</MenuItem>
              <MenuItem value="in_progress">En cours</MenuItem>
              <MenuItem value="completed">Terminée</MenuItem>
              <MenuItem value="cancelled">Annulée</MenuItem>
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
          <Button variant="contained" startIcon={<AddIcon />} onClick={() => setOpenForm(true)}>
            Nouvelle mission
          </Button>
        </Box>
      </Box>

      {selectedMission && (
        <Box sx={{ mb: 3 }}>
          <Stepper activeStep={STATUS_STEP[selectedMission.status] ?? 0}>
            {STEPS.map((label) => <Step key={label}><StepLabel>{label}</StepLabel></Step>)}
          </Stepper>
        </Box>
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
        onRowClick={(p) => setSelectedMission(p.row as Mission)}
        autoHeight
        disableRowSelectionOnClick
      />

      {/* Checklist dialog */}
      <Dialog
        open={!!checklistMission}
        onClose={() => { setChecklistMission(null); setChecklistError(null); }}
        maxWidth="sm"
        fullWidth
        PaperProps={{ sx: { borderRadius: 3 } }}
      >
        <DialogTitle sx={{ pb: 1 }}>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
            <AssignmentIcon color="primary" />
            <Typography variant="h6" fontWeight="bold">Checklist de sécurité</Typography>
          </Box>
          <Typography variant="body2" color="text.secondary" sx={{ mt: 0.5 }}>
            Mission : <strong>{checklistMission?.origin}</strong> → <strong>{checklistMission?.destination}</strong>
          </Typography>
        </DialogTitle>

        <Divider />

        <DialogContent sx={{ pt: 2 }}>
          {checklistError && (
            <Alert severity="error" sx={{ mb: 2 }} onClose={() => setChecklistError(null)}>
              {checklistError}
            </Alert>
          )}

          {checklist?.items?.length ? (
            <>
              <Typography variant="body2" color="text.secondary" sx={{ mb: 1.5 }}>
                Cochez tous les points de sécurité obligatoires avant de démarrer la mission.
              </Typography>
              <FormGroup sx={{ gap: 0.5 }}>
                {checklist.items.map((item: ChecklistItem) => {
                  const required = item.is_required ?? true;
                  const checked = !!checkedItems[item.id];
                  return (
                    <Box
                      key={item.id}
                      sx={{
                        border: 1,
                        borderColor: checked ? 'success.main' : 'divider',
                        borderRadius: 2,
                        px: 1.5,
                        py: 0.5,
                        bgcolor: checked ? 'success.50' : 'background.paper',
                        transition: 'all 0.2s',
                      }}
                    >
                      <FormControlLabel
                        control={
                          <Checkbox
                            checked={checked}
                            onChange={(e) => setCheckedItems(prev => ({ ...prev, [item.id]: e.target.checked }))}
                            color="success"
                          />
                        }
                        label={
                          <Box sx={{ width: '100%' }}>
                            <Typography variant="body2" fontWeight={required ? 600 : 400}>
                              {item.label}
                              {required && <Typography component="span" color="error"> *</Typography>}
                            </Typography>
                            {item.note && (
                              <Typography variant="caption" color="text.secondary">{item.note}</Typography>
                            )}
                          </Box>
                        }
                        sx={{ width: '100%', m: 0 }}
                      />
                      {item.requires_photo && (
                        <Box sx={{ pl: 5, pb: 0.5, display: 'flex', alignItems: 'center', gap: 1 }}>
                          {item.has_photo ? (
                            <Chip label="Photo ✓" color="success" size="small" />
                          ) : (
                            <Chip label="Photo requise" color="warning" size="small" />
                          )}
                          <Button
                            component="label"
                            size="small"
                            variant="outlined"
                            disabled={uploadingItem === item.id}
                          >
                            {uploadingItem === item.id ? <CircularProgress size={14} /> : item.has_photo ? 'Remplacer' : 'Ajouter'}
                            <input
                              type="file"
                              hidden
                              accept="image/*"
                              onChange={(e) => {
                                const f = e.target.files?.[0];
                                if (f) void handlePhotoUpload(item.id, f);
                                e.target.value = '';
                              }}
                            />
                          </Button>
                        </Box>
                      )}
                    </Box>
                  );
                })}
              </FormGroup>
              <Typography variant="caption" color="text.secondary" sx={{ mt: 1.5, display: 'block' }}>
                * Éléments obligatoires
              </Typography>
            </>
          ) : (
            <Box sx={{ textAlign: 'center', py: 4 }}>
              <AssignmentIcon sx={{ fontSize: 48, color: 'text.disabled', mb: 1 }} />
              <Typography color="text.secondary">Aucune checklist requise pour cette mission.</Typography>
              <Typography variant="body2" color="text.secondary">Vous pouvez démarrer directement.</Typography>
            </Box>
          )}
        </DialogContent>

        <Divider />

        <DialogActions sx={{ px: 3, py: 2 }}>
          <Button onClick={() => { setChecklistMission(null); setChecklistError(null); }} color="inherit">
            Annuler
          </Button>
          <Button
            variant="contained"
            color="success"
            startIcon={submitChecklist.isPending || validateChecklist.isPending || startMutation.isPending ? <CircularProgress size={16} color="inherit" /> : <PlayArrowIcon />}
            onClick={handleChecklistSubmit}
            disabled={submitChecklist.isPending || validateChecklist.isPending || startMutation.isPending}
          >
            Valider et démarrer
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog open={openForm} onClose={handleClose} maxWidth="sm" fullWidth>
        <DialogTitle>Nouvelle mission</DialogTitle>
        <Box component="form" onSubmit={handleSubmit(onSubmit)}>
          <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            <TextField label="Origine" fullWidth {...register('origin', { required: true })} error={!!errors.origin} />
            <TextField label="Destination" fullWidth {...register('destination', { required: true })} error={!!errors.destination} />
            <TextField label="Départ prévu" type="datetime-local" fullWidth InputLabelProps={{ shrink: true }}
              {...register('start_date', { required: true })} error={!!errors.start_date} />
            <TextField label="Arrivée prévue" type="datetime-local" fullWidth InputLabelProps={{ shrink: true }}
              {...register('end_date')} />
            <FormControl fullWidth>
              <InputLabel>Véhicule</InputLabel>
              <Controller
                name="vehicle_id"
                control={control}
                rules={{ required: true }}
                render={({ field }) => (
                  <Select
                    label="Véhicule"
                    value={field.value ?? ''}
                    onChange={(e) => field.onChange(Number(e.target.value))}
                  >
                    <MenuItem value="">— Sélectionner —</MenuItem>
                    {vehicles.filter((v) => typeof v.id === 'number').map((v) => (
                      <MenuItem key={v.uuid} value={v.id as number}>
                        {v.plate_number} — {v.brand} {v.model}
                      </MenuItem>
                    ))}
                  </Select>
                )}
              />
            </FormControl>
            <FormControl fullWidth>
              <InputLabel>Chauffeur</InputLabel>
              <Controller
                name="driver_id"
                control={control}
                rules={{ required: true }}
                render={({ field }) => (
                  <Select
                    label="Chauffeur"
                    value={field.value ?? ''}
                    onChange={(e) => field.onChange(Number(e.target.value))}
                  >
                    <MenuItem value="">— Sélectionner —</MenuItem>
                    {drivers.filter((d) => typeof d.id === 'number').map((d) => (
                      <MenuItem key={d.uuid} value={d.id as number}>
                        {(d.user?.name ?? 'Sans nom')} — {d.license_number}
                      </MenuItem>
                    ))}
                  </Select>
                )}
              />
            </FormControl>
            <TextField label="Notes" fullWidth multiline rows={3} {...register('notes')} />
          </DialogContent>
          <DialogActions>
            <Button onClick={handleClose}>Annuler</Button>
            <Button type="submit" variant="contained" disabled={createMutation.isPending}>
              {createMutation.isPending ? <CircularProgress size={20} /> : 'Créer'}
            </Button>
          </DialogActions>
        </Box>
      </Dialog>
    </Box>
  );
}
