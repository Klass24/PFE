import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  AppBar, Toolbar, IconButton, Box, Avatar, Tooltip, Typography, Chip,
  Badge, Popover, List, ListItem, ListItemText, Button, Divider, CircularProgress, useTheme,
} from '@mui/material';
import MenuIcon              from '@mui/icons-material/Menu';
import LogoutIcon            from '@mui/icons-material/Logout';
import NotificationsNoneIcon from '@mui/icons-material/NotificationsNone';
import LightModeIcon         from '@mui/icons-material/LightMode';
import DarkModeIcon          from '@mui/icons-material/DarkMode';
import { useAuthStore }      from '@/store/authStore';
import { useThemeStore }     from '@/store/themeStore';
import { authService }       from '@/services/authService';
import { useUnreadCount, useNotifications, useMarkAllAsRead } from '@/hooks/useNotifications';
import { notificationService } from '@/services/notificationService';
import { useQueryClient }    from '@tanstack/react-query';

const ROLE_COLOR: Record<string, 'primary' | 'success' | 'warning'> = {
  admin: 'primary', manager: 'success', driver: 'warning',
};

const NOTIF_TYPE_ICONS: Record<string, string> = {
  document_expiring: '📄', incident_reported: '🚨', expense_threshold: '💰',
  mission_started: '🚗', license_expired: '⚠️', license_expiring: '⚠️',
};

interface Props {
  drawerWidth: number;
  onMenuToggle: () => void;
}

