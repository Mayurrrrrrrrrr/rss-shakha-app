import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../api/api_client.dart';
import '../db/local_repository.dart';
import '../sync/sync_engine.dart';
import '../models/models.dart';

// Base instances
final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient();
});

final localRepoProvider = Provider<LocalRepository>((ref) {
  return LocalRepository();
});

final syncEngineProvider = Provider<SyncEngine>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  final localRepo = ref.watch(localRepoProvider);
  return SyncEngine(apiClient: apiClient, localRepo: localRepo);
});

// Authentication state notifier
class SessionState {
  final bool isLoggedIn;
  final String? token;
  final int? userId;
  final String? userName;
  final String? role;
  final int? shakhaId;

  SessionState({
    required this.isLoggedIn,
    this.token,
    this.userId,
    this.userName,
    this.role,
    this.shakhaId,
  });

  factory SessionState.empty() => SessionState(isLoggedIn: false);
}

class SessionNotifier extends StateNotifier<SessionState> {
  SessionNotifier() : super(SessionState.empty()) {
    _loadSession();
  }

  Future<void> _loadSession() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('api_token');
    if (token != null && token.isNotEmpty) {
      state = SessionState(
        isLoggedIn: true,
        token: token,
        userId: prefs.getInt('user_id'),
        userName: prefs.getString('user_name'),
        role: prefs.getString('user_role'),
        shakhaId: prefs.getInt('shakha_id'),
      );
    }
  }

  Future<void> login(Map<String, dynamic> userData) async {
    final prefs = await SharedPreferences.getInstance();
    final token = userData['token'] as String;
    final id = userData['id'] as int;
    final name = userData['name'] as String;
    final role = userData['role'] as String;
    final shakhaId = userData['shakha_id'] as int;

    await prefs.setString('api_token', token);
    await prefs.setInt('user_id', id);
    await prefs.setString('user_name', name);
    await prefs.setString('user_role', role);
    await prefs.setInt('shakha_id', shakhaId);

    state = SessionState(
      isLoggedIn: true,
      token: token,
      userId: id,
      userName: name,
      role: role,
      shakhaId: shakhaId,
    );
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('api_token');
    await prefs.remove('user_id');
    await prefs.remove('user_name');
    await prefs.remove('user_role');
    await prefs.remove('shakha_id');
    await prefs.remove('last_sync_timestamp');
    
    // Clear SQLite database contents on logout for security/clean state
    await LocalRepository().bulkUpsert('shakhas', []);
    
    state = SessionState.empty();
  }
}

final sessionProvider = StateNotifierProvider<SessionNotifier, SessionState>((ref) {
  return SessionNotifier();
});

// Swayamsevaks list provider (watches database updates)
final swayamsevaksListProvider = FutureProvider<List<Swayamsevak>>((ref) async {
  final repo = ref.watch(localRepoProvider);
  return await repo.getAllSwayamsevaks();
});

// Library content providers
final subhashitsListProvider = FutureProvider<List<Subhashit>>((ref) async {
  final repo = ref.watch(localRepoProvider);
  return await repo.getSubhashits();
});

final amritVachansListProvider = FutureProvider<List<AmritVachan>>((ref) async {
  final repo = ref.watch(localRepoProvider);
  return await repo.getAmritVachans();
});

final geetsListProvider = FutureProvider<List<Geet>>((ref) async {
  final repo = ref.watch(localRepoProvider);
  return await repo.getGeets();
});

final ghoshnayeinListProvider = FutureProvider<List<Ghoshna>>((ref) async {
  final repo = ref.watch(localRepoProvider);
  return await repo.getGhoshnayein();
});

final eventsListProvider = FutureProvider<List<Event>>((ref) async {
  final repo = ref.watch(localRepoProvider);
  return await repo.getEvents();
});

final noticesListProvider = FutureProvider<List<Notice>>((ref) async {
  final repo = ref.watch(localRepoProvider);
  return await repo.getNotices();
});

final personalitiesListProvider = FutureProvider<List<Personality>>((ref) async {
  final repo = ref.watch(localRepoProvider);
  return await repo.getPersonalities();
});
