import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { expenseService } from '@/services/expenseService';
import type { Expense } from '@/types';

export const useExpenses = (params?: Record<string, string>) =>
  useQuery({
    queryKey: ['expenses', params],
    queryFn: () => expenseService.getAll(params),
    staleTime: 2 * 60 * 1000,
  });

export const useExpensesByMission = (missionUuid: string) =>
  useQuery({
    queryKey: ['expenses', 'mission', missionUuid],
    queryFn: () => expenseService.getByMission(missionUuid),
    enabled: !!missionUuid,
  });

export const useCreateExpense = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ missionUuid, data }: { missionUuid: string; data: Partial<Expense> }) =>
      expenseService.create(missionUuid, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['expenses'] }),
  });
};

export const useUpdateExpense = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Partial<Expense> }) =>
      expenseService.update(uuid, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['expenses'] }),
  });
};

export const useDeleteExpense = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: expenseService.delete,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['expenses'] }),
  });
};
