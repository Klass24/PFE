import { useState } from 'react';
import {
  Box, Typography, Card, CardContent, Grid, CircularProgress, Alert,
  List, ListItem, ListItemText, ListItemIcon, Chip, Button, Dialog,
  DialogTitle, DialogContent, DialogActions, TextField,
} from '@mui/material';
import GpsFixedIcon from '@mui/icons-material/GpsFixed';
import DirectionsCarIcon from '@mui/icons-material/DirectionsCar';
import PersonIcon from '@mui/icons-material/Person';
import SpeedIcon from '@mui/icons-material/Speed';
import AddIcon from '@mui/icons-material/Add';
import { useForm, Controller } from 'react-hook-form';
import { useSnackbar } from 'notistack';
import { useActiveTracking, useGeofences, useCreateGeofence } from '@/hooks/useTracking';

export default function TrackingPage() {
  const [openGeofence, setOpenGeofence] = useState(false);
  const { enqueueSnackbar } = useSnackbar();

  const { data: activeVehicles, isLoading, isError } = useActiveTracking();
  const { data: geofences } = useGeofences();
  const createGeofenceMut = useCreateGeofence();
  const { control, handleSubmit, reset } = useForm();

  const onGeofenceSubmit = (formData: any) => {
    createGeofenceMut.mutate({ ...formData, type: 'circle' }, {
      onSuccess: () => { enqueueSnackbar('Geofence créé', { variant: 'success' }); setOpenGeofence(false); reset(); },
      onError: () => enqueueSnackbar('Erreur', { variant: 'error' }),
    });
  };

  if (isLoading) return <CircularProgress />;
  if (isError) return <Alert severity="error">Erreur de chargement du tracking</Alert>;

  const vehicles = activeVehicles || [];

  return (
    <Box>
      <Box display="flex" justifyContent="space-between" alignItems="center" mb={3}>
        <Typography variant="h5"><GpsFixedIcon sx={{ mr: 1, verticalAlign: 'middle' }} />Tracking GPS Temps Réel</Typography>
        <Button startIcon={<AddIcon />} variant="outlined" onClick={() => setOpenGeofence(true)}>
          Ajouter Geofence
        </Button>
      </Box>

      <Grid container spacing={2} mb={3}>
        <Grid item xs={12} sm={4}>
          <Card><CardContent>
            <Typography variant="body2" color="text.secondary">Véhicules actifs</Typography>
            <Typography variant="h4">{vehicles.length}</Typography>
          </CardContent></Card>
        </Grid>
        <Grid item xs={12} sm={4}>
          <Card><CardContent>
            <Typography variant="body2" color="text.secondary">Geofences actives</Typography>
            <Typography variant="h4">{geofences?.length || 0}</Typography>
          </CardContent></Card>
        </Grid>
        <Grid item xs={12} sm={4}>
          <Card><CardContent>
            <Typography variant="body2" color="text.secondary">Dernière MAJ</Typography>
            <Typography variant="h6">{new Date().toLocaleTimeString('fr-FR')}</Typography>
          </CardContent></Card>
        </Grid>
      </Grid>

      {/* Map placeholder - integrable with react-leaflet */}
      <Card sx={{ mb: 3, minHeight: 400, display: 'flex', alignItems: 'center', justifyContent: 'center', bgcolor: '#f5f5f5' }}>
        <CardContent sx={{ textAlign: 'center' }}>
          <GpsFixedIcon sx={{ fontSize: 64, color: '#ccc' }} />
          <Typography variant="h6" color="text.secondary" mt={2}>
            Carte GPS — Intégrer react-leaflet ici
          </Typography>
          <Typography variant="body2" color="text.secondary">
            {vehicles.length} véhicule(s) en mission actuellement
          </Typography>
        </CardContent>
      </Card>

      {/* Active vehicles list */}
      <Typography variant="h6" mb={2}>Véhicules en mouvement</Typography>
      {vehicles.length === 0 ? (
        <Alert severity="info">Aucun véhicule en mission actuellement</Alert>
      ) : (
        <List>
          {vehicles.map((v: any, i: number) => (
            <ListItem key={i} divider>
              <ListItemIcon><DirectionsCarIcon /></ListItemIcon>
              <ListItemText
                primary={v.plate_number}
                secondary={
                  <Box display="flex" gap={2} alignItems="center">
                    <PersonIcon fontSize="small" /> {v.driver_name}
                    <SpeedIcon fontSize="small" /> {v.speed ? `${v.speed} km/h` : 'N/A'}
                    <Typography variant="caption">
                      ({v.latitude?.toFixed(4)}, {v.longitude?.toFixed(4)})
                    </Typography>
                  </Box>
                }
              />
              <Chip label="En mission" color="warning" size="small" />
            </ListItem>
          ))}
        </List>
      )}

      {/* Geofences list */}
      {geofences && geofences.length > 0 && (
        <>
          <Typography variant="h6" mt={4} mb={2}>Geofences ({geofences.length})</Typography>
          <Grid container spacing={2}>
            {geofences.map((g: any) => (
              <Grid item xs={12} sm={6} md={4} key={g.uuid}>
                <Card>
                  <CardContent>
                    <Typography variant="subtitle1" fontWeight={600}>{g.name}</Typography>
                    <Typography variant="body2" color="text.secondary">
                      Type: {g.type} | Rayon: {g.radius_meters}m | Déclencheur: {g.trigger_on}
                    </Typography>
                  </CardContent>
                </Card>
              </Grid>
            ))}
          </Grid>
        </>
      )}

      {/* Geofence Dialog */}
      <Dialog open={openGeofence} onClose={() => setOpenGeofence(false)} maxWidth="sm" fullWidth>
        <form onSubmit={handleSubmit(onGeofenceSubmit)}>
          <DialogTitle>Créer une Geofence</DialogTitle>
          <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
            <Controller name="name" control={control} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Nom" required fullWidth />
            )} />
            <Controller name="center_lat" control={control} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Latitude centre" type="number" inputProps={{ step: 0.000001 }} required fullWidth />
            )} />
            <Controller name="center_lng" control={control} defaultValue="" render={({ field }) => (
              <TextField {...field} label="Longitude centre" type="number" inputProps={{ step: 0.000001 }} required fullWidth />
            )} />
            <Controller name="radius_meters" control={control} defaultValue="500" render={({ field }) => (
              <TextField {...field} label="Rayon (mètres)" type="number" required fullWidth />
            )} />
          </DialogContent>
          <DialogActions>
            <Button onClick={() => setOpenGeofence(false)}>Annuler</Button>
            <Button type="submit" variant="contained" disabled={createGeofenceMut.isPending}>Créer</Button>
          </DialogActions>
        </form>
      </Dialog>
    </Box>
  );
}
