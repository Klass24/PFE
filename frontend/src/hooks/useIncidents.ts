import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { incidentService } from '@/services/incidentService';
import type { Incident } from '@/types';

export const useIncidents = (params?: Record<string, string>) =>
  useQuery({
    queryKey: ['incidents', params],
    queryFn: () => incidentService.getAll(params),
    staleTime: 2 * 60 * 1000,
  });

export const useIncidentsByMission = (missionUuid: string) =>
  useQuery({
    queryKey: ['incidents', 'mission', missionUuid],
    queryFn: () => incidentService.getByMission(missionUuid),
    enabled: !!missionUuid,
  });

export const useCreateIncident = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ missionUuid, data }: { missionUuid: string; data: Partial<Incident> }) =>
      incidentService.create(missionUuid, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['incidents'] }),
  });
};

export const useDeleteIncident = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: incidentService.delete,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['incidents'] }),
  });
};
