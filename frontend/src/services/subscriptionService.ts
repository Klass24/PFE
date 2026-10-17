import api from './api';
import type { ApiResponse, Subscription, QuotaUsage } from '@/types';

export const subscriptionService = {
  getCurrent: (): Promise<ApiResponse<Subscription | null>> =>
    api.get('/subscriptions/current'),

  changePlan: (plan: string): Promise<ApiResponse<Subscription>> =>
    api.post('/subscriptions/change-plan', { plan }),

  getQuotas: (): Promise<ApiResponse<QuotaUsage>> =>
    api.get('/subscriptions/quotas'),
};
