import '../../../core/providers/providers.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../core/api/api_client.dart';
import '../models/event_models.dart';
import '../services/event_api_service.dart';

final eventApiServiceProvider = Provider<EventApiService>((ref) {
  final apiClient = ref.read(apiClientProvider);
  return EventApiService(apiClient);
});

class EventSessionState {
  final bool isLoggedIn;
  final String? token;
  final int? organizerId;
  final String? organizerName;
  final String? role;
  final int? eventId;
  final String? eventName;

  EventSessionState({
    this.isLoggedIn = false,
    this.token,
    this.organizerId,
    this.organizerName,
    this.role,
    this.eventId,
    this.eventName,
  });

  EventSessionState copyWith({
    bool? isLoggedIn,
    String? token,
    int? organizerId,
    String? organizerName,
    String? role,
    int? eventId,
    String? eventName,
  }) {
    return EventSessionState(
      isLoggedIn: isLoggedIn ?? this.isLoggedIn,
      token: token ?? this.token,
      organizerId: organizerId ?? this.organizerId,
      organizerName: organizerName ?? this.organizerName,
      role: role ?? this.role,
      eventId: eventId ?? this.eventId,
      eventName: eventName ?? this.eventName,
    );
  }
}

class EventSessionNotifier extends StateNotifier<EventSessionState> {
  EventSessionNotifier() : super(EventSessionState()) {
    _loadSession();
  }

  Future<void> _loadSession() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('event_api_token');
    
    if (token != null && token.isNotEmpty) {
      state = state.copyWith(
        isLoggedIn: true,
        token: token,
        organizerId: prefs.getInt('event_organizer_id'),
        organizerName: prefs.getString('event_organizer_name'),
        role: prefs.getString('event_role'),
        eventId: prefs.getInt('event_event_id'),
        eventName: prefs.getString('event_event_name'),
      );
    }
  }

  Future<void> login(Map<String, dynamic> userData) async {
    final prefs = await SharedPreferences.getInstance();
    
    final token = userData['token'] as String?;
    final organizerId = userData['id'] != null 
      ? (userData['id'] is String ? int.tryParse(userData['id']) : userData['id'] as int?) 
      : null;
    final organizerName = userData['name'] as String?;
    final role = userData['role'] as String?;
    
    if (token != null) await prefs.setString('event_api_token', token);
    if (organizerId != null) await prefs.setInt('event_organizer_id', organizerId);
    if (organizerName != null) await prefs.setString('event_organizer_name', organizerName);
    if (role != null) await prefs.setString('event_role', role);
    
    state = state.copyWith(
      isLoggedIn: true,
      token: token,
      organizerId: organizerId,
      organizerName: organizerName,
      role: role,
    );
  }

  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    
    final keys = prefs.getKeys();
    for (String key in keys) {
      if (key.startsWith('event_')) {
        await prefs.remove(key);
      }
    }
    
    state = EventSessionState();
  }

  Future<void> switchEvent(int eventId, String eventName) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt('event_event_id', eventId);
    await prefs.setString('event_event_name', eventName);
    
    state = state.copyWith(
      eventId: eventId,
      eventName: eventName,
    );
  }
}

final eventSessionProvider = StateNotifierProvider<EventSessionNotifier, EventSessionState>((ref) {
  return EventSessionNotifier();
});

final eventDashboardProvider = FutureProvider<dynamic>((ref) async {
  final api = ref.watch(eventApiServiceProvider);
  return api.getDashboardStats();
});

final eventParticipantsProvider = FutureProvider.family<dynamic, Map<String, dynamic>>((ref, params) async {
  final api = ref.watch(eventApiServiceProvider);
  return api.getParticipants(search: params['search'] as String?, group: params['group'] as int?, category: params['category'] as String?, page: (params['page'] as int?) ?? 1);
});

final eventScheduleProvider = FutureProvider.family<dynamic, String>((ref, date) async {
  final api = ref.watch(eventApiServiceProvider);
  return api.getSchedule(date: date);
});

final eventAttendanceSessionsProvider = FutureProvider<dynamic>((ref) async {
  final api = ref.watch(eventApiServiceProvider);
  return api.getAttendanceSessions();
});

final attendanceListProvider = FutureProvider.family<dynamic, Map<String, dynamic>>((ref, params) async {
  final api = ref.watch(eventApiServiceProvider);
  return api.getAttendanceList(params['session_id'] as int, search: params['search'] as String?);
});

final eventMyTasksProvider = FutureProvider<dynamic>((ref) async {
  final api = ref.watch(eventApiServiceProvider);
  return api.getMyTasks();
});

final eventRoomsProvider = FutureProvider<dynamic>((ref) async {
  final api = ref.watch(eventApiServiceProvider);
  return api.getRooms();
});

final eventMealsProvider = FutureProvider.family<dynamic, String>((ref, date) async {
  final api = ref.watch(eventApiServiceProvider);
  return api.getMeals(date: date);
});


final eventListProvider = FutureProvider<List<EventInfo>>((ref) async {
  final api = ref.watch(eventApiServiceProvider);
  return api.getEvents();
});

final participantListProvider = FutureProvider.family<dynamic, Map<String, dynamic>>((ref, params) async {
  final api = ref.watch(eventApiServiceProvider);
  return api.getParticipants(search: params['search'] as String?, group: params['group'] as int?, category: params['category'] as String?, page: (params['page'] as int?) ?? 1);
});
