/**
 * FleetControl - Badge/Chip Components
 * Based on Section 05 "BADGES / ÉTIQUETTES" of the design charter
 * 
 * Status badges:
 * - Validé (green)
 * - En attente (yellow/orange)
 * - En retard (red)
 * - Nouveau (blue)
 */

import { Chip, ChipProps, styled } from '@mui/material';
import { colors, borderRadius, typography } from '../../theme/design-tokens';

export type BadgeStatus = 'success' | 'warning' | 'error' | 'info' | 'default' | 'validated' | 'pending' | 'late' | 'new';

export interface FCBadgeProps extends Omit<ChipProps, 'color'> {
  status?: BadgeStatus;
}

const getStatusStyles = (status: BadgeStatus) => {
  switch (status) {
    case 'success':
    case 'validated':
      return {
        backgroundColor: colors.success.background,
        color: colors.success.dark,
        borderColor: colors.success.main,
      };
    case 'warning':
    case 'pending':
      return {
        backgroundColor: colors.warning.background,
        color: colors.warning.dark,
        borderColor: colors.warning.main,
      };
    case 'error':
    case 'late':
      return {
        backgroundColor: colors.error.background,
        color: colors.error.dark,
        borderColor: colors.error.main,
      };
    case 'info':
    case 'new':
      return {
        backgroundColor: colors.info.background,
        color: colors.info.dark,
        borderColor: colors.info.main,
      };
    case 'default':
    default:
      return {
        backgroundColor: colors.neutral[100],
        color: colors.neutral[700],
        borderColor: colors.neutral[300],
      };
  }
};

// Badge de base stylisé
const StyledBadge = styled(Chip)<{ status?: BadgeStatus }>(({ status = 'default' }) => {
  const statusStyles = getStatusStyles(status);
  return {
    borderRadius: borderRadius.full,
    fontWeight: typography.weights.medium,
    fontSize: '0.75rem',
    height: '26px',
    ...statusStyles,
    '& .MuiChip-label': {
      padding: '0 10px',
    },
    '& .MuiChip-icon': {
      marginLeft: '6px',
      color: 'inherit',
    },
  };
});

// Badge avec bordure (outline)
export const OutlinedBadge = styled(Chip)<{ status?: BadgeStatus }>(({ status = 'default' }) => {
  const statusStyles = getStatusStyles(status);
  return {
    borderRadius: borderRadius.full,
    fontWeight: typography.weights.medium,
    fontSize: '0.75rem',
    height: '26px',
    backgroundColor: 'transparent',
    color: statusStyles.color,
    border: `1.5px solid ${statusStyles.borderColor}`,
    '& .MuiChip-label': {
      padding: '0 10px',
    },
  };
});

// Badge rond (dot indicator)
export const DotBadge = styled('span')<{ status?: BadgeStatus }>(({ status = 'default' }) => {
  const statusStyles = getStatusStyles(status);
  return {
    width: '8px',
    height: '8px',
    borderRadius: '50%',
    backgroundColor: statusStyles.borderColor,
    display: 'inline-block',
    marginRight: '6px',
  };
});

// Badge numérique (pour notifications)
export const CountBadge = styled('span')<{ status?: BadgeStatus }>(({ status = 'error' }) => {
  const statusStyles = getStatusStyles(status);
  return {
    minWidth: '20px',
    height: '20px',
    borderRadius: borderRadius.full,
    backgroundColor: statusStyles.borderColor,
    color: '#FFFFFF',
    fontSize: '0.6875rem',
    fontWeight: typography.weights.semiBold,
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    padding: '0 6px',
  };
});

// Badge prédéfinis selon la charte
export const ValidatedBadge = (props: Omit<FCBadgeProps, 'status'>) => (
  <StyledBadge status="validated" label="Validé" {...props} />
);

export const PendingBadge = (props: Omit<FCBadgeProps, 'status'>) => (
  <StyledBadge status="pending" label="En attente" {...props} />
);

export const LateBadge = (props: Omit<FCBadgeProps, 'status'>) => (
  <StyledBadge status="late" label="En retard" {...props} />
);

export const NewBadge = (props: Omit<FCBadgeProps, 'status'>) => (
  <StyledBadge status="new" label="Nouveau" {...props} />
);

// Composant Badge générique
export function FCBadge({ status = 'default', ...props }: FCBadgeProps) {
  return <StyledBadge status={status} {...props} />;
}

export default FCBadge;
