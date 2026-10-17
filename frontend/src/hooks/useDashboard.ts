import { useQuery } from '@tanstack/react-query';
import { dashboardService } from '@/services/dashboardService';

export const useDashboardStats = () =>
  useQuery({
    queryKey: ['dashboard', 'stats'],
    queryFn: () => dashboardService.getStats().then((r) => r.data),
    staleTime: 5 * 60 * 1000,
    refetchInterval: 5 * 60 * 1000,
  });

export const useExpensesByVehicle = (params?: { from?: string; to?: string }) =>
  useQuery({
    queryKey: ['dashboard', 'expenses-by-vehicle', params],
    queryFn: () => dashboardService.getExpensesByVehicle(params).then((r) => r.data),
    staleTime: 5 * 60 * 1000,
  });

export const useIncidentsByType = (params?: { from?: string; to?: string }) =>
  useQuery({
    queryKey: ['dashboard', 'incidents-by-type', params],
    queryFn: () => dashboardService.getIncidentsByType(params).then((r) => r.data),
    staleTime: 5 * 60 * 1000,
  });

export const useExpiringDocuments = () =>
  useQuery({
    queryKey: ['dashboard', 'expiring-documents'],
    queryFn: () => dashboardService.getExpiringDocuments().then((r) => r.data),
    staleTime: 5 * 60 * 1000,
  });

export const useDriverLeaderboard = () =>
  useQuery({
    queryKey: ['dashboard', 'driver-leaderboard'],
    queryFn: () => dashboardService.getDriverLeaderboard().then((r: any) => r.data),
    staleTime: 24 * 60 * 60 * 1000, // 24h
  });
