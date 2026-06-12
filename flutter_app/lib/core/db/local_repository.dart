import 'dart:convert';
import 'package:sqflite/sqflite.dart';
import 'package:uuid/uuid.dart';
import '../models/models.dart';
import 'database_helper.dart';

class LocalRepository {
  final _dbHelper = DatabaseHelper.instance;
  final _uuid = const Uuid();

  // ==========================================
  // OFFLINE QUEUE UTILS
  // ==========================================

  Future<void> queueAction(String actionType, String endpoint, Map<String, dynamic> payload) async {
    final db = await _dbHelper.database;
    final actionId = _uuid.v4();
    await db.insert('offline_actions_queue', {
      'id': actionId,
      'action_type': actionType,
      'endpoint': endpoint,
      'payload': jsonEncode(payload),
      'created_at': DateTime.now().toIso8601String(),
    });
  }

  Future<List<Map<String, dynamic>>> getPendingActions() async {
    final db = await _dbHelper.database;
    return await db.query('offline_actions_queue', orderBy: 'created_at ASC');
  }

  Future<void> removeQueueAction(String id) async {
    final db = await _dbHelper.database;
    await db.delete('offline_actions_queue', where: 'id = ?', whereArgs: [id]);
  }

  // ==========================================
  // SWAYAMSEVAK CRUD
  // ==========================================

  Future<List<Swayamsevak>> getAllSwayamsevaks() async {
    final db = await _dbHelper.database;
    final maps = await db.query('swayamsevaks', where: 'is_active = 1', orderBy: 'name ASC');
    return maps.map((m) => Swayamsevak.fromJson(m)).toList();
  }

  Future<Swayamsevak?> getSwayamsevakById(int id) async {
    final db = await _dbHelper.database;
    final maps = await db.query('swayamsevaks', where: 'id = ?', whereArgs: [id]);
    if (maps.isEmpty) return null;
    return Swayamsevak.fromJson(maps.first);
  }

  Future<int> saveSwayamsevak(Swayamsevak swayamsevak, {bool sync = true}) async {
    final db = await _dbHelper.database;
    int id;

    if (swayamsevak.id != null) {
      id = swayamsevak.id!;
      await db.update(
        'swayamsevaks',
        swayamsevak.toJson(),
        where: 'id = ?',
        whereArgs: [id],
      );
    } else {
      // Generate temporary negative ID for offline insert
      id = (DateTime.now().millisecondsSinceEpoch % 1000000) * -1;
      final newSway = Swayamsevak(
        id: id,
        name: swayamsevak.name,
        address: swayamsevak.address,
        phone: swayamsevak.phone,
        age: swayamsevak.age,
        username: swayamsevak.username,
        shakhaId: swayamsevak.shakhaId,
        category: swayamsevak.category,
        gat: swayamsevak.gat,
        isGatNayak: swayamsevak.isGatNayak,
        isActive: swayamsevak.isActive,
        updatedAt: DateTime.now().toIso8601String(),
      );
      await db.insert('swayamsevaks', newSway.toJson());
    }

    if (sync) {
      // Queue action
      await queueAction(
        'save_swayamsevak',
        '/api/actions/swayamsevak_save.php',
        {
          'offline_id': id.toString(),
          ...swayamsevak.toJson(),
        },
      );
    }

    return id;
  }

  Future<void> deleteSwayamsevak(int id, {bool sync = true}) async {
    final db = await _dbHelper.database;
    await db.update(
      'swayamsevaks',
      {'is_active': 0, 'updated_at': DateTime.now().toIso8601String()},
      where: 'id = ?',
      whereArgs: [id],
    );

    if (sync) {
      await queueAction(
        'delete_swayamsevak',
        '/api/actions/swayamsevak_delete.php',
        {'id': id.toString()},
      );
    }
  }

