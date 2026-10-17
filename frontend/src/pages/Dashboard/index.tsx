import {
  Grid, Card, CardContent, Typography, Box, CircularProgress, Alert,
  LinearProgress, Chip, Divider, Button, Table, TableBody, TableCell,
  TableHead, TableRow,
} from '@mui/material';
import DirectionsCarIcon  from '@mui/icons-material/DirectionsCar';
import MapIcon            from '@mui/icons-material/Map';
import ReceiptIcon        from '@mui/icons-material/Receipt';
import WarningAmberIcon   from '@mui/icons-material/WarningAmber';
import TrendingUpIcon     from '@mui/icons-material/TrendingUp';
import PeopleIcon         from '@mui/icons-material/People';
import ErrorOutlineIcon   from '@mui/icons-material/ErrorOutline';
import FileDownloadIcon   from '@mui/icons-material/FileDownload';
import {
  BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip,
  ResponsiveContainer, PieChart, Pie, Cell,
} from 'recharts';
import {
  useDashboardStats, useExpensesByVehicle,
  useIncidentsByType, useExpiringDocuments,
} from '@/hooks/useDashboard';
import { exportService } from '@/services/exportService';
import { useSnackbar } from 'notistack';
import type { ExpiringDocument } from '@/types';

const INCIDENT_COLORS = ['#EF4444', '#F59E0B', '#2563EB', '#22C55E', '#9b59b6', '#17a2b8'];

const DOC_STATUS_COLOR: Record<ExpiringDocument['status'], string> = {
  expired: '#EF4444', critical: '#F59E0B', warning: '#22C55E',
};

// ── StatCard ─────────────────────────────────────────────────────────────────
interface StatCardProps {
  title: string; value: string | number; subtitle?: string;
  icon: React.ReactNode; iconBg: string; progress?: number; progressColor?: string;
}

