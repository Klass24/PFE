/**
 * FleetControl - Public Header
 * Based on the design charter - Section 01 Logo & 05 Composants UI
 */

import { useState } from 'react';
import { Box, Button, Container, Stack, IconButton, Drawer, List, ListItemButton, ListItemText } from '@mui/material';
import { Menu, X } from 'lucide-react';
import { Link as RouterLink, useNavigate, useLocation } from 'react-router-dom';
import { FCLogo } from '../ui/Logo';
import { PrimaryButton, SecondaryButton } from '../ui/Button';
import { colors, shadows, typography } from '../../theme/design-tokens';

const navLinks = [
  { label: 'Accueil', to: '/landing' },
  { label: 'À propos', to: '/a-propos' },
  { label: 'Contact', to: '/contact' },
  { label: 'FAQ', to: '/faq' },
];

export default function PublicHeader() {
  const navigate = useNavigate();
  const location = useLocation();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const isActive = (path: string) => location.pathname === path;

  return (
    <>
      <Box
        component="header"
        sx={{
          position: 'sticky',
          top: 0,
          zIndex: 1200,
          backdropFilter: 'blur(12px)',
          backgroundColor: 'rgba(15, 23, 42, 0.97)',
          boxShadow: shadows.lg,
          borderBottom: '1px solid rgba(255, 255, 255, 0.05)',
        }}
      >
        <Container maxWidth="lg" sx={{ py: 1.5 }}>
          <Stack direction="row" justifyContent="space-between" alignItems="center" spacing={2}>
            {/* Logo */}
            <FCLogo 
              variant="full" 
              size="md" 
              colorScheme="light" 
              onClick={() => navigate('/landing')}
            />

            {/* Navigation Links - Desktop */}
            <Stack 
              direction="row" 
              spacing={0.5} 
              sx={{ display: { xs: 'none', md: 'flex' } }}
            >
              {navLinks.map((link) => (
                <Button
                  key={link.to}
                  component={RouterLink}
                  to={link.to}
                  sx={{
                    color: isActive(link.to) ? '#FFFFFF' : colors.neutral[400],
                    fontWeight: isActive(link.to) ? typography.weights.semiBold : typography.weights.medium,
                    fontSize: '0.9375rem',
                    px: 2,
                    py: 1,
                    borderRadius: '8px',
                    position: 'relative',
                    transition: 'all 0.2s ease-in-out',
                    '&:hover': {
                      color: '#FFFFFF',
                      backgroundColor: 'rgba(255, 255, 255, 0.08)',
                    },
                    ...(isActive(link.to) && {
                      '&::after': {
                        content: '""',
                        position: 'absolute',
                        bottom: 0,
                        left: '50%',
                        transform: 'translateX(-50%)',
                        width: '24px',
                        height: '3px',
                        backgroundColor: colors.primary.light,
                        borderRadius: '3px 3px 0 0',
                      },
                    }),
                  }}
                >
                  {link.label}
                </Button>
              ))}
            </Stack>

            {/* Auth Buttons - Desktop */}
            <Stack direction="row" spacing={1.5} sx={{ display: { xs: 'none', md: 'flex' } }}>
              <SecondaryButton
                onClick={() => navigate('/login')}
                sx={{
                  borderColor: 'rgba(255, 255, 255, 0.3)',
                  color: '#FFFFFF',
                  '&:hover': {
                    borderColor: '#FFFFFF',
                    backgroundColor: 'rgba(255, 255, 255, 0.08)',
                  },
                }}
              >
                Connexion
              </SecondaryButton>
              <PrimaryButton onClick={() => navigate('/register')}>
                Essai gratuit
              </PrimaryButton>
            </Stack>

            {/* Mobile Menu Button */}
            <IconButton
              sx={{ display: { xs: 'flex', md: 'none' }, color: '#FFFFFF' }}
              onClick={() => setMobileMenuOpen(true)}
            >
              <Menu size={24} />
            </IconButton>
          </Stack>
        </Container>
      </Box>

      {/* Mobile Drawer */}
      <Drawer
        anchor="right"
        open={mobileMenuOpen}
        onClose={() => setMobileMenuOpen(false)}
        PaperProps={{
          sx: {
            width: 280,
            backgroundColor: colors.background.dark,
            borderLeft: '1px solid rgba(255, 255, 255, 0.1)',
          },
        }}
      >
        <Box sx={{ p: 2 }}>
          <Stack direction="row" justifyContent="space-between" alignItems="center" mb={3}>
            <FCLogo variant="full" size="sm" colorScheme="light" />
            <IconButton 
              onClick={() => setMobileMenuOpen(false)} 
              sx={{ color: '#FFFFFF' }}
            >
              <X size={24} />
            </IconButton>
          </Stack>

          <List>
            {navLinks.map((link) => (
              <ListItemButton
                key={link.to}
                component={RouterLink}
                to={link.to}
                onClick={() => setMobileMenuOpen(false)}
                sx={{
                  borderRadius: '8px',
                  mb: 0.5,
                  color: isActive(link.to) ? '#FFFFFF' : colors.neutral[400],
                  backgroundColor: isActive(link.to) ? 'rgba(37, 99, 235, 0.15)' : 'transparent',
                  '&:hover': {
                    backgroundColor: 'rgba(255, 255, 255, 0.08)',
                    color: '#FFFFFF',
                  },
                }}
              >
                <ListItemText 
                  primary={link.label}
                  primaryTypographyProps={{ fontWeight: typography.weights.medium }}
                />
              </ListItemButton>
            ))}
          </List>

          <Stack spacing={1.5} mt={3}>
            <SecondaryButton
              fullWidth
              onClick={() => { navigate('/login'); setMobileMenuOpen(false); }}
              sx={{
                borderColor: 'rgba(255, 255, 255, 0.3)',
                color: '#FFFFFF',
              }}
            >
              Connexion
            </SecondaryButton>
            <PrimaryButton
              fullWidth
              onClick={() => { navigate('/register'); setMobileMenuOpen(false); }}
            >
              Essai gratuit
            </PrimaryButton>
          </Stack>
        </Box>
      </Drawer>
    </>
  );
}
