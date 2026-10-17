import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { driverService } from '@/services/driverService';
import type { CreateDriverPayload } from '@/services/driverService';
import type { UpdateDriverPayload } from '@/services/driverService';

export const useDrivers = (params?: Record<string, string>) =>
  useQuery({
    queryKey: ['drivers', params],
    queryFn: () => driverService.getAll(params),
    staleTime: 5 * 60 * 1000,
  });

export const useDriver = (uuid: string) =>
  useQuery({
    queryKey: ['drivers', uuid],
    queryFn: () => driverService.getByUuid(uuid),
    enabled: !!uuid,
  });

export const useCreateDriver = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateDriverPayload) => driverService.create(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['drivers'] }),
  });
};

export const useUpdateDriver = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: UpdateDriverPayload }) =>
      driverService.update(uuid, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['drivers'] }),
  });
};

export const useDeleteDriver = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: driverService.delete,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['drivers'] }),
  });
};
