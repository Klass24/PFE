import '../../../local_db/database_helper.dart';
import '../../../services/api_service.dart';
import '../../../services/sync_service.dart';

class IncidentRepository {
  final ApiService _api;
  final DatabaseHelper _db;
  final SyncService _sync;

  IncidentRepository({
    required ApiService api,
    required DatabaseHelper db,
    required SyncService sync,
  })  : _api = api,
        _db = db,
        _sync = sync;

  Future<List<Map<String, dynamic>>> getIncidents(String missionUuid) async {
    return _db.getIncidentsForMission(missionUuid);
  }

  Future<void> reportOffline(Map<String, dynamic> data) async {
    await _db.insertIncident({...data, 'synced': 0});
    await _sync.enqueue(type: 'incident', action: 'create', data: data);
  }
}
