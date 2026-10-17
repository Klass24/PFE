import api from './api';

export type ImportResource =
  | 'vehicles'
  | 'drivers'
  | 'expenses'
  | 'missions'
  | 'incidents'
  | 'documents'
  | 'maintenance'
  | 'users';

export interface ImportResult {
  imported: number;
  errors: string[];
}

export const importService = {
  upload: async (resource: ImportResource, file: File): Promise<ImportResult> => {
    const fd = new FormData();
    fd.append('file', file);

    const res = await api.post(`/import/${resource}`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    return (res as unknown as { data: ImportResult }).data;
  },
};
