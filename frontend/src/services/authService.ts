import api from './api';
import type { ApiResponse, AuthUser, LoginCredentials, RegisterDto } from '@/types';

export const authService = {
  login: (credentials: LoginCredentials): Promise<ApiResponse<{ token: string; user: AuthUser }>> =>
    api.post('/auth/login', credentials),

  register: (data: RegisterDto): Promise<ApiResponse<{ token: string; user: AuthUser }>> =>
    api.post('/auth/register', data),

  logout: (): Promise<void> => api.post('/auth/logout'),

  me: (): Promise<ApiResponse<AuthUser>> => api.get('/auth/me'),
};
