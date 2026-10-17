import { useMutation, useQueryClient } from '@tanstack/react-query';
import { importService, type ImportResource } from '@/services/importService';

export const useImport = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ resource, file }: { resource: ImportResource; file: File }) =>
      importService.upload(resource, file),
    onSuccess: (_data, { resource }) => {
      qc.invalidateQueries({ queryKey: [resource] });
    },
  });
};
