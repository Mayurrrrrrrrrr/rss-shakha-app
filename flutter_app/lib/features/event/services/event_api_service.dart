import '../../../core/api/api_client.dart';
import '../models/event_models.dart';

class EventApiService {
  final ApiClient _apiClient;

  EventApiService(this._apiClient);

  void _handleError(Map<String, dynamic> response) {
    if (response['success'] == false) {
      throw Exception(response['message'] ?? 'An error occurred');
    }
  }

  // Auth
  Future<Map<String, dynamic>> login(String username, String password, {int? eventId}) async {
    final data = {
      'username': username,
      'password': password,
      if (eventId != null) 'event_id': eventId,
    };
    final response = await _apiClient.post('/api/v1/event/login', data: data);
    if (response.data['success'] == true) {
      return response.data['data'];
    }
    throw Exception(response.data['message'] ?? 'Login failed');
  }

  Future<Map<String, dynamic>> getProfile() async {
    final response = await _apiClient.get('/api/v1/event/profile');
    if (response.data['success'] == true) {
      return response.data['data'];
    }
    throw Exception(response.data['message'] ?? 'Failed to get profile');
  }

  // Events
  Future<List<EventInfo>> getEvents({String? status}) async {
    final query = status != null ? {'status': status} : null;
    final response = await _apiClient.get('/api/v1/event/events', queryParameters: query);
    if (response.data['success'] == true) {
      final List data = response.data['data'];
      return data.map((e) => EventInfo.fromJson(e)).toList();
    }
    throw Exception(response.data['message'] ?? 'Failed to get events');
  }

  Future<EventInfo> createEvent({
    required String name,
    String? description,
    String? venue,
    required String startDate,
    String? endDate,
  }) async {
    final data = {
      'name': name,
      'description': description,
      'venue': venue,
      'start_date': startDate,
      'end_date': endDate,
    };
    final response = await _apiClient.post('/api/v1/event/events', data: data);
    if (response.data['success'] == true) {
      return EventInfo.fromJson(response.data['data']);
    }
    throw Exception(response.data['message'] ?? 'Failed to create event');
  }

  Future<void> updateEvent(int eventId, Map<String, dynamic> data) async {
    final response = await _apiClient.post('/api/v1/event/events/$eventId', data: data);
    _handleError(response.data);
  }

  Future<Map<String, dynamic>> getEventDetails(int eventId) async {
    final response = await _apiClient.get('/api/v1/event/events/$eventId');
    if (response.data['success'] == true) {
      return response.data['data'];
    }
    throw Exception(response.data['message'] ?? 'Failed to get event details');
  }

  Future<EventInfo> cloneEvent(
    int sourceEventId, {
    required String newName,
    required String newStartDate,
    String? newEndDate,
    String? newVenue,
  }) async {
    final data = {
      'new_name': newName,
      'new_start_date': newStartDate,
      'new_end_date': newEndDate,
      'new_venue': newVenue,
    };
    final response = await _apiClient.post('/api/v1/event/events/$sourceEventId/clone', data: data);
    if (response.data['success'] == true) {
      return EventInfo.fromJson(response.data['data']);
    }
    throw Exception(response.data['message'] ?? 'Failed to clone event');
  }

  Future<Map<String, dynamic>> exportEvent(int eventId, {String format = 'json'}) async {
    final response = await _apiClient.get('/api/v1/event/events/$eventId/export', queryParameters: {'format': format});
    if (response.data['success'] == true) {
      return response.data;
    }
    throw Exception(response.data['message'] ?? 'Failed to export event');
  }

  // Organizers
  Future<List<Organizer>> getOrganizers({String? role}) async {
    final query = role != null ? {'role': role} : null;
    final response = await _apiClient.get('/api/v1/event/organizers', queryParameters: query);
    if (response.data['success'] == true) {
      final List data = response.data['data'];
      return data.map((e) => Organizer.fromJson(e)).toList();
    }
    throw Exception(response.data['message'] ?? 'Failed to get organizers');
  }

  Future<Organizer> saveOrganizer(Map<String, dynamic> data) async {
    final response = await _apiClient.post('/api/v1/event/organizers', data: data);
    if (response.data['success'] == true) {
      return Organizer.fromJson(response.data['data']);
    }
    throw Exception(response.data['message'] ?? 'Failed to save organizer');
  }

