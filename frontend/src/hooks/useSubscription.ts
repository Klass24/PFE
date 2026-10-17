import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { subscriptionService } from '@/services/subscriptionService';

export const useCurrentSubscription = () =>
  useQuery({
    queryKey: ['subscription', 'current'],
    queryFn: () => subscriptionService.getCurrent(),
    staleTime: 5 * 60 * 1000,
  });

export const useQuotas = () =>
  useQuery({
    queryKey: ['subscription', 'quotas'],
    queryFn: () => subscriptionService.getQuotas(),
    staleTime: 2 * 60 * 1000,
  });

export const useChangePlan = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (plan: string) => subscriptionService.changePlan(plan),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['subscription'] });
    },
  });
};
