import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/services/api';

export interface GroupedPermissions {
  [module: string]: string[];
}

export interface RolePermissions {
  role: string;
  permissions: string[];
}

export interface UserPermissions {
  user_uuid: string;
  name: string;
  role: string;
  permissions: string[];
}

const permissionService = {
  getAll: (): Promise<{ data: GroupedPermissions }> => api.get('/permissions'),
  getRolePermissions: (role: string): Promise<{ data: RolePermissions }> =>
    api.get(`/roles/${role}/permissions`),
  updateRolePermissions: (role: string, permissions: string[]): Promise<{ data: RolePermissions }> =>
    api.put(`/roles/${role}/permissions`, { permissions }),
  getUserPermissions: (uuid: string): Promise<{ data: UserPermissions }> =>
    api.get(`/users/${uuid}/permissions`),
  updateUserPermissions: (uuid: string, permissions: string[]): Promise<{ data: UserPermissions }> =>
    api.put(`/users/${uuid}/permissions`, { permissions }),
};

export const useAllPermissions = (enabled = true) =>
  useQuery({
    queryKey: ['permissions'],
    queryFn: permissionService.getAll,
    enabled,
    staleTime: 10 * 60 * 1000,
  });

export const useRolePermissions = (role: string) =>
  useQuery({
    queryKey: ['role-permissions', role],
    queryFn: () => permissionService.getRolePermissions(role),
    enabled: !!role,
  });

export const useUpdateRolePermissions = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ role, permissions }: { role: string; permissions: string[] }) =>
      permissionService.updateRolePermissions(role, permissions),
    onSuccess: (_d, vars) => qc.invalidateQueries({ queryKey: ['role-permissions', vars.role] }),
  });
};

export const useUserPermissions = (uuid: string) =>
  useQuery({
    queryKey: ['user-permissions', uuid],
    queryFn: () => permissionService.getUserPermissions(uuid),
    enabled: !!uuid,
  });

export const useUpdateUserPermissions = () => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ uuid, permissions }: { uuid: string; permissions: string[] }) =>
      permissionService.updateUserPermissions(uuid, permissions),
    onSuccess: (_d, vars) => qc.invalidateQueries({ queryKey: ['user-permissions', vars.uuid] }),
  });
};
