import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { documentService } from '@/services/documentService';
import type { CreateDocumentDto } from '@/types';

export const useDocuments = (params?: Record<string, string>) =>
  useQuery({
    queryKey: ['documents', params],
    queryFn: () => documentService.getAll(params),
    staleTime: 2 * 60 * 1000,
  });

export const useExpiringDocuments = () =>
  useQuery({
    queryKey: ['documents', 'expiring'],
    queryFn: () => documentService.getExpiring(),
    staleTime: 2 * 60 * 1000,
  });

export const useCreateDocument = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateDocumentDto & { file: File }) => documentService.create(data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['documents'] }),
  });
};

export const useDeleteDocument = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: documentService.delete,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['documents'] }),
  });
};
