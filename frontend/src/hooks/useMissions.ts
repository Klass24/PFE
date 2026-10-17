import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { missionService } from '@/services/missionService';
import type { Mission } from '@/types';

export const useMissions = (params?: Record<string, string>) =>
  useQuery({
    queryKey: ['missions', params],
    queryFn: () => missionService.getAll(params),
    staleTime: 2 * 60 * 1000,
  });

export const useMission = (uuid: string) =>
  useQuery({
    queryKey: ['missions', uuid],
    queryFn: () => missionService.getByUuid(uuid),
    enabled: !!uuid,
  });

export const useCreateMission = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: missionService.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['missions'] }),
  });
};

export const useUpdateMission = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Partial<Mission> }) =>
      missionService.update(uuid, data),
    onSuccess: (_, vars) => {
      qc.invalidateQueries({ queryKey: ['missions'] });
      qc.invalidateQueries({ queryKey: ['missions', vars.uuid] });
    },
  });
};

export const useStartMission = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: missionService.start,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['missions'] }),
  });
};

export const useCompleteMission = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: missionService.complete,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['missions'] }),
  });
};

export const useCancelMission = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: missionService.cancel,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['missions'] }),
  });
};
