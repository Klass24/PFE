import 'package:sqflite/sqflite.dart';
import '../../../local_db/database_helper.dart';
import '../../../services/sync_service.dart';
import '../domain/checklist.dart';

class ChecklistRepository {
  final DatabaseHelper _db;
  final SyncService _sync;

  ChecklistRepository({required DatabaseHelper db, required SyncService sync})
      : _db = db,
        _sync = sync;

  Future<Checklist?> getForMission(String missionUuid) async {
    final db = await DatabaseHelper.database;
    final rows = await db.query('checklists', where: 'mission_uuid = ?', whereArgs: [missionUuid]);
    if (rows.isEmpty) return null;

    final row = rows.first;
    final itemRows = await db.query('checklist_items', where: 'checklist_uuid = ?', whereArgs: [row['uuid']]);

    return Checklist(
      uuid: row['uuid'] as String,
      missionUuid: row['mission_uuid'] as String,
      status: row['status'] as String,
      synced: (row['synced'] as int?) == 1,
      items: itemRows.map(ChecklistItem.fromMap).toList(),
    );
  }

  Future<void> saveLocally(Checklist checklist) async {
    final db = await DatabaseHelper.database;
    await db.insert('checklists', {
      'uuid': checklist.uuid,
      'mission_uuid': checklist.missionUuid,
      'status': checklist.status,
      'synced': 0,
      'submitted_at': DateTime.now().toIso8601String(),
    }, conflictAlgorithm: ConflictAlgorithm.replace);

    for (final item in checklist.items) {
      await db.insert('checklist_items', {
        ...item.toMap(),
        'checklist_uuid': checklist.uuid,
      }, conflictAlgorithm: ConflictAlgorithm.replace);
    }
  }

  Future<void> submitForSync(Checklist checklist) async {
    await saveLocally(checklist);
    await _sync.enqueue(
      type: 'checklist',
      action: 'create',
      data: {
        'mission_uuid': checklist.missionUuid,
        'status': 'submitted',
        'submitted_at': DateTime.now().toIso8601String(),
        'items': checklist.items.map((i) => i.toMap()).toList(),
      },
    );
  }
}


