import { Accordion, AccordionDetails, AccordionSummary, Box, Container, Typography } from '@mui/material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import PublicHeader from '../../components/common/PublicHeader';
import PublicFooter from '../../components/common/PublicFooter';

const faqs = [
  {
    q: 'FleetControl est-il adapte aux PME ?',
    a: 'Oui. Le plan Starter permet de demarrer rapidement et les plans superieurs accompagnent la croissance.',
  },
  {
    q: 'Peut-on utiliser l application mobile sans reseau ?',
    a: 'Oui. Le mode offline-first enregistre les donnees localement et synchronise des que le reseau revient.',
  },
  {
    q: 'Quels modules sont inclus ?',
    a: 'Vehicules, chauffeurs, missions, documents, incidents, maintenance, exports et tableaux de bord.',
  },
  {
    q: 'Comment obtenir une demo ?',
    a: 'Rendez-vous sur la page Contact et envoyez votre demande. Notre equipe repond sous 24h ouvrables.',
  },
];

export default function FAQPage() {
  return (
    <Box sx={{ minHeight: '100vh', bgcolor: '#f7fafc' }}>
      <PublicHeader />
      <Container maxWidth="md" sx={{ py: { xs: 6, md: 9 } }}>
        <Typography variant="h3" sx={{ fontWeight: 800, mb: 1.5 }}>
          Questions frequentes
        </Typography>
        <Typography sx={{ color: '#5c7087', mb: 4 }}>
          Les reponses aux questions les plus courantes sur FleetControl.
        </Typography>
        {faqs.map((item) => (
          <Accordion key={item.q} sx={{ mb: 1.5, borderRadius: 2, '&:before': { display: 'none' } }}>
            <AccordionSummary expandIcon={<ExpandMoreIcon />}>
              <Typography sx={{ fontWeight: 600 }}>{item.q}</Typography>
            </AccordionSummary>
            <AccordionDetails>
              <Typography sx={{ color: '#5c7087' }}>{item.a}</Typography>
            </AccordionDetails>
          </Accordion>
        ))}
      </Container>
      <PublicFooter />
    </Box>
  );
}
