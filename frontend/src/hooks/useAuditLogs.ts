import { useQuery } from '@tanstack/react-query';
import { auditService } from '@/services/auditService';

export const useAuditLogs = (params?: Record<string, string>) =>
  useQuery({
    queryKey: ['audit-logs', params],
    queryFn: () => auditService.getAll(params),
    staleTime: 2 * 60 * 1000,
  });
