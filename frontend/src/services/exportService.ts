import api from './api';

export type ExportResource =
  | 'vehicles'
  | 'drivers'
  | 'missions'
  | 'expenses'
  | 'incidents'
  | 'documents'
  | 'maintenance'
  | 'users';
export type ExportFormat = 'csv' | 'xlsx';

const MIME: Record<ExportFormat, string> = {
  csv: 'text/csv',
  xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
};

export const exportService = {
  download: async (
    resource: ExportResource,
    params?: Record<string, string>,
    format: ExportFormat = 'csv',
  ): Promise<void> => {
    const response = await api.get(`/export/${resource}`, {
      params: { ...params, format },
      responseType: 'blob',
    });

    const blob = new Blob([response as unknown as BlobPart], { type: MIME[format] });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `${resource}_${new Date().toISOString().slice(0, 10)}.${format}`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  },
};