  Future<void> resolveSwayamsevakMappings(Map<String, int> mappings) async {
    final db = await _dbHelper.database;
    await db.transaction((txn) async {
      for (var entry in mappings.entries) {
        final tempId = int.parse(entry.key);
        final realId = entry.value;

        // Update swayamsevak primary ID
        await txn.update(
          'swayamsevaks',
          {'id': realId},
          where: 'id = ?',
          whereArgs: [tempId],
        );

        // Update attendance references
        await txn.update(
          'attendance',
          {'swayamsevak_id': realId},
          where: 'swayamsevak_id = ?',
          whereArgs: [tempId],
        );

        // Update daily activities conducted_by reference
        await txn.update(
          'daily_activities',
          {'conducted_by': realId},
          where: 'conducted_by = ?',
          whereArgs: [tempId],
        );
      }
    });
  }

  // ==========================================
  // DAILY RECORD CRUD
  // ==========================================

  Future<DailyRecord?> getDailyRecordByDate(String date) async {
    final db = await _dbHelper.database;
    final maps = await db.query('daily_records', where: 'record_date = ? AND is_active = 1', whereArgs: [date]);
    if (maps.isEmpty) return null;
    return DailyRecord.fromJson(maps.first);
  }

  Future<List<Attendance>> getAttendanceForRecord(int recordId) async {
    final db = await _dbHelper.database;
    final maps = await db.query('attendance', where: 'daily_record_id = ?', whereArgs: [recordId]);
    return maps.map((m) => Attendance.fromJson(m)).toList();
  }

  Future<List<DailyActivity>> getActivitiesForRecord(int recordId) async {
    final db = await _dbHelper.database;
    final maps = await db.query('daily_activities', where: 'daily_record_id = ?', whereArgs: [recordId]);
    return maps.map((m) => DailyActivity.fromJson(m)).toList();
  }

  Future<int> saveDailyRecord({
    required DailyRecord record,
    required List<Attendance> attendance,
    required List<DailyActivity> activities,
    bool sync = true,
  }) async {
    final db = await _dbHelper.database;
    int recordId = record.id ?? (DateTime.now().millisecondsSinceEpoch % 1000000) * -1;

    await db.transaction((txn) async {
      // Save record base
      if (record.id != null) {
        await txn.update(
          'daily_records',
          record.toJson(),
          where: 'id = ?',
          whereArgs: [recordId],
        );
      } else {
        final newRecord = DailyRecord(
          id: recordId,
          recordDate: record.recordDate,
          yugabdh: record.yugabdh,
          vikramSamvat: record.vikramSamvat,
          shakaSamvat: record.shakaSamvat,
          hindiMonth: record.hindiMonth,
          paksh: record.paksh,
          tithi: record.tithi,
          utsav: record.utsav,
          customMessage: record.customMessage,
          shakhaId: record.shakhaId,
          isActive: record.isActive,
          updatedAt: DateTime.now().toIso8601String(),
        );
        await txn.insert('daily_records', newRecord.toJson(), conflictAlgorithm: ConflictAlgorithm.replace);
      }

      // Save attendance list
      await txn.delete('attendance', where: 'daily_record_id = ?', whereArgs: [recordId]);
      for (var att in attendance) {
        final newAtt = Attendance(
          dailyRecordId: recordId,
          swayamsevakId: att.swayamsevakId,
          isPresent: att.isPresent,
          updatedAt: DateTime.now().toIso8601String(),
        );
        await txn.insert('attendance', newAtt.toJson());
      }

      // Save activities list
      await txn.delete('daily_activities', where: 'daily_record_id = ?', whereArgs: [recordId]);
      for (var act in activities) {
        final newAct = DailyActivity(
          dailyRecordId: recordId,
          activityId: act.activityId,
          isDone: act.isDone,
          conductedBy: act.conductedBy,
          updatedAt: DateTime.now().toIso8601String(),
        );
        await txn.insert('daily_activities', newAct.toJson());
      }
    });

    if (sync) {
      // Queue action
      final Map<String, dynamic> attendanceMap = {};
      for (var att in attendance) {
        attendanceMap[att.swayamsevakId.toString()] = att.isPresent == 1;
      }

      final Map<String, dynamic> activitiesMap = {};
      for (var act in activities) {
        activitiesMap[act.activityId.toString()] = {
          'is_done': act.isDone == 1,
          'conducted_by': act.conductedBy,
        };
      }

      await queueAction(
        'save_daily_record',
        '/api/actions/daily_record_save.php',
        {
          'offline_id': recordId.toString(),
          'record_date': record.recordDate,
          'yugabdh': record.yugabdh,
          'vikram_samvat': record.vikramSamvat,
          'shaka_samvat': record.shakaSamvat,
          'hindi_month': record.hindiMonth,
          'paksh': record.paksh,
          'tithi': record.tithi,
          'utsav': record.utsav,
          'custom_message': record.customMessage,
          'attendance': attendanceMap,
          'activities': activitiesMap,
        },
      );
    }

    return recordId;
  }

