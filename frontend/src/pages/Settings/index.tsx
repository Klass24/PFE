import { useState } from 'react';
import {
  Box, Typography, Tabs, Tab, Card, CardContent, Chip, Button,
  LinearProgress, Alert, Dialog, DialogTitle, DialogContent, DialogActions,
  TextField, IconButton, Tooltip, Table, TableHead, TableRow, TableCell,
  TableBody, CircularProgress,
} from '@mui/material';
import ContentCopyIcon from '@mui/icons-material/ContentCopy';
import DeleteIcon from '@mui/icons-material/Delete';
import { useSnackbar } from 'notistack';
import { useAuthStore } from '@/store/authStore';
import { useCurrentSubscription, useQuotas, useChangePlan } from '@/hooks/useSubscription';
import { useApiTokens, useCreateApiToken, useRevokeApiToken } from '@/hooks/useApiTokens';

const PLAN_LABELS: Record<string, string> = { starter: 'Starter', pro: 'Business', enterprise: 'Enterprise' };
const PLAN_COLORS: Record<string, 'default' | 'primary' | 'secondary'> = { starter: 'default', pro: 'primary', enterprise: 'secondary' };

function TabPanel({ value, index, children }: { value: number; index: number; children: React.ReactNode }) {
  return value === index ? <Box sx={{ pt: 3 }}>{children}</Box> : null;
}

export default function SettingsPage() {
  const [tab, setTab] = useState(0);

  return (
    <Box>
      <Typography variant="h5" fontWeight="bold" sx={{ mb: 2 }}>Paramètres</Typography>
      <Tabs value={tab} onChange={(_, v) => setTab(v)}>
        <Tab label="Profil entreprise" />
        <Tab label="Abonnement" />
        <Tab label="Intégrations API" />
      </Tabs>
      <TabPanel value={tab} index={0}><ProfileTab /></TabPanel>
      <TabPanel value={tab} index={1}><SubscriptionTab /></TabPanel>
      <TabPanel value={tab} index={2}><ApiTokensTab /></TabPanel>
    </Box>
  );
}

function ProfileTab() {
  const user = useAuthStore((s) => s.user);
  return (
    <Card>
      <CardContent sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
        <Typography variant="h6">Informations du compte</Typography>
        <TextField label="Nom" value={user?.name ?? ''} disabled fullWidth />
        <TextField label="Email" value={user?.email ?? ''} disabled fullWidth />
        <TextField label="Rôle" value={user?.role ?? ''} disabled fullWidth />
      </CardContent>
    </Card>
  );
}

function SubscriptionTab() {
  const { data: subData, isLoading: subLoading } = useCurrentSubscription();
  const { data: quotaData, isLoading: quotaLoading } = useQuotas();
  const changePlan = useChangePlan();
  const { enqueueSnackbar } = useSnackbar();
  const [confirmPlan, setConfirmPlan] = useState<string | null>(null);

  const quota = quotaData?.data;
  const sub = subData?.data;

  const handleChangePlan = async () => {
    if (!confirmPlan) return;
    try {
      await changePlan.mutateAsync(confirmPlan);
      enqueueSnackbar('Plan mis à jour avec succès.', { variant: 'success' });
      setConfirmPlan(null);
    } catch (err: unknown) {
      enqueueSnackbar(typeof err === 'string' ? err : 'Erreur lors du changement de plan.', { variant: 'error' });
    }
  };

  if (subLoading || quotaLoading) return <CircularProgress />;

  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
      <Card>
        <CardContent>
          <Typography variant="h6" sx={{ mb: 1 }}>Plan actuel</Typography>
          <Box sx={{ display: 'flex', gap: 2, alignItems: 'center', mb: 2 }}>
            <Chip label={PLAN_LABELS[quota?.plan ?? 'starter'] ?? quota?.plan} color={PLAN_COLORS[quota?.plan ?? 'starter']} />
            {sub && <Typography variant="body2" color="text.secondary">Statut : {sub.billing_status}</Typography>}
          </Box>
          <Typography variant="subtitle2" sx={{ mb: 1 }}>Utilisation des quotas</Typography>
          {quota && (['vehicles', 'drivers', 'users'] as const).map((key) => {
            const item = quota[key];
            const pct = item.max > 0 ? (item.used / item.max) * 100 : 0;
            const label = key === 'vehicles' ? 'Véhicules' : key === 'drivers' ? 'Chauffeurs' : 'Utilisateurs';
            return (
              <Box key={key} sx={{ mb: 1.5 }}>
                <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
                  <Typography variant="body2">{label}</Typography>
                  <Typography variant="body2" fontWeight="bold">{item.used} / {item.max}</Typography>
                </Box>
                <LinearProgress variant="determinate" value={Math.min(pct, 100)} color={pct >= 90 ? 'error' : pct >= 70 ? 'warning' : 'primary'} sx={{ height: 8, borderRadius: 4 }} />
              </Box>
            );
          })}
        </CardContent>
      </Card>

      <Card>
        <CardContent>
          <Typography variant="h6" sx={{ mb: 2 }}>Changer de plan</Typography>
          <Box sx={{ display: 'flex', gap: 2 }}>
            {['starter', 'pro', 'enterprise'].map((plan) => (
              <Button
                key={plan}
                variant={quota?.plan === plan ? 'contained' : 'outlined'}
                disabled={quota?.plan === plan}
                onClick={() => setConfirmPlan(plan)}
              >
                {PLAN_LABELS[plan]}
              </Button>
            ))}
          </Box>
        </CardContent>
      </Card>

      <Dialog open={!!confirmPlan} onClose={() => setConfirmPlan(null)}>
        <DialogTitle>Confirmer le changement de plan</DialogTitle>
        <DialogContent>
          <Alert severity="warning" sx={{ mt: 1 }}>
            Vous allez passer au plan <strong>{PLAN_LABELS[confirmPlan ?? '']}</strong>. Les quotas seront ajustés en conséquence.
          </Alert>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setConfirmPlan(null)}>Annuler</Button>
          <Button variant="contained" onClick={handleChangePlan} disabled={changePlan.isPending}>
            {changePlan.isPending ? <CircularProgress size={20} /> : 'Confirmer'}
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}

