/**
 * FleetControl - TextField Components
 * Based on Section 05 "CHAMPS DE SAISIE" of the design charter
 * 
 * Input field styles:
 * - Default: grey border
 * - Active: blue border with focus
 * - Error: red border
 * - Disabled: grey background
 */

import { forwardRef } from 'react';
import { 
  TextField as MuiTextField, 
  TextFieldProps as MuiTextFieldProps,
  InputAdornment,
  styled,
  alpha,
} from '@mui/material';
import { colors, borderRadius, typography } from '../../theme/design-tokens';

export interface FCTextFieldProps extends Omit<MuiTextFieldProps, 'variant'> {
  startIcon?: React.ReactNode;
  endIcon?: React.ReactNode;
}

// TextField stylisé selon la charte
const StyledTextField = styled(MuiTextField)(({ theme, error }) => ({
  '& .MuiOutlinedInput-root': {
    borderRadius: borderRadius.md,
    fontFamily: typography.fontFamily,
    fontSize: '0.9375rem',
    transition: 'all 0.2s ease-in-out',
    backgroundColor: theme.palette.mode === 'light' 
      ? colors.background.paper 
      : colors.background.darkPaper,
    
    '& fieldset': {
      borderColor: error ? colors.error.main : colors.neutral[300],
      borderWidth: '1.5px',
      transition: 'all 0.2s ease-in-out',
    },
    
    '&:hover fieldset': {
      borderColor: error ? colors.error.main : colors.primary.light,
    },
    
    '&.Mui-focused fieldset': {
      borderColor: error ? colors.error.main : colors.primary.light,
      borderWidth: '2px',
      boxShadow: error 
        ? `0 0 0 3px ${alpha(colors.error.main, 0.1)}`
        : `0 0 0 3px ${alpha(colors.primary.light, 0.1)}`,
    },
    
    '&.Mui-disabled': {
      backgroundColor: colors.neutral[100],
      '& fieldset': {
        borderColor: colors.neutral[200],
      },
    },
    
    '& input': {
      padding: '12px 14px',
      '&::placeholder': {
        color: colors.neutral[400],
        opacity: 1,
      },
    },
    
    '& textarea': {
      '&::placeholder': {
        color: colors.neutral[400],
        opacity: 1,
      },
    },
  },
  
  '& .MuiInputLabel-root': {
    fontFamily: typography.fontFamily,
    fontWeight: typography.weights.medium,
    color: colors.neutral[600],
    
    '&.Mui-focused': {
      color: error ? colors.error.main : colors.primary.light,
    },
    
    '&.Mui-error': {
      color: colors.error.main,
    },
  },
  
  '& .MuiFormHelperText-root': {
    fontFamily: typography.fontFamily,
    marginLeft: '4px',
    marginTop: '6px',
    
    '&.Mui-error': {
      color: colors.error.main,
    },
  },
  
  '& .MuiInputAdornment-root': {
    color: colors.neutral[500],
    
    '& .MuiSvgIcon-root': {
      fontSize: '1.25rem',
    },
  },
}));

// Composant TextField principal
export const FCTextField = forwardRef<HTMLDivElement, FCTextFieldProps>(
  ({ startIcon, endIcon, InputProps, ...props }, ref) => {
    return (
      <StyledTextField
        ref={ref}
        variant="outlined"
        InputProps={{
          ...InputProps,
          startAdornment: startIcon ? (
            <InputAdornment position="start">{startIcon}</InputAdornment>
          ) : InputProps?.startAdornment,
          endAdornment: endIcon ? (
            <InputAdornment position="end">{endIcon}</InputAdornment>
          ) : InputProps?.endAdornment,
        }}
        {...props}
      />
    );
  }
);

FCTextField.displayName = 'FCTextField';

// Search Input stylisé
export const SearchInput = styled(StyledTextField)(({ theme }) => ({
  '& .MuiOutlinedInput-root': {
    borderRadius: borderRadius.full,
    backgroundColor: theme.palette.mode === 'light' 
      ? colors.neutral[100] 
      : colors.neutral[800],
    
    '& fieldset': {
      borderColor: 'transparent',
    },
    
    '&:hover fieldset': {
      borderColor: colors.neutral[300],
    },
    
    '&.Mui-focused fieldset': {
      borderColor: colors.primary.light,
      backgroundColor: theme.palette.mode === 'light' 
        ? colors.background.paper 
        : colors.background.darkPaper,
    },
    
    '& input': {
      padding: '10px 16px',
    },
  },
}));

// Textarea stylisé
export const FCTextArea = forwardRef<HTMLDivElement, FCTextFieldProps>(
  (props, ref) => {
    return (
      <FCTextField
        ref={ref}
        multiline
        minRows={3}
        {...props}
      />
    );
  }
);

FCTextArea.displayName = 'FCTextArea';

export default FCTextField;
