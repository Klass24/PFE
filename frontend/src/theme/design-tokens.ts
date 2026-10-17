/**
 * FleetControl Design Tokens
 * Charte graphique - Design System
 */

// ============================================
// 01. PALETTE DE COULEURS
// ============================================

// Couleurs principales
export const colors = {
  // Primaires
  primary: {
    main: '#1E3A8A',      // Bleu primaire foncé
    light: '#2563EB',     // Bleu secondaire
    dark: '#0F172A',      // Bleu très foncé
    contrastText: '#FFFFFF',
  },
  secondary: {
    main: '#2563EB',
    light: '#3B82F6',
    dark: '#1E3A8A',
    contrastText: '#FFFFFF',
  },
  
  // Neutres
  neutral: {
    900: '#0F172A',       // Foncé
    800: '#1E293B',
    700: '#334155',       // Gris foncé
    600: '#475569',
    500: '#64748B',
    400: '#94A3B8',
    300: '#CBD5E1',
    200: '#E2E8F0',       // Gris clair
    100: '#F1F5F9',
    50: '#F8FAFC',
  },
  
  // Fonctionnelles
  success: {
    main: '#22C55E',
    light: '#4ADE80',
    dark: '#16A34A',
    background: 'rgba(34, 197, 94, 0.1)',
    contrastText: '#FFFFFF',
  },
  warning: {
    main: '#F59E0B',
    light: '#FBBF24',
    dark: '#D97706',
    background: 'rgba(245, 158, 11, 0.1)',
    contrastText: '#FFFFFF',
  },
  error: {
    main: '#EF4444',
    light: '#F87171',
    dark: '#DC2626',
    background: 'rgba(239, 68, 68, 0.1)',
    contrastText: '#FFFFFF',
  },
  info: {
    main: '#3B82F6',
    light: '#60A5FA',
    dark: '#2563EB',
    background: 'rgba(59, 130, 246, 0.1)',
    contrastText: '#FFFFFF',
  },
  
  // Backgrounds
  background: {
    default: '#F8FAFC',
    paper: '#FFFFFF',
    dark: '#0F172A',
    darkPaper: '#1E293B',
  },
  
  // Text
  text: {
    primary: '#0F172A',
    secondary: '#334155',
    disabled: '#94A3B8',
    light: '#F1F5F9',
    lightSecondary: '#94A3B8',
  },
} as const;

// ============================================
// 03. TYPOGRAPHIE
// ============================================

export const typography = {
  fontFamily: '"Poppins", "Helvetica Neue", Arial, sans-serif',
  
  weights: {
    regular: 400,
    medium: 500,
    semiBold: 600,
    bold: 700,
  },
  
  sizes: {
    xs: '0.75rem',      // 12px
    sm: '0.875rem',     // 14px
    base: '1rem',       // 16px
    lg: '1.125rem',     // 18px
    xl: '1.25rem',      // 20px
    '2xl': '1.5rem',    // 24px
    '3xl': '1.875rem',  // 30px
    '4xl': '2.25rem',   // 36px
    '5xl': '3rem',      // 48px
  },
  
  lineHeights: {
    tight: 1.25,
    snug: 1.375,
    normal: 1.5,
    relaxed: 1.625,
    loose: 2,
  },
  
  // Styles pré-définis selon la charte
  styles: {
    heading1: {
      fontSize: '3rem',
      fontWeight: 700,
      lineHeight: 1.25,
    },
    heading2: {
      fontSize: '2.25rem',
      fontWeight: 700,
      lineHeight: 1.25,
    },
    heading3: {
      fontSize: '1.875rem',
      fontWeight: 600,
      lineHeight: 1.375,
    },
    heading4: {
      fontSize: '1.5rem',
      fontWeight: 600,
      lineHeight: 1.375,
    },
    heading5: {
      fontSize: '1.25rem',
      fontWeight: 600,
      lineHeight: 1.5,
    },
    heading6: {
      fontSize: '1.125rem',
      fontWeight: 600,
      lineHeight: 1.5,
    },
    bodyLarge: {
      fontSize: '1.125rem',
      fontWeight: 400,
      lineHeight: 1.625,
    },
    body: {
      fontSize: '1rem',
      fontWeight: 400,
      lineHeight: 1.5,
    },
    bodySmall: {
      fontSize: '0.875rem',
      fontWeight: 400,
      lineHeight: 1.5,
    },
    caption: {
      fontSize: '0.75rem',
      fontWeight: 400,
      lineHeight: 1.5,
    },
    button: {
      fontSize: '0.875rem',
      fontWeight: 600,
      lineHeight: 1.5,
      textTransform: 'none' as const,
    },
  },
} as const;

// ============================================
// 04. SPACING & SIZING
// ============================================

export const spacing = {
  xs: '0.25rem',    // 4px
  sm: '0.5rem',     // 8px
  md: '1rem',       // 16px
  lg: '1.5rem',     // 24px
  xl: '2rem',       // 32px
  '2xl': '3rem',    // 48px
  '3xl': '4rem',    // 64px
  '4xl': '6rem',    // 96px
} as const;

export const borderRadius = {
  none: '0',
  sm: '0.25rem',    // 4px
  base: '0.375rem', // 6px
  md: '0.5rem',     // 8px
  lg: '0.75rem',    // 12px
  xl: '1rem',       // 16px
  '2xl': '1.5rem',  // 24px
  full: '9999px',
} as const;

// ============================================
// 05. SHADOWS
// ============================================

export const shadows = {
  none: 'none',
  sm: '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
  base: '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1)',
  md: '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1)',
  lg: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1)',
  xl: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)',
  '2xl': '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
  inner: 'inset 0 2px 4px 0 rgba(0, 0, 0, 0.05)',
  card: '0 1px 4px 0 rgba(0, 0, 0, 0.08)',
  cardHover: '0 8px 24px 0 rgba(0, 0, 0, 0.12)',
  button: '0 4px 14px 0 rgba(37, 99, 235, 0.4)',
} as const;

// ============================================
// 06. TRANSITIONS
// ============================================

export const transitions = {
  fast: '150ms ease-in-out',
  base: '200ms ease-in-out',
  slow: '300ms ease-in-out',
  slower: '500ms ease-in-out',
} as const;

// ============================================
// 07. Z-INDEX
// ============================================

export const zIndex = {
  base: 0,
  dropdown: 100,
  sticky: 200,
  fixed: 300,
  modal: 400,
  popover: 500,
  tooltip: 600,
  toast: 700,
} as const;

// ============================================
// 08. BREAKPOINTS
// ============================================

export const breakpoints = {
  xs: '0px',
  sm: '600px',
  md: '900px',
  lg: '1200px',
  xl: '1536px',
} as const;

// Export par défaut
const designTokens = {
  colors,
  typography,
  spacing,
  borderRadius,
  shadows,
  transitions,
  zIndex,
  breakpoints,
};

export default designTokens;
