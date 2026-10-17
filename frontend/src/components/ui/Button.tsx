/**
 * FleetControl - Custom Button Components
 * Based on Section 05 "COMPOSANTS UI" of the design charter
 * 
 * Variants:
 * - Primary Button: Filled blue button for main actions
 * - Secondary Button: Outlined blue button for secondary actions
 * - Tertiary Button: Text button for less important actions
 */

import { Button as MuiButton, ButtonProps as MuiButtonProps, styled } from '@mui/material';
import { colors, borderRadius, shadows, typography } from '../../theme/design-tokens';

export interface FCButtonProps extends MuiButtonProps {
  fcVariant?: 'primary' | 'secondary' | 'tertiary';
}

// Bouton Principal (rempli bleu)
export const PrimaryButton = styled(MuiButton)(() => ({
  backgroundColor: colors.primary.light,
  color: '#FFFFFF',
  fontWeight: typography.weights.semiBold,
  borderRadius: borderRadius.md,
  padding: '10px 24px',
  textTransform: 'none',
  boxShadow: 'none',
  transition: 'all 0.2s ease-in-out',
  '&:hover': {
    backgroundColor: colors.primary.main,
    boxShadow: shadows.button,
    transform: 'translateY(-1px)',
  },
  '&:active': {
    transform: 'translateY(0)',
  },
  '&:disabled': {
    backgroundColor: colors.neutral[300],
    color: colors.neutral[500],
  },
}));

// Bouton Secondaire (outline bleu)
export const SecondaryButton = styled(MuiButton)(() => ({
  backgroundColor: 'transparent',
  color: colors.primary.light,
  fontWeight: typography.weights.semiBold,
  borderRadius: borderRadius.md,
  padding: '10px 24px',
  textTransform: 'none',
  border: `2px solid ${colors.primary.light}`,
  transition: 'all 0.2s ease-in-out',
  '&:hover': {
    backgroundColor: 'rgba(37, 99, 235, 0.08)',
    borderColor: colors.primary.main,
    color: colors.primary.main,
  },
  '&:disabled': {
    borderColor: colors.neutral[300],
    color: colors.neutral[400],
  },
}));

// Bouton Tertiaire (texte)
export const TertiaryButton = styled(MuiButton)(() => ({
  backgroundColor: 'transparent',
  color: colors.primary.light,
  fontWeight: typography.weights.semiBold,
  borderRadius: borderRadius.md,
  padding: '10px 24px',
  textTransform: 'none',
  transition: 'all 0.2s ease-in-out',
  '&:hover': {
    backgroundColor: 'rgba(37, 99, 235, 0.08)',
  },
  '&:disabled': {
    color: colors.neutral[400],
  },
}));

// Success Button
export const SuccessButton = styled(MuiButton)(() => ({
  backgroundColor: colors.success.main,
  color: '#FFFFFF',
  fontWeight: typography.weights.semiBold,
  borderRadius: borderRadius.md,
  padding: '10px 24px',
  textTransform: 'none',
  boxShadow: 'none',
  transition: 'all 0.2s ease-in-out',
  '&:hover': {
    backgroundColor: colors.success.dark,
    boxShadow: '0 4px 14px 0 rgba(34, 197, 94, 0.4)',
  },
  '&:disabled': {
    backgroundColor: colors.neutral[300],
    color: colors.neutral[500],
  },
}));

// Error/Danger Button
export const DangerButton = styled(MuiButton)(() => ({
  backgroundColor: colors.error.main,
  color: '#FFFFFF',
  fontWeight: typography.weights.semiBold,
  borderRadius: borderRadius.md,
  padding: '10px 24px',
  textTransform: 'none',
  boxShadow: 'none',
  transition: 'all 0.2s ease-in-out',
  '&:hover': {
    backgroundColor: colors.error.dark,
    boxShadow: '0 4px 14px 0 rgba(239, 68, 68, 0.4)',
  },
  '&:disabled': {
    backgroundColor: colors.neutral[300],
    color: colors.neutral[500],
  },
}));

// Warning Button
export const WarningButton = styled(MuiButton)(() => ({
  backgroundColor: colors.warning.main,
  color: '#FFFFFF',
  fontWeight: typography.weights.semiBold,
  borderRadius: borderRadius.md,
  padding: '10px 24px',
  textTransform: 'none',
  boxShadow: 'none',
  transition: 'all 0.2s ease-in-out',
  '&:hover': {
    backgroundColor: colors.warning.dark,
    boxShadow: '0 4px 14px 0 rgba(245, 158, 11, 0.4)',
  },
  '&:disabled': {
    backgroundColor: colors.neutral[300],
    color: colors.neutral[500],
  },
}));

// Icon Button avec style FleetControl
export const FCIconButton = styled(MuiButton)(() => ({
  minWidth: '40px',
  width: '40px',
  height: '40px',
  padding: 0,
  borderRadius: borderRadius.md,
  backgroundColor: 'transparent',
  color: colors.primary.light,
  transition: 'all 0.2s ease-in-out',
  '&:hover': {
    backgroundColor: 'rgba(37, 99, 235, 0.08)',
  },
}));

// Composant Button générique avec prop fcVariant
export function FCButton({ fcVariant = 'primary', ...props }: FCButtonProps) {
  switch (fcVariant) {
    case 'secondary':
      return <SecondaryButton {...props} />;
    case 'tertiary':
      return <TertiaryButton {...props} />;
    case 'primary':
    default:
      return <PrimaryButton {...props} />;
  }
}

export default FCButton;
