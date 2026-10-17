import { useNavigate, useLocation } from 'react-router-dom';
import {
  Box, Drawer, List, ListItem, ListItemButton, ListItemIcon,
  ListItemText, Typography, Divider, Avatar, useMediaQuery, useTheme,
} from '@mui/material';
import DashboardIcon      from '@mui/icons-material/Dashboard';
import DirectionsCarIcon  from '@mui/icons-material/DirectionsCar';
import PersonIcon         from '@mui/icons-material/Person';
import MapIcon            from '@mui/icons-material/Map';
import ReceiptIcon        from '@mui/icons-material/Receipt';
import WarningIcon        from '@mui/icons-material/Warning';
import DescriptionIcon    from '@mui/icons-material/Description';
import NotificationsIcon   from '@mui/icons-material/Notifications';
import FileDownloadIcon    from '@mui/icons-material/FileDownload';
import GroupIcon           from '@mui/icons-material/Group';
import SettingsIcon        from '@mui/icons-material/Settings';
import HistoryIcon         from '@mui/icons-material/History';
import AdminPanelSettingsIcon from '@mui/icons-material/AdminPanelSettings';
import BusinessIcon        from '@mui/icons-material/Business';
import SecurityIcon        from '@mui/icons-material/Security';
import BuildIcon           from '@mui/icons-material/Build';
import LocalGasStationIcon from '@mui/icons-material/LocalGasStation';
import GpsFixedIcon        from '@mui/icons-material/GpsFixed';
import NotificationsActiveIcon from '@mui/icons-material/NotificationsActive';
import PaymentIcon from '@mui/icons-material/Payment';
import CardMembershipIcon from '@mui/icons-material/CardMembership';
import ReceiptLongIcon from '@mui/icons-material/ReceiptLong';
import { useAuthStore }   from '@/store/authStore';

// FleetControl sidebar color palette
const SIDEBAR_BG   = '#0F172A';
const SIDEBAR_HOVER = '#1E293B';
const SIDEBAR_ACTIVE = '#2563EB';
const SIDEBAR_TEXT  = '#94A3B8';
const SIDEBAR_TEXT_ACTIVE = '#ffffff';

const navItems = [
  { label: 'Tableau de bord', path: '/dashboard', icon: <DashboardIcon fontSize="small" />, roles: ['admin', 'manager', 'driver'] },
  { label: 'Véhicules',       path: '/vehicles',  icon: <DirectionsCarIcon fontSize="small" />, roles: ['admin', 'manager'] },
  { label: 'Chauffeurs',      path: '/drivers',   icon: <PersonIcon fontSize="small" />, roles: ['admin', 'manager'] },
  { label: 'Missions',        path: '/missions',  icon: <MapIcon fontSize="small" />, roles: ['admin', 'manager', 'driver'] },
  { label: 'Dépenses',        path: '/expenses',  icon: <ReceiptIcon fontSize="small" />, roles: ['admin', 'manager', 'driver'] },
  { label: 'Incidents',       path: '/incidents', icon: <WarningIcon fontSize="small" />, roles: ['admin', 'manager', 'driver'] },
  { label: 'Documents',       path: '/documents',     icon: <DescriptionIcon fontSize="small" />, roles: ['admin', 'manager'] },
  { label: 'Maintenance',     path: '/maintenance',   icon: <BuildIcon fontSize="small" />, roles: ['admin', 'manager'] },
  { label: 'Carburant',       path: '/fuel',          icon: <LocalGasStationIcon fontSize="small" />, roles: ['admin', 'manager'] },
  { label: 'GPS Tracking',    path: '/tracking',      icon: <GpsFixedIcon fontSize="small" />, roles: ['admin', 'manager', 'driver'] },
  { label: 'Règles d\'alerte', path: '/alert-rules',  icon: <NotificationsActiveIcon fontSize="small" />, roles: ['admin', 'manager'] },
  { label: 'Utilisateurs',    path: '/users',         icon: <GroupIcon fontSize="small" />, roles: ['admin', 'super_admin'] },
  { label: 'Notifications',   path: '/notifications', icon: <NotificationsIcon fontSize="small" />, roles: ['admin', 'manager', 'driver'] },
  { label: 'Export',          path: '/export',        icon: <FileDownloadIcon fontSize="small" />, roles: ['admin', 'manager'] },
  { label: 'Audit',           path: '/audit',         icon: <HistoryIcon fontSize="small" />, roles: ['admin'] },
  { label: 'Permissions',     path: '/permissions',   icon: <SecurityIcon fontSize="small" />, roles: ['admin', 'super_admin'] },
  { label: 'Paramètres',      path: '/settings',      icon: <SettingsIcon fontSize="small" />, roles: ['admin', 'manager'] },
  { label: 'Abonnement',      path: '/pricing',       icon: <PaymentIcon fontSize="small" />, roles: ['admin'] },
];

