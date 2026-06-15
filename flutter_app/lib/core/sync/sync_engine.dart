import 'dart:async';
import 'dart:convert';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../api/api_client.dart';
import '../db/local_repository.dart';

class SyncEngine {
  final ApiClient apiClient;
  final LocalRepository localRepo;
  
  final ValueNotifier<bool> isSyncing = ValueNotifier<bool>(false);
  final ValueNotifier<String?> lastSyncTime = ValueNotifier<String?>(null);
  final ValueNotifier<String?> syncError = ValueNotifier<String?>(null);

  Timer? _periodicSyncTimer;

  SyncEngine({required this.apiClient, required this.localRepo}) {
    _initLastSyncTime();
    _setupConnectivityListener();
    _startPeriodicSync();
  }

  Future<void> _initLastSyncTime() async {
    final prefs = await SharedPreferences.getInstance();
    lastSyncTime.value = prefs.getString('last_sync_timestamp');
  }

  void _setupConnectivityListener() {
    Connectivity().onConnectivityChanged.listen((List<ConnectivityResult> results) {
      if (results.isNotEmpty && results.first != ConnectivityResult.none) {
        // Auto sync when back online
        sync();
      }
    });
  }

  void _startPeriodicSync() {
    _periodicSyncTimer?.cancel();
    _periodicSyncTimer = Timer.periodic(const Duration(minutes: 5), (timer) {
      debugPrint('Sync: Periodic background sync triggered.');
      sync();
    });
  }

  void dispose() {
    _periodicSyncTimer?.cancel();
  }

  Future<void> sync() async {
    if (isSyncing.value) return;
    
    // Check if network is available
    final connectivity = await Connectivity().checkConnectivity();
    if (connectivity.isEmpty || connectivity.first == ConnectivityResult.none) {
      debugPrint('Sync skipped: Device offline');
      syncError.value = 'डिवाइस ऑफ़लाइन है (Device offline)';
      return;
    }

    isSyncing.value = true;
    syncError.value = null; // Clear previous error
    try {
      debugPrint('Sync: Starting upload of queued actions...');
      await _pushOfflineQueue();

      debugPrint('Sync: Starting download of server updates...');
      await _pullServerChanges();

      debugPrint('Sync completed successfully.');
      syncError.value = null;
    } catch (e) {
      debugPrint('Sync Engine failed: $e');
      syncError.value = e.toString().replaceAll('Exception: ', '');
    } finally {
      isSyncing.value = false;
    }
  }

  Future<void> forceFullSync() async {
    if (isSyncing.value) return;

    final connectivity = await Connectivity().checkConnectivity();
    if (connectivity.isEmpty || connectivity.first == ConnectivityResult.none) {
      debugPrint('Sync skipped: Device offline');
      syncError.value = 'डिवाइस ऑफ़लाइन है (Device offline)';
      return;
    }

    isSyncing.value = true;
    syncError.value = null;
    try {
      debugPrint('Sync: Forcing full sync. Resetting timestamp...');
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('last_sync_timestamp', '1970-01-01 00:00:00');

      debugPrint('Sync: Starting upload of queued actions...');
      await _pushOfflineQueue();

      debugPrint('Sync: Starting download of ALL server updates...');
      await _pullServerChanges();

      debugPrint('Sync completed successfully (forced full sync).');
      syncError.value = null;
    } catch (e) {
      debugPrint('Sync Engine failed: $e');
      syncError.value = e.toString().replaceAll('Exception: ', '');
    } finally {
      isSyncing.value = false;
    }
  }

