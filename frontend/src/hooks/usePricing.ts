import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { pricingService } from '@/services/pricingService';

/** Plans publics (landing, register) */
export const usePublicPlans = () =>
  useQuery({
    queryKey: ['plans', 'public'],
    queryFn: () => pricingService.getPublicPlans(),
    staleTime: 10 * 60 * 1000,
  });

/** Plan actuel de l'entreprise connectée + usage */
export const useCurrentPlan = () =>
  useQuery({
    queryKey: ['pricing', 'current'],
    queryFn: () => pricingService.getCurrent(),
    staleTime: 2 * 60 * 1000,
  });

/** Comparaison plan actuel vs cible */
export const useComparePlan = (slug: string | null) =>
  useQuery({
    queryKey: ['pricing', 'compare', slug],
    queryFn: () => pricingService.compare(slug!),
    enabled: !!slug,
    staleTime: 60 * 1000,
  });

/** Admin: tous les plans */
export const useAdminPlans = () =>
  useQuery({
    queryKey: ['admin', 'plans'],
    queryFn: () => pricingService.getAll(),
    staleTime: 5 * 60 * 1000,
  });

/** Admin: créer un plan */
export const useCreatePlan = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: pricingService.create,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin', 'plans'] });
      qc.invalidateQueries({ queryKey: ['plans', 'public'] });
    },
  });
};

/** Admin: modifier un plan */
export const useUpdatePlan = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Partial<import('@/types').Plan> }) =>
      pricingService.update(uuid, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin', 'plans'] });
      qc.invalidateQueries({ queryKey: ['plans', 'public'] });
    },
  });
};

/** Admin: supprimer un plan */
export const useDeletePlan = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: pricingService.delete,
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin', 'plans'] });
      qc.invalidateQueries({ queryKey: ['plans', 'public'] });
    },
  });
};
