import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiTokenService } from '@/services/apiTokenService';
import type { CreateApiTokenDto } from '@/types';

export const useApiTokens = () =>
  useQuery({
    queryKey: ['api-tokens'],
    queryFn: () => apiTokenService.getAll(),
    staleTime: 5 * 60 * 1000,
  });

export const useCreateApiToken = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateApiTokenDto) => apiTokenService.create(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['api-tokens'] }),
  });
};

export const useRevokeApiToken = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: apiTokenService.revoke,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['api-tokens'] }),
  });
};
