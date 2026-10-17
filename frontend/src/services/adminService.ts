import api from './api';
import type { ApiResponse, PaginatedResponse, AdminCompany, AdminStats, Plan } from '@/types';

export const adminService = {
  getStats: (): Promise<ApiResponse<AdminStats>> =>
    api.get('/admin/stats'),

  // ─── Companies ──────────────────────────────────────────────────────────────
  getCompanies: (params?: Record<string, string>): Promise<PaginatedResponse<AdminCompany>> =>
    api.get('/admin/companies', { params }),

  getCompany: (uuid: string): Promise<ApiResponse<AdminCompany>> =>
    api.get(`/admin/companies/${uuid}`),

  createCompany: (data: Record<string, unknown>): Promise<ApiResponse<AdminCompany>> =>
    api.post('/admin/companies', data),

  updateCompany: (uuid: string, data: Record<string, unknown>): Promise<ApiResponse<AdminCompany>> =>
    api.put(`/admin/companies/${uuid}`, data),

  suspend: (uuid: string): Promise<ApiResponse<AdminCompany>> =>
    api.put(`/admin/companies/${uuid}/suspend`),

  activate: (uuid: string): Promise<ApiResponse<AdminCompany>> =>
    api.put(`/admin/companies/${uuid}/activate`),

  deleteCompany: (uuid: string): Promise<ApiResponse<null>> =>
    api.delete(`/admin/companies/${uuid}`),

  // ─── Subscriptions ──────────────────────────────────────────────────────────
  getAllSubscriptions: (params?: Record<string, string>): Promise<PaginatedResponse<unknown>> =>
    api.get('/admin/subscriptions', { params }),

  getCompanySubscriptions: (uuid: string, params?: Record<string, string>): Promise<PaginatedResponse<unknown>> =>
    api.get(`/admin/companies/${uuid}/subscriptions`, { params }),

  changePlanForCompany: (uuid: string, data: { plan: string; end_date?: string }): Promise<ApiResponse<unknown>> =>
    api.post(`/admin/companies/${uuid}/subscriptions/change-plan`, data),

  getCompanyQuotas: (uuid: string): Promise<ApiResponse<unknown>> =>
    api.get(`/admin/companies/${uuid}/quotas`),

  // ─── Plans (super_admin CRUD) ───────────────────────────────────────────────
  getPlans: (): Promise<ApiResponse<Plan[]>> =>
    api.get('/admin/plans'),

  createPlan: (data: Partial<Plan>): Promise<ApiResponse<Plan>> =>
    api.post('/admin/plans', data),

  updatePlan: (uuid: string, data: Partial<Plan>): Promise<ApiResponse<Plan>> =>
    api.put(`/admin/plans/${uuid}`, data),

  deletePlan: (uuid: string): Promise<ApiResponse<null>> =>
    api.delete(`/admin/plans/${uuid}`),

  // ─── Payments ───────────────────────────────────────────────────────────────
  getAllPayments: (params?: Record<string, string>): Promise<PaginatedResponse<unknown>> =>
    api.get('/admin/payments', { params }),

  getPayment: (uuid: string): Promise<ApiResponse<unknown>> =>
    api.get(`/admin/payments/${uuid}`),
};
