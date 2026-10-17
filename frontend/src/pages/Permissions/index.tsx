import { useState, useEffect, useMemo } from 'react';
import {
  Box, Typography, Tabs, Tab, Card, CardContent, Checkbox,
  FormControlLabel, Button, CircularProgress, Alert, Snackbar,
  Table, TableHead, TableBody, TableRow, TableCell, Chip,
  FormControl, InputLabel, Select, MenuItem,
} from '@mui/material';
import SecurityIcon from '@mui/icons-material/Security';
import SaveIcon from '@mui/icons-material/Save';
import { useAuthStore } from '@/store/authStore';
import {
  useAllPermissions,
  useRolePermissions,
  useUpdateRolePermissions,
  useUserPermissions,
  useUpdateUserPermissions,
} from '@/hooks/usePermissions';
import { useUsers } from '@/hooks/useUsers';

const MODULE_LABELS: Record<string, string> = {
  vehicles: 'Véhicules',
  drivers: 'Chauffeurs',
  missions: 'Missions',
  expenses: 'Dépenses',
  incidents: 'Incidents',
  documents: 'Documents',
  users: 'Utilisateurs',
  company: 'Entreprise',
  reports: 'Rapports',
};

const ACTION_LABELS: Record<string, string> = {
  view: 'Voir',
  create: 'Créer',
  update: 'Modifier',
  delete: 'Supprimer',
  start: 'Démarrer',
  complete: 'Terminer',
  cancel: 'Annuler',
  manage: 'Gérer',
};

export default function PermissionsPage() {
  const user = useAuthStore((s) => s.user);
  const isSuperAdmin = user?.role === 'super_admin';
  const isAdmin = user?.role === 'admin';

  if (!isSuperAdmin && !isAdmin) {
    return <Alert severity="error">Accès non autorisé.</Alert>;
  }

  return (
    <Box sx={{ p: 3 }}>
      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 3 }}>
        <SecurityIcon color="primary" />
        <Typography variant="h5">Gestion des permissions</Typography>
      </Box>

      {isSuperAdmin && <SuperAdminView />}
      {isAdmin && !isSuperAdmin && <AdminView />}
    </Box>
  );
}

/* ─── Super Admin : gère les rôles admin / manager ──────────────────────────── */
function SuperAdminView() {
  const [tab, setTab] = useState<'admin' | 'manager'>('admin');
  const { data: allPerms, isLoading: loadingAll } = useAllPermissions();
  const { data: rolePermsData, isLoading: loadingRole } = useRolePermissions(tab);
  const updateMutation = useUpdateRolePermissions();

  const [selected, setSelected] = useState<string[]>([]);
  const [snack, setSnack] = useState('');

  const grouped = useMemo(() => (allPerms?.data as Record<string, string[]>) ?? {}, [allPerms]);
  const allPermsList = useMemo(() => Object.values(grouped).flat(), [grouped]);

  useEffect(() => {
    if (rolePermsData?.data?.permissions) {
      setSelected([...rolePermsData.data.permissions]);
    }
  }, [rolePermsData]);

  const toggle = (perm: string) =>
    setSelected((prev) => (prev.includes(perm) ? prev.filter((p) => p !== perm) : [...prev, perm]));

  const toggleModule = (_module: string, perms: string[]) => {
    const allChecked = perms.every((p) => selected.includes(p));
    setSelected((prev) =>
      allChecked ? prev.filter((p) => !perms.includes(p)) : [...new Set([...prev, ...perms])],
    );
  };

  const handleSave = () => {
    updateMutation.mutate(
      { role: tab, permissions: selected },
      { onSuccess: () => setSnack('Permissions mises à jour avec succès.') },
    );
  };

  if (loadingAll || loadingRole) return <CircularProgress sx={{ mt: 4 }} />;

  return (
    <>
      <Tabs value={tab} onChange={(_, v) => setTab(v)} sx={{ mb: 3 }}>
        <Tab label="Rôle Admin" value="admin" />
        <Tab label="Rôle Manager" value="manager" />
      </Tabs>

      <PermissionGrid
        grouped={grouped}
        selected={selected}
        onToggle={toggle}
        onToggleModule={toggleModule}
        allPermsList={allPermsList}
      />

      <Box sx={{ mt: 3, display: 'flex', gap: 2 }}>
        <Button
          variant="contained"
          startIcon={<SaveIcon />}
          onClick={handleSave}
          disabled={updateMutation.isPending}
        >
          {updateMutation.isPending ? 'Enregistrement…' : 'Enregistrer'}
        </Button>
        <Chip label={`${selected.length} / ${allPermsList.length} permissions`} variant="outlined" />
      </Box>

      <Snackbar open={!!snack} autoHideDuration={3000} onClose={() => setSnack('')}>
        <Alert severity="success">{snack}</Alert>
      </Snackbar>
    </>
  );
}

