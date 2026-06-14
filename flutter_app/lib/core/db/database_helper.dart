import 'dart:async';
import 'dart:convert';
import 'package:path/path.dart';
import 'package:sqflite/sqflite.dart';

class DatabaseHelper {
  static final DatabaseHelper instance = DatabaseHelper._init();
  static Database? _database;

  DatabaseHelper._init();

  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDB('sanghasthan.db');
    await _sanitizeLocalDatabase(_database!);
    return _database!;
  }

  Future<Database> _initDB(String filePath) async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, filePath);

    return await openDatabase(
      path,
      version: 5,
      onCreate: _createDB,
      onUpgrade: _onUpgrade,
    );
  }

  Future<void> _onUpgrade(Database db, int oldVersion, int newVersion) async {
    if (oldVersion < 3) {
      final tables = [
        'shakhas',
        'swayamsevaks',
        'daily_records',
        'attendance',
        'activities',
        'daily_activities',
        'timetable_defaults',
        'timetable_overrides',
        'events',
        'subhashits',
        'amrit_vachan',
        'geet',
        'ghoshnayein',
        'offline_actions_queue'
      ];
      for (var table in tables) {
        await db.execute('DROP TABLE IF EXISTS $table');
      }
      await _createDB(db, newVersion);
    }

    if (oldVersion < 4) {
      final tables = [
        'shakhas',
        'swayamsevaks',
        'daily_records',
        'attendance',
        'activities',
        'daily_activities',
        'timetable_defaults',
        'timetable_overrides',
        'events',
        'subhashits',
        'amrit_vachan',
        'geet',
        'ghoshnayein'
      ];
      for (var table in tables) {
        try {
          await db.execute('ALTER TABLE $table ADD COLUMN created_at TEXT');
        } catch (_) {}
        try {
          await db.execute('ALTER TABLE $table ADD COLUMN is_deleted INTEGER DEFAULT 0');
        } catch (_) {}
        try {
          await db.execute('ALTER TABLE $table ADD COLUMN updated_at TEXT');
        } catch (_) {}
      }
    }

    if (oldVersion < 5) {
      await db.execute('''
        CREATE TABLE IF NOT EXISTS panchang_cache (
          date TEXT PRIMARY KEY,
          tithi TEXT,
          paksha TEXT,
          vikram_month TEXT,
          shaka_month TEXT,
          vikram_samvat TEXT,
          shaka_samvat TEXT,
          yugabdha TEXT,
          utsav TEXT,
          nakshatra TEXT,
          sunrise TEXT,
          sunset TEXT
        )
      ''');
    }
  }

  Future<void> _createDB(Database db, int version) async {
    const textType = 'TEXT';
    const integerType = 'INTEGER';
    const primaryKeyAuto = 'INTEGER PRIMARY KEY AUTOINCREMENT';

    // 1. Shakhas Table
    await db.execute('''
      CREATE TABLE shakhas (
        id $integerType PRIMARY KEY,
        name $textType,
        openai_api_key $textType,
        use_ai_crosscheck $integerType,
        created_at $textType,
        updated_at $textType,
        is_deleted $integerType DEFAULT 0
      )
    ''');

    // 2. Swayamsevaks Table
    await db.execute('''
      CREATE TABLE swayamsevaks (
        id $primaryKeyAuto,
        name $textType NOT NULL,
        address $textType,
        phone $textType,
        age $integerType,
        username $textType,
        shakha_id $integerType,
        category $textType,
        gat $textType,
        is_gat_nayak $integerType,
        is_active $integerType DEFAULT 1,
        created_at $textType,
        updated_at $textType,
        is_deleted $integerType DEFAULT 0
      )
    ''');

    // 3. Daily Records Table
    await db.execute('''
      CREATE TABLE daily_records (
        id $primaryKeyAuto,
        record_date $textType UNIQUE,
        yugabdh $textType,
        vikram_samvat $textType,
        shaka_samvat $textType,
        hindi_month $textType,
        paksh $textType,
        tithi $textType,
        utsav $textType,
        custom_message $textType,
        shakha_id $integerType,
        is_active $integerType DEFAULT 1,
        pending_sync $integerType DEFAULT 0,
        created_at $textType,
        updated_at $textType,
        is_deleted $integerType DEFAULT 0
      )
    ''');

    // 4. Attendance Table
    await db.execute('''
      CREATE TABLE attendance (
        daily_record_id $integerType,
        swayamsevak_id $integerType,
        is_present $integerType,
        created_at $textType,
        updated_at $textType,
        is_deleted $integerType DEFAULT 0,
        PRIMARY KEY (daily_record_id, swayamsevak_id)
      )
    ''');

    // 5. Activities Table
    await db.execute('''
      CREATE TABLE activities (
        id $integerType PRIMARY KEY,
        name $textType,
        is_active $integerType DEFAULT 1,
        shakha_id $integerType,
        created_at $textType,
        updated_at $textType,
        is_deleted $integerType DEFAULT 0
      )
    ''');

    // 6. Daily Activities Table
    await db.execute('''
      CREATE TABLE daily_activities (
        daily_record_id $integerType,
        activity_id $integerType,
        is_done $integerType,
        conducted_by $integerType,
        created_at $textType,
        updated_at $textType,
        is_deleted $integerType DEFAULT 0,
        PRIMARY KEY (daily_record_id, activity_id)
      )
    ''');

    // 7. Timetable Defaults Table
    await db.execute('''
      CREATE TABLE timetable_defaults (
        shakha_id $integerType,
        day_of_week $integerType,
        slots $textType,
        is_active $integerType DEFAULT 1,
        created_at $textType,
        updated_at $textType,
        is_deleted $integerType DEFAULT 0,
        PRIMARY KEY (shakha_id, day_of_week)
      )
    ''');

    // 8. Timetable Overrides Table
    await db.execute('''
      CREATE TABLE timetable_overrides (
        shakha_id $integerType,
        override_date $textType,
        slots $textType,
        is_active $integerType DEFAULT 1,
        created_at $textType,
        updated_at $textType,
        is_deleted $integerType DEFAULT 0,
        PRIMARY KEY (shakha_id, override_date)
      )
    ''');

    // 9. Events Table
    await db.execute('''
      CREATE TABLE events (
        id $primaryKeyAuto,
        shakha_id $integerType,
        title $textType NOT NULL,
        description $textType,
        event_date $textType,
        event_time $textType,
        location $textType,
        meeting_link $textType,
        created_by $integerType,
        is_active $integerType DEFAULT 1,
        created_at $textType,
        updated_at $textType,
        is_deleted $integerType DEFAULT 0
      )
    ''');

    // 10. Subhashits Table
    await db.execute('''
      CREATE TABLE subhashits (
        id $primaryKeyAuto,
        shakha_id $integerType,
        sanskrit_text $textType,
        hindi_meaning $textType,
        shabdarth $textType,
        subhashit_date $textType,
        panchang_text $textType,
        created_by $integerType,
        is_active $integerType DEFAULT 1,
        created_at $textType,
        updated_at $textType,
        is_deleted $integerType DEFAULT 0
      )
    ''');

    // 11. Amrit Vachan Table
    await db.execute('''
      CREATE TABLE amrit_vachan (
        id $primaryKeyAuto,
        shakha_id $integerType,
        content $textType,
        author $textType,
        vachan_date $textType,
        created_by $integerType,
        is_active $integerType DEFAULT 1,
        created_at $textType,
        updated_at $textType,
        is_deleted $integerType DEFAULT 0
      )
    ''');

    // 12. Geet Table
    await db.execute('''
      CREATE TABLE geet (
        id $primaryKeyAuto,
        shakha_id $integerType,
        title $textType NOT NULL,
        lyrics $textType NOT NULL,
        meaning_or_context $textType,
        geet_type $textType,
        geet_date $textType,
        created_by $integerType,
        is_active $integerType DEFAULT 1,
        created_at $textType,
        updated_at $textType,
        is_deleted $integerType DEFAULT 0
      )
    ''');

    // 13. Ghoshnayein Table
    await db.execute('''
      CREATE TABLE ghoshnayein (
        id $primaryKeyAuto,
        shakha_id $integerType,
        slogan_sanskrit $textType,
        slogan_hindi $textType,
        context $textType,
        ghoshna_date $textType,
        created_by $integerType,
        is_active $integerType DEFAULT 1,
        created_at $textType,
        updated_at $textType,
        is_deleted $integerType DEFAULT 0
      )
    ''');

    // 14. Action Queue Table
    await db.execute('''
      CREATE TABLE offline_actions_queue (
        id $textType PRIMARY KEY,
        action_type $textType NOT NULL,
        endpoint $textType NOT NULL,
        payload $textType NOT NULL,
        created_at $textType NOT NULL
      )
    ''');

    // 15. Panchang Cache Table
    await db.execute('''
      CREATE TABLE panchang_cache (
        date $textType PRIMARY KEY,
        tithi $textType,
        paksha $textType,
        vikram_month $textType,
        shaka_month $textType,
        vikram_samvat $textType,
        shaka_samvat $textType,
        yugabdha $textType,
        utsav $textType,
        nakshatra $textType,
        sunrise $textType,
        sunset $textType
      )
    ''');
  }

  Future<void> clearAllData() async {
    final db = await database;
    final tables = [
      'shakhas',
      'swayamsevaks',
      'daily_records',
      'attendance',
      'activities',
      'daily_activities',
      'timetable_defaults',
      'timetable_overrides',
      'events',
      'subhashits',
      'amrit_vachan',
      'geet',
      'ghoshnayein',
      'offline_actions_queue',
      'panchang_cache'
    ];
    for (var table in tables) {
      await db.delete(table);
    }
  }

  Future<void> close() async {
    final db = _database;
    if (db != null) {
      await db.close();
    }
  }

  Future<void> _sanitizeLocalDatabase(Database db) async {
    try {
      // 1. Sanitize offline actions queue
      final actions = await db.query('offline_actions_queue');
      for (var action in actions) {
        final id = action['id'] as String;
        final payloadStr = action['payload'] as String;
        try {
          final payload = jsonDecode(payloadStr) as Map<String, dynamic>;
          bool changed = false;
          
          if (payload.containsKey('record_date')) {
            final date = payload['record_date'] as String;
            final sanitized = _sanitizeDateString(date);
            if (sanitized != date) {
              payload['record_date'] = sanitized;
              changed = true;
            }
          }
          if (payload.containsKey('override_date')) {
            final date = payload['override_date'] as String;
            final sanitized = _sanitizeDateString(date);
            if (sanitized != date) {
              payload['override_date'] = sanitized;
              changed = true;
            }
          }
          
          if (changed) {
            await db.update(
              'offline_actions_queue',
              {'payload': jsonEncode(payload)},
              where: 'id = ?',
              whereArgs: [id],
            );
          }
        } catch (_) {}
      }

      // 2. Sanitize daily_records table
      final records = await db.query('daily_records');
      for (var record in records) {
        final id = record['id'] as int;
        final date = record['record_date'] as String;
        final sanitized = _sanitizeDateString(date);
        if (sanitized != date) {
          final existing = await db.query('daily_records', where: 'record_date = ?', whereArgs: [sanitized]);
          if (existing.isNotEmpty) {
            await db.delete('daily_records', where: 'id = ?', whereArgs: [id]);
            await db.delete('attendance', where: 'daily_record_id = ?', whereArgs: [id]);
            await db.delete('daily_activities', where: 'daily_record_id = ?', whereArgs: [id]);
          } else {
            await db.update('daily_records', {'record_date': sanitized}, where: 'id = ?', whereArgs: [id]);
          }
        }
      }
      
      // 3. Sanitize timetable_overrides table
      final overrides = await db.query('timetable_overrides');
      for (var ov in overrides) {
        final shakhaId = ov['shakha_id'] as int;
        final date = ov['override_date'] as String;
        final sanitized = _sanitizeDateString(date);
        if (sanitized != date) {
          final existing = await db.query('timetable_overrides', 
              where: 'shakha_id = ? AND override_date = ?', whereArgs: [shakhaId, sanitized]);
          if (existing.isNotEmpty) {
            await db.delete('timetable_overrides', 
                where: 'shakha_id = ? AND override_date = ?', whereArgs: [shakhaId, date]);
          } else {
            await db.update('timetable_overrides', 
                {'override_date': sanitized}, 
                where: 'shakha_id = ? AND override_date = ?', whereArgs: [shakhaId, date]);
          }
        }
      }
    } catch (_) {}
  }

  String _sanitizeDateString(String dateStr) {
    final parts = dateStr.split('-');
    if (parts.length == 3) {
      var y = int.tryParse(parts[0]) ?? DateTime.now().year;
      var m = int.tryParse(parts[1]) ?? DateTime.now().month;
      var d = int.tryParse(parts[2]) ?? DateTime.now().day;
      
      if (m < 1 || m > 12) {
        m = DateTime.now().month;
      }
      return '${y.toString().padLeft(4, '0')}-${m.toString().padLeft(2, '0')}-${d.toString().padLeft(2, '0')}';
    }
    return dateStr;
  }
}
