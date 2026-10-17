import api from './api';

export const fuelService = {
  getAll: (params?: Record<string, unknown>) =>
    api.get('/fuel', { params }),

  create: (data: Record<string, unknown>) =>
    api.post('/fuel', data),

  getAnomalies: (params?: Record<string, unknown>) =>
    api.get('/fuel/anomalies', { params }),

  resolveAnomaly: (uuid: string, data: { status: string; notes?: string }) =>
    api.put(`/fuel/anomalies/${uuid}`, data),

  getAnalytics: (params?: { from?: string; to?: string }) =>
    api.get('/fuel/analytics', { params }),
};
