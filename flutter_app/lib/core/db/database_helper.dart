import 'dart:async';
import 'package:path/path.dart';
import 'package:sqflite/sqflite.dart';

class DatabaseHelper {
  static final DatabaseHelper instance = DatabaseHelper._init();
  static Database? _database;

  DatabaseHelper._init();

  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDB('sanghasthan.db');
    return _database!;
  }

  Future<Database> _initDB(String filePath) async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, filePath);

    return await openDatabase(
      path,
      version: 2,
      onCreate: _createDB,
      onUpgrade: _onUpgrade,
    );
  }

  Future<void> _onUpgrade(Database db, int oldVersion, int newVersion) async {
    if (oldVersion < 2) {
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
        updated_at $textType
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
        updated_at $textType
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
        updated_at $textType
      )
    ''');

    // 4. Attendance Table
    await db.execute('''
      CREATE TABLE attendance (
        daily_record_id $integerType,
        swayamsevak_id $integerType,
        is_present $integerType,
        updated_at $textType,
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
        updated_at $textType
      )
    ''');

    // 6. Daily Activities Table
    await db.execute('''
      CREATE TABLE daily_activities (
        daily_record_id $integerType,
        activity_id $integerType,
        is_done $integerType,
        conducted_by $integerType,
        updated_at $textType,
        PRIMARY KEY (daily_record_id, activity_id)
      )
    ''');

    // 7. Timetable Defaults Table
    await db.execute('''
      CREATE TABLE timetable_defaults (
        shakha_id $integerType,
        day_of_week $integerType,
        slots $textType,
        updated_at $textType,
        is_active $integerType DEFAULT 1,
        PRIMARY KEY (shakha_id, day_of_week)
      )
    ''');

    // 8. Timetable Overrides Table
    await db.execute('''
      CREATE TABLE timetable_overrides (
        shakha_id $integerType,
        override_date $textType,
        slots $textType,
        updated_at $textType,
        is_active $integerType DEFAULT 1,
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
        updated_at $textType
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
        updated_at $textType
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
        updated_at $textType
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
        updated_at $textType
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
        updated_at $textType
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
      'offline_actions_queue'
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
}
