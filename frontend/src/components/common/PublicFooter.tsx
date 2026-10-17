/**
 * FleetControl - Public Footer
 * Based on the design charter - Section 01 Logo & 08 Tonalité visuelle
 */

import { Box, Container, Divider, Link as MuiLink, Stack, Typography, Grid, IconButton } from '@mui/material';
import { Link as RouterLink } from 'react-router-dom';
import { Mail, Phone, MapPin } from 'lucide-react';
import LinkedInIcon from '@mui/icons-material/LinkedIn';
import TwitterIcon from '@mui/icons-material/Twitter';
import FacebookIcon from '@mui/icons-material/Facebook';
import { FCLogo } from '../ui/Logo';
import { colors, typography, borderRadius } from '../../theme/design-tokens';

const footerLinks = [
  { label: 'Accueil', to: '/landing' },
  { label: 'À propos', to: '/a-propos' },
  { label: 'Tarifs', to: '/pricing' },
  { label: 'Contact', to: '/contact' },
  { label: 'FAQ', to: '/faq' },
];

const legalLinks = [
  { label: 'Mentions légales', to: '/mentions-legales' },
  { label: 'Politique de confidentialité', to: '/mentions-legales#privacy' },
  { label: 'CGU', to: '/mentions-legales#cgu' },
];

const features = [
  { label: 'Gestion de flotte', to: '/landing#features' },
  { label: 'Suivi en temps réel', to: '/landing#tracking' },
  { label: 'Gestion documentaire', to: '/landing#documents' },
  { label: 'Rapports & Analytics', to: '/landing#analytics' },
];

const socialLinks = [
  { icon: LinkedInIcon, href: '#', label: 'LinkedIn' },
  { icon: TwitterIcon, href: '#', label: 'Twitter' },
  { icon: FacebookIcon, href: '#', label: 'Facebook' },
];

