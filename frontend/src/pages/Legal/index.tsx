import { Box, Container, Divider, Typography } from '@mui/material';
import PublicHeader from '../../components/common/PublicHeader';
import PublicFooter from '../../components/common/PublicFooter';

export default function LegalPage() {
  return (
    <Box sx={{ minHeight: '100vh', bgcolor: '#f4f7fa' }}>
      <PublicHeader />
      <Container maxWidth="md" sx={{ py: { xs: 6, md: 9 } }}>
        <Typography variant="h3" sx={{ fontWeight: 800, mb: 3 }}>
          Mentions legales
        </Typography>
        <Typography sx={{ color: '#5c7087', lineHeight: 1.9 }}>
          FleetControl est une plateforme logicielle de gestion de flotte. Les informations presentes sur ce site
          sont fournies a titre informatif et peuvent etre modifiees sans preavis.
        </Typography>
        <Divider sx={{ my: 3 }} />
        <Typography variant="h6" sx={{ fontWeight: 700, mb: 1.2 }}>Editeur</Typography>
        <Typography sx={{ color: '#5c7087', lineHeight: 1.9 }}>
          FleetControl, Congo. Contact: contact@fleetcontrol.app
        </Typography>
        <Divider sx={{ my: 3 }} />
        <Typography variant="h6" sx={{ fontWeight: 700, mb: 1.2 }}>Protection des donnees</Typography>
        <Typography sx={{ color: '#5c7087', lineHeight: 1.9 }}>
          Nous appliquons des mesures de securite techniques et organisationnelles pour proteger les donnees
          de nos utilisateurs et de leurs entreprises.
        </Typography>
      </Container>
      <PublicFooter />
    </Box>
  );
}
