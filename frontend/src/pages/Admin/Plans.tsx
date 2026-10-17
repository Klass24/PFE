import { useState } from 'react';
import {
  Box, Typography, Button, Card, CardContent, Grid, Chip, IconButton,
  Dialog, DialogTitle, DialogContent, DialogActions, TextField, Switch,
  FormControlLabel, Tooltip,
} from '@mui/material';
import AddIcon from '@mui/icons-material/Add';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';
import StarIcon from '@mui/icons-material/Star';
import { useSnackbar } from 'notistack';
import { useAdminPlans, useAdminCreatePlan, useAdminUpdatePlan, useAdminDeletePlan } from '@/hooks/useAdmin';
import type { Plan } from '@/types';

const emptyPlan = {
  name: '', slug: '', price_monthly: 0, price_yearly: 0,
  max_vehicles: 5, max_drivers: 5, max_users: 3,
  features: [] as string[], is_active: true, is_popular: false,
  sort_order: 0, trial_days: 0,
};

export default function AdminPlansPage() {
  const { data, isLoading } = useAdminPlans();
  const createMut = useAdminCreatePlan();
  const updateMut = useAdminUpdatePlan();
  const deleteMut = useAdminDeletePlan();
  const { enqueueSnackbar } = useSnackbar();

  const [dialog, setDialog] = useState<{ open: boolean; mode: 'create' | 'edit'; plan: typeof emptyPlan & { uuid?: string } }>({
    open: false, mode: 'create', plan: { ...emptyPlan },
  });
  const [featuresText, setFeaturesText] = useState('');

  const plans: Plan[] = data?.data ?? [];

  const openCreate = () => {
    setDialog({ open: true, mode: 'create', plan: { ...emptyPlan } });
    setFeaturesText('');
  };

  const openEdit = (p: Plan) => {
    setDialog({ open: true, mode: 'edit', plan: { ...p } });
    setFeaturesText((p.features ?? []).join('\n'));
  };

  const handleSave = async () => {
    const d = {
      ...dialog.plan,
      features: featuresText.split('\n').map((f) => f.trim()).filter(Boolean),
    };
    try {
      if (dialog.mode === 'create') {
        await createMut.mutateAsync(d);
        enqueueSnackbar('Plan créé.', { variant: 'success' });
      } else {
        await updateMut.mutateAsync({ uuid: d.uuid!, data: d });
        enqueueSnackbar('Plan mis à jour.', { variant: 'success' });
      }
      setDialog((s) => ({ ...s, open: false }));
    } catch {
      enqueueSnackbar('Erreur lors de la sauvegarde.', { variant: 'error' });
    }
  };

  const handleDelete = async (uuid: string) => {
    if (!window.confirm('Supprimer ce plan ? Cette action est irréversible.')) return;
    try {
      await deleteMut.mutateAsync(uuid);
      enqueueSnackbar('Plan supprimé.', { variant: 'success' });
    } catch {
      enqueueSnackbar('Impossible de supprimer (des entreprises utilisent ce plan).', { variant: 'error' });
    }
  };

  const setField = (key: string, value: unknown) => {
    setDialog((s) => ({ ...s, plan: { ...s.plan, [key]: value } }));
  };

  return (
    <Box>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3 }}>
        <Typography variant="h5" fontWeight="bold">Gestion des Plans</Typography>
        <Button variant="contained" startIcon={<AddIcon />} onClick={openCreate}>
          Nouveau plan
        </Button>
      </Box>

      {isLoading ? (
        <Typography>Chargement...</Typography>
      ) : (
        <Grid container spacing={3}>
          {plans.map((p) => (
            <Grid item xs={12} md={4} key={p.uuid}>
              <Card sx={{
                border: p.is_popular ? '2px solid' : '1px solid',
                borderColor: p.is_popular ? 'primary.main' : '#e8ecef',
                position: 'relative',
                opacity: p.is_active ? 1 : 0.6,
              }}>
                {p.is_popular && (
                  <Chip icon={<StarIcon sx={{ fontSize: 14 }} />} label="Populaire" color="primary" size="small"
                    sx={{ position: 'absolute', top: -12, left: '50%', transform: 'translateX(-50%)' }} />
                )}
                <CardContent>
                  <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 1 }}>
                    <Typography variant="h6" fontWeight="bold">{p.name}</Typography>
                    <Box>
                      <Tooltip title="Modifier">
                        <IconButton size="small" onClick={() => openEdit(p)}><EditIcon fontSize="small" /></IconButton>
                      </Tooltip>
                      <Tooltip title="Supprimer">
                        <IconButton size="small" color="error" onClick={() => handleDelete(p.uuid)}><DeleteIcon fontSize="small" /></IconButton>
                      </Tooltip>
                    </Box>
                  </Box>

                  <Chip label={p.slug} size="small" variant="outlined" sx={{ mb: 1 }} />
                  {!p.is_active && <Chip label="Inactif" size="small" color="error" sx={{ ml: 1, mb: 1 }} />}

                  <Box sx={{ mb: 2 }}>
                    <Typography variant="h4" fontWeight="bold" color="primary">
                      {p.is_free ? 'Gratuit' : `${p.price_monthly}€`}
                    </Typography>
                    {!p.is_free && (
                      <Typography variant="body2" color="text.secondary">
                        /mois — {p.price_yearly}€/an {p.yearly_saving > 0 && `(-${p.yearly_saving}%)`}
                      </Typography>
                    )}
                  </Box>

                  <Box sx={{ mb: 2 }}>
                    <Typography variant="caption" fontWeight={600} color="text.secondary">QUOTAS</Typography>
                    <Typography variant="body2">Véhicules: {p.max_vehicles >= 999 ? '∞' : p.max_vehicles}</Typography>
                    <Typography variant="body2">Chauffeurs: {p.max_drivers >= 999 ? '∞' : p.max_drivers}</Typography>
                    <Typography variant="body2">Utilisateurs: {p.max_users >= 999 ? '∞' : p.max_users}</Typography>
                  </Box>

                  {p.trial_days > 0 && (
                    <Chip label={`${p.trial_days}j d'essai`} size="small" color="success" sx={{ mb: 1 }} />
                  )}

                  {'companies_count' in p && (
                    <Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
                      {(p as Plan & { companies_count: number }).companies_count} entreprise(s)
                    </Typography>
                  )}
                </CardContent>
              </Card>
            </Grid>
          ))}
        </Grid>
      )}

      {/* Create/Edit Dialog */}
      <Dialog open={dialog.open} onClose={() => setDialog((s) => ({ ...s, open: false }))} maxWidth="sm" fullWidth>
        <DialogTitle>{dialog.mode === 'create' ? 'Créer un plan' : 'Modifier le plan'}</DialogTitle>
        <DialogContent>
          <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, mt: 1 }}>
            <TextField label="Nom" value={dialog.plan.name} onChange={(e) => setField('name', e.target.value)} fullWidth />
            <TextField label="Slug" value={dialog.plan.slug} onChange={(e) => setField('slug', e.target.value)} fullWidth
              disabled={dialog.mode === 'edit'} helperText="Identifiant unique (ex: starter, pro, enterprise)" />

            <Box sx={{ display: 'flex', gap: 2 }}>
              <TextField label="Prix mensuel (€)" type="number" value={dialog.plan.price_monthly}
                onChange={(e) => setField('price_monthly', +e.target.value)} fullWidth />
              <TextField label="Prix annuel (€)" type="number" value={dialog.plan.price_yearly}
                onChange={(e) => setField('price_yearly', +e.target.value)} fullWidth />
            </Box>

            <Box sx={{ display: 'flex', gap: 2 }}>
              <TextField label="Max véhicules" type="number" value={dialog.plan.max_vehicles}
                onChange={(e) => setField('max_vehicles', +e.target.value)} fullWidth />
              <TextField label="Max chauffeurs" type="number" value={dialog.plan.max_drivers}
                onChange={(e) => setField('max_drivers', +e.target.value)} fullWidth />
              <TextField label="Max utilisateurs" type="number" value={dialog.plan.max_users}
                onChange={(e) => setField('max_users', +e.target.value)} fullWidth />
            </Box>

            <Box sx={{ display: 'flex', gap: 2 }}>
              <TextField label="Jours d'essai" type="number" value={dialog.plan.trial_days}
                onChange={(e) => setField('trial_days', +e.target.value)} fullWidth />
              <TextField label="Ordre d'affichage" type="number" value={dialog.plan.sort_order}
                onChange={(e) => setField('sort_order', +e.target.value)} fullWidth />
            </Box>

            <TextField label="Fonctionnalités (une par ligne)" multiline rows={4} value={featuresText}
              onChange={(e) => setFeaturesText(e.target.value)} fullWidth />

            <Box sx={{ display: 'flex', gap: 2 }}>
              <FormControlLabel control={<Switch checked={dialog.plan.is_active}
                onChange={(e) => setField('is_active', e.target.checked)} />} label="Actif" />
              <FormControlLabel control={<Switch checked={dialog.plan.is_popular}
                onChange={(e) => setField('is_popular', e.target.checked)} />} label="Populaire" />
            </Box>
          </Box>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setDialog((s) => ({ ...s, open: false }))}>Annuler</Button>
          <Button variant="contained" onClick={handleSave}
            disabled={createMut.isPending || updateMut.isPending}>
            {dialog.mode === 'create' ? 'Créer' : 'Enregistrer'}
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
