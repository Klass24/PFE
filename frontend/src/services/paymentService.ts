import api from './api';
import type { ApiResponse, PaginatedResponse } from '@/types';

export interface CheckoutResponse {
  checkout_url?: string; // stripe
  session_id?: string;   // stripe
  order_id?: string;     // paypal
  approval_url?: string; // paypal
}

export interface PaymentRecord {
  uuid: string;
  gateway: string;
  gateway_payment_id: string | null;
  amount: number;
  currency: string;
  status: string;
  payment_method: string | null;
  description: string | null;
  paid_at: string | null;
  company?: { uuid: string; name: string };
  subscription_plan?: string | null;
  created_at: string;
}

export const paymentService = {
  /** Initier un checkout Stripe ou PayPal */
  checkout: (data: { plan_slug: string; gateway: 'stripe' | 'paypal'; billing_cycle?: string }): Promise<ApiResponse<CheckoutResponse>> =>
    api.post('/payments/checkout', data),

  /** Capturer un paiement PayPal */
  capturePayPal: (orderId: string): Promise<ApiResponse<{ status: string }>> =>
    api.post('/payments/paypal/capture', { order_id: orderId }),

  /** Historique des paiements */
  getHistory: (): Promise<PaginatedResponse<PaymentRecord>> =>
    api.get('/payments/history'),
};
