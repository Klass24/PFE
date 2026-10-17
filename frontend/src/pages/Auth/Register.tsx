import { useState } from 'react';
import { useNavigate, Link as RouterLink } from 'react-router-dom';
import {
  Box, Card, CardContent, Typography, TextField, Button,
  Alert, CircularProgress, Select, MenuItem, FormControl, InputLabel, Link,
} from '@mui/material';
import LocalShippingIcon from '@mui/icons-material/LocalShipping';
import { useForm, Controller } from 'react-hook-form';
import { useAuthStore } from '@/store/authStore';
import { authService } from '@/services/authService';
import type { RegisterDto } from '@/types';

export default function RegisterPage() {
  const navigate = useNavigate();
  const setAuth = useAuthStore((s) => s.setAuth);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const { register, handleSubmit, control, formState: { errors } } = useForm<RegisterDto>();

  const onSubmit = async (data: RegisterDto) => {
    setLoading(true);
    setError('');
    try {
      const res = await authService.register(data);
      setAuth(res.data.token, res.data.user);
      navigate('/dashboard');
    } catch (err: unknown) {
      setError(typeof err === 'string' ? err : "Erreur lors de l'inscription.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <Box sx={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', bgcolor: '#0F172A' }}>
      <Card sx={{ width: '100%', maxWidth: 480, mx: 2 }}>
        <CardContent sx={{ p: 4 }}>
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 3, justifyContent: 'center' }}>
            <Box sx={{ bgcolor: '#2563EB', borderRadius: 1.5, p: 0.8, display: 'flex' }}>
              <LocalShippingIcon sx={{ color: '#fff', fontSize: 24 }} />
            </Box>
            <Typography variant="h5" fontWeight="bold" sx={{ fontFamily: 'Poppins, sans-serif' }}>FleetControl</Typography>
          </Box>
          <Typography variant="h6" textAlign="center" sx={{ mb: 3 }}>Créer votre compte</Typography>

          {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}

          <Box component="form" onSubmit={handleSubmit(onSubmit)} sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
            <TextField label="Nom de l'entreprise" fullWidth {...register('company_name', { required: true })} error={!!errors.company_name} />
            <TextField label="Email de l'entreprise" type="email" fullWidth {...register('company_email', { required: true })} error={!!errors.company_email} />
            <TextField label="Votre nom" fullWidth {...register('name', { required: true })} error={!!errors.name} />
            <TextField label="Email" type="email" fullWidth {...register('email', { required: true })} error={!!errors.email} />
            <TextField label="Mot de passe" type="password" fullWidth {...register('password', { required: true, minLength: 8 })} error={!!errors.password} helperText="Min. 8 caractères" />
            <TextField label="Confirmer le mot de passe" type="password" fullWidth {...register('password_confirmation', { required: true })} />
            <FormControl fullWidth>
              <InputLabel>Plan</InputLabel>
              <Controller
                name="plan"
                control={control}
                defaultValue="starter"
                render={({ field }) => (
                  <Select label="Plan" {...field}>
                    <MenuItem value="starter">Starter (Gratuit)</MenuItem>
                    <MenuItem value="pro">Business</MenuItem>
                    <MenuItem value="enterprise">Enterprise</MenuItem>
                  </Select>
                )}
              />
            </FormControl>
            <Button type="submit" variant="contained" size="large" disabled={loading} fullWidth>
              {loading ? <CircularProgress size={24} /> : "S'inscrire"}
            </Button>
            <Typography variant="body2" textAlign="center" color="text.secondary">
              Déjà un compte ?{' '}
              <Link component={RouterLink} to="/login">Se connecter</Link>
            </Typography>
          </Box>
        </CardContent>
      </Card>
    </Box>
  );
}
