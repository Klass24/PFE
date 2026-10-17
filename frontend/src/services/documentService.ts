import api from './api';
import type { ApiResponse, PaginatedResponse, Document, CreateDocumentDto } from '@/types';

export const documentService = {
  getAll: (params?: Record<string, string>): Promise<PaginatedResponse<Document>> =>
    api.get('/documents', { params }),

  getExpiring: (): Promise<PaginatedResponse<Document>> =>
    api.get('/documents/expiring'),

  getByUuid: (uuid: string): Promise<ApiResponse<Document>> =>
    api.get(`/documents/${uuid}`),

  create: (data: CreateDocumentDto & { file: File }): Promise<ApiResponse<Document>> => {
    const form = new FormData();
    form.append('documentable_type', data.documentable_type);
    form.append('documentable_uuid', data.documentable_uuid);
    form.append('type', data.type);
    form.append('file', data.file);
    if (data.issue_date)  form.append('issue_date', data.issue_date);
    if (data.expiry_date) form.append('expiry_date', data.expiry_date);
    return api.post('/documents', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },

  delete: (uuid: string): Promise<ApiResponse<null>> =>
    api.delete(`/documents/${uuid}`),
};