const adminItems = [
  { label: 'Back-office',      path: '/admin/dashboard',      icon: <AdminPanelSettingsIcon fontSize="small" /> },
  { label: 'Entreprises',      path: '/admin/companies',      icon: <BusinessIcon fontSize="small" /> },
  { label: 'Plans',            path: '/admin/plans',           icon: <CardMembershipIcon fontSize="small" /> },
  { label: 'Abonnements',      path: '/admin/subscriptions',   icon: <ReceiptLongIcon fontSize="small" /> },
];

interface Props {
  drawerWidth: number;
  mobileOpen: boolean;
  onClose: () => void;
}

export default function Sidebar({ drawerWidth, mobileOpen, onClose }: Props) {
  const navigate = useNavigate();
  const { pathname } = useLocation();
  const user = useAuthStore((s) => s.user);
  const theme = useTheme();
  const isDesktop = useMediaQuery(theme.breakpoints.up('sm'));

  const drawerContent = (
    <Box sx={{  display: 'flex', flexDirection: 'column', bgcolor: SIDEBAR_BG }}>

      {/* Logo */}
      <Box sx={{ px: 3, py: 2.5, display: 'flex', alignItems: 'center', gap: 1.5 }}>
        <Box sx={{ display: 'flex', alignItems: 'center' }}>
          <Box component="img" src="/images/FleetControl.png" alt="FleetControl" sx={{ width: 36, height: 36, borderRadius: 1 }} />
        </Box>
        <Typography sx={{ color: '#fff', fontWeight: 700, fontSize: 16, letterSpacing: 0.5, fontFamily: 'Poppins, sans-serif' }}>
          FleetControl
        </Typography>
      </Box>

      <Divider sx={{ borderColor: 'rgba(255,255,255,0.07)', mx: 2 }} />

      {/* Nav label */}
      <Typography sx={{ px: 3, pt: 2.5, pb: 1, fontSize: 10, fontWeight: 700, letterSpacing: 1.5, color: 'rgba(160,176,200,0.5)', textTransform: 'uppercase' }}>
        Navigation
      </Typography>

      {/* Nav items */}
      <List sx={{ px: 1.5, flex: 1 }} dense>
        {navItems
          .filter((item) => !('roles' in item) || (item.roles as string[])?.includes(user?.role ?? ''))
          .map((item) => {
          const active = pathname.startsWith(item.path);
          return (
            <ListItem key={item.path} disablePadding sx={{ mb: 0.5 }}>
              <ListItemButton
                onClick={() => { navigate(item.path); onClose(); }}
                sx={{
                  borderRadius: 1.5,
                  px: 1.5, py: 1,
                  bgcolor: active ? SIDEBAR_ACTIVE : 'transparent',
                  '&:hover': { bgcolor: active ? SIDEBAR_ACTIVE : SIDEBAR_HOVER },
                  transition: 'background 0.15s',
                }}
              >
                <ListItemIcon sx={{
                  minWidth: 34,
                  color: active ? SIDEBAR_TEXT_ACTIVE : SIDEBAR_TEXT,
                }}>
                  {item.icon}
                </ListItemIcon>
                <ListItemText
                  primary={item.label}
                  primaryTypographyProps={{
                    fontSize: 13.5,
                    fontWeight: active ? 600 : 400,
                    color: active ? SIDEBAR_TEXT_ACTIVE : SIDEBAR_TEXT,
                  }}
                />
                {active && (
                  <Box sx={{ width: 4, height: 4, borderRadius: '50%', bgcolor: '#fff', opacity: 0.7 }} />
                )}
              </ListItemButton>
            </ListItem>
          );
        })}
      </List>

      {/* Admin section — super_admin only */}
      {user?.role === 'super_admin' && (
        <>
          <Divider sx={{ borderColor: 'rgba(255,255,255,0.07)', mx: 2 }} />
          <Typography sx={{ px: 3, pt: 2, pb: 1, fontSize: 10, fontWeight: 700, letterSpacing: 1.5, color: 'rgba(160,176,200,0.5)', textTransform: 'uppercase' }}>
            Administration
          </Typography>
          <List sx={{ px: 1.5 }} dense>
            {adminItems.map((item) => {
              const active = pathname.startsWith(item.path);
              return (
                <ListItem key={item.path} disablePadding sx={{ mb: 0.5 }}>
                  <ListItemButton
                    onClick={() => { navigate(item.path); onClose(); }}
                    sx={{
                      borderRadius: 1.5, px: 1.5, py: 1,
                      bgcolor: active ? SIDEBAR_ACTIVE : 'transparent',
                      '&:hover': { bgcolor: active ? SIDEBAR_ACTIVE : SIDEBAR_HOVER },
                    }}
                  >
                    <ListItemIcon sx={{ minWidth: 34, color: active ? SIDEBAR_TEXT_ACTIVE : SIDEBAR_TEXT }}>{item.icon}</ListItemIcon>
                    <ListItemText primary={item.label} primaryTypographyProps={{ fontSize: 13.5, fontWeight: active ? 600 : 400, color: active ? SIDEBAR_TEXT_ACTIVE : SIDEBAR_TEXT }} />
                  </ListItemButton>
                </ListItem>
              );
            })}
          </List>
        </>
      )}

      <Divider sx={{ borderColor: 'rgba(255,255,255,0.07)', mx: 2 }} />

      {/* User footer */}
      <Box sx={{ px: 2, py: 2, display: 'flex', alignItems: 'center', gap: 1.5 }}>
        <Avatar sx={{ width: 32, height: 32, bgcolor: SIDEBAR_ACTIVE, fontSize: 13 }}>
          {user?.name?.charAt(0).toUpperCase()}
        </Avatar>
        <Box sx={{ overflow: 'hidden' }}>
          <Typography sx={{ color: '#fff', fontSize: 12.5, fontWeight: 600, lineHeight: 1.3, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
            {user?.name}
          </Typography>
          <Typography sx={{ color: SIDEBAR_TEXT, fontSize: 11, textTransform: 'capitalize' }}>
            {user?.role}
          </Typography>
        </Box>
      </Box>
    </Box>
  );

  const paperSx = { width: drawerWidth, boxSizing: 'border-box' as const, border: 'none' };

  return (
    <Box component="nav" sx={{ width: { sm: drawerWidth }, flexShrink: { sm: 0 } }}>
      {!isDesktop && (
        <Drawer
          variant="temporary"
          open={mobileOpen}
          onClose={onClose}
          keepMounted={false}
          ModalProps={{ keepMounted: false }}
          sx={{ '& .MuiDrawer-paper': paperSx }}
        >
          {drawerContent}
        </Drawer>
      )}
      <Drawer variant="permanent"
        sx={{ display: { xs: 'none', sm: 'block' }, '& .MuiDrawer-paper': paperSx }}
        open>
        {drawerContent}
      </Drawer>
    </Box>
  );
}
