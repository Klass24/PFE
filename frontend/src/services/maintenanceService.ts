import api from './api';

export const maintenanceService = {
  getAll: (params?: Record<string, unknown>) =>
    api.get('/maintenance', { params }),

  getAlerts: () =>
    api.get('/maintenance/alerts'),

  getOne: (uuid: string) =>
    api.get(`/maintenance/${uuid}`),

  create: (data: Record<string, unknown>) =>
    api.post('/maintenance', data),

  complete: (uuid: string, data: Record<string, unknown>) =>
    api.post(`/maintenance/${uuid}/complete`, data),

  remove: (uuid: string) =>
    api.delete(`/maintenance/${uuid}`),
};
