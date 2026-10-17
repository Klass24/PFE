import { Box, Card, CardContent, Chip, Container, Stack, Typography } from '@mui/material';
import TrackChangesIcon from '@mui/icons-material/TrackChanges';
import PublicHeader from '../../components/common/PublicHeader';
import PublicFooter from '../../components/common/PublicFooter';

const pillars = [
  {
    title: 'Vision metier',
    desc: 'Rendre les operations de flotte lisibles et pilotables en temps reel.',
  },
  {
    title: 'Execution terrain',
    desc: 'Connecter chauffeurs, gestionnaires et direction avec des workflows simples.',
  },
  {
    title: 'Performance durable',
    desc: 'Reduire les couts, les retards et les incidents grace a la donnee.',
  },
];

export default function AboutPage() {
  return (
    <Box sx={{ minHeight: '100vh', bgcolor: '#f3f6f9' }}>
      <PublicHeader />

      <Container maxWidth="lg" sx={{ py: { xs: 6, md: 10 } }}>
        <Chip icon={<TrackChangesIcon />} label="A propos de FleetControl" color="primary" sx={{ mb: 2 }} />
        <Typography variant="h3" sx={{ fontWeight: 800, mb: 2, maxWidth: 800 }}>
          Une plateforme concue pour les operations de flotte en croissance
        </Typography>
        <Typography sx={{ color: '#53657a', lineHeight: 1.9, maxWidth: 920 }}>
          FleetControl centralise le suivi des vehicules, des missions, des incidents, de la maintenance et
          des documents reglementaires. Notre objectif est de fournir une interface claire, rapide et fiable
          pour tous les acteurs de la chaine logistique.
        </Typography>

        <Stack direction={{ xs: 'column', md: 'row' }} spacing={3} sx={{ mt: 5 }}>
          {pillars.map((item) => (
            <Card key={item.title} sx={{ flex: 1, borderRadius: 3 }}>
              <CardContent sx={{ p: 3 }}>
                <Typography variant="h6" sx={{ fontWeight: 700, mb: 1.2 }}>{item.title}</Typography>
                <Typography sx={{ color: '#5c7087', lineHeight: 1.8 }}>{item.desc}</Typography>
              </CardContent>
            </Card>
          ))}
        </Stack>
      </Container>

      <PublicFooter />
    </Box>
  );
}
