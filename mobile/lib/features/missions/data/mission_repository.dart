import 'package:flutter/foundation.dart';
import '../../../local_db/database_helper.dart';
import '../../../services/api_service.dart';
import '../../../services/sync_service.dart';
import '../domain/mission.dart';

class MissionRepository {
  final ApiService _api;
  final DatabaseHelper _db;
  final SyncService _sync;
  
  // Cache missions in memory for web
  List<Mission>? _cachedMissions;

  MissionRepository({
    required ApiService api,
    required DatabaseHelper db,
    required SyncService sync,
  })  : _api = api,
        _db = db,
        _sync = sync;

  /// Returns missions from local DB (or API on web)
  Future<List<Mission>> getMissions() async {
    if (kIsWeb) {
      // On web, return cached missions or fetch from API
      if (_cachedMissions != null) return _cachedMissions!;
      await fetchAndStore();
      return _cachedMissions ?? [];
    }
    final rows = await _db.getMissions();
    return rows.map(Mission.fromMap).toList();
  }

  /// Pull latest missions from API and store locally
  Future<void> fetchAndStore() async {
    debugPrint('[MissionRepository] Fetching missions from API...');
    final response = await _api.get('/missions');
    debugPrint('[MissionRepository] API response: $response');
    final items = (response['data'] as List<dynamic>?) ?? [];
    debugPrint('[MissionRepository] Found ${items.length} items');
    
    final missions = <Mission>[];
    for (final item in items) {
      final raw = Map<String, dynamic>.from(item as Map);
      // Filter only fields that exist in local DB schema
      final missionMap = {
        'uuid': raw['uuid'],
        'origin': raw['origin'],
        'destination': raw['destination'],
        'start_date': raw['start_date'],
        'end_date': raw['end_date'],
        'status': raw['status'] ?? 'planned',
        'notes': raw['notes'],
        'synced': 1,
        'updated_at': raw['created_at'],
      };
      missions.add(Mission.fromMap(missionMap));
      
      // Store in local DB only on non-web platforms
      if (!kIsWeb) {
        await _db.upsertMission(missionMap);
      }
    }
    
    // Cache for web
    _cachedMissions = missions;
    debugPrint('[MissionRepository] Cached ${missions.length} missions');
  }

  /// Create locally + enqueue for sync
  Future<Mission> createOffline(Map<String, dynamic> data) async {
    final mission = data;
    if (!kIsWeb) {
      await _db.upsertMission({...mission, 'synced': 0});
    }
    await _sync.enqueue(type: 'mission', action: 'create', data: mission);
    return Mission.fromMap(mission);
  }
}
