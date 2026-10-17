import 'package:flutter_test/flutter_test.dart';
import 'package:fleetcontrol_mobile/features/incidents/domain/incident.dart';

void main() {
  group('Incident Model', () {
    test('fromMap with geolocation', () {
      final map = {
        'uuid': 'inc-001',
        'mission_uuid': 'mission-001',
        'type': 'accident',
        'description': 'Collision mineure',
        'latitude': 36.7538,
        'longitude': 3.0588,
        'synced': 0,
        'created_at': '2026-04-10T14:30:00',
      };

      final incident = Incident.fromMap(map);

      expect(incident.type, 'accident');
      expect(incident.latitude, 36.7538);
      expect(incident.longitude, 3.0588);
      expect(incident.synced, false);
    });

    test('fromMap without geolocation', () {
      final map = {
        'uuid': 'inc-002',
        'mission_uuid': 'mission-002',
        'type': 'breakdown',
        'description': 'Panne moteur',
        'latitude': null,
        'longitude': null,
        'synced': 1,
        'created_at': '2026-04-10T15:00:00',
      };

      final incident = Incident.fromMap(map);

      expect(incident.latitude, isNull);
      expect(incident.longitude, isNull);
      expect(incident.synced, true);
    });
  });
}
