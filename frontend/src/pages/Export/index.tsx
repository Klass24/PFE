import { useRef, useState } from 'react';
import {
  Box, Typography, Grid, Card, CardContent, CardActions,
  Button, TextField, CircularProgress, ToggleButtonGroup, ToggleButton,
  Divider, Alert, Chip, List, ListItem, ListItemText, Paper,
} from '@mui/material';
import FileDownloadIcon   from '@mui/icons-material/FileDownload';
import FileUploadIcon     from '@mui/icons-material/FileUpload';
import MapIcon            from '@mui/icons-material/Map';
import ReceiptIcon        from '@mui/icons-material/Receipt';
import WarningIcon        from '@mui/icons-material/Warning';
import DirectionsCarIcon  from '@mui/icons-material/DirectionsCar';
import PersonIcon         from '@mui/icons-material/Person';
import DescriptionIcon    from '@mui/icons-material/Description';
import { useSnackbar }    from 'notistack';
import { exportService, type ExportResource, type ExportFormat } from '@/services/exportService';
import { type ImportResource } from '@/services/importService';
import { useImport } from '@/hooks/useImport';

/* ─── Export cards config ─────────────────────────────────────────────── */

const EXPORT_CARDS: { resource: ExportResource; label: string; description: string; icon: React.ReactNode; color: string }[] = [
  {
    resource: 'vehicles',
    label: 'Véhicules',
    description: 'Inventaire du parc : immatriculation, marque, modèle, kilométrage…',
    icon: <DirectionsCarIcon sx={{ fontSize: 32, color: '#F59E0B' }} />,
    color: 'rgba(245,158,11,0.08)',
  },
  {
    resource: 'drivers',
    label: 'Chauffeurs',
    description: 'Liste des chauffeurs avec permis, téléphone et statut.',
    icon: <PersonIcon sx={{ fontSize: 32, color: '#1E3A5A' }} />,
    color: 'rgba(30,58,90,0.08)',
  },
  {
    resource: 'missions',
    label: 'Missions',
    description: 'Historique des missions (origine, destination, statut, coût…)',
    icon: <MapIcon sx={{ fontSize: 32, color: '#2563EB' }} />,
    color: 'rgba(37,99,235,0.08)',
  },
  {
    resource: 'expenses',
    label: 'Dépenses',
    description: 'Dépenses (carburant, péage, réparation…) avec litres et kilométrage.',
    icon: <ReceiptIcon sx={{ fontSize: 32, color: '#22C55E' }} />,
    color: 'rgba(34,197,94,0.08)',
  },
  {
    resource: 'incidents',
    label: 'Incidents',
    description: 'Registre des incidents avec type, localisation et synchronisation.',
    icon: <WarningIcon sx={{ fontSize: 32, color: '#EF4444' }} />,
    color: 'rgba(239,68,68,0.08)',
  },
  {
    resource: 'documents',
    label: 'Documents',
    description: 'Documents (assurance, carte grise, contrôle technique…) et leur expiration.',
    icon: <DescriptionIcon sx={{ fontSize: 32, color: '#3B82F6' }} />,
    color: 'rgba(59,130,246,0.08)',
  },
];

/* ─── Import cards config ─────────────────────────────────────────────── */

const IMPORT_CARDS: { resource: ImportResource; label: string; accept: string; templateHeaders: string }[] = [
  { resource: 'vehicles',  label: 'Véhicules',  accept: '.csv,.xlsx,.xls', templateHeaders: 'plate_number;brand;model;year;type;fuel_type;mileage;status' },
  { resource: 'drivers',   label: 'Chauffeurs',  accept: '.csv,.xlsx,.xls', templateHeaders: 'name;email;license_number;license_expiry;phone' },
  { resource: 'expenses',  label: 'Dépenses',    accept: '.csv,.xlsx,.xls', templateHeaders: 'mission_uuid;type;amount;liters;mileage;notes' },
  { resource: 'incidents', label: 'Incidents',    accept: '.csv,.xlsx,.xls', templateHeaders: 'mission_uuid;type;description;latitude;longitude' },
];

