import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { checklistService } from '@/services/checklistService';

export const useChecklist = (missionUuid: string) =>
  useQuery({
    queryKey: ['checklist', missionUuid],
    queryFn: () => checklistService.getByMission(missionUuid).then((r) => r.data),
    enabled: !!missionUuid,
    staleTime: 30 * 1000,
  });

export const useUpdateChecklistItems = (missionUuid: string) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (items: Array<{ id: number; is_ok: boolean; note?: string }>) =>
      checklistService.updateItems(missionUuid, items),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['checklist', missionUuid] }),
  });
};

export const useSubmitChecklist = (missionUuid: string) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () => checklistService.submit(missionUuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['checklist', missionUuid] });
      qc.invalidateQueries({ queryKey: ['missions'] });
    },
  });
};

export const useUploadChecklistPhoto = (missionUuid: string) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ itemId, file }: { itemId: number; file: File }) =>
      checklistService.uploadItemPhoto(missionUuid, itemId, file),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['checklist', missionUuid] }),
  });
};

export const useValidateChecklist = (missionUuid: string) => {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () => checklistService.validate(missionUuid),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['checklist', missionUuid] });
      qc.invalidateQueries({ queryKey: ['missions'] });
    },
  });
};
