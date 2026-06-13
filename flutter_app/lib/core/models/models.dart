
class Shakha {
  final int id;
  final String name;
  final String? openaiApiKey;
  final int useAiCrosscheck;
  final String? updatedAt;

  Shakha({
    required this.id,
    required this.name,
    this.openaiApiKey,
    required this.useAiCrosscheck,
    this.updatedAt,
  });

  factory Shakha.fromJson(Map<String, dynamic> json) {
    return Shakha(
      id: json['id'] is String ? int.parse(json['id']) : json['id'],
      name: json['name'] ?? '',
      openaiApiKey: json['openai_api_key'],
      useAiCrosscheck: json['use_ai_crosscheck'] is String
          ? int.parse(json['use_ai_crosscheck'])
          : (json['use_ai_crosscheck'] ?? 0),
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'openai_api_key': openaiApiKey,
        'use_ai_crosscheck': useAiCrosscheck,
        'updated_at': updatedAt,
      };
}

class Swayamsevak {
  final int? id;
  final String name;
  final String? address;
  final String? phone;
  final int? age;
  final String? username;
  final int? shakhaId;
  final String category;
  final String? gat;
  final int isGatNayak;
  final int isActive;
  final String? updatedAt;

  Swayamsevak({
    this.id,
    required this.name,
    this.address,
    this.phone,
    this.age,
    this.username,
    this.shakhaId,
    required this.category,
    this.gat,
    required this.isGatNayak,
    required this.isActive,
    this.updatedAt,
  });

  factory Swayamsevak.fromJson(Map<String, dynamic> json) {
    return Swayamsevak(
      id: json['id'] != null ? (json['id'] is String ? int.parse(json['id']) : json['id']) : null,
      name: json['name'] ?? '',
      address: json['address'],
      phone: json['phone'],
      age: json['age'] != null ? (json['age'] is String ? int.parse(json['age']) : json['age']) : null,
      username: json['username'],
      shakhaId: json['shakha_id'] != null ? (json['shakha_id'] is String ? int.parse(json['shakha_id']) : json['shakha_id']) : null,
      category: json['category'] ?? 'Tarun',
      gat: json['gat'],
      isGatNayak: json['is_gat_nayak'] is String ? int.parse(json['is_gat_nayak']) : (json['is_gat_nayak'] ?? 0),
      isActive: json['is_active'] is String ? int.parse(json['is_active']) : (json['is_active'] ?? 1),
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'address': address,
        'phone': phone,
        'age': age,
        'username': username,
        'shakha_id': shakhaId,
        'category': category,
        'gat': gat,
        'is_gat_nayak': isGatNayak,
        'is_active': isActive,
        'updated_at': updatedAt,
      };
}

class DailyRecord {
  final int? id;
  final String recordDate;
  final String? yugabdh;
  final String? vikramSamvat;
  final String? shakaSamvat;
  final String? hindiMonth;
  final String? paksh;
  final String? tithi;
  final String? utsav;
  final String? customMessage;
  final int? shakhaId;
  final int isActive;
  final String? updatedAt;

  DailyRecord({
    this.id,
    required this.recordDate,
    this.yugabdh,
    this.vikramSamvat,
    this.shakaSamvat,
    this.hindiMonth,
    this.paksh,
    this.tithi,
    this.utsav,
    this.customMessage,
    this.shakhaId,
    required this.isActive,
    this.updatedAt,
  });

  factory DailyRecord.fromJson(Map<String, dynamic> json) {
    return DailyRecord(
      id: json['id'] != null ? (json['id'] is String ? int.parse(json['id']) : json['id']) : null,
      recordDate: json['record_date'] ?? '',
      yugabdh: json['yugabdh'],
      vikramSamvat: json['vikram_samvat'],
      shakaSamvat: json['shaka_samvat'],
      hindiMonth: json['hindi_month'],
      paksh: json['paksh'],
      tithi: json['tithi'],
      utsav: json['utsav'],
      customMessage: json['custom_message'],
      shakhaId: json['shakha_id'] != null ? (json['shakha_id'] is String ? int.parse(json['shakha_id']) : json['shakha_id']) : null,
      isActive: json['is_active'] is String ? int.parse(json['is_active']) : (json['is_active'] ?? 1),
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'record_date': recordDate,
        'yugabdh': yugabdh,
        'vikram_samvat': vikramSamvat,
        'shaka_samvat': shakaSamvat,
        'hindi_month': hindiMonth,
        'paksh': paksh,
        'tithi': tithi,
        'utsav': utsav,
        'custom_message': customMessage,
        'shakha_id': shakhaId,
        'is_active': isActive,
        'updated_at': updatedAt,
      };
}

class Attendance {
  final int dailyRecordId;
  final int swayamsevakId;
  final int isPresent;
  final String? updatedAt;

