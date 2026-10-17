import api from './api';
import type { PaginatedResponse, AuditLog } from '@/types';

export const auditService = {
  getAll: (params?: Record<string, string>): Promise<PaginatedResponse<AuditLog>> =>
    api.get('/audit-logs', { params }),
};