  Future<void> deleteOrganizer(int id) async {
    final response = await _apiClient.post('/api/v1/event/organizers/$id/delete'); // Assuming POST for deletion
    _handleError(response.data);
  }

  // Participants
  Future<Map<String, dynamic>> getParticipants({
    int page = 1,
    int perPage = 50,
    String? search,
    int? group,
    String? category,
  }) async {
    final query = {
      'page': page,
      'per_page': perPage,
      if (search != null) 'search': search,
      if (group != null) 'group': group,
      if (category != null) 'category': category,
    };
    final response = await _apiClient.get('/api/v1/event/participants', queryParameters: query);
    if (response.data['success'] == true) {
      return response.data['data'];
    }
    throw Exception(response.data['message'] ?? 'Failed to get participants');
  }

  Future<Participant> saveParticipant(Map<String, dynamic> data) async {
    final response = await _apiClient.post('/api/v1/event/participants', data: data);
    if (response.data['success'] == true) {
      return Participant.fromJson(response.data['data']);
    }
    throw Exception(response.data['message'] ?? 'Failed to save participant');
  }

  Future<Participant> spotEntry(Map<String, dynamic> data) async {
    final response = await _apiClient.post('/api/v1/event/participants/spot-entry', data: data);
    if (response.data['success'] == true) {
      return Participant.fromJson(response.data['data']);
    }
    throw Exception(response.data['message'] ?? 'Failed to create spot entry');
  }

  Future<List<Participant>> searchParticipants(String query) async {
    final response = await _apiClient.get('/api/v1/event/participants/search', queryParameters: {'q': query});
    if (response.data['success'] == true) {
      final List data = response.data['data'];
      return data.map((e) => Participant.fromJson(e)).toList();
    }
    throw Exception(response.data['message'] ?? 'Failed to search participants');
  }

  Future<void> deleteParticipant(int id) async {
    final response = await _apiClient.post('/api/v1/event/participants/$id/delete');
    _handleError(response.data);
  }

  Future<Map<String, dynamic>> importParticipants(String csvContent) async {
    final response = await _apiClient.post('/api/v1/event/participants/import', data: {'csv_content': csvContent});
    if (response.data['success'] == true) {
      return response.data['data'];
    }
    throw Exception(response.data['message'] ?? 'Failed to import participants');
  }

  // Attendance
  Future<List<AttendanceSession>> getAttendanceSessions() async {
    final response = await _apiClient.get('/api/v1/event/attendance/sessions');
    if (response.data['success'] == true) {
      final List data = response.data['data'];
      return data.map((e) => AttendanceSession.fromJson(e)).toList();
    }
    throw Exception(response.data['message'] ?? 'Failed to get attendance sessions');
  }

  Future<Map<String, dynamic>> getAttendanceList(int sessionId, {String? search}) async {
    final query = {'session_id': sessionId};
    if (search != null && search.isNotEmpty) {
      query['search'] = search;
    }
    final response = await _apiClient.get('/api/v1/event/attendance/list.php', queryParameters: query);
    if (response.data['success'] == true) {
      return response.data['data'];
    }
    throw Exception(response.data['message'] ?? 'Failed to get attendance list');
  }

  Future<AttendanceSession> saveAttendanceSession(Map<String, dynamic> data) async {
    final response = await _apiClient.post('/api/v1/event/attendance/sessions', data: data);
    if (response.data['success'] == true) {
      return AttendanceSession.fromJson(response.data['data']);
    }
    throw Exception(response.data['message'] ?? 'Failed to save attendance session');
  }

  Future<List<AttendanceDuty>> getAttendanceDuties() async {
    final response = await _apiClient.get('/api/v1/event/attendance/duties');
    if (response.data['success'] == true) {
      final List data = response.data['data'];
      return data.map((e) => AttendanceDuty.fromJson(e)).toList();
    }
    throw Exception(response.data['message'] ?? 'Failed to get attendance duties');
  }

  Future<void> assignAttendanceDuty(Map<String, dynamic> data) async {
    final response = await _apiClient.post('/api/v1/event/attendance/duties/assign', data: data);
    _handleError(response.data);
  }

