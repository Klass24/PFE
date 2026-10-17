import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { vehicleService } from '@/services/vehicleService';
import type { CreateVehicleDto } from '@/types';

export const useVehicles = (params?: Record<string, string>) =>
  useQuery({
    queryKey: ['vehicles', params],
    queryFn: () => vehicleService.getAll(params),
    staleTime: 5 * 60 * 1000,
  });

export const useVehicle = (uuid: string) =>
  useQuery({
    queryKey: ['vehicles', uuid],
    queryFn: () => vehicleService.getByUuid(uuid),
    enabled: !!uuid,
  });

export const useVehicleBrands = () =>
  useQuery({
    queryKey: ['vehicles', 'catalog', 'brands'],
    queryFn: () => vehicleService.getBrands(),
    staleTime: 24 * 60 * 60 * 1000, // données statiques — 24h
  });

export const useVehicleModels = (brand: string) =>
  useQuery({
    queryKey: ['vehicles', 'catalog', 'models', brand],
    queryFn: () => vehicleService.getModelsByBrand(brand),
    enabled: brand.length > 0,
    staleTime: 24 * 60 * 60 * 1000,
  });

export const useCreateVehicle = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: vehicleService.create,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['vehicles'] }),
  });
};

export const useUpdateVehicle = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ uuid, data }: { uuid: string; data: Partial<CreateVehicleDto> }) =>
      vehicleService.update(uuid, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['vehicles'] }),
  });
};

export const useDeleteVehicle = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: vehicleService.delete,
    onSuccess: () => qc.invalidateQueries({ queryKey: ['vehicles'] }),
  });
};
