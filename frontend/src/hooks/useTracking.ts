import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { trackingService } from '@/services/trackingService';

export const useActiveTracking = () =>
  useQuery({
    queryKey: ['tracking', 'active'],
    queryFn: () => trackingService.getActiveTracking().then((r: any) => r.data),
    staleTime: 30 * 1000, // 30 seconds for real-time
    refetchInterval: 30 * 1000,
  });

export const useMissionLive = (missionUuid: string) =>
  useQuery({
    queryKey: ['tracking', 'live', missionUuid],
    queryFn: () => trackingService.getMissionLive(missionUuid).then((r: any) => r.data),
    enabled: !!missionUuid,
    staleTime: 15 * 1000,
    refetchInterval: 15 * 1000,
  });

export const useMissionHistory = (missionUuid: string, params?: { from?: string; to?: string }) =>
  useQuery({
    queryKey: ['tracking', 'history', missionUuid, params],
    queryFn: () => trackingService.getMissionHistory(missionUuid, params).then((r: any) => r.data),
    enabled: !!missionUuid,
  });

export const useGeofences = () =>
  useQuery({
    queryKey: ['tracking', 'geofences'],
    queryFn: () => trackingService.getGeofences().then((r: any) => r.data),
    staleTime: 5 * 60 * 1000,
  });

export const useCreateGeofence = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: trackingService.createGeofence,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['tracking', 'geofences'] }),
  });
};