  Attendance({
    required this.dailyRecordId,
    required this.swayamsevakId,
    required this.isPresent,
    this.updatedAt,
  });

  factory Attendance.fromJson(Map<String, dynamic> json) {
    return Attendance(
      dailyRecordId: json['daily_record_id'] is String ? int.parse(json['daily_record_id']) : json['daily_record_id'],
      swayamsevakId: json['swayamsevak_id'] is String ? int.parse(json['swayamsevak_id']) : json['swayamsevak_id'],
      isPresent: json['is_present'] is String ? int.parse(json['is_present']) : (json['is_present'] ?? 0),
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() => {
        'daily_record_id': dailyRecordId,
        'swayamsevak_id': swayamsevakId,
        'is_present': isPresent,
        'updated_at': updatedAt,
      };
}

class Activity {
  final int id;
  final String name;
  final int isActive;
  final int? shakhaId;
  final String? updatedAt;

  Activity({
    required this.id,
    required this.name,
    required this.isActive,
    this.shakhaId,
    this.updatedAt,
  });

  factory Activity.fromJson(Map<String, dynamic> json) {
    return Activity(
      id: json['id'] is String ? int.parse(json['id']) : json['id'],
      name: json['name'] ?? '',
      isActive: json['is_active'] is String ? int.parse(json['is_active']) : (json['is_active'] ?? 1),
      shakhaId: json['shakha_id'] != null ? (json['shakha_id'] is String ? int.parse(json['shakha_id']) : json['shakha_id']) : null,
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'is_active': isActive,
        'shakha_id': shakhaId,
        'updated_at': updatedAt,
      };
}

class DailyActivity {
  final int dailyRecordId;
  final int activityId;
  final int isDone;
  final int? conductedBy;
  final String? updatedAt;

  DailyActivity({
    required this.dailyRecordId,
    required this.activityId,
    required this.isDone,
    this.conductedBy,
    this.updatedAt,
  });

  factory DailyActivity.fromJson(Map<String, dynamic> json) {
    return DailyActivity(
      dailyRecordId: json['daily_record_id'] is String ? int.parse(json['daily_record_id']) : json['daily_record_id'],
      activityId: json['activity_id'] is String ? int.parse(json['activity_id']) : json['activity_id'],
      isDone: json['is_done'] is String ? int.parse(json['is_done']) : (json['is_done'] ?? 0),
      conductedBy: json['conducted_by'] != null
          ? (json['conducted_by'] is String ? int.parse(json['conducted_by']) : json['conducted_by'])
          : null,
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() => {
        'daily_record_id': dailyRecordId,
        'activity_id': activityId,
        'is_done': isDone,
        'conducted_by': conductedBy,
        'updated_at': updatedAt,
      };
}

class TimetableDefault {
  final int shakhaId;
  final int dayOfWeek;
  final String slots;
  final int isActive;
  final String? updatedAt;

  TimetableDefault({
    required this.shakhaId,
    required this.dayOfWeek,
    required this.slots,
    required this.isActive,
    this.updatedAt,
  });

  factory TimetableDefault.fromJson(Map<String, dynamic> json) {
    return TimetableDefault(
      shakhaId: json['shakha_id'] is String ? int.parse(json['shakha_id']) : json['shakha_id'],
      dayOfWeek: json['day_of_week'] is String ? int.parse(json['day_of_week']) : json['day_of_week'],
      slots: json['slots'] ?? '[]',
      isActive: json['is_active'] is String ? int.parse(json['is_active']) : (json['is_active'] ?? 1),
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() => {
        'shakha_id': shakhaId,
        'day_of_week': dayOfWeek,
        'slots': slots,
        'is_active': isActive,
        'updated_at': updatedAt,
      };
}

class TimetableOverride {
  final int shakhaId;
  final String overrideDate;
  final String slots;
  final int isActive;
  final String? updatedAt;

  TimetableOverride({
    required this.shakhaId,
    required this.overrideDate,
    required this.slots,
    required this.isActive,
    this.updatedAt,
  });

  factory TimetableOverride.fromJson(Map<String, dynamic> json) {
    return TimetableOverride(
      shakhaId: json['shakha_id'] is String ? int.parse(json['shakha_id']) : json['shakha_id'],
      overrideDate: json['override_date'] ?? '',
      slots: json['slots'] ?? '[]',
      isActive: json['is_active'] is String ? int.parse(json['is_active']) : (json['is_active'] ?? 1),
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() => {
        'shakha_id': shakhaId,
        'override_date': overrideDate,
        'slots': slots,
        'is_active': isActive,
        'updated_at': updatedAt,
      };
}

class Event {
  final int? id;
  final int? shakhaId;
  final String title;
  final String? description;
  final String eventDate;
  final String eventTime;
  final String? location;
  final String? meetingLink;
  final int? createdBy;
  final int isActive;
  final String? updatedAt;

  Event({
    this.id,
    this.shakhaId,
    required this.title,
    this.description,
    required this.eventDate,
    required this.eventTime,
    this.location,
    this.meetingLink,
    this.createdBy,
    required this.isActive,
    this.updatedAt,
  });

  factory Event.fromJson(Map<String, dynamic> json) {
    return Event(
      id: json['id'] != null ? (json['id'] is String ? int.parse(json['id']) : json['id']) : null,
      shakhaId: json['shakha_id'] != null ? (json['shakha_id'] is String ? int.parse(json['shakha_id']) : json['shakha_id']) : null,
      title: json['title'] ?? '',
      description: json['description'],
      eventDate: json['event_date'] ?? '',
      eventTime: json['event_time'] ?? '',
      location: json['location'],
      meetingLink: json['meeting_link'],
      createdBy: json['created_by'] != null ? (json['created_by'] is String ? int.parse(json['created_by']) : json['created_by']) : null,
      isActive: json['is_active'] is String ? int.parse(json['is_active']) : (json['is_active'] ?? 1),
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'shakha_id': shakhaId,
        'title': title,
        'description': description,
        'event_date': eventDate,
        'event_time': eventTime,
        'location': location,
        'meeting_link': meetingLink,
        'created_by': createdBy,
        'is_active': isActive,
        'updated_at': updatedAt,
      };
}

class Subhashit {
  final int? id;
  final int? shakhaId;
  final String sanskritText;
  final String hindiMeaning;
  final String? shabdarth;
  final String subhashitDate;
  final String? panchangText;
  final int? createdBy;
  final int isActive;
  final String? updatedAt;

  Subhashit({
    this.id,
    this.shakhaId,
    required this.sanskritText,
    required this.hindiMeaning,
    this.shabdarth,
    required this.subhashitDate,
    this.panchangText,
    this.createdBy,
    required this.isActive,
    this.updatedAt,
  });

  factory Subhashit.fromJson(Map<String, dynamic> json) {
    return Subhashit(
      id: json['id'] != null ? (json['id'] is String ? int.parse(json['id']) : json['id']) : null,
      shakhaId: json['shakha_id'] != null ? (json['shakha_id'] is String ? int.parse(json['shakha_id']) : json['shakha_id']) : null,
      sanskritText: json['sanskrit_text'] ?? '',
      hindiMeaning: json['hindi_meaning'] ?? '',
      shabdarth: json['shabdarth'],
      subhashitDate: json['subhashit_date'] ?? '',
      panchangText: json['panchang_text'],
      createdBy: json['created_by'] != null ? (json['created_by'] is String ? int.parse(json['created_by']) : json['created_by']) : null,
      isActive: json['is_active'] is String ? int.parse(json['is_active']) : (json['is_active'] ?? 1),
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'shakha_id': shakhaId,
        'sanskrit_text': sanskritText,
        'hindi_meaning': hindiMeaning,
        'shabdarth': shabdarth,
        'subhashit_date': subhashitDate,
        'panchang_text': panchangText,
        'created_by': createdBy,
        'is_active': isActive,
        'updated_at': updatedAt,
      };
}

class AmritVachan {
  final int? id;
  final int? shakhaId;
  final String content;
  final String? author;
  final String vachanDate;
  final int? createdBy;
  final int isActive;
  final String? updatedAt;

  AmritVachan({
    this.id,
    this.shakhaId,
    required this.content,
    this.author,
    required this.vachanDate,
    this.createdBy,
    required this.isActive,
    this.updatedAt,
  });

  factory AmritVachan.fromJson(Map<String, dynamic> json) {
    return AmritVachan(
      id: json['id'] != null ? (json['id'] is String ? int.parse(json['id']) : json['id']) : null,
      shakhaId: json['shakha_id'] != null ? (json['shakha_id'] is String ? int.parse(json['shakha_id']) : json['shakha_id']) : null,
      content: json['content'] ?? '',
      author: json['author'],
      vachanDate: json['vachan_date'] ?? '',
      createdBy: json['created_by'] != null ? (json['created_by'] is String ? int.parse(json['created_by']) : json['created_by']) : null,
      isActive: json['is_active'] is String ? int.parse(json['is_active']) : (json['is_active'] ?? 1),
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'shakha_id': shakhaId,
        'content': content,
        'author': author,
        'vachan_date': vachanDate,
        'created_by': createdBy,
        'is_active': isActive,
        'updated_at': updatedAt,
      };
}

class Geet {
  final int? id;
  final int? shakhaId;
  final String title;
  final String lyrics;
  final String? meaningOrContext;
  final String geetType;
  final String geetDate;
  final int? createdBy;
  final int isActive;
  final String? updatedAt;

  Geet({
    this.id,
    this.shakhaId,
    required this.title,
    required this.lyrics,
    this.meaningOrContext,
    required this.geetType,
    required this.geetDate,
    this.createdBy,
    required this.isActive,
    this.updatedAt,
  });

  factory Geet.fromJson(Map<String, dynamic> json) {
    return Geet(
      id: json['id'] != null ? (json['id'] is String ? int.parse(json['id']) : json['id']) : null,
      shakhaId: json['shakha_id'] != null ? (json['shakha_id'] is String ? int.parse(json['shakha_id']) : json['shakha_id']) : null,
      title: json['title'] ?? '',
      lyrics: json['lyrics'] ?? '',
      meaningOrContext: json['meaning_or_context'],
      geetType: json['geet_type'] ?? 'Sanghik',
      geetDate: json['geet_date'] ?? '',
      createdBy: json['created_by'] != null ? (json['created_by'] is String ? int.parse(json['created_by']) : json['created_by']) : null,
      isActive: json['is_active'] is String ? int.parse(json['is_active']) : (json['is_active'] ?? 1),
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'shakha_id': shakhaId,
        'title': title,
        'lyrics': lyrics,
        'meaning_or_context': meaningOrContext,
        'geet_type': geetType,
        'geet_date': geetDate,
        'created_by': createdBy,
        'is_active': isActive,
        'updated_at': updatedAt,
      };
}

class Ghoshna {
  final int? id;
  final int? shakhaId;
  final String sloganSanskrit;
  final String sloganHindi;
  final String? context;
  final String ghoshnaDate;
  final int? createdBy;
  final int isActive;
  final String? updatedAt;

  Ghoshna({
    this.id,
    this.shakhaId,
    required this.sloganSanskrit,
    required this.sloganHindi,
    this.context,
    required this.ghoshnaDate,
    this.createdBy,
    required this.isActive,
    this.updatedAt,
  });

  factory Ghoshna.fromJson(Map<String, dynamic> json) {
    return Ghoshna(
      id: json['id'] != null ? (json['id'] is String ? int.parse(json['id']) : json['id']) : null,
      shakhaId: json['shakha_id'] != null ? (json['shakha_id'] is String ? int.parse(json['shakha_id']) : json['shakha_id']) : null,
      sloganSanskrit: json['slogan_sanskrit'] ?? '',
      sloganHindi: json['slogan_hindi'] ?? '',
      context: json['context'],
      ghoshnaDate: json['ghoshna_date'] ?? '',
      createdBy: json['created_by'] != null ? (json['created_by'] is String ? int.parse(json['created_by']) : json['created_by']) : null,
      isActive: json['is_active'] is String ? int.parse(json['is_active']) : (json['is_active'] ?? 1),
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'shakha_id': shakhaId,
        'slogan_sanskrit': sloganSanskrit,
        'slogan_hindi': sloganHindi,
        'context': context,
        'ghoshna_date': ghoshnaDate,
        'created_by': createdBy,
        'is_active': isActive,
        'updated_at': updatedAt,
      };
}

class OfflineAction {
  final String id;
  final String actionType;
  final String endpoint;
  final String payload;
  final String createdAt;

  OfflineAction({
    required this.id,
    required this.actionType,
    required this.endpoint,
    required this.payload,
    required this.createdAt,
  });

  factory OfflineAction.fromJson(Map<String, dynamic> json) {
    return OfflineAction(
      id: json['id'] ?? '',
      actionType: json['action_type'] ?? '',
      endpoint: json['endpoint'] ?? '',
      payload: json['payload'] ?? '',
      createdAt: json['created_at'] ?? '',
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'action_type': actionType,
        'endpoint': endpoint,
        'payload': payload,
        'created_at': createdAt,
      };
}
