import api from './api';
import type { ApiResponse, PaginatedResponse, Expense } from '@/types';

export const expenseService = {
  getAll: (params?: Record<string, string>): Promise<PaginatedResponse<Expense>> =>
    api.get('/expenses', { params }),

  getByMission: (missionUuid: string): Promise<PaginatedResponse<Expense>> =>
    api.get(`/missions/${missionUuid}/expenses`),

  getByUuid: (uuid: string): Promise<ApiResponse<Expense>> =>
    api.get(`/expenses/${uuid}`),

  create: (missionUuid: string, data: Partial<Expense>): Promise<ApiResponse<Expense>> =>
    api.post(`/missions/${missionUuid}/expenses`, data),

  update: (uuid: string, data: Partial<Expense>): Promise<ApiResponse<Expense>> =>
    api.put(`/expenses/${uuid}`, data),

  delete: (uuid: string): Promise<void> => api.delete(`/expenses/${uuid}`),

  uploadReceipt: (uuid: string, file: File): Promise<ApiResponse<Expense>> => {
    const form = new FormData();
    form.append('receipt', file);
    return api.post(`/expenses/${uuid}/receipt`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
};
