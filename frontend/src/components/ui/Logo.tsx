/**
 * FleetControl - Logo Component
 * Based on Section 01 "LOGO" of the design charter
 * 
 * Logo variants:
 * - Full: Icon + Text
 * - Icon only
 * - Light/Dark variants
 */

import { Box, Stack, Typography, styled } from '@mui/material';
import { Truck } from 'lucide-react';
import { useState } from 'react';
import { colors, borderRadius, typography } from '../../theme/design-tokens';

export interface FCLogoProps {
  variant?: 'full' | 'icon' | 'text';
  size?: 'sm' | 'md' | 'lg';
  colorScheme?: 'light' | 'dark' | 'primary';
  onClick?: () => void;
}

const sizes = {
  sm: {
    iconBox: 28,
    iconSize: 16,
    fontSize: '1rem',
    spacing: 0.8,
  },
  md: {
    iconBox: 36,
    iconSize: 20,
    fontSize: '1.25rem',
    spacing: 1,
  },
  lg: {
    iconBox: 48,
    iconSize: 28,
    fontSize: '1.5rem',
    spacing: 1.2,
  },
};

const colorSchemes = {
  light: {
    iconBg: colors.primary.light,
    iconColor: '#FFFFFF',
    textColor: '#FFFFFF',
    accentColor: colors.primary.light,
  },
  dark: {
    iconBg: colors.primary.light,
    iconColor: '#FFFFFF',
    textColor: colors.neutral[900],
    accentColor: colors.primary.light,
  },
  primary: {
    iconBg: colors.primary.main,
    iconColor: '#FFFFFF',
    textColor: colors.primary.main,
    accentColor: colors.primary.light,
  },
};

// Container du logo
const LogoContainer = styled(Stack)<{ clickable?: boolean }>(({ clickable }) => ({
  cursor: clickable ? 'pointer' : 'default',
  userSelect: 'none',
  transition: 'transform 0.2s ease-in-out',
  ...(clickable && {
    '&:hover': {
      transform: 'scale(1.02)',
    },
  }),
}));

// Box de l'icône
const IconBox = styled(Box)<{ size: number; bgcolor: string }>(({ size, bgcolor }) => ({
  width: size,
  height: size,
  backgroundColor: bgcolor,
  borderRadius: borderRadius.md,
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'center',
  flexShrink: 0,
}));

export function FCLogo({ 
  variant = 'full', 
  size = 'md', 
  colorScheme = 'dark',
  onClick,
}: FCLogoProps) {
  const sizeConfig = sizes[size];
  const colorConfig = colorSchemes[colorScheme];

  const renderIcon = () => (
    <IconBox size={sizeConfig.iconBox} bgcolor={colorConfig.iconBg}>
      <Truck 
        size={sizeConfig.iconSize} 
        color={colorConfig.iconColor}
        strokeWidth={2.5}
      />
    </IconBox>
  );

  // Image fallback logic: try to load image from /images/, otherwise show icon
  const [imageOk, setImageOk] = useState(true);
  // Use exact filename (case-sensitive) present in `public/images`
  const logoSrc = '/images/FleetControl.png';
  const iconSrc = '/images/FleetControl.png';


  const renderText = () => (
    <Typography
      sx={{
        fontFamily: typography.fontFamily,
        fontWeight: typography.weights.bold,
        fontSize: sizeConfig.fontSize,
        color: colorConfig.textColor,
        letterSpacing: '-0.02em',
        lineHeight: 1,
      }}
    >
      Fleet
      <Box 
        component="span" 
        sx={{ color: colorConfig.accentColor }}
      >
        Control
      </Box>
    </Typography>
  );

  if (variant === 'icon') {
    return (
      <LogoContainer 
        clickable={!!onClick} 
        onClick={onClick}
        direction="row" 
        alignItems="center"
      >
        {imageOk ? (
          <Box component="img" src={iconSrc} alt="FleetControl" sx={{ width: sizeConfig.iconBox, height: sizeConfig.iconBox, borderRadius: 1 }} onError={() => setImageOk(false)} />
        ) : renderIcon()}
      </LogoContainer>
    );
  }

  if (variant === 'text') {
    return (
      <LogoContainer 
        clickable={!!onClick} 
        onClick={onClick}
        direction="row" 
        alignItems="center"
      >
        {renderText()}
      </LogoContainer>
    );
  }

  // Full variant: try to load image first, fallback to icon+text
  return (
    <LogoContainer 
      clickable={!!onClick} 
      onClick={onClick}
      direction="row" 
      alignItems="center" 
      spacing={sizeConfig.spacing}
    >
      {imageOk ? (
        <Box component="img" src={logoSrc} alt="FleetControl" sx={{ height: sizeConfig.iconBox, display: 'block' }} onError={() => setImageOk(false)} />
      ) : renderIcon()}
      {renderText()}
    </LogoContainer>
  );
}

// Logo avec tagline
export function FCLogoWithTagline({ 
  size = 'lg', 
  colorScheme = 'dark',
  tagline = 'Maîtrisez votre flotte, éliminez les pertes',
}: FCLogoProps & { tagline?: string }) {
  return (
    <Stack spacing={0.5} alignItems="flex-start">
      <FCLogo size={size} colorScheme={colorScheme} />
      <Typography
        variant="caption"
        sx={{
          color: colorScheme === 'light' 
            ? 'rgba(255, 255, 255, 0.7)' 
            : colors.neutral[500],
          fontWeight: typography.weights.medium,
          fontSize: '0.75rem',
          letterSpacing: '0.02em',
          pl: size === 'lg' ? 7.5 : size === 'md' ? 5.5 : 4.5,
        }}
      >
        {tagline}
      </Typography>
    </Stack>
  );
}

export default FCLogo;
