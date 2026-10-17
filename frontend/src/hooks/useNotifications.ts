import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { notificationService } from '@/services/notificationService';

export const useNotifications = (unreadOnly = false) =>
  useQuery({
    queryKey: ['notifications', { unreadOnly }],
    queryFn: () => notificationService.getAll({ unread_only: unreadOnly }),
    staleTime: 60 * 1000,
    refetchInterval: 60 * 1000,
  });

export const useUnreadCount = () =>
  useQuery({
    queryKey: ['notifications', 'count'],
    queryFn: () => notificationService.getUnreadCount().then((r) => r.data.count),
    staleTime: 60 * 1000,
    refetchInterval: 60 * 1000,
  });

export const useMarkAsRead = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: notificationService.markAsRead,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['notifications'] });
    },
  });
};

export const useMarkAllAsRead = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: notificationService.markAllAsRead,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['notifications'] });
    },
  });
};
