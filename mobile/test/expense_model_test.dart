import 'package:flutter_test/flutter_test.dart';
import 'package:fleetcontrol_mobile/features/expenses/domain/expense.dart';

void main() {
  group('Expense Model', () {
    test('fromMap creates instance with all fields', () {
      final map = {
        'uuid': 'exp-001',
        'mission_uuid': 'mission-001',
        'type': 'fuel',
        'amount': 5000.0,
        'liters': 45.5,
        'mileage': 120000,
        'notes': 'Plein autoroute',
        'receipt_image': '/path/to/photo.jpg',
        'synced': 0,
        'created_at': '2026-04-10T10:00:00',
      };

      final expense = Expense.fromMap(map);

      expect(expense.uuid, 'exp-001');
      expect(expense.type, 'fuel');
      expect(expense.amount, 5000.0);
      expect(expense.liters, 45.5);
      expect(expense.receiptImage, '/path/to/photo.jpg');
      expect(expense.synced, false);
    });

    test('toMap includes receipt_image', () {
      const expense = Expense(
        uuid: 'exp-002',
        missionUuid: 'mission-002',
        type: 'toll',
        amount: 200.0,
        receiptImage: '/path/receipt.jpg',
      );

      final map = expense.toMap();

      expect(map['receipt_image'], '/path/receipt.jpg');
      expect(map['type'], 'toll');
    });

    test('fromMap handles null optional fields', () {
      final map = {
        'uuid': 'exp-003',
        'mission_uuid': 'mission-003',
        'type': 'other',
        'amount': 100,
        'synced': 1,
        'created_at': null,
      };

      final expense = Expense.fromMap(map);

      expect(expense.liters, isNull);
      expect(expense.mileage, isNull);
      expect(expense.notes, isNull);
      expect(expense.receiptImage, isNull);
      expect(expense.synced, true);
    });
  });
}
