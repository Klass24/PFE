import { Box, Button, Card, CardContent, Container, Stack, TextField, Typography } from '@mui/material';
import PublicHeader from '../../components/common/PublicHeader';
import PublicFooter from '../../components/common/PublicFooter';

export default function ContactPage() {
  return (
    <Box sx={{ minHeight: '100vh', bgcolor: '#f4f8fb' }}>
      <PublicHeader />

      <Box
        sx={{
          py: { xs: 6, md: 9 },
          background:
            'linear-gradient(120deg, #0f2237 0%, #1e3957 45%, #2c5d89 100%)',
          color: '#fff',
        }}
      >
        <Container maxWidth="lg">
          <Typography variant="h3" sx={{ fontWeight: 800, mb: 2 }}>
            Contactez notre equipe
          </Typography>
          <Typography sx={{ color: '#d2e0ee', maxWidth: 760, lineHeight: 1.8 }}>
            Vous avez besoin d une demonstration, d un accompagnement au deploiement ou d une integration
            specifique ? Notre equipe vous repond rapidement.
          </Typography>
        </Container>
      </Box>

      <Container maxWidth="lg" sx={{ mt: -6 }}>
        <Stack direction={{ xs: 'column', md: 'row' }} spacing={3} alignItems="stretch">
          <Card sx={{ flex: 1, borderRadius: 3 }}>
            <CardContent sx={{ p: 3.5 }}>
              <Typography variant="h5" sx={{ fontWeight: 700, mb: 2 }}>
                Envoyer un message
              </Typography>
              <Stack spacing={2}>
                <TextField label="Nom complet" fullWidth />
                <TextField label="Email professionnel" fullWidth />
                <TextField label="Entreprise" fullWidth />
                <TextField label="Message" fullWidth multiline minRows={5} />
                <Button variant="contained" size="large">Envoyer</Button>
              </Stack>
            </CardContent>
          </Card>

          <Card sx={{ width: { xs: '100%', md: 360 }, borderRadius: 3 }}>
            <CardContent sx={{ p: 3.5 }}>
              <Typography variant="h6" sx={{ fontWeight: 700, mb: 2 }}>Informations directes</Typography>
              <Stack spacing={1.4}>
                <Typography><strong>Email:</strong> contact@fleetcontrol.app</Typography>
                <Typography><strong>Telephone:</strong> +242 06 000 00 00</Typography>
                <Typography><strong>Adresse:</strong> Brazzaville, Congo</Typography>
                <Typography><strong>Disponibilite:</strong> Dimanche a Jeudi, 9h-18h</Typography>
              </Stack>
            </CardContent>
          </Card>
        </Stack>
      </Container>

      <PublicFooter />
    </Box>
  );
}