  Future<void> _pushOfflineQueue() async {
    final actions = await localRepo.getPendingActions();
    final pendingRecordsDirect = await localRepo.getPendingDailyRecords();

    if (actions.isEmpty && pendingRecordsDirect.isEmpty) {
      debugPrint('Push Sync: No pending actions or records in queue.');
      return;
    }

    // Compile batch changes
    final List<Map<String, dynamic>> swayamsevaks = [];
    final List<Map<String, dynamic>> dailyRecords = [];
    final List<String> actionQueueIds = [];

    for (var act in actions) {
      final type = act['action_type'];
      final payload = jsonDecode(act['payload']) as Map<String, dynamic>;
      
      if (type == 'save_swayamsevak') {
        swayamsevaks.add(payload);
      } else if (type == 'save_daily_record') {
        dailyRecords.add(payload);
      }
      actionQueueIds.add(act['id']);
    }

    // Add any daily records directly marked as pending_sync in database
    for (var recDirect in pendingRecordsDirect) {
      final exists = dailyRecords.any((r) => r['offline_id'] == recDirect['offline_id'] || r['record_date'] == recDirect['record_date']);
      if (!exists) {
        dailyRecords.add(recDirect);
      }
    }

    final response = await apiClient.post(
      '/api/sync/push.php',
      data: {
        'swayamsevaks': swayamsevaks,
        'daily_records': dailyRecords,
      },
    );

    if (response.statusCode == 200 && response.data != null) {
      final data = response.data;
      if (data['success'] == true) {
        // 1. Resolve local swayamsevak ID mapping
        if (data['swayamsevak_mappings'] != null && data['swayamsevak_mappings'] is Map) {
          final swMappings = Map<String, dynamic>.from(data['swayamsevak_mappings'] as Map)
              .map((k, v) => MapEntry(k, v as int));
          await localRepo.resolveSwayamsevakMappings(swMappings);
        }

        // 2. Resolve local daily record ID mapping and clear pending_sync flag
        if (data['record_mappings'] != null && data['record_mappings'] is Map) {
          final recMappings = Map<String, dynamic>.from(data['record_mappings'] as Map)
              .map((k, v) => MapEntry(k, v as int));
          await localRepo.resolveRecordMappings(recMappings);
        }

        // Also proactively clear pending_sync for all records that were successfully pushed
        // in case any were omitted from mappings but successfully saved
        for (var rec in dailyRecords) {
          final offlineIdStr = rec['offline_id'] as String?;
          if (offlineIdStr != null) {
            final offlineId = int.tryParse(offlineIdStr);
            if (offlineId != null) {
              await localRepo.resolveRecordMappings({offlineIdStr: offlineId});
            }
          }
        }

        // 3. Clear processed actions from SQLite queue
        for (var id in actionQueueIds) {
          await localRepo.removeQueueAction(id);
        }
        debugPrint('Push Sync: Cleared ${actionQueueIds.length} items from action queue.');
      } else {
        throw Exception(data['message'] ?? 'Push failed on server');
      }
    } else {
      throw Exception('Failed to reach push endpoint: HTTP ${response.statusCode}');
    }
  }

  Future<void> _pullServerChanges() async {
    final prefs = await SharedPreferences.getInstance();
    final lastSync = prefs.getString('last_sync_timestamp') ?? '1970-01-01 00:00:00';

    final response = await apiClient.get(
      '/api/sync/pull.php',
      queryParameters: {'last_sync_timestamp': lastSync},
    );

    if (response.statusCode == 200 && response.data != null) {
      final data = response.data;
      if (data['success'] == true) {
        if (data['data'] is! Map) {
          throw Exception('Invalid server response: "data" field is not a map');
        }
        final tablesData = Map<String, dynamic>.from(data['data'] as Map);
        final serverTimestamp = data['server_timestamp'] as String;

        // Upsert tables sequentially
        if (tablesData['shakhas'] != null) {
          await localRepo.bulkUpsert('shakhas', tablesData['shakhas']);
        }
        if (tablesData['swayamsevaks'] != null) {
          await localRepo.bulkUpsert('swayamsevaks', tablesData['swayamsevaks']);
        }
        if (tablesData['daily_records'] != null) {
          await localRepo.bulkUpsert('daily_records', tablesData['daily_records']);
        }
        if (tablesData['attendance'] != null) {
          await localRepo.bulkUpsert('attendance', tablesData['attendance']);
        }
        if (tablesData['activities'] != null) {
          await localRepo.bulkUpsert('activities', tablesData['activities']);
        }
        if (tablesData['daily_activities'] != null) {
          await localRepo.bulkUpsert('daily_activities', tablesData['daily_activities']);
        }
        if (tablesData['timetable_defaults'] != null) {
          await localRepo.bulkUpsert('timetable_defaults', tablesData['timetable_defaults']);
        }
        if (tablesData['timetable_overrides'] != null) {
          await localRepo.bulkUpsert('timetable_overrides', tablesData['timetable_overrides']);
        }
        if (tablesData['events'] != null) {
          await localRepo.bulkUpsert('events', tablesData['events']);
        }
        if (tablesData['subhashits'] != null) {
          await localRepo.bulkUpsert('subhashits', tablesData['subhashits']);
        }
        if (tablesData['amrit_vachan'] != null) {
          await localRepo.bulkUpsert('amrit_vachan', tablesData['amrit_vachan']);
        }
        if (tablesData['geet'] != null) {
          await localRepo.bulkUpsert('geet', tablesData['geet']);
        }
        if (tablesData['ghoshnayein'] != null) {
          await localRepo.bulkUpsert('ghoshnayein', tablesData['ghoshnayein']);
        }
        if (tablesData['notices'] != null) {
          await localRepo.bulkUpsert('notices', tablesData['notices']);
        }
        if (tablesData['personalities'] != null) {
          await localRepo.bulkUpsert('personalities', tablesData['personalities']);
        }

        // Save new sync timestamp
        await prefs.setString('last_sync_timestamp', serverTimestamp);
        lastSyncTime.value = serverTimestamp;
        debugPrint('Pull Sync: DB updated. New sync timestamp: $serverTimestamp');
      } else {
        throw Exception(data['message'] ?? 'Pull failed on server');
      }
    } else {
      throw Exception('Failed to reach pull endpoint: HTTP ${response.statusCode}');
    }
  }
}