function ApiTokensTab() {
  const { data, isLoading } = useApiTokens();
  const createToken = useCreateApiToken();
  const revokeToken = useRevokeApiToken();
  const { enqueueSnackbar } = useSnackbar();
  const [openCreate, setOpenCreate] = useState(false);
  const [tokenName, setTokenName] = useState('');
  const [newPlainToken, setNewPlainToken] = useState<string | null>(null);

  const handleCreate = async () => {
    try {
      const result = await createToken.mutateAsync({ name: tokenName });
      setNewPlainToken(result.data.plain_text);
      setTokenName('');
      enqueueSnackbar('Token créé.', { variant: 'success' });
    } catch (err: unknown) {
      enqueueSnackbar(typeof err === 'string' ? err : 'Erreur.', { variant: 'error' });
    }
  };

  const handleRevoke = async (uuid: string) => {
    if (!window.confirm('Révoquer ce token ?')) return;
    try {
      await revokeToken.mutateAsync(uuid);
      enqueueSnackbar('Token révoqué.', { variant: 'success' });
    } catch {
      enqueueSnackbar('Erreur lors de la révocation.', { variant: 'error' });
    }
  };

  const copyToken = (text: string) => {
    navigator.clipboard.writeText(text);
    enqueueSnackbar('Token copié.', { variant: 'info' });
  };

  if (isLoading) return <CircularProgress />;

  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', gap: 3 }}>
      {newPlainToken && (
        <Alert severity="success" onClose={() => setNewPlainToken(null)} action={
          <IconButton size="small" onClick={() => copyToken(newPlainToken)}><ContentCopyIcon fontSize="small" /></IconButton>
        }>
          <Typography variant="body2" fontWeight="bold">Votre token (ne sera plus affiché) :</Typography>
          <Typography variant="body2" sx={{ wordBreak: 'break-all', fontFamily: 'monospace' }}>{newPlainToken}</Typography>
        </Alert>
      )}

      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <Typography variant="h6">Tokens API</Typography>
        <Button variant="contained" onClick={() => setOpenCreate(true)}>Générer un token</Button>
      </Box>

      <Table size="small">
        <TableHead>
          <TableRow>
            <TableCell>Nom</TableCell>
            <TableCell>Dernière utilisation</TableCell>
            <TableCell>Expiration</TableCell>
            <TableCell>Créé le</TableCell>
            <TableCell>Actions</TableCell>
          </TableRow>
        </TableHead>
        <TableBody>
          {(data?.data ?? []).map((token) => (
            <TableRow key={token.uuid}>
              <TableCell>{token.name}</TableCell>
              <TableCell>{token.last_used_at ? new Date(token.last_used_at).toLocaleDateString('fr-FR') : 'Jamais'}</TableCell>
              <TableCell>{token.expires_at ? new Date(token.expires_at).toLocaleDateString('fr-FR') : 'Jamais'}</TableCell>
              <TableCell>{new Date(token.created_at).toLocaleDateString('fr-FR')}</TableCell>
              <TableCell>
                <Tooltip title="Révoquer">
                  <IconButton size="small" color="error" onClick={() => handleRevoke(token.uuid)}><DeleteIcon fontSize="small" /></IconButton>
                </Tooltip>
              </TableCell>
            </TableRow>
          ))}
          {(data?.data ?? []).length === 0 && (
            <TableRow><TableCell colSpan={5} align="center">Aucun token API.</TableCell></TableRow>
          )}
        </TableBody>
      </Table>

      <Dialog open={openCreate} onClose={() => setOpenCreate(false)} maxWidth="sm" fullWidth>
        <DialogTitle>Nouveau token API</DialogTitle>
        <DialogContent>
          <TextField label="Nom du token" fullWidth value={tokenName} onChange={(e) => setTokenName(e.target.value)} sx={{ mt: 1 }} />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setOpenCreate(false)}>Annuler</Button>
          <Button variant="contained" onClick={handleCreate} disabled={!tokenName || createToken.isPending}>
            {createToken.isPending ? <CircularProgress size={20} /> : 'Générer'}
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
