import api from './api';
import type { ApiResponse, PaginatedResponse, Mission } from '@/types';

export const missionService = {
  getAll: (params?: Record<string, string>): Promise<PaginatedResponse<Mission>> =>
    api.get('/missions', { params }),

  getByUuid: (uuid: string): Promise<ApiResponse<Mission>> =>
    api.get(`/missions/${uuid}`),

  create: (data: Partial<Mission>): Promise<ApiResponse<Mission>> =>
    api.post('/missions', data),

  update: (uuid: string, data: Partial<Mission>): Promise<ApiResponse<Mission>> =>
    api.put(`/missions/${uuid}`, data),

  delete: (uuid: string): Promise<void> => api.delete(`/missions/${uuid}`),

  start: (uuid: string): Promise<ApiResponse<Mission>> =>
    api.post(`/missions/${uuid}/start`),

  complete: (uuid: string): Promise<ApiResponse<Mission>> =>
    api.post(`/missions/${uuid}/complete`),

  cancel: (uuid: string): Promise<ApiResponse<Mission>> =>
    api.post(`/missions/${uuid}/cancel`),
};