  Future<List<AttendanceDuty>> getMyDuties() async {
    final response = await _apiClient.get('/api/v1/event/attendance/my-duties');
    if (response.data['success'] == true) {
      final List data = response.data['data'];
      return data.map((e) => AttendanceDuty.fromJson(e)).toList();
    }
    throw Exception(response.data['message'] ?? 'Failed to get my duties');
  }

  Future<void> markAttendance(int sessionId, List<Map<String, dynamic>> attendances) async {
    final data = {
      'session_id': sessionId,
      'attendances': attendances,
    };
    final response = await _apiClient.post('/api/v1/event/attendance/mark', data: data);
    _handleError(response.data);
  }

  Future<Map<String, dynamic>> getAttendanceReport({int? sessionId, String? date}) async {
    final query = {
      if (sessionId != null) 'session_id': sessionId,
      if (date != null) 'date': date,
    };
    final response = await _apiClient.get('/api/v1/event/attendance/report', queryParameters: query);
    if (response.data['success'] == true) {
      return response.data['data'];
    }
    throw Exception(response.data['message'] ?? 'Failed to get attendance report');
  }

  // Work
  Future<List<WorkCategory>> getWorkCategories() async {
    final response = await _apiClient.get('/api/v1/event/work/categories');
    if (response.data['success'] == true) {
      final List data = response.data['data'];
      return data.map((e) => WorkCategory.fromJson(e)).toList();
    }
    throw Exception(response.data['message'] ?? 'Failed to get work categories');
  }

  Future<WorkCategory> saveWorkCategory(Map<String, dynamic> data) async {
    final response = await _apiClient.post('/api/v1/event/work/categories', data: data);
    if (response.data['success'] == true) {
      return WorkCategory.fromJson(response.data['data']);
    }
    throw Exception(response.data['message'] ?? 'Failed to save work category');
  }

  Future<List<WorkAssignment>> getWorkAssignments({String? date, int? organizerId, String? status}) async {
    final query = {
      if (date != null) 'date': date,
      if (organizerId != null) 'organizer_id': organizerId,
      if (status != null) 'status': status,
    };
    final response = await _apiClient.get('/api/v1/event/work/assignments', queryParameters: query);
    if (response.data['success'] == true) {
      final List data = response.data['data'];
      return data.map((e) => WorkAssignment.fromJson(e)).toList();
    }
    throw Exception(response.data['message'] ?? 'Failed to get work assignments');
  }

  Future<void> assignWork(Map<String, dynamic> data) async {
    final response = await _apiClient.post('/api/v1/event/work/assign', data: data);
    _handleError(response.data);
  }

  Future<List<WorkAssignment>> getMyTasks({String? date, String? status}) async {
    final query = {
      if (date != null) 'date': date,
      if (status != null) 'status': status,
    };
    final response = await _apiClient.get('/api/v1/event/work/my-tasks', queryParameters: query);
    if (response.data['success'] == true) {
      final List data = response.data['data'];
      return data.map((e) => WorkAssignment.fromJson(e)).toList();
    }
    throw Exception(response.data['message'] ?? 'Failed to get my tasks');
  }

  Future<void> updateTaskStatus(int id, String status) async {
    final data = {'status': status};
    final response = await _apiClient.post('/api/v1/event/work/tasks/$id/status', data: data);
    _handleError(response.data);
  }

  // Rooms
  Future<List<Room>> getRooms() async {
    final response = await _apiClient.get('/api/v1/event/rooms');
    if (response.data['success'] == true) {
      final List data = response.data['data'];
      return data.map((e) => Room.fromJson(e)).toList();
    }
    throw Exception(response.data['message'] ?? 'Failed to get rooms');
  }

  Future<Room> saveRoom(Map<String, dynamic> data) async {
    final response = await _apiClient.post('/api/v1/event/rooms', data: data);
    if (response.data['success'] == true) {
      return Room.fromJson(response.data['data']);
    }
    throw Exception(response.data['message'] ?? 'Failed to save room');
  }

  Future<void> allotRoom(Map<String, dynamic> data) async {
    final response = await _apiClient.post('/api/v1/event/rooms/allot', data: data);
    _handleError(response.data);
  }

