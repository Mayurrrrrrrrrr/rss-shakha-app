import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:table_calendar/table_calendar.dart';
import '../../core/models/models.dart';
import '../../core/providers/providers.dart';
import 'record_detail_screen.dart';
import 'daily_record_screen.dart';

class RecordsCalendarScreen extends ConsumerStatefulWidget {
  const RecordsCalendarScreen({super.key});

  @override
  ConsumerState<RecordsCalendarScreen> createState() => _RecordsCalendarScreenState();
}

class _RecordsCalendarScreenState extends ConsumerState<RecordsCalendarScreen> {
  DateTime _focusedDay = DateTime.now();
  DateTime? _selectedDay;
  Map<String, Map<String, dynamic>> _recordsMap = {};
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadRecords();
  }

  Future<void> _loadRecords() async {
    setState(() => _isLoading = true);
    try {
      final repo = ref.read(localRepoProvider);
      final records = await repo.getAllDailyRecords();
      final Map<String, Map<String, dynamic>> tempMap = {};
      for (var rec in records) {
        final dateStr = rec['record_date'] as String;
        tempMap[dateStr] = rec;
      }
      setState(() {
        _recordsMap = tempMap;
      });
    } catch (e) {
      debugPrint('Error loading records for calendar: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  String _dateToStr(DateTime date) {
    return '${date.year.toString().padLeft(4, '0')}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
  }

  List<Map<String, dynamic>> _getEventsForDay(DateTime day) {
    final key = _dateToStr(day);
    final record = _recordsMap[key];
    return record != null ? [record] : [];
  }

  void _showAttendanceBottomSheet(DateTime date, Map<String, dynamic> record) async {
    final presentCount = record['present_count'] ?? 0;
    final totalCount = record['total_count'] ?? 0;
    final utsav = record['utsav'] as String?;
    final message = record['custom_message'] as String?;
    final yugabdh = record['yugabdh'] as String?;
    final vikramSamvat = record['vikram_samvat'] as String?;
    final shakaSamvat = record['shaka_samvat'] as String?;
    final hindiMonth = record['hindi_month'] as String?;
    final paksh = record['paksh'] as String?;
    final tithi = record['tithi'] as String?;
    final recordId = record['id'] as int;

    List<String> doneActivitiesNames = [];
    try {
      final repo = ref.read(localRepoProvider);
      final dailyActivities = await repo.getActivitiesForRecord(recordId);
      final allActivities = await repo.getActiveActivities();
      for (var da in dailyActivities) {
        if (da.isDone == 1) {
          final matched = allActivities.firstWhere((a) => a.id == da.activityId, orElse: () => Activity(id: 0, name: '', isActive: 0));
          if (matched.name.isNotEmpty) {
            doneActivitiesNames.add(matched.name);
          }
        }
      }
    } catch (e) {
      debugPrint('Error loading conducted activities for sheet: $e');
    }

    final formattedDate = DateFormat('dd MMMM yyyy').format(date);
    
    if (!mounted) return;

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) {
        return Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.only(
              topLeft: Radius.circular(24),
              topRight: Radius.circular(24),
            ),
          ),
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.grey.shade300,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 20),
              
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        formattedDate,
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          color: Theme.of(context).colorScheme.onSurface,
                        ),
                      ),
                      if (utsav != null && utsav.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Row(
                          children: [
                            const Text('🎉 ', style: TextStyle(fontSize: 14)),
                            Text(
                              utsav,
                              style: const TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFFFF6B00),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: Colors.green.shade50,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: Colors.green.shade200),
                    ),
                    child: const Row(
                      children: [
                        Icon(Icons.check_circle, size: 16, color: Colors.green),
                        SizedBox(width: 6),
                        Text(
                          'शाखा आयोजित',
                          style: TextStyle(
                            color: Colors.green,
                            fontWeight: FontWeight.bold,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const Divider(height: 32),
              
              if ((tithi != null && tithi.isNotEmpty) || (hindiMonth != null && hindiMonth.isNotEmpty)) ...[
                const Text(
                  '🗓️ दैनिक पंचांग विवरण',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  '${hindiMonth ?? ""} ${paksh ?? ""} ${tithi ?? ""}\n'
                  '${yugabdh != null && yugabdh.isNotEmpty ? "युगाब्द: $yugabdh  " : ""}'
                  '${vikramSamvat != null && vikramSamvat.isNotEmpty ? "वि.सं.: $vikramSamvat  " : ""}'
                  '${shakaSamvat != null && shakaSamvat.isNotEmpty ? "शक: $shakaSamvat" : ""}',
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w500,
                    color: Theme.of(context).colorScheme.onSurface,
                    height: 1.4,
                  ),
                ),
                const SizedBox(height: 20),
              ],
              
              const Text(
                '👥 स्वयंसेवक उपस्थिति',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                  color: Colors.grey,
                ),
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: LinearProgressIndicator(
                        value: totalCount > 0 ? presentCount / totalCount : 0.0,
                        backgroundColor: Colors.grey.shade200,
                        color: const Color(0xFFFF6B00),
                        minHeight: 8,
                      ),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Text(
                    '$presentCount / $totalCount उपस्थित',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: Theme.of(context).colorScheme.onSurface,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              
              const Text(
                '🚩 दैनिक गतिविधि कार्यक्रम',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                  color: Colors.grey,
                ),
              ),
              const SizedBox(height: 8),
              if (doneActivitiesNames.isNotEmpty)
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: doneActivitiesNames.map((actName) {
                    return Chip(
                      label: Text(actName, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500)),
                      backgroundColor: const Color(0xFFFFF3E0),
                      side: const BorderSide(color: Color(0xFFFFE0B2)),
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      visualDensity: VisualDensity.compact,
                    );
                  }).toList(),
                )
              else
                Text(
                  'कोई गतिविधि अंकित नहीं है।',
                  style: TextStyle(
                    fontSize: 15,
                    color: Colors.grey.shade600,
                    fontStyle: FontStyle.italic,
                  ),
                ),
                
              if (message != null && message.trim().isNotEmpty) ...[
                const SizedBox(height: 20),
                const Text(
                  '📝 विशेष टिप्पणी / संदेश',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  message,
                  style: TextStyle(
                    fontSize: 15,
                    color: Colors.grey.shade800,
                    fontStyle: FontStyle.italic,
                  ),
                ),
              ],
              
              const SizedBox(height: 32),
              
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        side: const BorderSide(color: Colors.grey),
                      ),
                      onPressed: () => Navigator.pop(ctx),
                      child: const Text(
                        'बंद करें',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.grey),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFFF6B00),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      onPressed: () {
                        Navigator.pop(ctx);
                        final displayMonth = date.month;
                        final yearStr = date.year.toString();
                        final List<String> hindiMonths = [
                          'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून',
                          'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'
                        ];
                        final formatted = '${date.day} ${hindiMonths[displayMonth - 1]} $yearStr';
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (ctx) => RecordDetailScreen(
                              recordId: recordId,
                              dateStr: _dateToStr(date),
                              formattedDate: formatted,
                            ),
                          ),
                        ).then((_) => _loadRecords());
                      },
                      child: const Text(
                        'विवरण देखें',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  void _showCreatePromptDialog(DateTime date) {
    final formattedDate = DateFormat('dd MMMM yyyy').format(date);
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('नया रिकॉर्ड बनाएं?', style: TextStyle(fontWeight: FontWeight.bold)),
        content: Text('$formattedDate के लिए कोई दैनिक रिकॉर्ड नहीं है। क्या आप नया रिकॉर्ड बनाना चाहते हैं?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('रद्द करें', style: TextStyle(color: Colors.grey, fontWeight: FontWeight.bold)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFF6B00),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            onPressed: () {
              Navigator.pop(ctx);
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (ctx) => DailyRecordScreen(initialDate: date),
                ),
              ).then((_) => _loadRecords());
            },
            child: const Text('बनाएं', style: TextStyle(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '📅 रिकॉर्ड कैलेंडर',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)))
          : Container(
              color: const Color(0xFFF9F6F0),
              child: SingleChildScrollView(
                child: Column(
                  children: [
                    Padding(
                      padding: const EdgeInsets.all(12.0),
                      child: Card(
                        elevation: 4,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        color: Colors.white,
                        child: Padding(
                          padding: const EdgeInsets.all(8.0),
                          child: TableCalendar(
                            firstDay: DateTime.utc(2025, 1, 1),
                            lastDay: DateTime.utc(2035, 12, 31),
                            focusedDay: _focusedDay,
                            selectedDayPredicate: (day) => isSameDay(_selectedDay, day),
                            onDaySelected: (selectedDay, focusedDay) {
                              setState(() {
                                _selectedDay = selectedDay;
                                _focusedDay = focusedDay;
                              });
                              final key = _dateToStr(selectedDay);
                              final record = _recordsMap[key];
                              if (record != null) {
                                _showAttendanceBottomSheet(selectedDay, record);
                              } else {
                                _showCreatePromptDialog(selectedDay);
                              }
                            },
                            onPageChanged: (focusedDay) {
                              setState(() {
                                _focusedDay = focusedDay;
                              });
                            },
                            eventLoader: _getEventsForDay,
                            calendarStyle: CalendarStyle(
                              todayDecoration: BoxDecoration(
                                color: const Color(0xFFFF6B00).withValues(alpha: 0.15),
                                shape: BoxShape.circle,
                                border: Border.all(color: const Color(0xFFFF6B00), width: 1.5),
                              ),
                              todayTextStyle: const TextStyle(
                                color: Color(0xFFFF6B00),
                                fontWeight: FontWeight.bold,
                              ),
                              selectedDecoration: const BoxDecoration(
                                color: Color(0xFFFF6B00),
                                shape: BoxShape.circle,
                              ),
                              selectedTextStyle: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.bold,
                              ),
                              markerDecoration: const BoxDecoration(
                                color: Colors.green,
                                shape: BoxShape.circle,
                              ),
                              defaultTextStyle: const TextStyle(color: Theme.of(context).colorScheme.onSurface),
                              weekendTextStyle: TextStyle(color: Colors.red.shade700),
                            ),
                            headerStyle: const HeaderStyle(
                              formatButtonVisible: false,
                              titleCentered: true,
                              titleTextStyle: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Theme.of(context).colorScheme.onSurface,
                              ),
                              leftChevronIcon: Icon(Icons.chevron_left, color: Color(0xFFFF6B00)),
                              rightChevronIcon: Icon(Icons.chevron_right, color: Color(0xFFFF6B00)),
                            ),
                            calendarBuilders: CalendarBuilders(
                              markerBuilder: (context, date, events) {
                                if (events.isNotEmpty) {
                                  final record = events.first as Map<String, dynamic>;
                                  final presentCount = record['present_count'] ?? 0;
                                  final totalCount = record['total_count'] ?? 0;
                                  final ratio = totalCount > 0 ? presentCount / totalCount : 0.0;
                                  
                                  Color markerColor = Colors.green;
                                  if (ratio < 0.5) {
                                    markerColor = Colors.red.shade700;
                                  } else if (ratio < 0.8) {
                                    markerColor = Colors.orange;
                                  }
                                  
                                  return Positioned(
                                    bottom: 3,
                                    child: Container(
                                      width: 6,
                                      height: 6,
                                      decoration: BoxDecoration(
                                        color: markerColor,
                                        shape: BoxShape.circle,
                                      ),
                                    ),
                                  );
                                }
                                return null;
                              },
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16.0),
                      child: Card(
                        elevation: 1,
                        color: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        child: Padding(
                          padding: const EdgeInsets.symmetric(vertical: 12.0, horizontal: 16.0),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'रंग संकेत (Attendance Legend):',
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 14,
                                  color: Theme.of(context).colorScheme.onSurface,
                                ),
                              ),
                              const SizedBox(height: 12),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  _buildLegendItem(Colors.green, 'उत्कृष्ट (>=80%)'),
                                  _buildLegendItem(Colors.orange, 'मध्यम (50%-79%)'),
                                  _buildLegendItem(Colors.red.shade700, 'न्यून (<50%)'),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 32),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildLegendItem(Color color, String label) {
    return Row(
      children: [
        Container(
          width: 12,
          height: 12,
          decoration: BoxDecoration(
            color: color,
            shape: BoxShape.circle,
          ),
        ),
        const SizedBox(width: 6),
        Text(
          label,
          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.black87),
        ),
      ],
    );
  }
}
