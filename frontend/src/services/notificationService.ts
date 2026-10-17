import api from './api';
import type { ApiResponse, PaginatedResponse, Notification } from '@/types';

export const notificationService = {
  getAll: (params?: { unread_only?: boolean }): Promise<PaginatedResponse<Notification>> =>
    api.get('/notifications', { params }),

  getUnreadCount: (): Promise<ApiResponse<{ count: number }>> =>
    api.get('/notifications/unread-count'),

  markAsRead: (uuid: string): Promise<ApiResponse<null>> =>
    api.post(`/notifications/${uuid}/read`),

  markAllAsRead: (): Promise<ApiResponse<{ updated: number }>> =>
    api.post('/notifications/read-all'),
};
