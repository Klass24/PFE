import api from './api';
import type { ApiResponse } from '@/types';
import type { Plan, PlanComparison, CurrentPlanDetails } from '@/types';

export const pricingService = {
  /** Plans publics (pas besoin d'auth) */
  getPublicPlans: (): Promise<ApiResponse<Plan[]>> =>
    api.get('/plans'),

  /** Détail public d'un plan */
  getPublicPlan: (slug: string): Promise<ApiResponse<Plan>> =>
    api.get(`/plans/${slug}`),

  /** Plan actuel + usage (auth) */
  getCurrent: (): Promise<ApiResponse<CurrentPlanDetails>> =>
    api.get('/pricing/current'),

  /** Comparer plan actuel vs plan cible (auth) */
  compare: (slug: string): Promise<ApiResponse<PlanComparison>> =>
    api.get(`/pricing/compare/${slug}`),

  // ─── Super Admin ────────────────────────────────────────────────────────────

  /** Tous les plans (admin) */
  getAll: (): Promise<ApiResponse<Plan[]>> =>
    api.get('/admin/plans'),

  /** Détail admin */
  getOne: (uuid: string): Promise<ApiResponse<Plan>> =>
    api.get(`/admin/plans/${uuid}`),

  /** Créer un plan */
  create: (data: Partial<Plan>): Promise<ApiResponse<Plan>> =>
    api.post('/admin/plans', data),

  /** Modifier un plan */
  update: (uuid: string, data: Partial<Plan>): Promise<ApiResponse<Plan>> =>
    api.put(`/admin/plans/${uuid}`, data),

  /** Supprimer un plan */
  delete: (uuid: string): Promise<ApiResponse<null>> =>
    api.delete(`/admin/plans/${uuid}`),
};
