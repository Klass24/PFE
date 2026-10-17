import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { adminService } from '@/services/adminService';

export const useAdminStats = () =>
  useQuery({
    queryKey: ['admin', 'stats'],
    queryFn: () => adminService.getStats(),
    staleTime: 2 * 60 * 1000,
  });

export const useAdminCompanies = (params?: Record<string, string>) =>
  useQuery({
    queryKey: ['admin', 'companies', params],
    queryFn: () => adminService.getCompanies(params),
    staleTime: 2 * 60 * 1000,
  });

export const useCreateCompany = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: adminService.createCompany,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin'] }),
  });
};

export const useUpdateCompany = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Record<string, unknown> }) =>
      adminService.updateCompany(uuid, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin'] }),
  });
};

export const useSuspendCompany = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: adminService.suspend,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin'] }),
  });
};

export const useActivateCompany = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: adminService.activate,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin'] }),
  });
};

export const useDeleteCompany = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: adminService.deleteCompany,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin'] }),
  });
};

// ─── Plans CRUD ───────────────────────────────────────────────────────────────

export const useAdminPlans = () =>
  useQuery({
    queryKey: ['admin', 'plans'],
    queryFn: () => adminService.getPlans(),
    staleTime: 5 * 60 * 1000,
  });

export const useAdminCreatePlan = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: adminService.createPlan,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin', 'plans'] });
      qc.invalidateQueries({ queryKey: ['plans'] });
    },
  });
};

export const useAdminUpdatePlan = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Partial<import('@/types').Plan> }) =>
      adminService.updatePlan(uuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin', 'plans'] });
      qc.invalidateQueries({ queryKey: ['plans'] });
    },
  });
};

export const useAdminDeletePlan = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: adminService.deletePlan,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin', 'plans'] });
      qc.invalidateQueries({ queryKey: ['plans'] });
    },
  });
};

// ─── Subscriptions ────────────────────────────────────────────────────────────

export const useAdminSubscriptions = (params?: Record<string, string>) =>
  useQuery({
    queryKey: ['admin', 'subscriptions', params],
    queryFn: () => adminService.getAllSubscriptions(params),
    staleTime: 2 * 60 * 1000,
  });

export const useAdminChangePlan = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: { plan: string; end_date?: string } }) =>
      adminService.changePlanForCompany(uuid, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin'] }),
  });
};

// ─── Payments ─────────────────────────────────────────────────────────────────

export const useAdminPayments = (params?: Record<string, string>) =>
  useQuery({
    queryKey: ['admin', 'payments', params],
    queryFn: () => adminService.getAllPayments(params),
    staleTime: 2 * 60 * 1000,
  });
