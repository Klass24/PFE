import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Box, Button, TextField, Typography, Paper, Alert, CircularProgress } from '@mui/material';
import { useForm } from 'react-hook-form';
import { authService } from '@/services/authService';
import { useAuthStore } from '@/store/authStore';
import type { LoginCredentials } from '@/types';

export default function LoginPage() {
  const navigate = useNavigate();
  const setAuth = useAuthStore((s) => s.setAuth);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const { register, handleSubmit, formState: { errors } } = useForm<LoginCredentials>();

  const onSubmit = async (data: LoginCredentials) => {
    setLoading(true);
    setError('');
    try {
      const res = await authService.login(data);
      if (res.data.user.role === 'driver') {
        setError('Les chauffeurs doivent utiliser l\'application mobile.');
        return;
      }
      setAuth(res.data.token, res.data.user);
      navigate('/dashboard');
    } catch (err: unknown) {
      setError(typeof err === 'string' ? err : 'Email ou mot de passe incorrect.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Box sx={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', bgcolor: 'grey.100' }}>
      <Paper sx={{ p: 4, width: '100%', maxWidth: 400 }}>
        <Typography variant="h5" fontWeight="bold" color="primary" textAlign="center" mb={3}>
          FleetControl
        </Typography>
        <Typography variant="h6" textAlign="center" mb={3}>Connexion</Typography>

        {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}

        <Box component="form" onSubmit={handleSubmit(onSubmit)} sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
          <TextField
            label="Email"
            type="email"
            fullWidth
            {...register('email', { required: "L'email est obligatoire" })}
            error={!!errors.email}
            helperText={errors.email?.message}
          />
          <TextField
            label="Mot de passe"
            type="password"
            fullWidth
            {...register('password', { required: 'Le mot de passe est obligatoire' })}
            error={!!errors.password}
            helperText={errors.password?.message}
          />
          <Button type="submit" variant="contained" size="large" disabled={loading}>
            {loading ? <CircularProgress size={24} color="inherit" /> : 'Se connecter'}
          </Button>
        </Box>
      </Paper>
    </Box>
  );
}