export default function PublicFooter() {
  return (
    <Box
      component="footer"
      sx={{
        mt: 'auto',
        pt: 8,
        pb: 4,
        color: colors.neutral[200],
        background: `
          radial-gradient(circle at 10% 20%, rgba(37, 99, 235, 0.15) 0%, transparent 40%),
          radial-gradient(circle at 90% 80%, rgba(37, 99, 235, 0.1) 0%, transparent 40%),
          linear-gradient(180deg, ${colors.neutral[800]} 0%, ${colors.background.dark} 100%)
        `,
        borderTop: '1px solid rgba(255, 255, 255, 0.05)',
      }}
    >
      <Container maxWidth="lg">
        <Grid container spacing={4}>
          {/* Logo & Description */}
          <Grid item xs={12} md={4}>
            <Box sx={{ maxWidth: 320 }}>
              <FCLogo variant="full" size="md" colorScheme="light" />
              <Typography 
                variant="body2" 
                sx={{ 
                  mt: 2.5,
                  color: colors.neutral[400], 
                  lineHeight: 1.8,
                  fontSize: '0.9375rem',
                }}
              >
                Plateforme SaaS de gestion de flotte intelligente. Maîtrisez vos véhicules, 
                optimisez vos coûts et digitalisez vos opérations terrain.
              </Typography>

              {/* Social Links */}
              <Stack direction="row" spacing={1} mt={3}>
                {socialLinks.map((social) => (
                  <IconButton
                    key={social.label}
                    href={social.href}
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label={social.label}
                    sx={{
                      backgroundColor: 'rgba(255, 255, 255, 0.05)',
                      color: colors.neutral[400],
                      borderRadius: borderRadius.md,
                      transition: 'all 0.2s ease-in-out',
                      '&:hover': {
                        backgroundColor: colors.primary.light,
                        color: '#FFFFFF',
                      },
                    }}
                  >
                    <social.icon sx={{ fontSize: 18 }} />
                  </IconButton>
                ))}
              </Stack>
            </Box>
          </Grid>

          {/* Navigation Links */}
          <Grid item xs={6} sm={4} md={2}>
            <Typography 
              sx={{ 
                color: '#FFFFFF', 
                fontWeight: typography.weights.semiBold,
                mb: 2,
                fontSize: '0.9375rem',
              }}
            >
              Navigation
            </Typography>
            <Stack spacing={1.2}>
              {footerLinks.map((item) => (
                <MuiLink
                  key={item.to}
                  component={RouterLink}
                  to={item.to}
                  underline="none"
                  sx={{ 
                    color: colors.neutral[400], 
                    fontSize: '0.875rem',
                    transition: 'all 0.2s ease-in-out',
                    '&:hover': { 
                      color: '#FFFFFF',
                      paddingLeft: '4px',
                    },
                  }}
                >
                  {item.label}
                </MuiLink>
              ))}
            </Stack>
          </Grid>

          {/* Features Links */}
          <Grid item xs={6} sm={4} md={2}>
            <Typography 
              sx={{ 
                color: '#FFFFFF', 
                fontWeight: typography.weights.semiBold,
                mb: 2,
                fontSize: '0.9375rem',
              }}
            >
              Fonctionnalités
            </Typography>
            <Stack spacing={1.2}>
              {features.map((item) => (
                <MuiLink
                  key={item.to}
                  component={RouterLink}
                  to={item.to}
                  underline="none"
                  sx={{ 
                    color: colors.neutral[400], 
                    fontSize: '0.875rem',
                    transition: 'all 0.2s ease-in-out',
                    '&:hover': { 
                      color: '#FFFFFF',
                      paddingLeft: '4px',
                    },
                  }}
                >
                  {item.label}
                </MuiLink>
              ))}
            </Stack>
          </Grid>

          {/* Contact */}
          <Grid item xs={12} sm={4} md={4}>
            <Typography 
              sx={{ 
                color: '#FFFFFF', 
                fontWeight: typography.weights.semiBold,
                mb: 2,
                fontSize: '0.9375rem',
              }}
            >
              Contact
            </Typography>
            <Stack spacing={2}>
              <Stack direction="row" spacing={1.5} alignItems="center">
                <Box 
                  sx={{ 
                    p: 0.75,
                    borderRadius: borderRadius.md,
                    backgroundColor: 'rgba(37, 99, 235, 0.15)',
                    display: 'flex',
                  }}
                >
                  <Mail size={16} color={colors.primary.light} />
                </Box>
                <Typography 
                  variant="body2" 
                  sx={{ color: colors.neutral[400], fontSize: '0.875rem' }}
                >
                  contact@fleetcontrol.app
                </Typography>
              </Stack>
              <Stack direction="row" spacing={1.5} alignItems="center">
                <Box 
                  sx={{ 
                    p: 0.75,
                    borderRadius: borderRadius.md,
                    backgroundColor: 'rgba(37, 99, 235, 0.15)',
                    display: 'flex',
                  }}
                >
                  <Phone size={16} color={colors.primary.light} />
                </Box>
                <Typography 
                  variant="body2" 
                  sx={{ color: colors.neutral[400], fontSize: '0.875rem' }}
                >
                  +242 06 000 00 00
                </Typography>
              </Stack>
              <Stack direction="row" spacing={1.5} alignItems="center">
                <Box 
                  sx={{ 
                    p: 0.75,
                    borderRadius: borderRadius.md,
                    backgroundColor: 'rgba(37, 99, 235, 0.15)',
                    display: 'flex',
                  }}
                >
                  <MapPin size={16} color={colors.primary.light} />
                </Box>
                <Typography 
                  variant="body2" 
                  sx={{ color: colors.neutral[400], fontSize: '0.875rem' }}
                >
                  Brazzaville, Congo
                </Typography>
              </Stack>
            </Stack>
          </Grid>
        </Grid>

        <Divider 
          sx={{ 
            my: 4, 
            borderColor: 'rgba(148, 163, 184, 0.15)',
          }} 
        />

        {/* Bottom bar */}
        <Stack 
          direction={{ xs: 'column', sm: 'row' }} 
          justifyContent="space-between" 
          alignItems="center"
          spacing={2}
        >
          <Typography 
            variant="body2" 
            sx={{ 
              color: colors.neutral[500], 
              fontSize: '0.8125rem',
            }}
          >
            © {new Date().getFullYear()} FleetControl. Tous droits réservés.
          </Typography>
          <Stack direction="row" spacing={3}>
            {legalLinks.map((link) => (
              <MuiLink
                key={link.to}
                component={RouterLink}
                to={link.to}
                underline="none"
                sx={{ 
                  color: colors.neutral[500], 
                  fontSize: '0.8125rem',
                  transition: 'color 0.2s ease-in-out',
                  '&:hover': { color: colors.neutral[300] },
                }}
              >
                {link.label}
              </MuiLink>
            ))}
          </Stack>
        </Stack>
      </Container>
    </Box>
  );
}
