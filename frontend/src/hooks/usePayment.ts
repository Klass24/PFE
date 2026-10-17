import { useQuery, useMutation } from '@tanstack/react-query';
import { paymentService } from '@/services/paymentService';

export const useCheckout = () =>
  useMutation({
    mutationFn: paymentService.checkout,
  });

export const useCapturePayPal = () =>
  useMutation({
    mutationFn: paymentService.capturePayPal,
  });

export const usePaymentHistory = () =>
  useQuery({
    queryKey: ['payments', 'history'],
    queryFn: () => paymentService.getHistory(),
    staleTime: 2 * 60 * 1000,
  });
