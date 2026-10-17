import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { userService } from '@/services/userService';
import type { CreateUserDto } from '@/types';

export const useUsers = (params?: Record<string, string>) =>
  useQuery({
    queryKey: ['users', params],
    queryFn: () => userService.getAll(params),
    staleTime: 5 * 60 * 1000,
  });

export const useCreateUser = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: userService.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['users'] }),
  });
};

export const useUpdateUser = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Partial<CreateUserDto> }) =>
      userService.update(uuid, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['users'] }),
  });
};

export const useDeleteUser = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: userService.delete,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['users'] }),
  });
};
