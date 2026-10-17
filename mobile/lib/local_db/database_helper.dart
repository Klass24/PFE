import 'package:sqflite/sqflite.dart';
import 'package:path/path.dart';

class DatabaseHelper {
  static const _dbName = 'fleetcontrol.db';
  static const _dbVersion = 1;

  static Database? _db;

  static Future<Database> get database async {
    _db ??= await _initDb();
    return _db!;
  }

  static Future<Database> _initDb() async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, _dbName);
    return openDatabase(path, version: _dbVersion, onCreate: _onCreate);
  }

  static Future<void> _onCreate(Database db, int version) async {
    await db.execute('''
      CREATE TABLE missions (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid       TEXT    NOT NULL UNIQUE,
        origin     TEXT    NOT NULL,
        destination TEXT   NOT NULL,
        start_date TEXT,
        end_date   TEXT,
        status     TEXT    NOT NULL DEFAULT 'planned',
        notes      TEXT,
        synced     INTEGER NOT NULL DEFAULT 1,
        updated_at TEXT
      )
    ''');

    await db.execute('''
      CREATE TABLE expenses (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid        TEXT    NOT NULL UNIQUE,
        mission_uuid TEXT   NOT NULL,
        type        TEXT    NOT NULL,
        amount      REAL    NOT NULL,
        liters      REAL,
        mileage     INTEGER,
        notes       TEXT,
        receipt_image TEXT,
        synced      INTEGER NOT NULL DEFAULT 0,
        created_at  TEXT
      )
    ''');

    await db.execute('''
      CREATE TABLE incidents (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid        TEXT    NOT NULL UNIQUE,
        mission_uuid TEXT   NOT NULL,
        type        TEXT    NOT NULL,
        description TEXT    NOT NULL,
        latitude    REAL,
        longitude   REAL,
        synced      INTEGER NOT NULL DEFAULT 0,
        created_at  TEXT
      )
    ''');

    await db.execute('''
      CREATE TABLE checklists (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        uuid         TEXT    NOT NULL UNIQUE,
        mission_uuid TEXT    NOT NULL,
        status       TEXT    NOT NULL DEFAULT 'draft',
        synced       INTEGER NOT NULL DEFAULT 0,
        submitted_at TEXT
      )
    ''');

    await db.execute('''
      CREATE TABLE checklist_items (
        id              INTEGER PRIMARY KEY AUTOINCREMENT,
        checklist_uuid  TEXT    NOT NULL,
        item_key        TEXT    NOT NULL,
        label           TEXT    NOT NULL,
        is_ok           INTEGER NOT NULL DEFAULT 0,
        requires_photo  INTEGER NOT NULL DEFAULT 0,
        photo_path      TEXT,
        note            TEXT
      )
    ''');

    await db.execute('''
      CREATE TABLE sync_queue (
        id               INTEGER PRIMARY KEY AUTOINCREMENT,
        local_uuid       TEXT NOT NULL UNIQUE,
        operation_type   TEXT NOT NULL,
        action           TEXT NOT NULL DEFAULT 'create',
        payload          TEXT NOT NULL,
        client_created_at TEXT NOT NULL
      )
    ''');
  }

  // ─── Missions ────────────────────────────────────────────────────────────────

  Future<List<Map<String, dynamic>>> getMissions() async {
    final db = await database;
    return db.query('missions', orderBy: 'start_date DESC');
  }

  Future<void> upsertMission(Map<String, dynamic> mission) async {
    final db = await database;
    await db.insert('missions', mission, conflictAlgorithm: ConflictAlgorithm.replace);
  }

  // ─── Expenses ─────────────────────────────────────────────────────────────

  Future<List<Map<String, dynamic>>> getExpensesForMission(String missionUuid) async {
    final db = await database;
    return db.query('expenses', where: 'mission_uuid = ?', whereArgs: [missionUuid]);
  }

  Future<void> insertExpense(Map<String, dynamic> expense) async {
    final db = await database;
    await db.insert('expenses', expense, conflictAlgorithm: ConflictAlgorithm.replace);
  }

  // ─── Incidents ────────────────────────────────────────────────────────────

  Future<List<Map<String, dynamic>>> getIncidentsForMission(String missionUuid) async {
    final db = await database;
    return db.query('incidents', where: 'mission_uuid = ?', whereArgs: [missionUuid]);
  }

  Future<void> insertIncident(Map<String, dynamic> incident) async {
    final db = await database;
    await db.insert('incidents', incident, conflictAlgorithm: ConflictAlgorithm.replace);
  }

  // ─── Sync Queue ───────────────────────────────────────────────────────────

  Future<List<Map<String, dynamic>>> getUnsyncedOperations() async {
    final db = await database;
    return db.query('sync_queue');
  }

  Future<void> enqueueOperation(Map<String, dynamic> operation) async {
    final db = await database;
    await db.insert('sync_queue', operation, conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<void> removeFromQueue(List<String> localUuids) async {
    if (localUuids.isEmpty) return;
    final db = await database;
    final placeholders = List.filled(localUuids.length, '?').join(',');
    await db.delete('sync_queue', where: 'local_uuid IN ($placeholders)', whereArgs: localUuids);
  }
}
