import { Box, Typography, Container, Grid, Paper, Stack, Button, Card, CardContent } from '@mui/material';
import { useNavigate } from 'react-router-dom';
import DashboardOutlinedIcon from '@mui/icons-material/DashboardOutlined';
import LocalShippingOutlinedIcon from '@mui/icons-material/LocalShippingOutlined';
import GroupOutlinedIcon from '@mui/icons-material/GroupOutlined';
import MapOutlinedIcon from '@mui/icons-material/MapOutlined';
import ReceiptOutlinedIcon from '@mui/icons-material/ReceiptOutlined';
import DescriptionOutlinedIcon from '@mui/icons-material/DescriptionOutlined';
import GpsFixedOutlinedIcon from '@mui/icons-material/GpsFixedOutlined';
import NotificationsOutlinedIcon from '@mui/icons-material/NotificationsOutlined';
import BuildOutlinedIcon from '@mui/icons-material/BuildOutlined';
import WarningAmberOutlinedIcon from '@mui/icons-material/WarningAmberOutlined';
import CheckCircleOutlineIcon from '@mui/icons-material/CheckCircleOutline';
import SpeedOutlinedIcon from '@mui/icons-material/SpeedOutlined';
import SecurityOutlinedIcon from '@mui/icons-material/SecurityOutlined';
import SavingsOutlinedIcon from '@mui/icons-material/SavingsOutlined';
import PublicHeader from '../../components/common/PublicHeader';
import PublicFooter from '../../components/common/PublicFooter';
import Chatbot from '../../components/common/Chatbot';

const COLORS = {
  primary: '#1E3A5A',
  secondary: '#2563EB',
  dark: '#0F172A',
  success: '#22C55E',
  alert: '#F59E0B',
  error: '#EF4444',
  info: '#3B82F6',
  surface: '#F8FAFC',
  text: '#0F172A',
  textLight: '#334155',
  border: '#E2E8F0',
  white: '#FFFFFF',
  grayLight: '#E3E8F0',
};

const FEATURES = [
  { icon: <DashboardOutlinedIcon sx={{ fontSize: 32 }} />, title: 'Tableau de bord', desc: 'Vue d\'ensemble complète de votre flotte en temps réel' },
  { icon: <LocalShippingOutlinedIcon sx={{ fontSize: 32 }} />, title: 'Gestion véhicules', desc: 'Suivi détaillé de chaque véhicule de votre parc' },
  { icon: <GroupOutlinedIcon sx={{ fontSize: 32 }} />, title: 'Gestion chauffeurs', desc: 'Profils, permis et affectations des conducteurs' },
  { icon: <MapOutlinedIcon sx={{ fontSize: 32 }} />, title: 'Missions', desc: 'Planification et suivi des missions en cours' },
  { icon: <ReceiptOutlinedIcon sx={{ fontSize: 32 }} />, title: 'Dépenses', desc: 'Carburant, péages, réparations - tout centralisé' },
  { icon: <WarningAmberOutlinedIcon sx={{ fontSize: 32 }} />, title: 'Incidents', desc: 'Déclaration et suivi des incidents terrain' },
  { icon: <DescriptionOutlinedIcon sx={{ fontSize: 32 }} />, title: 'Documents', desc: 'Gestion des documents et alertes d\'expiration' },
  { icon: <GpsFixedOutlinedIcon sx={{ fontSize: 32 }} />, title: 'GPS Tracking', desc: 'Localisation en temps réel de vos véhicules' },
  { icon: <BuildOutlinedIcon sx={{ fontSize: 32 }} />, title: 'Maintenance', desc: 'Planification préventive et curative' },
  { icon: <NotificationsOutlinedIcon sx={{ fontSize: 32 }} />, title: 'Alertes', desc: 'Notifications intelligentes personnalisables' },
];

const BENEFITS = [
  { icon: <SpeedOutlinedIcon sx={{ fontSize: 40 }} />, title: 'Performance', value: '+40%', desc: 'Amélioration de la productivité de votre flotte' },
  { icon: <SavingsOutlinedIcon sx={{ fontSize: 40 }} />, title: 'Économies', value: '-25%', desc: 'Réduction des coûts opérationnels' },
  { icon: <SecurityOutlinedIcon sx={{ fontSize: 40 }} />, title: 'Conformité', value: '100%', desc: 'Documents à jour et conformes' },
];

