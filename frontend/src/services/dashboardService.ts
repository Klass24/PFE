import api from './api';
import type { ApiResponse, DashboardStats, VehicleExpenseStat, IncidentTypeStat, MissionCostStat, ExpiringDocument } from '@/types';

export const dashboardService = {
  getStats: (): Promise<ApiResponse<DashboardStats>> =>
    api.get('/dashboard/stats'),

  getExpensesByVehicle: (params?: { from?: string; to?: string }): Promise<ApiResponse<VehicleExpenseStat[]>> =>
    api.get('/dashboard/expenses-by-vehicle', { params }),

  getIncidentsByType: (params?: { from?: string; to?: string }): Promise<ApiResponse<IncidentTypeStat[]>> =>
    api.get('/dashboard/incidents-by-type', { params }),

  getCostPerMission: (params?: { from?: string; to?: string }): Promise<ApiResponse<MissionCostStat[]>> =>
    api.get('/dashboard/cost-per-mission', { params }),

  getExpiringDocuments: (): Promise<ApiResponse<ExpiringDocument[]>> =>
    api.get('/dashboard/expiring-documents'),

  getDriverLeaderboard: () =>
    api.get('/dashboard/driver-leaderboard'),
};
