import 'dart:convert';
import 'package:sqflite/sqflite.dart';
import 'package:uuid/uuid.dart';
import '../models/models.dart';
import 'database_helper.dart';

class LocalRepository {
  final _dbHelper = DatabaseHelper.instance;
  final _uuid = const Uuid();

  Future<Map<String, dynamic>?> getShakhaById(int id) async {
    final db = await _dbHelper.database;
    final List<Map<String, dynamic>> maps = await db.query(
      'shakhas',
      where: 'id = ?',
      whereArgs: [id],
    );
    if (maps.isEmpty) return null;
    return maps.first;
  }

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
    final maps = await db.query('swayamsevaks', where: 'is_active = 1 AND is_deleted = 0', orderBy: 'name ASC');
    return maps.map((m) => Swayamsevak.fromJson(m)).toList();
  }

  Future<Swayamsevak?> getSwayamsevakById(int id) async {
    final db = await _dbHelper.database;
    final maps = await db.query('swayamsevaks', where: 'id = ? AND is_deleted = 0', whereArgs: [id]);
    if (maps.isEmpty) return null;
    return Swayamsevak.fromJson(maps.first);
  }

  Future<int> saveSwayamsevak(Swayamsevak swayamsevak, {bool sync = true}) async {
    final db = await _dbHelper.database;
    int id;

    if (swayamsevak.id != null) {
      id = swayamsevak.id!;
      final data = swayamsevak.toJson();
      data['updated_at'] = DateTime.now().toIso8601String();
      await db.update(
        'swayamsevaks',
        data,
        where: 'id = ?',
        whereArgs: [id],
      );
    } else {
      // Generate temporary negative ID for offline insert
      id = (DateTime.now().millisecondsSinceEpoch % 1000000) * -1;
      final data = swayamsevak.toJson();
      data['id'] = id;
      data['created_at'] = DateTime.now().toIso8601String();
      data['updated_at'] = DateTime.now().toIso8601String();
      data['is_deleted'] = 0;
      await db.insert('swayamsevaks', data);
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
    final nowStr = DateTime.now().toIso8601String();
    await db.update(
      'swayamsevaks',
      {
        'is_active': 0,
        'is_deleted': 1,
        'updated_at': nowStr
      },
      where: 'id = ?',
      whereArgs: [id],
    );

    if (sync) {
      final maps = await db.query('swayamsevaks', where: 'id = ?', whereArgs: [id]);
      if (maps.isNotEmpty) {
        final data = Map<String, dynamic>.from(maps.first);
        await queueAction(
          'save_swayamsevak',
          '/api/actions/swayamsevak_save.php',
          {
            'offline_id': id.toString(),
            ...data,
          },
        );
      }
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
    final maps = await db.query('daily_records', where: 'record_date = ? AND is_active = 1 AND is_deleted = 0', whereArgs: [date]);
    if (maps.isEmpty) return null;
    return DailyRecord.fromJson(maps.first);
  }

  Future<List<Attendance>> getAttendanceForRecord(int recordId) async {
    final db = await _dbHelper.database;
    final maps = await db.query('attendance', where: 'daily_record_id = ? AND is_deleted = 0', whereArgs: [recordId]);
    return maps.map((m) => Attendance.fromJson(m)).toList();
  }

  Future<List<DailyActivity>> getActivitiesForRecord(int recordId) async {
    final db = await _dbHelper.database;
    final maps = await db.query('daily_activities', where: 'daily_record_id = ? AND is_deleted = 0', whereArgs: [recordId]);
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
        final data = record.toJson();
        if (sync) {
          data['pending_sync'] = 1;
        }
        data['updated_at'] = DateTime.now().toIso8601String();
        await txn.update(
          'daily_records',
          data,
          where: 'id = ?',
          whereArgs: [recordId],
        );
      } else {
        final data = record.toJson();
        data['id'] = recordId;
        if (sync) {
          data['pending_sync'] = 1;
        }
        data['created_at'] = DateTime.now().toIso8601String();
        data['updated_at'] = DateTime.now().toIso8601String();
        data['is_deleted'] = 0;
        await txn.insert('daily_records', data, conflictAlgorithm: ConflictAlgorithm.replace);
      }

      // Save attendance list
      await txn.delete('attendance', where: 'daily_record_id = ?', whereArgs: [recordId]);
      for (var att in attendance) {
        final data = att.toJson();
        data['daily_record_id'] = recordId;
        data['created_at'] = DateTime.now().toIso8601String();
        data['updated_at'] = DateTime.now().toIso8601String();
        data['is_deleted'] = 0;
        await txn.insert('attendance', data);
      }

      // Save activities list
      await txn.delete('daily_activities', where: 'daily_record_id = ?', whereArgs: [recordId]);
      for (var act in activities) {
        final data = act.toJson();
        data['daily_record_id'] = recordId;
        data['created_at'] = DateTime.now().toIso8601String();
        data['updated_at'] = DateTime.now().toIso8601String();
        data['is_deleted'] = 0;
        await txn.insert('daily_activities', data);
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

  Future<List<Map<String, dynamic>>> getPendingDailyRecords() async {
    final db = await _dbHelper.database;
    final records = await db.query('daily_records', where: 'pending_sync = 1');
    final List<Map<String, dynamic>> results = [];
    
    for (var rec in records) {
      final recordId = rec['id'] as int;
      
      // Fetch attendance
      final attendanceList = await getAttendanceForRecord(recordId);
      final Map<String, dynamic> attendanceMap = {};
      for (var att in attendanceList) {
        attendanceMap[att.swayamsevakId.toString()] = att.isPresent == 1;
      }
      
      // Fetch activities
      final activitiesList = await getActivitiesForRecord(recordId);
      final Map<String, dynamic> activitiesMap = {};
      for (var act in activitiesList) {
        activitiesMap[act.activityId.toString()] = {
          'is_done': act.isDone == 1,
          'conducted_by': act.conductedBy,
        };
      }
      
      results.add({
        'offline_id': recordId.toString(),
        'record_date': rec['record_date'],
        'yugabdh': rec['yugabdh'],
        'vikram_samvat': rec['vikram_samvat'],
        'shaka_samvat': rec['shaka_samvat'],
        'hindi_month': rec['hindi_month'],
        'paksh': rec['paksh'],
        'tithi': rec['tithi'],
        'utsav': rec['utsav'],
        'custom_message': rec['custom_message'],
        'attendance': attendanceMap,
        'activities': activitiesMap,
      });
    }
    
    return results;
  }

  Future<void> resolveRecordMappings(Map<String, int> mappings) async {
    final db = await _dbHelper.database;
    await db.transaction((txn) async {
      for (var entry in mappings.entries) {
        final tempId = int.parse(entry.key);
        final realId = entry.value;

        // Update record primary ID and clear pending_sync flag
        await txn.update(
          'daily_records',
          {'id': realId, 'pending_sync': 0},
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
    final maps = await db.query('subhashits', where: 'is_active = 1 AND is_deleted = 0', orderBy: 'subhashit_date DESC');
    return maps.map((m) => Subhashit.fromJson(m)).toList();
  }

  Future<List<AmritVachan>> getAmritVachans() async {
    final db = await _dbHelper.database;
    final maps = await db.query('amrit_vachan', where: 'is_active = 1 AND is_deleted = 0', orderBy: 'vachan_date DESC');
    return maps.map((m) => AmritVachan.fromJson(m)).toList();
  }

  Future<List<Geet>> getGeets() async {
    final db = await _dbHelper.database;
    final maps = await db.query('geet', where: 'is_active = 1 AND is_deleted = 0', orderBy: 'geet_date DESC');
    return maps.map((m) => Geet.fromJson(m)).toList();
  }

  Future<List<Ghoshna>> getGhoshnayein() async {
    final db = await _dbHelper.database;
    final maps = await db.query('ghoshnayein', where: 'is_active = 1 AND is_deleted = 0', orderBy: 'ghoshna_date DESC');
    return maps.map((m) => Ghoshna.fromJson(m)).toList();
  }

  Future<List<Event>> getEvents() async {
    final db = await _dbHelper.database;
    final maps = await db.query('events', where: 'is_active = 1 AND is_deleted = 0', orderBy: 'event_date DESC, event_time DESC');
    return maps.map((m) => Event.fromJson(m)).toList();
  }

  Future<List<Activity>> getActiveActivities() async {
    final db = await _dbHelper.database;
    final maps = await db.query('activities', where: 'is_active = 1 AND is_deleted = 0', orderBy: 'name ASC');
    return maps.map((m) => Activity.fromJson(m)).toList();
  }

  Future<List<TimetableDefault>> getTimetableDefaults() async {
    final db = await _dbHelper.database;
    final maps = await db.query('timetable_defaults', where: 'is_active = 1 AND is_deleted = 0');
    return maps.map((m) => TimetableDefault.fromJson(m)).toList();
  }

  Future<TimetableOverride?> getTimetableOverrideForDate(String date) async {
    final db = await _dbHelper.database;
    final maps = await db.query('timetable_overrides', where: 'override_date = ? AND is_active = 1 AND is_deleted = 0', whereArgs: [date]);
    if (maps.isEmpty) return null;
    return TimetableOverride.fromJson(maps.first);
  }

  Future<int> getTotalSwayamsevaks() async {
    final db = await _dbHelper.database;
    final result = await db.rawQuery('SELECT COUNT(*) as count FROM swayamsevaks WHERE is_active = 1 AND is_deleted = 0');
    return Sqflite.firstIntValue(result) ?? 0;
  }

  Future<int> getTotalDailyRecords() async {
    final db = await _dbHelper.database;
    final result = await db.rawQuery('SELECT COUNT(*) as count FROM daily_records WHERE is_active = 1 AND is_deleted = 0');
    return Sqflite.firstIntValue(result) ?? 0;
  }

  Future<List<Map<String, dynamic>>> getRecentDailyRecords(int limit) async {
    final db = await _dbHelper.database;
    final List<Map<String, dynamic>> maps = await db.query(
      'daily_records',
      where: 'is_active = 1 AND is_deleted = 0',
      orderBy: 'record_date DESC',
      limit: limit,
    );
    return _enrichRecords(maps);
  }

  Future<List<Map<String, dynamic>>> getAllDailyRecords() async {
    final db = await _dbHelper.database;
    final List<Map<String, dynamic>> maps = await db.query(
      'daily_records',
      where: 'is_active = 1 AND is_deleted = 0',
      orderBy: 'record_date DESC',
    );
    return _enrichRecords(maps);
  }

  Future<List<Map<String, dynamic>>> _enrichRecords(List<Map<String, dynamic>> maps) async {
    final db = await _dbHelper.database;
    final List<Map<String, dynamic>> result = [];
    for (var m in maps) {
      final recordId = m['id'] as int;
      final presentResult = await db.rawQuery(
        'SELECT COUNT(*) as count FROM attendance WHERE daily_record_id = ? AND is_present = 1',
        [recordId],
      );
      final presentCount = Sqflite.firstIntValue(presentResult) ?? 0;

      final totalResult = await db.rawQuery(
        'SELECT COUNT(*) as count FROM attendance WHERE daily_record_id = ?',
        [recordId],
      );
      final totalCount = Sqflite.firstIntValue(totalResult) ?? 0;

      final activityDoneResult = await db.rawQuery(
        'SELECT COUNT(*) as count FROM daily_activities WHERE daily_record_id = ? AND is_done = 1',
        [recordId],
      );
      final activityDoneCount = Sqflite.firstIntValue(activityDoneResult) ?? 0;

      final activityTotalResult = await db.rawQuery(
        'SELECT COUNT(*) as count FROM daily_activities WHERE daily_record_id = ?',
        [recordId],
      );
      final activityTotalCount = Sqflite.firstIntValue(activityTotalResult) ?? 0;

      final map = Map<String, dynamic>.from(m);
      map['present_count'] = presentCount;
      map['total_count'] = totalCount;
      map['activities_done'] = activityDoneCount;
      map['activities_total'] = activityTotalCount;
      result.add(map);
    }
    return result;
  }

  // ==========================================
  // BULK UPSERT OPERATIONS FOR PULL SYNC
  // ==========================================

  Future<void> bulkUpsert(String tableName, List<dynamic> rows) async {
    if (rows.isEmpty) return;
    final db = await _dbHelper.database;
    
    // Get columns of the SQLite table dynamically to avoid crashes on missing columns (e.g. created_at)
    final List<Map<String, dynamic>> columnsInfo = await db.rawQuery('PRAGMA table_info($tableName)');
    final Set<String> existingColumns = columnsInfo.map((col) => col['name'] as String).toSet();
    
    await db.transaction((txn) async {
      for (var row in rows) {
        final data = Map<String, dynamic>.from(row);
        // Filter out keys that do not exist as columns in SQLite
        data.removeWhere((key, value) => !existingColumns.contains(key));
        
        await txn.insert(
          tableName,
          data,
          conflictAlgorithm: ConflictAlgorithm.replace,
        );
      }
    });
  }

  // ==========================================
  // OFFLINE PANCHANG CACHE
  // ==========================================

  Future<Map<String, dynamic>?> getCachedPanchang(String date) async {
    final db = await _dbHelper.database;
    final List<Map<String, dynamic>> maps = await db.query(
      'panchang_cache',
      where: 'date = ?',
      whereArgs: [date],
    );
    if (maps.isEmpty) return null;
    return maps.first;
  }

  Future<void> cachePanchangList(List<dynamic> list) async {
    if (list.isEmpty) return;
    final db = await _dbHelper.database;
    await db.transaction((txn) async {
      for (var entry in list) {
        final data = Map<String, dynamic>.from(entry);
        await txn.insert(
          'panchang_cache',
          data,
          conflictAlgorithm: ConflictAlgorithm.replace,
        );
      }
    });
  }
}
