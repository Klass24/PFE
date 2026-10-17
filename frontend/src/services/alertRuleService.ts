import api from './api';

export const alertRuleService = {
  getAll: (params?: Record<string, unknown>) =>
    api.get('/alert-rules', { params }),

  create: (data: Record<string, unknown>) =>
    api.post('/alert-rules', data),

  update: (uuid: string, data: Record<string, unknown>) =>
    api.put(`/alert-rules/${uuid}`, data),

  remove: (uuid: string) =>
    api.delete(`/alert-rules/${uuid}`),
};