  Future<void> resolveRecordMappings(Map<String, int> mappings) async {
    final db = await _dbHelper.database;
    await db.transaction((txn) async {
      for (var entry in mappings.entries) {
        final tempId = int.parse(entry.key);
        final realId = entry.value;

        // Update record primary ID
        await txn.update(
          'daily_records',
          {'id': realId},
          where: 'id = ?',
          whereArgs: [tempId],
        );

        // Update attendance foreign key reference
        await txn.update(
          'attendance',
          {'daily_record_id': realId},
          where: 'daily_record_id = ?',
          whereArgs: [tempId],
        );

        // Update daily activities foreign key reference
        await txn.update(
          'daily_activities',
          {'daily_record_id': realId},
          where: 'daily_record_id = ?',
          whereArgs: [tempId],
        );
      }
    });
  }

  // ==========================================
  // CONTENT LIBRARIES & CALENDARS
  // ==========================================

  Future<List<Subhashit>> getSubhashits() async {
    final db = await _dbHelper.database;
    final maps = await db.query('subhashits', where: 'is_active = 1', orderBy: 'subhashit_date DESC');
    return maps.map((m) => Subhashit.fromJson(m)).toList();
  }

  Future<List<AmritVachan>> getAmritVachans() async {
    final db = await _dbHelper.database;
    final maps = await db.query('amrit_vachan', where: 'is_active = 1', orderBy: 'vachan_date DESC');
    return maps.map((m) => AmritVachan.fromJson(m)).toList();
  }

  Future<List<Geet>> getGeets() async {
    final db = await _dbHelper.database;
    final maps = await db.query('geet', where: 'is_active = 1', orderBy: 'geet_date DESC');
    return maps.map((m) => Geet.fromJson(m)).toList();
  }

  Future<List<Ghoshna>> getGhoshnayein() async {
    final db = await _dbHelper.database;
    final maps = await db.query('ghoshnayein', where: 'is_active = 1', orderBy: 'ghoshnayein_date DESC');
    return maps.map((m) => Ghoshna.fromJson(m)).toList();
  }

  Future<List<Event>> getEvents() async {
    final db = await _dbHelper.database;
    final maps = await db.query('events', where: 'is_active = 1', orderBy: 'event_date DESC, event_time DESC');
    return maps.map((m) => Event.fromJson(m)).toList();
  }

  Future<List<Activity>> getActiveActivities() async {
    final db = await _dbHelper.database;
    final maps = await db.query('activities', where: 'is_active = 1', orderBy: 'name ASC');
    return maps.map((m) => Activity.fromJson(m)).toList();
  }

  Future<List<TimetableDefault>> getTimetableDefaults() async {
    final db = await _dbHelper.database;
    final maps = await db.query('timetable_defaults', where: 'is_active = 1');
    return maps.map((m) => TimetableDefault.fromJson(m)).toList();
  }

  Future<TimetableOverride?> getTimetableOverrideForDate(String date) async {
    final db = await _dbHelper.database;
    final maps = await db.query('timetable_overrides', where: 'override_date = ? AND is_active = 1', whereArgs: [date]);
    if (maps.isEmpty) return null;
    return TimetableOverride.fromJson(maps.first);
  }

  // ==========================================
  // BULK UPSERT OPERATIONS FOR PULL SYNC
  // ==========================================

  Future<void> bulkUpsert(String tableName, List<dynamic> rows) async {
    if (rows.isEmpty) return;
    final db = await _dbHelper.database;
    await db.transaction((txn) async {
      for (var row in rows) {
        final data = Map<String, dynamic>.from(row);
        await txn.insert(
          tableName,
          data,
          conflictAlgorithm: ConflictAlgorithm.replace,
        );
      }
    });
  }
}
