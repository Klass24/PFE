import 'package:flutter_test/flutter_test.dart';
import 'package:fleetcontrol_mobile/features/missions/domain/mission.dart';

void main() {
  group('Mission Model', () {
    test('fromMap creates instance correctly', () {
      final map = {
        'uuid': 'abc-123',
        'origin': 'Brazzaville',
        'destination': 'Pointe-Noire',
        'start_date': '2026-04-10T08:00:00',
        'end_date': null,
        'status': 'planned',
        'notes': 'Test mission',
        'synced': 1,
      };

      final mission = Mission.fromMap(map);

      expect(mission.uuid, 'abc-123');
      expect(mission.origin, 'Brazzaville');
      expect(mission.destination, 'Pointe-Noire');
      expect(mission.status, 'planned');
      expect(mission.synced, true);
    });

    test('toMap returns correct map', () {
      const mission = Mission(
        uuid: 'abc-123',
        origin: 'Brazzaville',
        destination: 'Pointe-Noire',
        status: 'in_progress',
        synced: false,
      );

      final map = mission.toMap();

      expect(map['uuid'], 'abc-123');
      expect(map['origin'], 'Brazzaville');
      expect(map['status'], 'in_progress');
      expect(map['synced'], 0);
    });

    test('default status is planned', () {
      const mission = Mission(
        uuid: 'test',
        origin: 'A',
        destination: 'B',
      );

      expect(mission.status, 'planned');
    });
  });
}
