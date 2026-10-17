/**
 * FleetControl MUI Theme Configuration
 * Based on the Design Charter
 */

import { createTheme, ThemeOptions, PaletteMode } from '@mui/material';
import { colors, typography, borderRadius, shadows } from './design-tokens';

// Create theme options based on mode
export const getThemeOptions = (mode: PaletteMode): ThemeOptions => ({
  palette: {
    mode,
    primary: {
      main: colors.primary.main,
      light: colors.primary.light,
      dark: colors.primary.dark,
      contrastText: colors.primary.contrastText,
    },
    secondary: {
      main: colors.secondary.main,
      light: colors.secondary.light,
      dark: colors.secondary.dark,
      contrastText: colors.secondary.contrastText,
    },
    error: {
      main: colors.error.main,
      light: colors.error.light,
      dark: colors.error.dark,
    },
    warning: {
      main: colors.warning.main,
      light: colors.warning.light,
      dark: colors.warning.dark,
    },
    success: {
      main: colors.success.main,
      light: colors.success.light,
      dark: colors.success.dark,
    },
    info: {
      main: colors.info.main,
      light: colors.info.light,
      dark: colors.info.dark,
    },
    background: mode === 'light' 
      ? { default: colors.background.default, paper: colors.background.paper }
      : { default: colors.background.dark, paper: colors.background.darkPaper },
    text: mode === 'light'
      ? { primary: colors.text.primary, secondary: colors.text.secondary }
      : { primary: colors.text.light, secondary: colors.text.lightSecondary },
    grey: {
      50: colors.neutral[50],
      100: colors.neutral[100],
      200: colors.neutral[200],
      300: colors.neutral[300],
      400: colors.neutral[400],
      500: colors.neutral[500],
      600: colors.neutral[600],
      700: colors.neutral[700],
      800: colors.neutral[800],
      900: colors.neutral[900],
    },
  },
  typography: {
    fontFamily: typography.fontFamily,
    h1: {
      ...typography.styles.heading1,
    },
    h2: {
      ...typography.styles.heading2,
    },
    h3: {
      ...typography.styles.heading3,
    },
    h4: {
      ...typography.styles.heading4,
    },
    h5: {
      ...typography.styles.heading5,
    },
    h6: {
      ...typography.styles.heading6,
    },
    body1: {
      ...typography.styles.body,
    },
    body2: {
      ...typography.styles.bodySmall,
      color: mode === 'light' ? colors.neutral[500] : colors.neutral[400],
    },
    caption: {
      ...typography.styles.caption,
    },
    button: {
      ...typography.styles.button,
    },
    subtitle1: {
      fontSize: '1rem',
      fontWeight: typography.weights.medium,
      lineHeight: 1.5,
    },
    subtitle2: {
      fontSize: '0.875rem',
      fontWeight: typography.weights.medium,
      lineHeight: 1.5,
    },
  },
  shape: {
    borderRadius: 6,
  },
  shadows: [
    'none',
    shadows.sm,
    shadows.base,
    shadows.md,
    shadows.md,
    shadows.lg,
    shadows.lg,
    shadows.lg,
    shadows.xl,
    shadows.xl,
    shadows.xl,
    shadows.xl,
    shadows['2xl'],
    shadows['2xl'],
    shadows['2xl'],
    shadows['2xl'],
    shadows['2xl'],
    shadows['2xl'],
    shadows['2xl'],
    shadows['2xl'],
    shadows['2xl'],
    shadows['2xl'],
    shadows['2xl'],
    shadows['2xl'],
    shadows['2xl'],
  ] as ThemeOptions['shadows'],
  components: {
    // ========================================
    // BOUTONS - Section 05 de la charte
    // ========================================
    MuiButton: {
      defaultProps: {
        disableElevation: true,
      },
      styleOverrides: {
        root: {
          textTransform: 'none',
          fontWeight: typography.weights.semiBold,
          borderRadius: borderRadius.md,
          padding: '10px 20px',
          transition: 'all 0.2s ease-in-out',
        },
        // Bouton Principal (rempli)
        contained: {
          backgroundColor: colors.primary.light,
          color: '#FFFFFF',
          boxShadow: 'none',
          '&:hover': {
            backgroundColor: colors.primary.main,
            boxShadow: shadows.button,
          },
        },
        containedPrimary: {
          backgroundColor: colors.primary.light,
          '&:hover': {
            backgroundColor: colors.primary.main,
          },
        },
        // Bouton Secondaire (outline)
        outlined: {
          borderColor: colors.primary.light,
          color: colors.primary.light,
          borderWidth: '2px',
          '&:hover': {
            borderWidth: '2px',
            backgroundColor: 'rgba(37, 99, 235, 0.08)',
            borderColor: colors.primary.main,
          },
        },
        outlinedPrimary: {
          borderColor: colors.primary.light,
          color: colors.primary.light,
          '&:hover': {
            backgroundColor: 'rgba(37, 99, 235, 0.08)',
          },
        },
        // Bouton Tertiaire (texte)
        text: {
          color: colors.primary.light,
          '&:hover': {
            backgroundColor: 'rgba(37, 99, 235, 0.08)',
          },
        },
        // Tailles
        sizeLarge: {
          padding: '12px 28px',
          fontSize: '1rem',
        },
        sizeSmall: {
          padding: '6px 14px',
          fontSize: '0.8125rem',
        },
      },
    },
    // ========================================
    // CHAMPS DE SAISIE - Section 05
    // ========================================
    MuiTextField: {
      defaultProps: {
        variant: 'outlined',
        size: 'medium',
      },
      styleOverrides: {
        root: {
          '& .MuiOutlinedInput-root': {
            borderRadius: borderRadius.md,
            transition: 'all 0.2s ease-in-out',
            '& fieldset': {
              borderColor: colors.neutral[300],
              borderWidth: '1.5px',
            },
            '&:hover fieldset': {
              borderColor: colors.primary.light,
            },
            '&.Mui-focused fieldset': {
              borderColor: colors.primary.light,
              borderWidth: '2px',
            },
          },
        },
      },
    },
    MuiOutlinedInput: {
      styleOverrides: {
        root: {
          borderRadius: borderRadius.md,
          '& fieldset': {
            borderColor: colors.neutral[300],
          },
          '&:hover fieldset': {
            borderColor: colors.primary.light,
          },
          '&.Mui-focused fieldset': {
            borderColor: colors.primary.light,
          },
          '&.Mui-error fieldset': {
            borderColor: colors.error.main,
          },
        },
        input: {
          padding: '12px 14px',
        },
      },
    },
    MuiInputLabel: {
      styleOverrides: {
        root: {
          fontWeight: typography.weights.medium,
          color: colors.neutral[600],
          '&.Mui-focused': {
            color: colors.primary.light,
          },
        },
      },
    },
    // ========================================
    // CARTES - Section 05
    // ========================================
    MuiCard: {
      styleOverrides: {
        root: ({ theme }) => ({
          boxShadow: shadows.card,
          borderRadius: borderRadius.lg,
          border: theme.palette.mode === 'light' 
            ? `1px solid ${colors.neutral[200]}`
            : `1px solid ${colors.neutral[700]}`,
          transition: 'all 0.2s ease-in-out',
          '&:hover': {
            boxShadow: shadows.cardHover,
          },
        }),
      },
    },
    MuiCardContent: {
      styleOverrides: {
        root: {
          padding: '20px',
          '&:last-child': {
            paddingBottom: '20px',
          },
        },
      },
    },
    MuiCardHeader: {
      styleOverrides: {
        root: {
          padding: '16px 20px',
        },
        title: {
          fontWeight: typography.weights.semiBold,
          fontSize: '1.125rem',
        },
      },
    },
    // ========================================
    // BADGES / CHIPS - Section 05
    // ========================================
    MuiChip: {
      styleOverrides: {
        root: {
          borderRadius: borderRadius.full,
          fontWeight: typography.weights.medium,
          fontSize: '0.75rem',
        },
        // Variantes de couleur
        colorSuccess: {
          backgroundColor: colors.success.background,
          color: colors.success.dark,
        },
        colorWarning: {
          backgroundColor: colors.warning.background,
          color: colors.warning.dark,
        },
        colorError: {
          backgroundColor: colors.error.background,
          color: colors.error.dark,
        },
        colorInfo: {
          backgroundColor: colors.info.background,
          color: colors.info.dark,
        },
        colorPrimary: {
          backgroundColor: 'rgba(37, 99, 235, 0.1)',
          color: colors.primary.light,
        },
        sizeSmall: {
          height: '24px',
          fontSize: '0.6875rem',
        },
      },
    },
    // ========================================
    // TABLEAUX
    // ========================================
    MuiTableCell: {
      styleOverrides: {
        head: ({ theme }) => ({
          fontWeight: typography.weights.bold,
          backgroundColor: theme.palette.mode === 'light' 
            ? colors.neutral[50] 
            : colors.neutral[800],
          color: theme.palette.mode === 'light' 
            ? colors.neutral[600] 
            : colors.neutral[300],
          borderBottom: `2px solid ${theme.palette.mode === 'light' 
            ? colors.neutral[200] 
            : colors.neutral[700]}`,
        }),
        body: ({ theme }) => ({
          borderBottom: `1px solid ${theme.palette.mode === 'light' 
            ? colors.neutral[200] 
            : colors.neutral[700]}`,
        }),
      },
    },
    MuiTableRow: {
      styleOverrides: {
        root: ({ theme }) => ({
          '&:hover': {
            backgroundColor: theme.palette.mode === 'light'
              ? 'rgba(37, 99, 235, 0.04)'
              : 'rgba(37, 99, 235, 0.08)',
          },
        }),
      },
    },
    // ========================================
    // DIALOGUES / MODALES
    // ========================================
    MuiDialog: {
      styleOverrides: {
        paper: {
          borderRadius: borderRadius.xl,
          boxShadow: shadows['2xl'],
        },
      },
    },
    MuiDialogTitle: {
      styleOverrides: {
        root: {
          fontWeight: typography.weights.semiBold,
          fontSize: '1.25rem',
          padding: '20px 24px 16px',
        },
      },
    },
    MuiDialogContent: {
      styleOverrides: {
        root: {
          padding: '16px 24px',
        },
      },
    },
    MuiDialogActions: {
      styleOverrides: {
        root: {
          padding: '16px 24px 20px',
          gap: '12px',
        },
      },
    },
    // ========================================
    // NAVIGATION
    // ========================================
    MuiAppBar: {
      styleOverrides: {
        root: ({ theme }) => ({
          backgroundColor: theme.palette.mode === 'light'
            ? colors.background.paper
            : colors.background.dark,
          boxShadow: shadows.sm,
        }),
      },
    },
    MuiDrawer: {
      styleOverrides: {
        paper: ({ theme }) => ({
          backgroundColor: theme.palette.mode === 'light'
            ? colors.background.paper
            : colors.background.darkPaper,
          borderRight: `1px solid ${theme.palette.mode === 'light' 
            ? colors.neutral[200] 
            : colors.neutral[700]}`,
        }),
      },
    },
    // ========================================
    // TOOLTIPS
    // ========================================
    MuiTooltip: {
      styleOverrides: {
        tooltip: {
          backgroundColor: colors.neutral[900],
          color: '#FFFFFF',
          fontSize: '0.75rem',
          fontWeight: typography.weights.medium,
          borderRadius: borderRadius.md,
          padding: '8px 12px',
        },
        arrow: {
          color: colors.neutral[900],
        },
      },
    },
    // ========================================
    // AVATARS
    // ========================================
    MuiAvatar: {
      styleOverrides: {
        root: {
          backgroundColor: colors.primary.light,
          fontWeight: typography.weights.semiBold,
        },
      },
    },
    // ========================================
    // ALERTES
    // ========================================
    MuiAlert: {
      styleOverrides: {
        root: {
          borderRadius: borderRadius.md,
          fontWeight: typography.weights.medium,
        },
        standardSuccess: {
          backgroundColor: colors.success.background,
          color: colors.success.dark,
        },
        standardWarning: {
          backgroundColor: colors.warning.background,
          color: colors.warning.dark,
        },
        standardError: {
          backgroundColor: colors.error.background,
          color: colors.error.dark,
        },
        standardInfo: {
          backgroundColor: colors.info.background,
          color: colors.info.dark,
        },
      },
    },
    // ========================================
    // PAGINATION
    // ========================================
    MuiPagination: {
      styleOverrides: {
        root: {
          '& .MuiPaginationItem-root': {
            fontWeight: typography.weights.medium,
            '&.Mui-selected': {
              backgroundColor: colors.primary.light,
              color: '#FFFFFF',
              '&:hover': {
                backgroundColor: colors.primary.main,
              },
            },
          },
        },
      },
    },
    // ========================================
    // TABS
    // ========================================
    MuiTabs: {
      styleOverrides: {
        indicator: {
          backgroundColor: colors.primary.light,
          height: '3px',
          borderRadius: '3px 3px 0 0',
        },
      },
    },
    MuiTab: {
      styleOverrides: {
        root: {
          fontWeight: typography.weights.medium,
          textTransform: 'none',
          '&.Mui-selected': {
            color: colors.primary.light,
          },
        },
      },
    },
    // ========================================
    // SKELETON
    // ========================================
    MuiSkeleton: {
      styleOverrides: {
        root: ({ theme }) => ({
          backgroundColor: theme.palette.mode === 'light' 
            ? colors.neutral[200] 
            : colors.neutral[700],
        }),
      },
    },
    // ========================================
    // LISTE
    // ========================================
    MuiListItemButton: {
      styleOverrides: {
        root: {
          borderRadius: borderRadius.md,
          margin: '2px 8px',
          '&.Mui-selected': {
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            color: colors.primary.light,
            '&:hover': {
              backgroundColor: 'rgba(37, 99, 235, 0.15)',
            },
          },
          '&:hover': {
            backgroundColor: 'rgba(37, 99, 235, 0.05)',
          },
        },
      },
    },
  },
});

// Create light and dark themes
export const lightTheme = createTheme(getThemeOptions('light'));
export const darkTheme = createTheme(getThemeOptions('dark'));

// Default export function to create theme based on mode
export const createFleetControlTheme = (mode: PaletteMode = 'light') => 
  createTheme(getThemeOptions(mode));

export default createFleetControlTheme;
