import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { maintenanceService } from '@/services/maintenanceService';

export const useMaintenance = (params?: Record<string, unknown>) =>
  useQuery({
    queryKey: ['maintenance', params],
    queryFn: () => maintenanceService.getAll(params),
    staleTime: 5 * 60 * 1000,
  });

export const useMaintenanceAlerts = () =>
  useQuery({
    queryKey: ['maintenance', 'alerts'],
    queryFn: () => maintenanceService.getAlerts().then((r: any) => r.data),
    staleTime: 5 * 60 * 1000,
  });

export const useMaintenanceDetail = (uuid: string) =>
  useQuery({
    queryKey: ['maintenance', uuid],
    queryFn: () => maintenanceService.getOne(uuid).then((r: any) => r.data),
    enabled: !!uuid,
  });

export const useCreateMaintenance = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: maintenanceService.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['maintenance'] }),
  });
};

export const useCompleteMaintenance = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Record<string, unknown> }) =>
      maintenanceService.complete(uuid, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['maintenance'] }),
  });
};

export const useDeleteMaintenance = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: maintenanceService.remove,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['maintenance'] }),
  });
};
