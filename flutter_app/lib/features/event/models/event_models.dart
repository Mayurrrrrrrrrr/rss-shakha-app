class EventInfo {
  final int? id;
  final String name;
  final String? description;
  final String? venue;
  final String? startDate;
  final String? endDate;
  final String? status;
  final String? createdAt;

  EventInfo({
    this.id,
    required this.name,
    this.description,
    this.venue,
    this.startDate,
    this.endDate,
    this.status,
    this.createdAt,
  });

  factory EventInfo.fromJson(Map<String, dynamic> json) {
    return EventInfo(
      id: json['id'] != null ? (json['id'] is String ? int.tryParse(json['id']) : json['id']) : null,
      name: json['name'] ?? '',
      description: json['description'],
      venue: json['venue'],
      startDate: json['start_date'] ?? json['startDate'],
      endDate: json['end_date'] ?? json['endDate'],
      status: json['status'],
      createdAt: json['created_at'] ?? json['createdAt'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      'name': name,
      'description': description,
      'venue': venue,
      'start_date': startDate,
      'end_date': endDate,
      'status': status,
      'created_at': createdAt,
    };
  }
}

class Organizer {
  final int? id;
  final int? eventId;
  final String name;
  final String? phone;
  final String? username;
  final String? role;
  final bool isActive;

  Organizer({
    this.id,
    this.eventId,
    required this.name,
    this.phone,
    this.username,
    this.role,
    this.isActive = true,
  });

  factory Organizer.fromJson(Map<String, dynamic> json) {
    return Organizer(
      id: json['id'] != null ? (json['id'] is String ? int.tryParse(json['id']) : json['id']) : null,
      eventId: json['event_id'] != null ? (json['event_id'] is String ? int.tryParse(json['event_id']) : json['event_id']) : null,
      name: json['name'] ?? '',
      phone: json['phone'],
      username: json['username'],
      role: json['role'],
      isActive: json['is_active'] == 1 || json['is_active'] == '1' || json['is_active'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      if (eventId != null) 'event_id': eventId,
      'name': name,
      'phone': phone,
      'username': username,
      'role': role,
      'is_active': isActive ? 1 : 0,
    };
  }
}

class Participant {
  final int? id;
  final int? eventId;
  final String name;
  final String? phone;
  final String? city;
  final String? address;
  final int? age;
  final String? gender;
  final String? category;
  final int? groupNumber;
  final String? notes;
  final String? entryType;
  final int? registeredBy;
  final bool isActive;

  Participant({
    this.id,
    this.eventId,
    required this.name,
    this.phone,
    this.city,
    this.address,
    this.age,
    this.gender,
    this.category,
    this.groupNumber,
    this.notes,
    this.entryType,
    this.registeredBy,
    this.isActive = true,
  });

  factory Participant.fromJson(Map<String, dynamic> json) {
    return Participant(
      id: json['id'] != null ? (json['id'] is String ? int.tryParse(json['id']) : json['id']) : null,
      eventId: json['event_id'] != null ? (json['event_id'] is String ? int.tryParse(json['event_id']) : json['event_id']) : null,
      name: json['name'] ?? '',
      phone: json['phone'],
      city: json['city'],
      address: json['address'],
      age: json['age'] != null ? (json['age'] is String ? int.tryParse(json['age']) : json['age']) : null,
      gender: json['gender'],
      category: json['category'],
      groupNumber: json['group_number'] != null ? (json['group_number'] is String ? int.tryParse(json['group_number']) : json['group_number']) : null,
      notes: json['notes'],
      entryType: json['entry_type'],
      registeredBy: json['registered_by'] != null ? (json['registered_by'] is String ? int.tryParse(json['registered_by']) : json['registered_by']) : null,
      isActive: json['is_active'] == 1 || json['is_active'] == '1' || json['is_active'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      if (eventId != null) 'event_id': eventId,
      'name': name,
      'phone': phone,
      'city': city,
      'address': address,
      'age': age,
      'gender': gender,
      'category': category,
      'group_number': groupNumber,
      'notes': notes,
      'entry_type': entryType,
      'registered_by': registeredBy,
      'is_active': isActive ? 1 : 0,
    };
  }
}

class WorkCategory {
  final int? id;
  final int? eventId;
  final String name;
  final String? description;
  final int? sortOrder;

  WorkCategory({
    this.id,
    this.eventId,
    required this.name,
    this.description,
    this.sortOrder,
  });

  factory WorkCategory.fromJson(Map<String, dynamic> json) {
    return WorkCategory(
      id: json['id'] != null ? (json['id'] is String ? int.tryParse(json['id']) : json['id']) : null,
      eventId: json['event_id'] != null ? (json['event_id'] is String ? int.tryParse(json['event_id']) : json['event_id']) : null,
      name: json['name'] ?? '',
      description: json['description'],
      sortOrder: json['sort_order'] != null ? (json['sort_order'] is String ? int.tryParse(json['sort_order']) : json['sort_order']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      if (eventId != null) 'event_id': eventId,
      'name': name,
      'description': description,
      'sort_order': sortOrder,
    };
  }
}

class WorkAssignment {
  final int? id;
  final int? eventId;
  final int? workCategoryId;
  final String? categoryName;
  final int? organizerId;
  final String? organizerName;
  final String? description;
  final String? assignmentDate;
  final String? timeSlot;
  final String? status;
  final int? assignedBy;
  final String? notes;

  WorkAssignment({
    this.id,
    this.eventId,
    this.workCategoryId,
    this.categoryName,
    this.organizerId,
    this.organizerName,
    this.description,
    this.assignmentDate,
    this.timeSlot,
    this.status,
    this.assignedBy,
    this.notes,
  });

  factory WorkAssignment.fromJson(Map<String, dynamic> json) {
    return WorkAssignment(
      id: json['id'] != null ? (json['id'] is String ? int.tryParse(json['id']) : json['id']) : null,
      eventId: json['event_id'] != null ? (json['event_id'] is String ? int.tryParse(json['event_id']) : json['event_id']) : null,
      workCategoryId: json['work_category_id'] != null ? (json['work_category_id'] is String ? int.tryParse(json['work_category_id']) : json['work_category_id']) : null,
      categoryName: json['category_name'],
      organizerId: json['organizer_id'] != null ? (json['organizer_id'] is String ? int.tryParse(json['organizer_id']) : json['organizer_id']) : null,
      organizerName: json['organizer_name'],
      description: json['description'],
      assignmentDate: json['assignment_date'],
      timeSlot: json['time_slot'],
      status: json['status'],
      assignedBy: json['assigned_by'] != null ? (json['assigned_by'] is String ? int.tryParse(json['assigned_by']) : json['assigned_by']) : null,
      notes: json['notes'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      if (eventId != null) 'event_id': eventId,
      'work_category_id': workCategoryId,
      'category_name': categoryName,
      'organizer_id': organizerId,
      'organizer_name': organizerName,
      'description': description,
      'assignment_date': assignmentDate,
      'time_slot': timeSlot,
      'status': status,
      'assigned_by': assignedBy,
      'notes': notes,
    };
  }
}

class AttendanceSession {
  final int? id;
  final int? eventId;
  final String sessionName;
  final String? sessionDate;
  final String? sessionTime;
  final String? description;

  AttendanceSession({
    this.id,
    this.eventId,
    required this.sessionName,
    this.sessionDate,
    this.sessionTime,
    this.description,
  });

  factory AttendanceSession.fromJson(Map<String, dynamic> json) {
    return AttendanceSession(
      id: json['id'] != null ? (json['id'] is String ? int.tryParse(json['id']) : json['id']) : null,
      eventId: json['event_id'] != null ? (json['event_id'] is String ? int.tryParse(json['event_id']) : json['event_id']) : null,
      sessionName: json['session_name'] ?? '',
      sessionDate: json['session_date'],
      sessionTime: json['session_time'],
      description: json['description'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      if (eventId != null) 'event_id': eventId,
      'session_name': sessionName,
      'session_date': sessionDate,
      'session_time': sessionTime,
      'description': description,
    };
  }
}

class AttendanceDuty {
  final int? id;
  final int? eventId;
  final int? attendanceSessionId;
  final String? sessionName;
  final int? organizerId;
  final String? organizerName;
  final int? participantGroup;
  final int? assignedBy;

  AttendanceDuty({
    this.id,
    this.eventId,
    this.attendanceSessionId,
    this.sessionName,
    this.organizerId,
    this.organizerName,
    this.participantGroup,
    this.assignedBy,
  });

  factory AttendanceDuty.fromJson(Map<String, dynamic> json) {
    return AttendanceDuty(
      id: json['id'] != null ? (json['id'] is String ? int.tryParse(json['id']) : json['id']) : null,
      eventId: json['event_id'] != null ? (json['event_id'] is String ? int.tryParse(json['event_id']) : json['event_id']) : null,
      attendanceSessionId: json['attendance_session_id'] != null ? (json['attendance_session_id'] is String ? int.tryParse(json['attendance_session_id']) : json['attendance_session_id']) : null,
      sessionName: json['session_name'],
      organizerId: json['organizer_id'] != null ? (json['organizer_id'] is String ? int.tryParse(json['organizer_id']) : json['organizer_id']) : null,
      organizerName: json['organizer_name'],
      participantGroup: json['participant_group'] != null ? (json['participant_group'] is String ? int.tryParse(json['participant_group']) : json['participant_group']) : null,
      assignedBy: json['assigned_by'] != null ? (json['assigned_by'] is String ? int.tryParse(json['assigned_by']) : json['assigned_by']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      if (eventId != null) 'event_id': eventId,
      'attendance_session_id': attendanceSessionId,
      'session_name': sessionName,
      'organizer_id': organizerId,
      'organizer_name': organizerName,
      'participant_group': participantGroup,
      'assigned_by': assignedBy,
    };
  }
}

class AttendanceRecord {
  final int? id;
  final int? eventId;
  final int? attendanceSessionId;
  final int? participantId;
  final String? participantName;
  final bool isPresent;
  final int? markedBy;
  final String? markedAt;

  AttendanceRecord({
    this.id,
    this.eventId,
    this.attendanceSessionId,
    this.participantId,
    this.participantName,
    this.isPresent = false,
    this.markedBy,
    this.markedAt,
  });

  factory AttendanceRecord.fromJson(Map<String, dynamic> json) {
    return AttendanceRecord(
      id: json['id'] != null ? (json['id'] is String ? int.tryParse(json['id']) : json['id']) : null,
      eventId: json['event_id'] != null ? (json['event_id'] is String ? int.tryParse(json['event_id']) : json['event_id']) : null,
      attendanceSessionId: json['attendance_session_id'] != null ? (json['attendance_session_id'] is String ? int.tryParse(json['attendance_session_id']) : json['attendance_session_id']) : null,
      participantId: json['participant_id'] != null ? (json['participant_id'] is String ? int.tryParse(json['participant_id']) : json['participant_id']) : null,
      participantName: json['participant_name'],
      isPresent: json['is_present'] == 1 || json['is_present'] == '1' || json['is_present'] == true,
      markedBy: json['marked_by'] != null ? (json['marked_by'] is String ? int.tryParse(json['marked_by']) : json['marked_by']) : null,
      markedAt: json['marked_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      if (eventId != null) 'event_id': eventId,
      'attendance_session_id': attendanceSessionId,
      'participant_id': participantId,
      'participant_name': participantName,
      'is_present': isPresent ? 1 : 0,
      'marked_by': markedBy,
      'marked_at': markedAt,
    };
  }
}

class Room {
  final int? id;
  final int? eventId;
  final String roomName;
  final String? roomType;
  final int? capacity;
  final String? floor;
  final String? building;
  final String? notes;
  final int? currentOccupancy;

  Room({
    this.id,
    this.eventId,
    required this.roomName,
    this.roomType,
    this.capacity,
    this.floor,
    this.building,
    this.notes,
    this.currentOccupancy,
  });

  factory Room.fromJson(Map<String, dynamic> json) {
    return Room(
      id: json['id'] != null ? (json['id'] is String ? int.tryParse(json['id']) : json['id']) : null,
      eventId: json['event_id'] != null ? (json['event_id'] is String ? int.tryParse(json['event_id']) : json['event_id']) : null,
      roomName: json['room_name'] ?? '',
      roomType: json['room_type'],
      capacity: json['capacity'] != null ? (json['capacity'] is String ? int.tryParse(json['capacity']) : json['capacity']) : null,
      floor: json['floor'],
      building: json['building'],
      notes: json['notes'],
      currentOccupancy: json['current_occupancy'] != null ? (json['current_occupancy'] is String ? int.tryParse(json['current_occupancy']) : json['current_occupancy']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      if (eventId != null) 'event_id': eventId,
      'room_name': roomName,
      'room_type': roomType,
      'capacity': capacity,
      'floor': floor,
      'building': building,
      'notes': notes,
      'current_occupancy': currentOccupancy,
    };
  }
}

class RoomAllotment {
  final int? id;
  final int? eventId;
  final int? roomId;
  final String? roomName;
  final String? allotteeType;
  final int? allotteeId;
  final String? allotteeName;
  final int? allottedBy;
  final String? notes;

  RoomAllotment({
    this.id,
    this.eventId,
    this.roomId,
    this.roomName,
    this.allotteeType,
    this.allotteeId,
    this.allotteeName,
    this.allottedBy,
    this.notes,
  });

  factory RoomAllotment.fromJson(Map<String, dynamic> json) {
    return RoomAllotment(
      id: json['id'] != null ? (json['id'] is String ? int.tryParse(json['id']) : json['id']) : null,
      eventId: json['event_id'] != null ? (json['event_id'] is String ? int.tryParse(json['event_id']) : json['event_id']) : null,
      roomId: json['room_id'] != null ? (json['room_id'] is String ? int.tryParse(json['room_id']) : json['room_id']) : null,
      roomName: json['room_name'],
      allotteeType: json['allottee_type'],
      allotteeId: json['allottee_id'] != null ? (json['allottee_id'] is String ? int.tryParse(json['allottee_id']) : json['allottee_id']) : null,
      allotteeName: json['allottee_name'],
      allottedBy: json['allotted_by'] != null ? (json['allotted_by'] is String ? int.tryParse(json['allotted_by']) : json['allotted_by']) : null,
      notes: json['notes'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      if (eventId != null) 'event_id': eventId,
      'room_id': roomId,
      'room_name': roomName,
      'allottee_type': allotteeType,
      'allottee_id': allotteeId,
      'allottee_name': allotteeName,
      'allotted_by': allottedBy,
      'notes': notes,
    };
  }
}

class Meal {
  final int? id;
  final int? eventId;
  final String mealName;
  final String? mealDate;
  final String? mealTime;
  final int? expectedCount;
  final int? actualCount;
  final int? expectedUpcoming;
  final int? optedCount;
  final int? consumedCount;
  final String? notes;

  Meal({
    this.id,
    this.eventId,
    required this.mealName,
    this.mealDate,
    this.mealTime,
    this.expectedCount,
    this.actualCount,
    this.expectedUpcoming,
    this.optedCount,
    this.consumedCount,
    this.notes,
  });

  factory Meal.fromJson(Map<String, dynamic> json) {
    return Meal(
      id: json['id'] != null ? (json['id'] is String ? int.tryParse(json['id']) : json['id']) : null,
      eventId: json['event_id'] != null ? (json['event_id'] is String ? int.tryParse(json['event_id']) : json['event_id']) : null,
      mealName: json['meal_name'] ?? '',
      mealDate: json['meal_date'],
      mealTime: json['meal_time'],
      expectedCount: json['expected_count'] != null ? (json['expected_count'] is String ? int.tryParse(json['expected_count']) : json['expected_count']) : null,
      actualCount: json['actual_count'] != null ? (json['actual_count'] is String ? int.tryParse(json['actual_count']) : json['actual_count']) : null,
      expectedUpcoming: json['expected_upcoming'] != null ? (json['expected_upcoming'] is String ? int.tryParse(json['expected_upcoming']) : json['expected_upcoming']) : null,
      optedCount: json['opted_count'] != null ? (json['opted_count'] is String ? int.tryParse(json['opted_count']) : json['opted_count']) : null,
      consumedCount: json['consumed_count'] != null ? (json['consumed_count'] is String ? int.tryParse(json['consumed_count']) : json['consumed_count']) : null,
      notes: json['notes'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      if (eventId != null) 'event_id': eventId,
      'meal_name': mealName,
      'meal_date': mealDate,
      'meal_time': mealTime,
      'expected_count': expectedCount,
      'actual_count': actualCount,
      'expected_upcoming': expectedUpcoming,
      'opted_count': optedCount,
      'consumed_count': consumedCount,
      'notes': notes,
    };
  }
}

class MealTracking {
  final int? id;
  final int? eventId;
  final int? mealId;
  final String? personType;
  final int? personId;
  final String? status;
  final int? markedBy;

  MealTracking({
    this.id,
    this.eventId,
    this.mealId,
    this.personType,
    this.personId,
    this.status,
    this.markedBy,
  });

  factory MealTracking.fromJson(Map<String, dynamic> json) {
    return MealTracking(
      id: json['id'] != null ? (json['id'] is String ? int.tryParse(json['id']) : json['id']) : null,
      eventId: json['event_id'] != null ? (json['event_id'] is String ? int.tryParse(json['event_id']) : json['event_id']) : null,
      mealId: json['meal_id'] != null ? (json['meal_id'] is String ? int.tryParse(json['meal_id']) : json['meal_id']) : null,
      personType: json['person_type'],
      personId: json['person_id'] != null ? (json['person_id'] is String ? int.tryParse(json['person_id']) : json['person_id']) : null,
      status: json['status'],
      markedBy: json['marked_by'] != null ? (json['marked_by'] is String ? int.tryParse(json['marked_by']) : json['marked_by']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      if (eventId != null) 'event_id': eventId,
      'meal_id': mealId,
      'person_type': personType,
      'person_id': personId,
      'status': status,
      'marked_by': markedBy,
    };
  }
}

class ScheduleItem {
  final int? id;
  final int? eventId;
  final String activityName;
  final String? activityDate;
  final String? startTime;
  final String? endTime;
  final String? venue;
  final int? responsibleOrganizerId;
  final String? responsibleOrganizerName;
  final String? description;
  final int? sortOrder;

  ScheduleItem({
    this.id,
    this.eventId,
    required this.activityName,
    this.activityDate,
    this.startTime,
    this.endTime,
    this.venue,
    this.responsibleOrganizerId,
    this.responsibleOrganizerName,
    this.description,
    this.sortOrder,
  });

  factory ScheduleItem.fromJson(Map<String, dynamic> json) {
    return ScheduleItem(
      id: json['id'] != null ? (json['id'] is String ? int.tryParse(json['id']) : json['id']) : null,
      eventId: json['event_id'] != null ? (json['event_id'] is String ? int.tryParse(json['event_id']) : json['event_id']) : null,
      activityName: json['activity_name'] ?? '',
      activityDate: json['activity_date'],
      startTime: json['start_time'],
      endTime: json['end_time'],
      venue: json['venue'],
      responsibleOrganizerId: json['responsible_organizer_id'] != null ? (json['responsible_organizer_id'] is String ? int.tryParse(json['responsible_organizer_id']) : json['responsible_organizer_id']) : null,
      responsibleOrganizerName: json['responsible_organizer_name'],
      description: json['description'],
      sortOrder: json['sort_order'] != null ? (json['sort_order'] is String ? int.tryParse(json['sort_order']) : json['sort_order']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id != null) 'id': id,
      if (eventId != null) 'event_id': eventId,
      'activity_name': activityName,
      'activity_date': activityDate,
      'start_time': startTime,
      'end_time': endTime,
      'venue': venue,
      'responsible_organizer_id': responsibleOrganizerId,
      'responsible_organizer_name': responsibleOrganizerName,
      'description': description,
      'sort_order': sortOrder,
    };
  }
}

class DashboardStats {
  final int participantCount;
  final int organizerCount;
  final Map<String, dynamic> todayAttendance;
  final List<Meal> todayMeals;
  final Map<String, dynamic> roomStats;
  final List<WorkAssignment> myPendingTasks;
  final ScheduleItem? nextActivity;
  final int spotEntriesToday;
  final EventInfo? eventInfo;

  DashboardStats({
    this.participantCount = 0,
    this.organizerCount = 0,
    this.todayAttendance = const {},
    this.todayMeals = const [],
    this.roomStats = const {},
    this.myPendingTasks = const [],
    this.nextActivity,
    this.spotEntriesToday = 0,
    this.eventInfo,
  });

  factory DashboardStats.fromJson(Map<String, dynamic> json) {
    return DashboardStats(
      participantCount: json['participant_count'] != null ? (json['participant_count'] is String ? int.tryParse(json['participant_count']) ?? 0 : json['participant_count']) : 0,
      organizerCount: json['organizer_count'] != null ? (json['organizer_count'] is String ? int.tryParse(json['organizer_count']) ?? 0 : json['organizer_count']) : 0,
      todayAttendance: json['today_attendance'] ?? {},
      todayMeals: (json['today_meals'] as List?)?.map((e) => Meal.fromJson(e)).toList() ?? [],
      roomStats: json['room_stats'] ?? {},
      myPendingTasks: (json['my_pending_tasks'] as List?)?.map((e) => WorkAssignment.fromJson(e)).toList() ?? [],
      nextActivity: json['next_activity'] != null ? ScheduleItem.fromJson(json['next_activity']) : null,
      spotEntriesToday: json['spot_entries_today'] != null ? (json['spot_entries_today'] is String ? int.tryParse(json['spot_entries_today']) ?? 0 : json['spot_entries_today']) : 0,
      eventInfo: json['event_info'] != null ? EventInfo.fromJson(json['event_info']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'participant_count': participantCount,
      'organizer_count': organizerCount,
      'today_attendance': todayAttendance,
      'today_meals': todayMeals.map((e) => e.toJson()).toList(),
      'room_stats': roomStats,
      'my_pending_tasks': myPendingTasks.map((e) => e.toJson()).toList(),
      'next_activity': nextActivity?.toJson(),
      'spot_entries_today': spotEntriesToday,
      'event_info': eventInfo?.toJson(),
    };
  }
}

class PaginationMeta {
  final int total;
  final int page;
  final int perPage;
  final int totalPages;

  PaginationMeta({
    this.total = 0,
    this.page = 1,
    this.perPage = 50,
    this.totalPages = 1,
  });

  factory PaginationMeta.fromJson(Map<String, dynamic> json) {
    return PaginationMeta(
      total: json['total'] != null ? (json['total'] is String ? int.tryParse(json['total']) ?? 0 : json['total']) : 0,
      page: json['page'] != null ? (json['page'] is String ? int.tryParse(json['page']) ?? 1 : json['page']) : 1,
      perPage: json['per_page'] != null ? (json['per_page'] is String ? int.tryParse(json['per_page']) ?? 50 : json['per_page']) : 50,
      totalPages: json['total_pages'] != null ? (json['total_pages'] is String ? int.tryParse(json['total_pages']) ?? 1 : json['total_pages']) : 1,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'total': total,
      'page': page,
      'per_page': perPage,
      'total_pages': totalPages,
    };
  }
}