/* ─── Admin : gère les permissions des drivers de son entreprise ────────────── */
function AdminView() {
  const { data: usersData, isLoading: loadingUsers } = useUsers({ role: 'driver' });
  const { data: allPerms, isLoading: loadingAll } = useAllPermissions();
  const [selectedUser, setSelectedUser] = useState('');
  const { data: userPermsData, isLoading: loadingUserPerms } = useUserPermissions(selectedUser);
  const updateMutation = useUpdateUserPermissions();

  const [selected, setSelected] = useState<string[]>([]);
  const [snack, setSnack] = useState('');

  const grouped = useMemo(() => (allPerms?.data as Record<string, string[]>) ?? {}, [allPerms]);
  const allPermsList = useMemo(() => Object.values(grouped).flat(), [grouped]);

  // Liste des drivers
  const drivers = useMemo(() => {
    if (!usersData) return [];
    const list = (usersData as any)?.data ?? usersData;
    return Array.isArray(list) ? list : [];
  }, [usersData]);

  useEffect(() => {
    if (userPermsData?.data?.permissions) {
      setSelected([...userPermsData.data.permissions]);
    }
  }, [userPermsData]);

  const toggle = (perm: string) =>
    setSelected((prev) => (prev.includes(perm) ? prev.filter((p) => p !== perm) : [...prev, perm]));

  const toggleModule = (_module: string, perms: string[]) => {
    const allChecked = perms.every((p) => selected.includes(p));
    setSelected((prev) =>
      allChecked ? prev.filter((p) => !perms.includes(p)) : [...new Set([...prev, ...perms])],
    );
  };

  const handleSave = () => {
    if (!selectedUser) return;
    updateMutation.mutate(
      { uuid: selectedUser, permissions: selected },
      { onSuccess: () => setSnack('Permissions du chauffeur mises à jour.') },
    );
  };

  if (loadingAll || loadingUsers) return <CircularProgress sx={{ mt: 4 }} />;

  return (
    <>
      <Card sx={{ mb: 3, p: 2 }}>
        <FormControl fullWidth size="small">
          <InputLabel>Sélectionner un chauffeur</InputLabel>
          <Select
            value={selectedUser}
            label="Sélectionner un chauffeur"
            onChange={(e) => setSelectedUser(e.target.value)}
          >
            {drivers.map((d: any) => (
              <MenuItem key={d.uuid} value={d.uuid}>
                {d.name} — {d.email}
              </MenuItem>
            ))}
          </Select>
        </FormControl>
      </Card>

      {selectedUser && loadingUserPerms && <CircularProgress sx={{ mt: 2 }} />}

      {selectedUser && !loadingUserPerms && (
        <>
          <PermissionGrid
            grouped={grouped}
            selected={selected}
            onToggle={toggle}
            onToggleModule={toggleModule}
            allPermsList={allPermsList}
          />

          <Box sx={{ mt: 3, display: 'flex', gap: 2 }}>
            <Button
              variant="contained"
              startIcon={<SaveIcon />}
              onClick={handleSave}
              disabled={updateMutation.isPending}
            >
              {updateMutation.isPending ? 'Enregistrement…' : 'Enregistrer'}
            </Button>
            <Chip label={`${selected.length} / ${allPermsList.length} permissions`} variant="outlined" />
          </Box>
        </>
      )}

      <Snackbar open={!!snack} autoHideDuration={3000} onClose={() => setSnack('')}>
        <Alert severity="success">{snack}</Alert>
      </Snackbar>
    </>
  );
}

/* ─── Composant grille de permissions réutilisable ──────────────────────────── */
interface PermissionGridProps {
  grouped: Record<string, string[]>;
  selected: string[];
  onToggle: (perm: string) => void;
  onToggleModule: (module: string, perms: string[]) => void;
  allPermsList: string[];
}

function PermissionGrid({ grouped, selected, onToggle, onToggleModule }: PermissionGridProps) {
  return (
    <Card>
      <CardContent sx={{ p: 0 }}>
        <Table size="small">
          <TableHead>
            <TableRow>
              <TableCell sx={{ fontWeight: 700, width: 180 }}>Module</TableCell>
              <TableCell sx={{ fontWeight: 700 }}>Permissions</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {Object.entries(grouped).map(([module, perms]) => {
              const allChecked = perms.every((p) => selected.includes(p));
              const someChecked = perms.some((p) => selected.includes(p));
              return (
                <TableRow key={module} hover>
                  <TableCell>
                    <FormControlLabel
                      control={
                        <Checkbox
                          checked={allChecked}
                          indeterminate={someChecked && !allChecked}
                          onChange={() => onToggleModule(module, perms)}
                          size="small"
                        />
                      }
                      label={
                        <Typography variant="body2" fontWeight={600}>
                          {MODULE_LABELS[module] ?? module}
                        </Typography>
                      }
                    />
                  </TableCell>
                  <TableCell>
                    <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1 }}>
                      {perms.map((perm) => {
                        const action = perm.split('.')[1] ?? perm;
                        return (
                          <FormControlLabel
                            key={perm}
                            control={
                              <Checkbox
                                checked={selected.includes(perm)}
                                onChange={() => onToggle(perm)}
                                size="small"
                              />
                            }
                            label={ACTION_LABELS[action] ?? action}
                            sx={{ mr: 2 }}
                          />
                        );
                      })}
                    </Box>
                  </TableCell>
                </TableRow>
              );
            })}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  );
}
