import { useMutation } from '@tanstack/react-query';
import { exportService, type ExportResource, type ExportFormat } from '@/services/exportService';

export const useExport = () =>
  useMutation({
    mutationFn: ({
      resource,
      params,
      format,
    }: {
      resource: ExportResource;
      params?: Record<string, string>;
      format?: ExportFormat;
    }) => exportService.download(resource, params, format),
  });
