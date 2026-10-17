import api from './api';
import type { ApiResponse, ApiToken, CreateApiTokenDto } from '@/types';

export const apiTokenService = {
  getAll: (): Promise<ApiResponse<ApiToken[]>> =>
    api.get('/api-tokens'),

  create: (data: CreateApiTokenDto): Promise<ApiResponse<{ token: ApiToken; plain_text: string }>> =>
    api.post('/api-tokens', data),

  revoke: (uuid: string): Promise<ApiResponse<null>> =>
    api.delete(`/api-tokens/${uuid}`),
};
