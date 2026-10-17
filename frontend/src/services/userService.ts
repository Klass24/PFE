import api from './api';
import type { ApiResponse, PaginatedResponse, User, CreateUserDto } from '@/types';

export const userService = {
  getAll: (params?: Record<string, string>): Promise<PaginatedResponse<User>> =>
    api.get('/users', { params }),

  getByUuid: (uuid: string): Promise<ApiResponse<User>> =>
    api.get(`/users/${uuid}`),

  create: (data: CreateUserDto): Promise<ApiResponse<User>> =>
    api.post('/users', data),

  update: (uuid: string, data: Partial<CreateUserDto>): Promise<ApiResponse<User>> =>
    api.put(`/users/${uuid}`, data),

  delete: (uuid: string): Promise<ApiResponse<null>> =>
    api.delete(`/users/${uuid}`),
};
