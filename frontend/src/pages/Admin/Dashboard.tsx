import { Box, Typography, Card, CardContent, CircularProgress, Alert, Chip } from '@mui/material';
import BusinessIcon from '@mui/icons-material/Business';
import PeopleIcon from '@mui/icons-material/People';
import BlockIcon from '@mui/icons-material/Block';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import { useAdminStats } from '@/hooks/useAdmin';

export default function AdminDashboardPage() {
  const { data, isLoading, isError } = useAdminStats();

  if (isLoading) return <CircularProgress />;
  if (isError) return <Alert severity="error">Impossible de charger les statistiques.</Alert>;

  const stats = data?.data;
  if (!stats) return null;

  const cards = [
    { label: 'Entreprises', value: stats.total_companies, icon: <BusinessIcon />, color: '#2563EB' },
    { label: 'Actives', value: stats.active_companies, icon: <CheckCircleIcon />, color: '#22C55E' },
    { label: 'Suspendues', value: stats.suspended_companies, icon: <BlockIcon />, color: '#EF4444' },
    { label: 'Total utilisateurs', value: stats.total_users, icon: <PeopleIcon />, color: '#F59E0B' },
  ];

  return (
    <Box>
      <Typography variant="h5" fontWeight="bold" sx={{ mb: 3 }}>Back-office SaaS</Typography>

      <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 2, mb: 3 }}>
        {cards.map((c) => (
          <Card key={c.label}>
            <CardContent sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
              <Box sx={{ bgcolor: c.color, borderRadius: 2, p: 1.2, color: '#fff', display: 'flex' }}>{c.icon}</Box>
              <Box>
                <Typography variant="body2" color="text.secondary">{c.label}</Typography>
                <Typography variant="h5" fontWeight="bold">{c.value}</Typography>
              </Box>
            </CardContent>
          </Card>
        ))}
      </Box>

      <Card sx={{ mb: 3 }}>
        <CardContent>
          <Typography variant="h6" sx={{ mb: 2 }}>Répartition par plan</Typography>
          <Box sx={{ display: 'flex', gap: 2 }}>
            {Object.entries(stats.companies_by_plan).map(([plan, count]) => (
              <Chip key={plan} label={`${plan}: ${count}`} variant="outlined" />
            ))}
          </Box>
        </CardContent>
      </Card>

      <Card>
        <CardContent>
          <Typography variant="h6" sx={{ mb: 2 }}>Entreprises récentes</Typography>
          {stats.recent_companies.map((c) => (
            <Box key={c.uuid} sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', py: 1, borderBottom: '1px solid #eee' }}>
              <Box>
                <Typography fontWeight="bold">{c.name}</Typography>
                <Typography variant="body2" color="text.secondary">{new Date(c.created_at).toLocaleDateString('fr-FR')}</Typography>
              </Box>
              <Box sx={{ display: 'flex', gap: 1 }}>
                <Chip label={c.plan} size="small" />
                <Chip label={c.status === 'active' ? 'Actif' : 'Suspendu'} color={c.status === 'active' ? 'success' : 'error'} size="small" />
              </Box>
            </Box>
          ))}
        </CardContent>
      </Card>
    </Box>
  );
}
