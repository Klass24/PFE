import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uuid/uuid.dart';
import '../local_db/database_helper.dart';
import 'api_service.dart';

class SyncService {
  final ApiService _api;
  final DatabaseHelper _db;
  final _uuid = const Uuid();

  SyncService({required ApiService apiService, required DatabaseHelper db})
      : _api = apiService,
        _db = db;

  /// Enqueue a local operation for later sync
  Future<void> enqueue({
    required String type,
    required String action,
    required Map<String, dynamic> data,
  }) async {
    final localUuid = _uuid.v4();
    await _db.enqueueOperation({
      'local_uuid': localUuid,
      'operation_type': type,
      'action': action,
      'payload': jsonEncode(data),
      'client_created_at': DateTime.now().toIso8601String(),
    });
  }

  /// Push all pending operations to the server
  Future<SyncResult> syncPendingOperations() async {
    final pending = await _db.getUnsyncedOperations();
    if (pending.isEmpty) return SyncResult(synced: 0, skipped: 0, errors: []);

    final prefs = await SharedPreferences.getInstance();
    final lastSyncAt = prefs.getString('last_sync_at');

    // Build device ID (generate once, persist)
    String? deviceId = prefs.getString('device_id');
    if (deviceId == null) {
      deviceId = _uuid.v4();
      await prefs.setString('device_id', deviceId);
    }

    final payload = {
      'device_id': deviceId,
      'last_sync_at': lastSyncAt,
      'operations': pending.map((op) => {
        'local_uuid': op['local_uuid'],
        'type': op['operation_type'],
        'action': op['action'],
        'data': jsonDecode(op['payload'] as String),
        'client_created_at': op['client_created_at'],
      }).toList(),
    };

    try {
      final response = await _api.post('/sync', payload);

      final syncedUuids = pending.map((op) => op['local_uuid'] as String).toList();
      await _db.removeFromQueue(syncedUuids);
      await prefs.setString('last_sync_at', DateTime.now().toIso8601String());

      // Merge server-side changes (missions) into local DB
      final changes = response['data']?['changes'] as Map<String, dynamic>?;
      if (changes != null) {
        await _mergeServerChanges(changes);
      }

      return SyncResult(
        synced: (response['data']?['synced_count'] as int?) ?? 0,
        skipped: (response['data']?['skipped_count'] as int?) ?? 0,
        errors: List<String>.from(response['data']?['errors'] ?? []),
      );
    } catch (e) {
      // Keep in queue — retry next time
      return SyncResult(synced: 0, skipped: 0, errors: [e.toString()]);
    }
  }

  Future<void> _mergeServerChanges(Map<String, dynamic> changes) async {
    final missions = changes['missions'] as List<dynamic>? ?? [];
    for (final m in missions) {
      await _db.upsertMission(Map<String, dynamic>.from(m as Map));
    }
  }
}

class SyncResult {
  final int synced;
  final int skipped;
  final List<String> errors;

  SyncResult({required this.synced, required this.skipped, required this.errors});

  bool get hasErrors => errors.isNotEmpty;
}
