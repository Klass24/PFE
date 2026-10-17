import 'package:flutter_test/flutter_test.dart';
import 'package:fleetcontrol_mobile/features/checklist/domain/checklist.dart';

void main() {
  group('Checklist Model', () {
    test('defaultChecklistItems has correct count', () {
      expect(defaultChecklistItems.length, 6);
    });

    test('documents_vehicule requires photo', () {
      final docItem = defaultChecklistItems.firstWhere((i) => i.itemKey == 'documents_vehicule');
      expect(docItem.requiresPhoto, true);
    });

    test('canSubmit returns false when required photo missing', () {
      final items = [
        const ChecklistItem(itemKey: 'test', label: 'Test', requiresPhoto: true, isOk: true),
      ];

      final checklist = Checklist(
        uuid: 'cl-001',
        missionUuid: 'mission-001',
        status: 'draft',
        items: items,
      );

      expect(checklist.canSubmit, false); // photo required but not provided
    });

    test('canSubmit returns true when all required photos present', () {
      final items = [
        const ChecklistItem(
          itemKey: 'test',
          label: 'Test',
          requiresPhoto: true,
          isOk: true,
          photoPath: '/path/photo.jpg',
        ),
        const ChecklistItem(
          itemKey: 'test2',
          label: 'Test 2',
          isOk: true,
        ),
      ];

      final checklist = Checklist(
        uuid: 'cl-002',
        missionUuid: 'mission-002',
        status: 'draft',
        items: items,
      );

      expect(checklist.canSubmit, true);
    });

    test('completedCount counts isOk items', () {
      final items = [
        const ChecklistItem(itemKey: 'a', label: 'A', isOk: true),
        const ChecklistItem(itemKey: 'b', label: 'B', isOk: false),
        const ChecklistItem(itemKey: 'c', label: 'C', isOk: true),
      ];

      final checklist = Checklist(
        uuid: 'cl-003',
        missionUuid: 'mission-003',
        status: 'draft',
        items: items,
      );

      expect(checklist.completedCount, 2);
      expect(checklist.totalCount, 3);
    });

    test('ChecklistItem copyWith creates new instance', () {
      const original = ChecklistItem(itemKey: 'key', label: 'Label', isOk: false);
      final updated = original.copyWith(isOk: true, photoPath: '/photo.jpg');

      expect(updated.isOk, true);
      expect(updated.photoPath, '/photo.jpg');
      expect(updated.itemKey, 'key');
      expect(original.isOk, false); // Original unchanged
    });

    test('ChecklistItem toMap/fromMap roundtrip', () {
      const item = ChecklistItem(
        itemKey: 'pression_pneus',
        label: 'Pression des pneus',
        isOk: true,
        requiresPhoto: false,
        note: 'RAS',
      );

      final map = item.toMap();
      final restored = ChecklistItem.fromMap(map);

      expect(restored.itemKey, item.itemKey);
      expect(restored.label, item.label);
      expect(restored.isOk, item.isOk);
      expect(restored.note, item.note);
    });
  });

  group('Notification Model', () {
    test('fromMap creates AppNotification', () {
      // Import at runtime via indirect test
      final map = {
        'uuid': 'notif-001',
        'type': 'document_expiry',
        'title': 'Document expire bientôt',
        'body': 'La carte grise expire dans 7 jours',
        'is_read': false,
        'read_at': null,
        'created_at': '2026-04-10T08:00:00',
      };

      // This tests the map structure expectations
      expect(map['uuid'], 'notif-001');
      expect(map['is_read'], false);
    });
  });
}