/* ─── Page component ──────────────────────────────────────────────────── */

export default function ExportPage() {
  const { enqueueSnackbar } = useSnackbar();
  const [exportLoading, setExportLoading] = useState<ExportResource | null>(null);
  const [format, setFormat] = useState<ExportFormat>('csv');
  const [filters, setFilters] = useState<Record<ExportResource, { from: string; to: string }>>({
    vehicles:  { from: '', to: '' },
    drivers:   { from: '', to: '' },
    missions:  { from: '', to: '' },
    expenses:  { from: '', to: '' },
    incidents: { from: '', to: '' },
    documents: { from: '', to: '' },
    maintenance: { from: '', to: '' },
    users: { from: '', to: '' },
  });
  const [importResult, setImportResult] = useState<{ resource: string; imported: number; errors: string[] } | null>(null);

  const importMutation = useImport();
  const fileInputRefs = useRef<Record<string, HTMLInputElement | null>>({});

  /* ── Export handler ─────────────────────────────────────────────────── */
  const handleDownload = async (resource: ExportResource) => {
    setExportLoading(resource);
    try {
      const { from, to } = filters[resource];
      const params: Record<string, string> = {};
      if (from) params.from = from;
      if (to)   params.to   = to;
      await exportService.download(resource, Object.keys(params).length ? params : undefined, format);
      enqueueSnackbar(`Export ${resource} (${format.toUpperCase()}) téléchargé.`, { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'export.', { variant: 'error' });
    } finally {
      setExportLoading(null);
    }
  };

  const setFilter = (resource: ExportResource, field: 'from' | 'to', value: string) => {
    setFilters(prev => ({ ...prev, [resource]: { ...prev[resource], [field]: value } }));
  };

  /* ── Import handler ─────────────────────────────────────────────────── */
  const handleImport = async (resource: ImportResource, file: File) => {
    try {
      const result = await importMutation.mutateAsync({ resource, file });
      setImportResult({ resource, ...result });
      enqueueSnackbar(`${result.imported} enregistrement(s) importé(s) pour ${resource}.`, { variant: 'success' });
    } catch (err) {
      enqueueSnackbar(typeof err === 'string' ? err : 'Erreur lors de l\'import.', { variant: 'error' });
    }
  };

  const downloadTemplate = (headers: string, name: string) => {
    const bom = '\uFEFF';
    const blob = new Blob([bom + headers + '\n'], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `template_${name}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
  };

  return (
    <Box>
      {/* ── Header ────────────────────────────────────────────────────── */}
      <Box mb={3} display="flex" justifyContent="space-between" alignItems="center" flexWrap="wrap" gap={2}>
        <Box>
          <Typography variant="h5" fontWeight={700} color="#2f3640">Export &amp; Import</Typography>
          <Typography variant="body2" color="text.secondary">Télécharger ou importer vos données en CSV / XLSX</Typography>
        </Box>
        <ToggleButtonGroup
          value={format}
          exclusive
          onChange={(_e, v) => v && setFormat(v)}
          size="small"
        >
          <ToggleButton value="csv">CSV</ToggleButton>
          <ToggleButton value="xlsx">XLSX</ToggleButton>
        </ToggleButtonGroup>
      </Box>

      {/* ── Export section ────────────────────────────────────────────── */}
      <Typography variant="h6" fontWeight={600} mb={2} color="#2f3640">
        <FileDownloadIcon sx={{ verticalAlign: 'middle', mr: 1 }} />
        Exporter
      </Typography>

      <Grid container spacing={2.5} mb={4}>
        {EXPORT_CARDS.map(({ resource, label, description, icon, color }) => (
          <Grid item xs={12} sm={6} lg={4} key={resource}>
            <Card sx={{ height: '100%', display: 'flex', flexDirection: 'column' }}>
              <CardContent sx={{ flex: 1, p: 2.5 }}>
                <Box sx={{ width: 56, height: 56, borderRadius: 2, bgcolor: color, display: 'flex', alignItems: 'center', justifyContent: 'center', mb: 1.5 }}>
                  {icon}
                </Box>
                <Typography variant="subtitle1" fontWeight={700} color="#2f3640" gutterBottom>{label}</Typography>
                <Typography variant="body2" color="text.secondary" mb={2}>{description}</Typography>
                <Box sx={{ display: 'flex', gap: 1 }}>
                  <TextField size="small" type="date" label="Du" value={filters[resource].from}
                    onChange={e => setFilter(resource, 'from', e.target.value)}
                    InputLabelProps={{ shrink: true }} fullWidth />
                  <TextField size="small" type="date" label="Au" value={filters[resource].to}
                    onChange={e => setFilter(resource, 'to', e.target.value)}
                    InputLabelProps={{ shrink: true }} fullWidth />
                </Box>
              </CardContent>
              <CardActions sx={{ px: 2.5, pb: 2.5, pt: 0 }}>
                <Button fullWidth variant="contained" size="medium" disableElevation
                  startIcon={exportLoading === resource ? <CircularProgress size={16} color="inherit" /> : <FileDownloadIcon />}
                  onClick={() => handleDownload(resource)}
                  disabled={exportLoading === resource}
                >
                  {format.toUpperCase()}
                </Button>
              </CardActions>
            </Card>
          </Grid>
        ))}
      </Grid>

      <Divider sx={{ mb: 4 }} />

      {/* ── Import section ────────────────────────────────────────────── */}
      <Typography variant="h6" fontWeight={600} mb={2} color="#2f3640">
        <FileUploadIcon sx={{ verticalAlign: 'middle', mr: 1 }} />
        Importer
      </Typography>

      <Grid container spacing={2.5} mb={3}>
        {IMPORT_CARDS.map(({ resource, label, accept, templateHeaders }) => (
          <Grid item xs={12} sm={6} lg={3} key={resource}>
            <Card sx={{ height: '100%' }}>
              <CardContent sx={{ p: 2.5 }}>
                <Typography variant="subtitle1" fontWeight={700} gutterBottom>{label}</Typography>
                <Typography variant="body2" color="text.secondary" mb={2}>
                  Importer depuis un fichier CSV ou XLSX.
                </Typography>
                <Button size="small" variant="text" onClick={() => downloadTemplate(templateHeaders, resource)} sx={{ mb: 1 }}>
                  Télécharger le modèle CSV
                </Button>
              </CardContent>
              <CardActions sx={{ px: 2.5, pb: 2.5, pt: 0, display: 'flex', gap: 1 }}>
                <input
                  type="file"
                  accept={accept}
                  hidden
                  ref={el => { fileInputRefs.current[resource] = el; }}
                  onChange={e => {
                    const file = e.target.files?.[0];
                    if (file) handleImport(resource, file);
                    e.target.value = '';
                  }}
                />
                <Button fullWidth variant="outlined" disableElevation
                  startIcon={importMutation.isPending ? <CircularProgress size={16} /> : <FileUploadIcon />}
                  onClick={() => fileInputRefs.current[resource]?.click()}
                  disabled={importMutation.isPending}
                >
                  Importer
                </Button>
              </CardActions>
            </Card>
          </Grid>
        ))}
      </Grid>

      {/* ── Import result ─────────────────────────────────────────────── */}
      {importResult && (
        <Paper variant="outlined" sx={{ p: 2, mb: 2 }}>
          <Box display="flex" alignItems="center" gap={1} mb={1}>
            <Typography variant="subtitle2" fontWeight={700}>Résultat import « {importResult.resource} »</Typography>
            <Chip label={`${importResult.imported} importé(s)`} color="success" size="small" />
            {importResult.errors.length > 0 && (
              <Chip label={`${importResult.errors.length} erreur(s)`} color="error" size="small" />
            )}
          </Box>
          {importResult.errors.length > 0 && (
            <List dense sx={{ maxHeight: 200, overflow: 'auto' }}>
              {importResult.errors.map((err, i) => (
                <ListItem key={i} disablePadding>
                  <ListItemText primary={<Alert severity="warning" sx={{ py: 0 }}>{err}</Alert>} />
                </ListItem>
              ))}
            </List>
          )}
        </Paper>
      )}
    </Box>
  );
}
