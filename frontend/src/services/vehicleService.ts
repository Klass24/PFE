import api from './api';
import type { ApiResponse, PaginatedResponse, Vehicle, CreateVehicleDto } from '@/types';

export const vehicleService = {
  getAll: (params?: Record<string, string>): Promise<PaginatedResponse<Vehicle>> =>
    api.get('/vehicles', { params }),

  getByUuid: (uuid: string): Promise<ApiResponse<Vehicle>> =>
    api.get(`/vehicles/${uuid}`),

  create: (data: CreateVehicleDto): Promise<ApiResponse<Vehicle>> =>
    api.post('/vehicles', data),

  update: (uuid: string, data: Partial<CreateVehicleDto>): Promise<ApiResponse<Vehicle>> =>
    api.put(`/vehicles/${uuid}`, data),

  delete: (uuid: string): Promise<ApiResponse<null>> =>
    api.delete(`/vehicles/${uuid}`),

  uploadImage: (uuid: string, file: File): Promise<ApiResponse<Vehicle>> => {
    const form = new FormData();
    form.append('image', file);
    return api.post(`/vehicles/${uuid}/image`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },

  // ── Catalogue marques / modèles ───────────────────────────────────────────
  getBrands: (): Promise<ApiResponse<string[]>> =>
    api.get('/vehicles/catalog/brands'),

  getModelsByBrand: (brand: string): Promise<ApiResponse<string[]>> =>
    api.get(`/vehicles/catalog/models/${encodeURIComponent(brand)}`),
};
