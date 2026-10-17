/**
 * FleetControl - Card Components
 * Based on Section 05 "CARTES" of the design charter
 * 
 * Card styles with FleetControl design system
 */

import { forwardRef, ReactNode } from 'react';
import { 
  Card as MuiCard, 
  CardProps as MuiCardProps, 
  CardContent, 
  CardHeader,
  CardActions,
  Box,
  Typography,
  styled,
  Stack
} from '@mui/material';
import { colors, borderRadius, shadows, typography } from '../../theme/design-tokens';

export interface FCCardProps extends MuiCardProps {
  title?: string;
  subtitle?: string;
  icon?: ReactNode;
  action?: ReactNode;
  noPadding?: boolean;
  hoverable?: boolean;
}

// Card de base stylisée
const StyledCard = styled(MuiCard)<{ hoverable?: boolean }>(({ theme, hoverable }) => ({
  borderRadius: borderRadius.lg,
  boxShadow: shadows.card,
  border: theme.palette.mode === 'light' 
    ? `1px solid ${colors.neutral[200]}`
    : `1px solid ${colors.neutral[700]}`,
  transition: 'all 0.2s ease-in-out',
  overflow: 'hidden',
  ...(hoverable && {
    cursor: 'pointer',
    '&:hover': {
      boxShadow: shadows.cardHover,
      transform: 'translateY(-2px)',
      borderColor: colors.primary.light,
    },
  }),
}));

// Stat Card (pour dashboard)
export const StatCard = styled(MuiCard)(({ theme }) => ({
  borderRadius: borderRadius.lg,
  boxShadow: shadows.card,
  border: theme.palette.mode === 'light' 
    ? `1px solid ${colors.neutral[200]}`
    : `1px solid ${colors.neutral[700]}`,
  padding: '20px',
  transition: 'all 0.2s ease-in-out',
  '&:hover': {
    boxShadow: shadows.cardHover,
  },
}));

// Stat Card Content Component
interface StatCardContentProps {
  title: string;
  value: string | number;
  subtitle?: string;
  icon?: ReactNode;
  trend?: {
    value: string;
    positive?: boolean;
  };
}

export function StatCardContent({ title, value, subtitle, icon, trend }: StatCardContentProps) {
  return (
    <Box>
      <Stack direction="row" justifyContent="space-between" alignItems="flex-start" mb={2}>
        <Typography 
          variant="body2" 
          sx={{ 
            color: colors.neutral[500],
            fontWeight: typography.weights.medium,
            textTransform: 'uppercase',
            fontSize: '0.75rem',
            letterSpacing: '0.5px',
          }}
        >
          {title}
        </Typography>
        {icon && (
          <Box 
            sx={{ 
              p: 1, 
              borderRadius: borderRadius.md, 
              bgcolor: 'rgba(37, 99, 235, 0.1)',
              color: colors.primary.light,
              display: 'flex',
            }}
          >
            {icon}
          </Box>
        )}
      </Stack>
      <Typography 
        variant="h4" 
        sx={{ 
          fontWeight: typography.weights.bold,
          color: colors.neutral[900],
          mb: 0.5,
        }}
      >
        {value}
      </Typography>
      {(subtitle || trend) && (
        <Stack direction="row" alignItems="center" spacing={1}>
          {trend && (
            <Typography 
              variant="caption"
              sx={{ 
                color: trend.positive ? colors.success.main : colors.error.main,
                fontWeight: typography.weights.medium,
              }}
            >
              {trend.positive ? '↑' : '↓'} {trend.value}
            </Typography>
          )}
          {subtitle && (
            <Typography variant="caption" sx={{ color: colors.neutral[500] }}>
              {subtitle}
            </Typography>
          )}
        </Stack>
      )}
    </Box>
  );
}

// Feature Card (pour landing page)
export const FeatureCard = styled(MuiCard)(({ theme }) => ({
  borderRadius: borderRadius.xl,
  boxShadow: shadows.card,
  border: theme.palette.mode === 'light' 
    ? `1px solid ${colors.neutral[200]}`
    : `1px solid ${colors.neutral[700]}`,
  padding: '32px',
  textAlign: 'center',
  transition: 'all 0.3s ease-in-out',
  '&:hover': {
    boxShadow: shadows.xl,
    transform: 'translateY(-4px)',
    borderColor: colors.primary.light,
  },
}));

// Glass Card (effet glassmorphism)
export const GlassCard = styled(MuiCard)(({ theme }) => ({
  borderRadius: borderRadius.lg,
  backgroundColor: theme.palette.mode === 'light'
    ? 'rgba(255, 255, 255, 0.7)'
    : 'rgba(30, 41, 59, 0.7)',
  backdropFilter: 'blur(10px)',
  border: theme.palette.mode === 'light'
    ? '1px solid rgba(255, 255, 255, 0.5)'
    : '1px solid rgba(148, 163, 184, 0.2)',
  boxShadow: shadows.lg,
}));

// Composant Card principal
export const FCCard = forwardRef<HTMLDivElement, FCCardProps>(
  ({ children, title, subtitle, icon, action, noPadding, hoverable, sx, ...props }, ref) => {
    return (
      <StyledCard ref={ref} hoverable={hoverable} sx={sx} {...props}>
        {(title || action) && (
          <CardHeader
            title={
              <Stack direction="row" alignItems="center" spacing={1.5}>
                {icon && (
                  <Box 
                    sx={{ 
                      p: 1, 
                      borderRadius: borderRadius.md, 
                      bgcolor: 'rgba(37, 99, 235, 0.1)', 
                      color: colors.primary.light,
                      display: 'flex',
                    }}
                  >
                    {icon}
                  </Box>
                )}
                <Box>
                  <Typography 
                    sx={{ 
                      fontWeight: typography.weights.semiBold, 
                      fontSize: '1.125rem',
                      color: colors.neutral[900],
                    }}
                  >
                    {title}
                  </Typography>
                  {subtitle && (
                    <Typography variant="body2" sx={{ color: colors.neutral[500] }}>
                      {subtitle}
                    </Typography>
                  )}
                </Box>
              </Stack>
            }
            action={action}
            sx={{
              px: 3,
              py: 2,
              borderBottom: `1px solid ${colors.neutral[200]}`,
            }}
          />
        )}
        {noPadding ? children : <CardContent sx={{ p: 3 }}>{children}</CardContent>}
      </StyledCard>
    );
  }
);

FCCard.displayName = 'FCCard';

// Re-export MUI components pour cohérence
export { CardActions as FCCardActions, CardContent as FCCardContent, CardHeader as FCCardHeader };

export default FCCard;
