import api from './api';
import type { ApiResponse, PaginatedResponse, Incident } from '@/types';

export const incidentService = {
  getAll: (params?: Record<string, string>): Promise<PaginatedResponse<Incident>> =>
    api.get('/incidents', { params }),

  getByMission: (missionUuid: string): Promise<PaginatedResponse<Incident>> =>
    api.get(`/missions/${missionUuid}/incidents`),

  getByUuid: (uuid: string): Promise<ApiResponse<Incident>> =>
    api.get(`/incidents/${uuid}`),

  create: (missionUuid: string, data: Partial<Incident>): Promise<ApiResponse<Incident>> =>
    api.post(`/missions/${missionUuid}/incidents`, data),

  delete: (uuid: string): Promise<ApiResponse<null>> =>
    api.delete(`/incidents/${uuid}`),
};
