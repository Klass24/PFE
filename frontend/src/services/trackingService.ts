import api from './api';

export const trackingService = {
  getActiveTracking: () =>
    api.get('/tracking/active'),

  getMissionLive: (missionUuid: string) =>
    api.get(`/tracking/mission/${missionUuid}/live`),

  getMissionHistory: (missionUuid: string, params?: { from?: string; to?: string }) =>
    api.get(`/tracking/mission/${missionUuid}/history`, { params }),

  recordPoint: (data: Record<string, unknown>) =>
    api.post('/tracking/record-point', data),

  recordBatch: (points: Record<string, unknown>[]) =>
    api.post('/tracking/record-batch', { points }),

  // Geofences
  getGeofences: () =>
    api.get('/tracking/geofences'),

  createGeofence: (data: Record<string, unknown>) =>
    api.post('/tracking/geofences', data),
};