function StatCard({ title, value, subtitle, icon, iconBg, progress, progressColor }: StatCardProps) {
  return (
    <Card sx={{ height: '100%' }}>
      <CardContent sx={{ p: 2.5, '&:last-child': { pb: 2.5 } }}>
        <Box sx={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', mb: 1.5 }}>
          <Box>
            <Typography sx={{ color: '#8e9bb0', mb: 0.5, fontSize: 11, fontWeight: 600, textTransform: 'uppercase', letterSpacing: 0.5 }}>
              {title}
            </Typography>
            <Typography variant="h4" sx={{ fontWeight: 700, color: '#2f3640', lineHeight: 1 }}>{value}</Typography>
            {subtitle && <Typography variant="caption" color="text.secondary" sx={{ mt: 0.5, display: 'block' }}>{subtitle}</Typography>}
          </Box>
          <Box sx={{ p: 1.2, borderRadius: 2, bgcolor: iconBg, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>{icon}</Box>
        </Box>
        {progress !== undefined && (
          <>
            <LinearProgress variant="determinate" value={Math.min(100, progress)}
              sx={{ height: 4, borderRadius: 2, bgcolor: '#e8ecef', '& .MuiLinearProgress-bar': { bgcolor: progressColor ?? '#2563EB', borderRadius: 2 } }} />
            <Typography variant="caption" color="text.secondary" sx={{ mt: 0.5, display: 'block' }}>{progress.toFixed(0)}% utilisés</Typography>
          </>
        )}
      </CardContent>
    </Card>
  );
}

// ── Main ─────────────────────────────────────────────────────────────────────
export default function DashboardPage() {
  const { enqueueSnackbar } = useSnackbar();
  const { data: stats, isLoading, isError } = useDashboardStats();
  const { data: expByVehicle = [], isLoading: expLoading } = useExpensesByVehicle();
  const { data: incByType = [], isLoading: incLoading } = useIncidentsByType();
  const { data: expiringDocs = [] } = useExpiringDocuments();

  const handleExport = async (resource: 'missions' | 'expenses' | 'vehicles') => {
    try {
      await exportService.download(resource);
      enqueueSnackbar(`Export téléchargé.`, { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de l\'export.', { variant: 'error' });
    }
  };

  if (isLoading) return (
    <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: 300 }}>
      <CircularProgress size={32} />
    </Box>
  );
  if (isError) return <Alert severity="error">Impossible de charger les statistiques.</Alert>;

  const vehicleUsage = stats?.total_vehicles
    ? ((stats.total_vehicles - stats.available_vehicles) / stats.total_vehicles) * 100 : 0;

  const fleetPieData = [
    { name: 'Disponible', value: stats?.available_vehicles ?? 0, color: '#22C55E' },
    { name: 'En mission',  value: stats?.on_mission_vehicles ?? 0, color: '#2563EB' },
    { name: 'Maintenance', value: stats?.maintenance_vehicles ?? 0, color: '#F59E0B' },
  ].filter(d => d.value > 0);

  return (
    <Box>
      {/* Header */}
      <Box sx={{ mb: 3, display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', flexWrap: 'wrap', gap: 1 }}>
        <Box>
          <Typography variant="h5" fontWeight={700} color="#2f3640">Tableau de bord</Typography>
          <Typography variant="body2" color="text.secondary">Vue d'ensemble de votre flotte</Typography>
        </Box>
        <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
          {(['missions', 'expenses', 'vehicles'] as const).map((r) => (
            <Button key={r} size="small" variant="outlined" startIcon={<FileDownloadIcon />}
              onClick={() => handleExport(r)} sx={{ fontSize: 12, textTransform: 'none' }}>
              {r === 'missions' ? 'Missions' : r === 'expenses' ? 'Dépenses' : 'Véhicules'}
            </Button>
          ))}
        </Box>
      </Box>

      {/* Alertes */}
      {(stats?.expired_documents ?? 0) > 0 && (
        <Alert severity="error" icon={<ErrorOutlineIcon />} sx={{ mb: 2, borderRadius: 2 }}>
          <strong>{stats!.expired_documents} document(s) expiré(s)</strong> — action immédiate requise.
        </Alert>
      )}
      {(stats?.expiring_documents_30 ?? 0) > 0 && (
        <Alert severity="warning" icon={<WarningAmberIcon />}
          action={<Button size="small" href="/documents" color="warning">Voir →</Button>}
          sx={{ mb: 3, borderRadius: 2 }}>
          <strong>{stats!.expiring_documents_30} document(s)</strong> expirent dans les 30 jours.
        </Alert>
      )}

      {/* KPIs */}
      <Grid container spacing={2.5} mb={3}>
        <Grid item xs={12} sm={6} xl={3}>
          <StatCard title="Véhicules" value={stats?.total_vehicles ?? 0}
            subtitle={`${stats?.available_vehicles ?? 0} disponibles`}
            icon={<DirectionsCarIcon sx={{ color: '#2563EB', fontSize: 22 }} />}
            iconBg="rgba(37,99,235,0.1)" progress={vehicleUsage} progressColor="#2563EB" />
        </Grid>
        <Grid item xs={12} sm={6} xl={3}>
          <StatCard title="Chauffeurs" value={stats?.total_drivers ?? 0}
            subtitle={`${stats?.active_missions ?? 0} en mission`}
            icon={<PeopleIcon sx={{ color: '#22C55E', fontSize: 22 }} />}
            iconBg="rgba(34,197,94,0.1)" />
        </Grid>
        <Grid item xs={12} sm={6} xl={3}>
          <StatCard title="Missions actives"
            value={(stats?.active_missions ?? 0) + (stats?.planned_missions ?? 0)}
            subtitle={`${stats?.active_missions ?? 0} en cours · ${stats?.planned_missions ?? 0} planifiées`}
            icon={<MapIcon sx={{ color: '#F59E0B', fontSize: 22 }} />}
            iconBg="rgba(245,158,11,0.1)" />
        </Grid>
        <Grid item xs={12} sm={6} xl={3}>
          <StatCard title="Dépenses ce mois"
            value={`${(stats?.total_expenses_month ?? 0).toLocaleString('fr-FR', { maximumFractionDigits: 0 })} XAF`}
            subtitle={`${stats?.incidents_this_month ?? 0} incidents`}
            icon={<ReceiptIcon sx={{ color: '#EF4444', fontSize: 22 }} />}
            iconBg="rgba(239,68,68,0.1)" />
        </Grid>
      </Grid>

      {/* Charts */}
      <Grid container spacing={2.5} mb={3}>
        <Grid item xs={12} lg={8}>
          <Card>
            <CardContent sx={{ p: 2.5, '&:last-child': { pb: 2.5 } }}>
              <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 2 }}>
                <Box>
                  <Typography fontWeight={700} color="#2f3640" fontSize={14}>Dépenses par véhicule</Typography>
                  <Typography variant="caption" color="text.secondary">Top 10 (mois en cours)</Typography>
                </Box>
                <TrendingUpIcon sx={{ color: '#22C55E', fontSize: 20 }} />
              </Box>
              {expLoading ? <CircularProgress size={24} /> :
                expByVehicle.length === 0 ? <Typography variant="body2" color="text.secondary">Aucune dépense ce mois.</Typography> : (
                  <ResponsiveContainer width="100%" height={200}>
                    <BarChart data={expByVehicle.slice(0, 10)} margin={{ top: 0, right: 10, left: -20, bottom: 0 }}>
                      <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                      <XAxis dataKey="plate_number" tick={{ fontSize: 11 }} />
                      <YAxis tick={{ fontSize: 11 }} />
                      <Tooltip formatter={(v: number) => [`${v.toLocaleString('fr-FR')} XAF`, 'Total']} />
                      <Bar dataKey="total" fill="#2563EB" radius={[4, 4, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                )}
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} lg={4}>
          <Card sx={{ height: '100%' }}>
            <CardContent sx={{ p: 2.5, '&:last-child': { pb: 2.5 } }}>
              <Typography fontWeight={700} color="#2f3640" fontSize={14} mb={1}>Répartition flotte</Typography>
              {fleetPieData.length > 0 ? (
                <>
                  <ResponsiveContainer width="100%" height={160}>
                    <PieChart>
                      <Pie data={fleetPieData} cx="50%" cy="50%" outerRadius={65} dataKey="value">
                        {fleetPieData.map((entry, i) => <Cell key={i} fill={entry.color} />)}
                      </Pie>
                      <Tooltip />
                    </PieChart>
                  </ResponsiveContainer>
                  <Divider sx={{ my: 1 }} />
                  {fleetPieData.map((item) => (
                    <Box key={item.name} sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 0.5 }}>
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <Box sx={{ width: 8, height: 8, borderRadius: '50%', bgcolor: item.color }} />
                        <Typography variant="caption" color="text.secondary">{item.name}</Typography>
                      </Box>
                      <Typography variant="caption" fontWeight={700} color="#2f3640">{item.value}</Typography>
                    </Box>
                  ))}
                </>
              ) : (
                <Box sx={{ height: 160, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                  <Typography variant="body2" color="text.secondary">Aucun véhicule</Typography>
                </Box>
              )}
            </CardContent>
          </Card>
        </Grid>
      </Grid>

      {/* Incidents + Documents */}
      <Grid container spacing={2.5}>
        <Grid item xs={12} lg={5}>
          <Card>
            <CardContent sx={{ p: 2.5, '&:last-child': { pb: 2.5 } }}>
              <Typography fontWeight={700} color="#2f3640" fontSize={14} mb={2}>Incidents par type</Typography>
              {incLoading ? <CircularProgress size={24} /> :
                incByType.length === 0 ? <Typography variant="body2" color="text.secondary">Aucun incident ce mois.</Typography> : (
                  <ResponsiveContainer width="100%" height={160}>
                    <BarChart data={incByType} layout="vertical" margin={{ top: 0, right: 10, left: 10, bottom: 0 }}>
                      <XAxis type="number" tick={{ fontSize: 11 }} />
                      <YAxis dataKey="type" type="category" tick={{ fontSize: 11 }} width={110} />
                      <Tooltip />
                      <Bar dataKey="count" radius={[0, 4, 4, 0]}>
                        {incByType.map((_, i) => <Cell key={i} fill={INCIDENT_COLORS[i % INCIDENT_COLORS.length]} />)}
                      </Bar>
                    </BarChart>
                  </ResponsiveContainer>
                )}
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} lg={7}>
          <Card>
            <CardContent sx={{ p: 2.5, '&:last-child': { pb: 2.5 } }}>
              <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 2 }}>
                <Typography fontWeight={700} color="#2f3640" fontSize={14}>Documents à surveiller</Typography>
                <Button size="small" href="/documents" sx={{ fontSize: 11 }}>Voir tout</Button>
              </Box>
              {expiringDocs.length === 0 ? (
                <Typography variant="body2" color="text.secondary">Aucun document expirant.</Typography>
              ) : (
                <Table size="small">
                  <TableHead>
                    <TableRow>
                      <TableCell sx={{ fontWeight: 700, fontSize: 12 }}>Type</TableCell>
                      <TableCell sx={{ fontWeight: 700, fontSize: 12 }}>Entité</TableCell>
                      <TableCell sx={{ fontWeight: 700, fontSize: 12 }}>Expiration</TableCell>
                      <TableCell sx={{ fontWeight: 700, fontSize: 12 }}>Statut</TableCell>
                    </TableRow>
                  </TableHead>
                  <TableBody>
                    {expiringDocs.slice(0, 6).map((doc) => (
                      <TableRow key={doc.uuid} hover>
                        <TableCell sx={{ fontSize: 12 }}>{doc.type}</TableCell>
                        <TableCell sx={{ fontSize: 12 }}>{doc.entity} #{doc.entity_id}</TableCell>
                        <TableCell sx={{ fontSize: 12 }}>{doc.expiry_date}</TableCell>
                        <TableCell>
                          <Chip size="small"
                            label={doc.status === 'expired' ? 'Expiré' : `J-${doc.days_left}`}
                            sx={{ bgcolor: DOC_STATUS_COLOR[doc.status] + '20', color: DOC_STATUS_COLOR[doc.status], fontWeight: 700, fontSize: 11, height: 20 }} />
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              )}
            </CardContent>
          </Card>
        </Grid>
      </Grid>
    </Box>
  );
}

