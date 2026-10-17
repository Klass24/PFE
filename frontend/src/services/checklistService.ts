import api from './api';
import type { ApiResponse, Checklist } from '@/types';

export const checklistService = {
  getByMission: (missionUuid: string): Promise<ApiResponse<Checklist>> =>
    api.get(`/missions/${missionUuid}/checklist`),

  updateItems: (
    missionUuid: string,
    items: Array<{ id: number; is_ok: boolean; note?: string }>
  ): Promise<ApiResponse<Checklist>> =>
    api.put(`/missions/${missionUuid}/checklist/items`, { items }),

  uploadItemPhoto: (missionUuid: string, itemId: number, file: File): Promise<ApiResponse<{ id: number; has_photo: boolean }>> => {
    const form = new FormData();
    form.append('photo', file);
    return api.post(`/missions/${missionUuid}/checklist/items/${itemId}/photo`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },

  submit: (missionUuid: string, deviceId?: string, dateSaisie?: string): Promise<ApiResponse<Checklist>> =>
    api.post(`/missions/${missionUuid}/checklist/submit`, { device_id: deviceId, date_saisie_reelle: dateSaisie }),

  validate: (missionUuid: string): Promise<ApiResponse<Checklist>> =>
    api.post(`/missions/${missionUuid}/checklist/validate`),
};