export default function Topbar({ drawerWidth, onMenuToggle }: Props) {
  const { user, logout } = useAuthStore();
  const mode = useThemeStore((s) => s.mode);
  const toggleMode = useThemeStore((s) => s.toggleMode);
  const theme = useTheme();
  const navigate = useNavigate();
  const qc = useQueryClient();

  const [anchorEl, setAnchorEl] = useState<HTMLButtonElement | null>(null);
  const notifOpen = Boolean(anchorEl);

  const { data: unreadCount = 0 } = useUnreadCount();
  const { data: notifData, isLoading: notifLoading } = useNotifications(false);
  const markAllMutation = useMarkAllAsRead();

  const handleLogout = async () => {
    try { await authService.logout(); } catch { /* ignore */ }
    logout();
    window.location.href = '/login';
  };

  const handleNotifClick = (event: React.MouseEvent<HTMLButtonElement>) => {
    setAnchorEl(event.currentTarget);
  };

  const handleNotifClose = () => setAnchorEl(null);

  const handleMarkAll = async () => {
    await markAllMutation.mutateAsync();
  };

  const handleNotifItemClick = async (uuid: string) => {
    await notificationService.markAsRead(uuid);
    qc.invalidateQueries({ queryKey: ['notifications'] });
  };

  const notifications = notifData?.data ?? [];

  return (
    <AppBar
      position="fixed"
      elevation={0}
      sx={{
        width: { sm: `calc(100% - ${drawerWidth}px)` },
        ml: { sm: `${drawerWidth}px` },
        bgcolor: '#ffffff',
        borderBottom: mode === 'light' ? '1px solid #e8ecef' : '1px solid #223142',
        color: theme.palette.text.primary,
        backgroundColor: theme.palette.background.paper,
      }}
    >
      <Toolbar sx={{ minHeight: '56px !important', px: { xs: 2, sm: 3 } }}>
        <IconButton edge="start" onClick={onMenuToggle} sx={{ mr: 1, display: { sm: 'none' }, color: '#6c757d' }}>
          <MenuIcon />
        </IconButton>

        <Box sx={{ flex: 1 }} />

        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
          <Tooltip title={mode === 'light' ? 'Activer mode sombre' : 'Activer mode clair'}>
            <IconButton size="small" onClick={toggleMode} sx={{ color: mode === 'light' ? '#6c757d' : '#9fb1c3' }}>
              {mode === 'light' ? <DarkModeIcon fontSize="small" /> : <LightModeIcon fontSize="small" />}
            </IconButton>
          </Tooltip>

          {/* Cloche notifications */}
          <Tooltip title="Notifications">
            <IconButton size="small" onClick={handleNotifClick} sx={{ color: mode === 'light' ? '#6c757d' : '#9fb1c3' }}>
              <Badge badgeContent={unreadCount || undefined} color="error" max={99}>
                <NotificationsNoneIcon fontSize="small" />
              </Badge>
            </IconButton>
          </Tooltip>

          <Popover
            open={notifOpen}
            anchorEl={anchorEl}
            onClose={handleNotifClose}
            anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
            transformOrigin={{ vertical: 'top', horizontal: 'right' }}
            PaperProps={{ sx: { width: 360, maxHeight: 480, overflow: 'hidden', display: 'flex', flexDirection: 'column' } }}
          >
            <Box sx={{ px: 2, py: 1.5, display: 'flex', alignItems: 'center', justifyContent: 'space-between', borderBottom: '1px solid #e8ecef' }}>
              <Typography variant="subtitle2" fontWeight={700}>Notifications</Typography>
              <Box sx={{ display: 'flex', gap: 1, alignItems: 'center' }}>
                {unreadCount > 0 && (
                  <Button size="small" onClick={handleMarkAll} sx={{ fontSize: 11 }}>
                    Tout lire
                  </Button>
                )}
                <Button size="small" onClick={() => { handleNotifClose(); navigate('/notifications'); }} sx={{ fontSize: 11 }}>
                  Voir tout
                </Button>
              </Box>
            </Box>

            <Box sx={{ overflowY: 'auto', flex: 1 }}>
              {notifLoading ? (
                <Box sx={{ display: 'flex', justifyContent: 'center', p: 3 }}>
                  <CircularProgress size={24} />
                </Box>
              ) : notifications.length === 0 ? (
                <Box sx={{ p: 3, textAlign: 'center' }}>
                  <Typography variant="body2" color="text.secondary">Aucune notification</Typography>
                </Box>
              ) : (
                <List disablePadding>
                  {notifications.slice(0, 10).map((n, i) => (
                    <Box key={n.uuid}>
                      <ListItem
                        alignItems="flex-start"
                        onClick={() => handleNotifItemClick(n.uuid)}
                        sx={{
                          cursor: 'pointer',
                          bgcolor: n.is_read ? 'transparent' : 'rgba(2,117,216,0.05)',
                          '&:hover': { bgcolor: '#f8f9fa' },
                          py: 1.5, px: 2,
                        }}
                      >
                        <Box sx={{ mr: 1.5, fontSize: 18, lineHeight: 1.4 }}>
                          {NOTIF_TYPE_ICONS[n.type] ?? '🔔'}
                        </Box>
                        <ListItemText
                          primary={
                            <Typography variant="body2" sx={{ fontWeight: n.is_read ? 400 : 600, fontSize: 13, lineHeight: 1.4 }}>
                              {n.message}
                            </Typography>
                          }
                          secondary={
                            <Typography variant="caption" color="text.secondary">
                              {n.sent_at ? new Date(n.sent_at).toLocaleString('fr-FR') : ''}
                            </Typography>
                          }
                        />
                        {!n.is_read && (
                          <Box sx={{ width: 8, height: 8, borderRadius: '50%', bgcolor: '#2563EB', ml: 1, mt: 0.5, flexShrink: 0 }} />
                        )}
                      </ListItem>
                      {i < notifications.length - 1 && <Divider />}
                    </Box>
                  ))}
                </List>
              )}
            </Box>
          </Popover>

          {/* User info */}
          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, ml: 0.5 }}>
            <Avatar sx={{ width: 30, height: 30, bgcolor: '#2563EB', fontSize: 12, fontWeight: 700 }}>
              {user?.name?.charAt(0).toUpperCase()}
            </Avatar>
            <Box sx={{ display: { xs: 'none', md: 'block' } }}>
              <Typography sx={{ fontSize: 13, fontWeight: 600, lineHeight: 1.2, color: theme.palette.text.primary }}>
                {user?.name}
              </Typography>
              <Chip
                label={user?.role}
                size="small"
                color={ROLE_COLOR[user?.role ?? 'driver']}
                sx={{ height: 16, fontSize: 10, fontWeight: 600, textTransform: 'capitalize', '& .MuiChip-label': { px: 0.8 } }}
              />
            </Box>
          </Box>

          <Tooltip title="Déconnexion">
            <IconButton size="small" onClick={handleLogout} sx={{ color: mode === 'light' ? '#6c757d' : '#9fb1c3', ml: 0.5 }}>
              <LogoutIcon fontSize="small" />
            </IconButton>
          </Tooltip>
        </Box>
      </Toolbar>
    </AppBar>
  );
}

