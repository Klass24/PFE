import api from './api';
import type { ApiResponse, PaginatedResponse, Driver } from '@/types';

export interface CreateDriverPayload {
  user_id?: number;
  user?: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    is_active?: boolean;
  };
  permissions?: string[];
  license_number: string;
  license_expiry: string;
  phone?: string;
  status?: 'available' | 'on_mission' | 'inactive';
}

export interface UpdateDriverPayload {
  license_number?: string;
  license_expiry?: string;
  phone?: string;
  status?: 'available' | 'on_mission' | 'inactive';
}

export const driverService = {
  getAll: (params?: Record<string, string>): Promise<PaginatedResponse<Driver>> =>
    api.get('/drivers', { params }),

  getByUuid: (uuid: string): Promise<ApiResponse<Driver>> =>
    api.get(`/drivers/${uuid}`),

  create: (data: CreateDriverPayload): Promise<ApiResponse<Driver>> =>
    api.post('/drivers', data),

  update: (uuid: string, data: UpdateDriverPayload): Promise<ApiResponse<Driver>> =>
    api.put(`/drivers/${uuid}`, data),

  delete: (uuid: string): Promise<void> => api.delete(`/drivers/${uuid}`),
};