export default function LandingPage() {
  const navigate = useNavigate();

  return (
    <Box sx={{ bgcolor: COLORS.surface, color: COLORS.text, fontFamily: 'Poppins, sans-serif' }}>
      <PublicHeader />

      {/* ─────────────────────── HERO SECTION ─────────────────────── */}
      <Box
        sx={{
          background: `linear-gradient(135deg, ${COLORS.dark} 0%, ${COLORS.primary} 100%)`,
          color: COLORS.white,
          py: { xs: 10, md: 14 },
          position: 'relative',
          overflow: 'hidden',
        }}
      >
        {/* Background decoration */}
        <Box sx={{ position: 'absolute', top: -150, right: -150, width: 500, height: 500, borderRadius: '50%', background: 'rgba(37,99,235,0.15)', pointerEvents: 'none' }} />
        <Box sx={{ position: 'absolute', bottom: -100, left: -100, width: 300, height: 300, borderRadius: '50%', background: 'rgba(37,99,235,0.1)', pointerEvents: 'none' }} />
        
        <Container maxWidth="lg" sx={{ position: 'relative' }}>
          <Grid container spacing={6} alignItems="center">
            <Grid item xs={12} md={6}>
              <Typography 
                variant="h1" 
                sx={{ 
                  fontSize: { xs: '2.2rem', md: '3.2rem' }, 
                  fontWeight: 700, 
                  mb: 3, 
                  lineHeight: 1.2,
                  fontFamily: 'Poppins, sans-serif'
                }}
              >
                Maîtrisez votre flotte,<br />
                <Box component="span" sx={{ color: '#93C5FD' }}>éliminez les pertes</Box>
              </Typography>
              <Typography sx={{ fontSize: '1.1rem', color: '#CBD5E1', mb: 4, maxWidth: 480, lineHeight: 1.8 }}>
                FleetControl est la plateforme SaaS qui digitalise les opérations terrain des flottes pour plus de transparence, de contrôle et de performance.
              </Typography>
              <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2}>
                <Button 
                  variant="contained" 
                  size="large" 
                  onClick={() => navigate('/register')}
                  sx={{ 
                    bgcolor: COLORS.secondary, 
                    color: COLORS.white, 
                    fontWeight: 600, 
                    fontSize: '1rem', 
                    px: 4, 
                    py: 1.5, 
                    borderRadius: '8px',
                    '&:hover': { bgcolor: '#1D4ED8' }
                  }}
                >
                  Démarrer gratuitement
                </Button>
                <Button 
                  variant="outlined" 
                  size="large" 
                  onClick={() => navigate('/contact')}
                  sx={{ 
                    borderColor: 'rgba(255,255,255,0.3)', 
                    color: COLORS.white, 
                    fontWeight: 600, 
                    fontSize: '1rem', 
                    px: 4, 
                    py: 1.5, 
                    borderRadius: '8px',
                    '&:hover': { borderColor: COLORS.white, bgcolor: 'rgba(255,255,255,0.1)' }
                  }}
                >
                  Demander une démo
                </Button>
              </Stack>
            </Grid>
            <Grid item xs={12} md={6}>
              <Box 
                sx={{ 
                  bgcolor: 'rgba(255,255,255,0.05)', 
                  borderRadius: '20px', 
                  p: 3, 
                  backdropFilter: 'blur(10px)',
                  border: '1px solid rgba(255,255,255,0.1)'
                }}
              >
                <Box 
                  component="img" 
                  src="/images/dashboard-admin.svg" 
                  alt="Dashboard FleetControl"
                  sx={{ width: '100%', borderRadius: '12px' }}
                />
              </Box>
            </Grid>
          </Grid>
        </Container>
      </Box>

      {/* ─────────────────────── STATS SECTION ─────────────────────── */}
      <Container maxWidth="lg" sx={{ mt: -6, position: 'relative', zIndex: 10 }}>
        <Paper 
          elevation={0} 
          sx={{ 
            p: 4, 
            borderRadius: '16px', 
            border: `1px solid ${COLORS.border}`,
            boxShadow: '0 10px 40px rgba(0,0,0,0.08)'
          }}
        >
          <Grid container spacing={4} alignItems="center" justifyContent="center">
            {[
              { value: '124', label: 'Entreprises clientes' },
              { value: '12.4M', unit: 'km', label: 'Distance parcourue' },
              { value: '8', label: 'Pays couverts' },
              { value: '35', unit: '%', label: 'Économies moyennes' },
            ].map((stat, i) => (
              <Grid item xs={6} md={3} key={i} sx={{ textAlign: 'center' }}>
                <Typography sx={{ fontSize: { xs: '1.8rem', md: '2.5rem' }, fontWeight: 700, color: COLORS.primary }}>
                  {stat.value}<Box component="span" sx={{ fontSize: '1rem', color: COLORS.secondary }}>{stat.unit}</Box>
                </Typography>
                <Typography sx={{ fontSize: '0.85rem', color: COLORS.textLight, fontWeight: 500 }}>{stat.label}</Typography>
              </Grid>
            ))}
          </Grid>
        </Paper>
      </Container>

      {/* ─────────────────────── FEATURES SECTION ─────────────────────── */}
      <Container maxWidth="lg" sx={{ py: { xs: 8, md: 12 } }}>
        <Box sx={{ textAlign: 'center', mb: 6 }}>
          <Typography 
            sx={{ 
              fontSize: '0.85rem', 
              fontWeight: 700, 
              color: COLORS.secondary, 
              textTransform: 'uppercase', 
              letterSpacing: 2, 
              mb: 2 
            }}
          >
            Fonctionnalités
          </Typography>
          <Typography variant="h2" sx={{ fontSize: { xs: '1.8rem', md: '2.5rem' }, fontWeight: 700, color: COLORS.text, mb: 2 }}>
            Tout ce dont vous avez besoin
          </Typography>
          <Typography sx={{ fontSize: '1rem', color: COLORS.textLight, maxWidth: 600, mx: 'auto' }}>
            Une suite complète d'outils pour gérer efficacement votre flotte de véhicules
          </Typography>
        </Box>

        <Grid container spacing={3}>
          {FEATURES.map((feature, i) => (
            <Grid item xs={12} sm={6} md={4} key={i}>
              <Card 
                elevation={0}
                sx={{ 
                  height: '100%',
                  border: `1px solid ${COLORS.border}`,
                  borderRadius: '12px',
                  transition: 'all 0.3s ease',
                  '&:hover': { 
                    boxShadow: '0 8px 30px rgba(0,0,0,0.08)',
                    borderColor: COLORS.secondary,
                    transform: 'translateY(-4px)'
                  }
                }}
              >
                <CardContent sx={{ p: 3 }}>
                  <Box 
                    sx={{ 
                      width: 56, 
                      height: 56, 
                      borderRadius: '12px', 
                      bgcolor: `${COLORS.secondary}15`, 
                      display: 'flex', 
                      alignItems: 'center', 
                      justifyContent: 'center',
                      color: COLORS.secondary,
                      mb: 2
                    }}
                  >
                    {feature.icon}
                  </Box>
                  <Typography sx={{ fontSize: '1rem', fontWeight: 600, color: COLORS.text, mb: 1 }}>
                    {feature.title}
                  </Typography>
                  <Typography sx={{ fontSize: '0.9rem', color: COLORS.textLight, lineHeight: 1.6 }}>
                    {feature.desc}
                  </Typography>
                </CardContent>
              </Card>
            </Grid>
          ))}
        </Grid>
      </Container>

      {/* ─────────────────────── BENEFITS SECTION ─────────────────────── */}
      <Box sx={{ bgcolor: COLORS.dark, py: { xs: 8, md: 12 } }}>
        <Container maxWidth="lg">
          <Grid container spacing={6} alignItems="center">
            <Grid item xs={12} md={5}>
              <Typography 
                sx={{ 
                  fontSize: '0.85rem', 
                  fontWeight: 700, 
                  color: COLORS.secondary, 
                  textTransform: 'uppercase', 
                  letterSpacing: 2, 
                  mb: 2 
                }}
              >
                Pourquoi FleetControl
              </Typography>
              <Typography variant="h2" sx={{ fontSize: { xs: '1.8rem', md: '2.2rem' }, fontWeight: 700, color: COLORS.white, mb: 3 }}>
                Des résultats mesurables pour votre entreprise
              </Typography>
              <Typography sx={{ fontSize: '1rem', color: '#94A3B8', lineHeight: 1.8, mb: 4 }}>
                Nos clients constatent des améliorations significatives dès les premiers mois d'utilisation. Rejoignez-les et transformez la gestion de votre flotte.
              </Typography>
              <Stack spacing={2}>
                {['Réduction des temps morts', 'Optimisation des tournées', 'Suivi en temps réel', 'Rapports automatisés'].map((item, i) => (
                  <Stack key={i} direction="row" alignItems="center" spacing={1.5}>
                    <CheckCircleOutlineIcon sx={{ color: COLORS.success, fontSize: 22 }} />
                    <Typography sx={{ color: COLORS.white, fontSize: '0.95rem' }}>{item}</Typography>
                  </Stack>
                ))}
              </Stack>
            </Grid>
            <Grid item xs={12} md={7}>
              <Grid container spacing={3}>
                {BENEFITS.map((benefit, i) => (
                  <Grid item xs={12} sm={4} key={i}>
                    <Box 
                      sx={{ 
                        textAlign: 'center', 
                        p: 4, 
                        borderRadius: '16px', 
                        bgcolor: 'rgba(255,255,255,0.05)',
                        border: '1px solid rgba(255,255,255,0.1)'
                      }}
                    >
                      <Box sx={{ color: COLORS.secondary, mb: 2 }}>{benefit.icon}</Box>
                      <Typography sx={{ fontSize: '2.5rem', fontWeight: 700, color: COLORS.white, mb: 0.5 }}>
                        {benefit.value}
                      </Typography>
                      <Typography sx={{ fontSize: '1rem', fontWeight: 600, color: COLORS.white, mb: 1 }}>
                        {benefit.title}
                      </Typography>
                      <Typography sx={{ fontSize: '0.85rem', color: '#94A3B8' }}>
                        {benefit.desc}
                      </Typography>
                    </Box>
                  </Grid>
                ))}
              </Grid>
            </Grid>
          </Grid>
        </Container>
      </Box>

      {/* ─────────────────────── APPLICATIONS SECTION ─────────────────────── */}
      <Container maxWidth="lg" sx={{ py: { xs: 8, md: 12 } }}>
        <Box sx={{ textAlign: 'center', mb: 6 }}>
          <Typography 
            sx={{ 
              fontSize: '0.85rem', 
              fontWeight: 700, 
              color: COLORS.secondary, 
              textTransform: 'uppercase', 
              letterSpacing: 2, 
              mb: 2 
            }}
          >
            Applications
          </Typography>
          <Typography variant="h2" sx={{ fontSize: { xs: '1.8rem', md: '2.5rem' }, fontWeight: 700, color: COLORS.text, mb: 2 }}>
            Web & Mobile
          </Typography>
          <Typography sx={{ fontSize: '1rem', color: COLORS.textLight, maxWidth: 600, mx: 'auto' }}>
            Accédez à FleetControl depuis n'importe où, sur n'importe quel appareil
          </Typography>
        </Box>

        <Grid container spacing={4}>
          <Grid item xs={12} md={7}>
            <Paper 
              elevation={0} 
              sx={{ 
                p: 4, 
                borderRadius: '16px', 
                bgcolor: '#F3F4F6', 
                height: '100%',
                display: 'flex',
                flexDirection: 'column',
                justifyContent: 'center'
              }}
            >
              <Typography sx={{ fontSize: '0.8rem', fontWeight: 700, color: COLORS.secondary, textTransform: 'uppercase', letterSpacing: 1, mb: 2 }}>
                Dashboard Web
              </Typography>
              <Typography sx={{ fontSize: '1.5rem', fontWeight: 700, color: COLORS.text, mb: 2 }}>
                Interface complète pour les gestionnaires
              </Typography>
              <Typography sx={{ fontSize: '0.95rem', color: COLORS.textLight, lineHeight: 1.7, mb: 3 }}>
                Tableau de bord interactif, rapports détaillés, gestion des utilisateurs et paramètres avancés. Tout ce dont vous avez besoin pour piloter votre flotte.
              </Typography>
              <Box 
                component="img" 
                src="/images/fleet-illustration.svg" 
                alt="Dashboard"
                sx={{ width: '100%', borderRadius: '12px', mt: 'auto' }}
              />
            </Paper>
          </Grid>
          <Grid item xs={12} md={5}>
            <Paper 
              elevation={0} 
              sx={{ 
                p: 4, 
                borderRadius: '16px', 
                bgcolor: COLORS.primary, 
                color: COLORS.white,
                height: '100%',
                display: 'flex',
                flexDirection: 'column'
              }}
            >
              <Typography sx={{ fontSize: '0.8rem', fontWeight: 700, color: '#93C5FD', textTransform: 'uppercase', letterSpacing: 1, mb: 2 }}>
                Application Mobile
              </Typography>
              <Typography sx={{ fontSize: '1.5rem', fontWeight: 700, mb: 2 }}>
                Pour les chauffeurs sur le terrain
              </Typography>
              <Typography sx={{ fontSize: '0.95rem', color: '#CBD5E1', lineHeight: 1.7, mb: 3 }}>
                Missions, dépenses, incidents, checklists - tout accessible depuis le smartphone. Fonctionne même hors ligne.
              </Typography>
              <Box 
                sx={{ 
                  display: 'flex', 
                  justifyContent: 'center', 
                  mt: 'auto',
                  pt: 2
                }}
              >
                <Box 
                  component="img" 
                  src="/images/app-mobile.svg" 
                  alt="App Mobile"
                  sx={{ maxWidth: 200, height: 'auto' }}
                />
              </Box>
            </Paper>
          </Grid>
        </Grid>
      </Container>

      {/* ─────────────────────── CTA SECTION ─────────────────────── */}
      <Box sx={{ bgcolor: COLORS.secondary, py: { xs: 6, md: 8 } }}>
        <Container maxWidth="md" sx={{ textAlign: 'center' }}>
          <Typography variant="h2" sx={{ fontSize: { xs: '1.6rem', md: '2rem' }, fontWeight: 700, color: COLORS.white, mb: 2 }}>
            Prêt à optimiser votre flotte ?
          </Typography>
          <Typography sx={{ fontSize: '1rem', color: 'rgba(255,255,255,0.8)', mb: 4, maxWidth: 500, mx: 'auto' }}>
            Commencez gratuitement pendant 14 jours. Aucune carte de crédit requise.
          </Typography>
          <Stack direction={{ xs: 'column', sm: 'row' }} spacing={2} justifyContent="center">
            <Button 
              variant="contained" 
              size="large" 
              onClick={() => navigate('/register')}
              sx={{ 
                bgcolor: COLORS.white, 
                color: COLORS.secondary, 
                fontWeight: 700, 
                px: 4, 
                py: 1.5, 
                borderRadius: '8px',
                '&:hover': { bgcolor: '#F1F5F9' }
              }}
            >
              Créer un compte gratuit
            </Button>
            <Button 
              variant="outlined" 
              size="large" 
              onClick={() => navigate('/contact')}
              sx={{ 
                borderColor: 'rgba(255,255,255,0.5)', 
                color: COLORS.white, 
                fontWeight: 600, 
                px: 4, 
                py: 1.5, 
                borderRadius: '8px',
                '&:hover': { borderColor: COLORS.white, bgcolor: 'rgba(255,255,255,0.1)' }
              }}
            >
              Contacter l'équipe
            </Button>
          </Stack>
        </Container>
      </Box>

      <PublicFooter />
      <Chatbot />
    </Box>
  );
}
