import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { alertRuleService } from '@/services/alertRuleService';

export const useAlertRules = (params?: Record<string, unknown>) =>
  useQuery({
    queryKey: ['alert-rules', params],
    queryFn: () => alertRuleService.getAll(params),
    staleTime: 5 * 60 * 1000,
  });

export const useCreateAlertRule = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: alertRuleService.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['alert-rules'] }),
  });
};

export const useUpdateAlertRule = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Record<string, unknown> }) =>
      alertRuleService.update(uuid, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['alert-rules'] }),
  });
};

export const useDeleteAlertRule = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: alertRuleService.remove,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['alert-rules'] }),
  });
};
