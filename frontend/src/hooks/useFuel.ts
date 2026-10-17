import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { fuelService } from '@/services/fuelService';

export const useFuelEntries = (params?: Record<string, unknown>) =>
  useQuery({
    queryKey: ['fuel', params],
    queryFn: () => fuelService.getAll(params),
    staleTime: 5 * 60 * 1000,
  });

export const useCreateFuelEntry = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: fuelService.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['fuel'] }),
  });
};

export const useFuelAnomalies = (params?: Record<string, unknown>) =>
  useQuery({
    queryKey: ['fuel', 'anomalies', params],
    queryFn: () => fuelService.getAnomalies(params),
    staleTime: 5 * 60 * 1000,
  });

export const useResolveFuelAnomaly = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: { status: string; notes?: string } }) =>
      fuelService.resolveAnomaly(uuid, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['fuel', 'anomalies'] }),
  });
};

export const useFuelAnalytics = (params?: { from?: string; to?: string }) =>
  useQuery({
    queryKey: ['fuel', 'analytics', params],
    queryFn: () => fuelService.getAnalytics(params).then((r: any) => r.data),
    staleTime: 5 * 60 * 1000,
  });