  Future<List<RoomAllotment>> getRoomAllotments({int? roomId}) async {
    final query = roomId != null ? {'room_id': roomId} : null;
    final response = await _apiClient.get('/api/v1/event/rooms/allotments', queryParameters: query);
    if (response.data['success'] == true) {
      final List data = response.data['data'];
      return data.map((e) => RoomAllotment.fromJson(e)).toList();
    }
    throw Exception(response.data['message'] ?? 'Failed to get room allotments');
  }

  Future<Map<String, dynamic>> getRoomReport() async {
    final response = await _apiClient.get('/api/v1/event/rooms/report');
    if (response.data['success'] == true) {
      return response.data['data'];
    }
    throw Exception(response.data['message'] ?? 'Failed to get room report');
  }

  // Food
  Future<List<Meal>> getMeals({String? date}) async {
    final query = date != null ? {'date': date} : null;
    final response = await _apiClient.get('/api/v1/event/food/meals', queryParameters: query);
    if (response.data['success'] == true) {
      final List data = response.data['data'];
      return data.map((e) => Meal.fromJson(e)).toList();
    }
    throw Exception(response.data['message'] ?? 'Failed to get meals');
  }

  Future<Meal> saveMeal(Map<String, dynamic> data) async {
    final response = await _apiClient.post('/api/v1/event/food/meals', data: data);
    if (response.data['success'] == true) {
      return Meal.fromJson(response.data['data']);
    }
    throw Exception(response.data['message'] ?? 'Failed to save meal');
  }

  Future<void> trackMeal(Map<String, dynamic> data) async {
    final response = await _apiClient.post('/api/v1/event/food/track', data: data);
    _handleError(response.data);
  }

  Future<Map<String, dynamic>> getFoodReport({String? date}) async {
    final query = date != null ? {'date': date} : null;
    final response = await _apiClient.get('/api/v1/event/food/report', queryParameters: query);
    if (response.data['success'] == true) {
      return response.data['data'];
    }
    throw Exception(response.data['message'] ?? 'Failed to get food report');
  }

  // Schedule
  Future<List<ScheduleItem>> getSchedule({String? date}) async {
    final query = date != null ? {'date': date} : null;
    final response = await _apiClient.get('/api/v1/event/schedule', queryParameters: query);
    if (response.data['success'] == true) {
      final List data = response.data['data'];
      return data.map((e) => ScheduleItem.fromJson(e)).toList();
    }
    throw Exception(response.data['message'] ?? 'Failed to get schedule');
  }

  Future<ScheduleItem> saveScheduleItem(Map<String, dynamic> data) async {
    final response = await _apiClient.post('/api/v1/event/schedule', data: data);
    if (response.data['success'] == true) {
      return ScheduleItem.fromJson(response.data['data']);
    }
    throw Exception(response.data['message'] ?? 'Failed to save schedule item');
  }

  Future<List<ScheduleItem>> getTodaySchedule() async {
    final response = await _apiClient.get('/api/v1/event/schedule/today');
    if (response.data['success'] == true) {
      final List data = response.data['data'];
      return data.map((e) => ScheduleItem.fromJson(e)).toList();
    }
    throw Exception(response.data['message'] ?? 'Failed to get today schedule');
  }

  // Dashboard
  Future<DashboardStats> getDashboardStats() async {
    final response = await _apiClient.get('/api/v1/event/dashboard/stats');
    if (response.data['success'] == true) {
      return DashboardStats.fromJson(response.data['data']);
    }
    throw Exception(response.data['message'] ?? 'Failed to get dashboard stats');
  }

  // Reports
  Future<Map<String, dynamic>> getSummaryReport() async {
    final response = await _apiClient.get('/api/v1/event/reports/summary');
    if (response.data['success'] == true) {
      return response.data['data'];
    }
    throw Exception(response.data['message'] ?? 'Failed to get summary report');
  }

  Future<dynamic> exportData({String type = 'all', String format = 'json'}) async {
    final query = {
      'type': type,
      'format': format,
    };
    final response = await _apiClient.get('/api/v1/event/reports/export', queryParameters: query);
    if (response.data['success'] == true) {
      return response.data['data'] ?? response.data;
    }
    throw Exception(response.data['message'] ?? 'Failed to export data');
  }
}
