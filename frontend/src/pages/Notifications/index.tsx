import { useState } from 'react';
import {
  Box, Typography, Card, CardContent, List, ListItem, ListItemText,
  ListItemAvatar, Avatar, Chip, Button, Tabs, Tab, CircularProgress,
  Alert, Divider, Tooltip,
} from '@mui/material';
import NotificationsNoneIcon from '@mui/icons-material/NotificationsNone';
import WarningAmberIcon      from '@mui/icons-material/WarningAmber';
import ErrorOutlineIcon      from '@mui/icons-material/ErrorOutline';
import InfoIcon              from '@mui/icons-material/Info';
import CheckCircleOutlineIcon from '@mui/icons-material/CheckCircleOutline';
import DoneAllIcon           from '@mui/icons-material/DoneAll';
import { useNotifications, useMarkAsRead, useMarkAllAsRead } from '@/hooks/useNotifications';
import type { Notification } from '@/types';

function timeAgo(dateStr: string): string {
  const diff = Date.now() - new Date(dateStr).getTime();
  const m = Math.floor(diff / 60000);
  if (m < 1) return 'il y a quelques secondes';
  if (m < 60) return `il y a ${m} min`;
  const h = Math.floor(m / 60);
  if (h < 24) return `il y a ${h}h`;
  const d = Math.floor(h / 24);
  return `il y a ${d}j`;
}

const TYPE_META: Record<string, { icon: React.ReactNode; color: string; label: string }> = {
  document_expiry:     { icon: <WarningAmberIcon fontSize="small" />, color: '#F59E0B', label: 'Document' },
  mission_completed:   { icon: <CheckCircleOutlineIcon fontSize="small" />, color: '#22C55E', label: 'Mission' },
  incident_reported:   { icon: <ErrorOutlineIcon fontSize="small" />, color: '#EF4444', label: 'Incident' },
  expense_created:     { icon: <InfoIcon fontSize="small" />, color: '#2563EB', label: 'Dépense' },
  license_expiry:      { icon: <WarningAmberIcon fontSize="small" />, color: '#F59E0B', label: 'Permis' },
};

function getMeta(type: string) {
  return TYPE_META[type] ?? { icon: <NotificationsNoneIcon fontSize="small" />, color: '#8e9bb0', label: 'Système' };
}

export default function NotificationsPage() {
  const [tab, setTab] = useState(0);       // 0 = Toutes, 1 = Non lues
  const { data: notifResponse, isLoading, isError } = useNotifications();
  const { mutate: markRead }    = useMarkAsRead();
  const { mutate: markAllRead } = useMarkAllAsRead();

  const notifications: Notification[] = notifResponse?.data ?? [];
  const displayed = tab === 1 ? notifications.filter((n) => !n.is_read) : notifications;
  const unreadCount = notifications.filter((n) => !n.is_read).length;

  const handleClick = (n: Notification) => {
    if (!n.is_read) markRead(n.uuid);
  };

  if (isLoading) return (
    <Box display="flex" justifyContent="center" alignItems="center" height={300}>
      <CircularProgress size={32} />
    </Box>
  );
  if (isError) return <Alert severity="error">Impossible de charger les notifications.</Alert>;

  return (
    <Box>
      {/* Header */}
      <Box display="flex" alignItems="flex-start" justifyContent="space-between" mb={3} flexWrap="wrap" gap={1}>
        <Box>
          <Typography variant="h5" fontWeight={700} color="#2f3640">Notifications</Typography>
          <Typography variant="body2" color="text.secondary">Centre de notifications</Typography>
        </Box>
        {unreadCount > 0 && (
          <Button variant="outlined" size="small" startIcon={<DoneAllIcon />}
            onClick={() => markAllRead(undefined)} sx={{ fontSize: 12 }}>
            Tout marquer comme lu ({unreadCount})
          </Button>
        )}
      </Box>

      <Card>
        <CardContent sx={{ p: 0, '&:last-child': { pb: 0 } }}>
          <Box sx={{ borderBottom: 1, borderColor: 'divider' }}>
            <Tabs value={tab} onChange={(_, v) => setTab(v)} sx={{ px: 2 }}>
              <Tab label={`Toutes (${notifications.length})`} />
              <Tab label={`Non lues (${unreadCount})`} />
            </Tabs>
          </Box>

          {displayed.length === 0 ? (
            <Box display="flex" flexDirection="column" alignItems="center" justifyContent="center" py={8} gap={2}>
              <NotificationsNoneIcon sx={{ fontSize: 48, color: '#ccc' }} />
              <Typography color="text.secondary">Aucune notification</Typography>
            </Box>
          ) : (
            <List disablePadding>
              {displayed.map((n: Notification, i: number) => {
                const meta = getMeta(n.type);
                const isUnread = !n.is_read;
                return (
                  <Box key={n.uuid}>
                    <ListItem
                      alignItems="flex-start"
                      onClick={() => handleClick(n)}
                      sx={{
                        px: 2.5, py: 1.5,
                        cursor: isUnread ? 'pointer' : 'default',
                        bgcolor: isUnread ? 'rgba(2,117,216,0.04)' : 'transparent',
                        '&:hover': { bgcolor: isUnread ? 'rgba(2,117,216,0.08)' : 'rgba(0,0,0,0.02)' },
                        transition: 'background 0.15s',
                      }}
                    >
                      <ListItemAvatar>
                        <Tooltip title={meta.label}>
                          <Avatar sx={{ bgcolor: meta.color + '20', color: meta.color, width: 36, height: 36 }}>
                            {meta.icon}
                          </Avatar>
                        </Tooltip>
                      </ListItemAvatar>
                      <ListItemText
                        primary={
                          <Box display="flex" alignItems="center" gap={1}>
                            <Typography variant="body2" fontWeight={isUnread ? 700 : 400} color="#2f3640" sx={{ flex: 1 }}>
                              {n.message}
                            </Typography>
                            <Chip size="small" label={meta.label}
                              sx={{ bgcolor: meta.color + '15', color: meta.color, fontWeight: 600, fontSize: 10, height: 18 }} />
                            {isUnread && (
                              <Box sx={{ width: 8, height: 8, borderRadius: '50%', bgcolor: '#2563EB', flexShrink: 0 }} />
                            )}
                          </Box>
                        }
                        secondary={
                          <Box display="flex" gap={1} mt={0.3}>
                            <Typography variant="caption" color="text.secondary">via {n.channel}</Typography>
                            <Typography variant="caption" color="text.secondary">·</Typography>
                            <Typography variant="caption" color="text.secondary">{timeAgo(n.created_at)}</Typography>
                          </Box>
                        }
                      />
                    </ListItem>
                    {i < displayed.length - 1 && <Divider sx={{ mx: 2 }} />}
                  </Box>
                );
              })}
            </List>
          )}
        </CardContent>
      </Card>
    </Box>
  );
}
