import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/providers/providers.dart';
import 'record_detail_screen.dart';
import 'daily_record_screen.dart';

class RecordsCalendarScreen extends ConsumerStatefulWidget {
  const RecordsCalendarScreen({super.key});

  @override
  ConsumerState<RecordsCalendarScreen> createState() => _RecordsCalendarScreenState();
}

class _RecordsCalendarScreenState extends ConsumerState<RecordsCalendarScreen> {
  DateTime _currentMonth = DateTime.now();
  Map<String, Map<String, dynamic>> _recordsMap = {};
  bool _isLoading = true;

  final List<String> _hindiMonths = [
    'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून',
    'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'
  ];

  final List<String> _weekDays = ['रवि', 'सोम', 'मंगल', 'बुध', 'गुरु', 'शुक्र', 'शनि'];

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

  void _nextMonth() {
    setState(() {
      _currentMonth = DateTime(_currentMonth.year, _currentMonth.month + 1, 1);
    });
  }

  void _prevMonth() {
    setState(() {
      _currentMonth = DateTime(_currentMonth.year, _currentMonth.month - 1, 1);
    });
  }

  @override
  Widget build(BuildContext context) {
    // Determine days of current month
    final firstDayOfMonth = DateTime(_currentMonth.year, _currentMonth.month, 1);
    final lastDayOfMonth = DateTime(_currentMonth.year, _currentMonth.month + 1, 0);
    final daysInMonth = lastDayOfMonth.day;
    final startWeekday = firstDayOfMonth.weekday % 7; // Sunday is 0, Monday is 1, etc.

    // Hindi representation of year/month
    final monthName = _hindiMonths[_currentMonth.month - 1];
    final yearStr = _currentMonth.year.toString();

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '📅 रिकॉर्ड कैलेंडर',
          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)))
          : Container(
              color: const Color(0xFFF9F6F0),
              child: Column(
                children: [
                  // Month navigation header
                  Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        IconButton(
                          icon: const Icon(Icons.chevron_left, size: 32, color: Color(0xFF5D4037)),
                          onPressed: _prevMonth,
                        ),
                        Text(
                          '$monthName $yearStr',
                          style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
                        ),
                        IconButton(
                          icon: const Icon(Icons.chevron_right, size: 32, color: Color(0xFF5D4037)),
                          onPressed: _nextMonth,
                        ),
                      ],
                    ),
                  ),
                  
                  // Calendar table
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 12.0),
                    child: Card(
                      elevation: 4,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      color: Colors.white,
                      child: Padding(
                        padding: const EdgeInsets.all(12.0),
                        child: Column(
                          children: [
                            // Weekdays
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceAround,
                              children: _weekDays.map((day) {
                                final isWeekend = day == 'रवि';
                                return Expanded(
                                  child: Center(
                                    child: Padding(
                                      padding: const EdgeInsets.symmetric(vertical: 8.0),
                                      child: Text(
                                        day,
                                        style: TextStyle(
                                          fontWeight: FontWeight.bold,
                                          color: isWeekend ? Colors.red.shade700 : const Color(0xFF5D4037),
                                        ),
                                      ),
                                    ),
                                  ),
                                );
                              }).toList(),
                            ),
                            const Divider(),
                            
                            // Days grid
                            GridView.builder(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: 7,
                                mainAxisSpacing: 8,
                                crossAxisSpacing: 8,
                              ),
                              itemCount: daysInMonth + startWeekday,
                              itemBuilder: (context, index) {
                                if (index < startWeekday) {
                                  return const SizedBox(); // Empty padding days
                                }
                                
                                final day = index - startWeekday + 1;
                                final dateStr = DateFormat('yyyy-MM-dd').format(DateTime(_currentMonth.year, _currentMonth.month, day));
                                final hasRecord = _recordsMap.containsKey(dateStr);
                                final recordData = _recordsMap[dateStr];
                                
                                return InkWell(
                                  onTap: () {
                                    if (hasRecord) {
                                      final recordId = recordData!['id'] as int;
                                      final present = recordData['present_count'] ?? 0;
                                      final total = recordData['total_count'] ?? 0;
                                      final List<String> hindiMonths = [
                                        'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून',
                                        'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'
                                      ];
                                      final formatted = '$day ${hindiMonths[_currentMonth.month - 1]} $yearStr';
                                      
                                      Navigator.push(
                                        context,
                                        MaterialPageRoute(
                                          builder: (ctx) => RecordDetailScreen(
                                            recordId: recordId,
                                            dateStr: dateStr,
                                            formattedDate: formatted,
                                          ),
                                        ),
                                      ).then((_) => _loadRecords());
                                    }
                                  },
                                  borderRadius: BorderRadius.circular(12),
                                  child: Container(
                                    decoration: BoxDecoration(
                                      color: hasRecord ? const Color(0xFFE8F5E9) : Colors.transparent,
                                      border: Border.all(
                                        color: hasRecord ? Colors.green.shade400 : Colors.grey.shade200,
                                        width: 1.5,
                                      ),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Stack(
                                      alignment: Alignment.center,
                                      children: [
                                        Column(
                                          mainAxisAlignment: MainAxisAlignment.center,
                                          children: [
                                            Text(
                                              day.toString(),
                                              style: TextStyle(
                                                fontWeight: FontWeight.bold,
                                                fontSize: 16,
                                                color: hasRecord ? Colors.green.shade900 : const Color(0xFF5D4037),
                                              ),
                                            ),
                                            if (hasRecord) ...[
                                              const SizedBox(height: 2),
                                              Text(
                                                '${recordData!['present_count']}/${recordData['total_count']}',
                                                style: TextStyle(
                                                  fontSize: 9,
                                                  fontWeight: FontWeight.bold,
                                                  color: Colors.green.shade700,
                                                ),
                                              ),
                                            ]
                                          ],
                                        ),
                                        if (hasRecord)
                                          Positioned(
                                            top: 4,
                                            right: 4,
                                            child: Icon(Icons.check_circle, size: 10, color: Colors.green.shade700),
                                          )
                                      ],
                                    ),
                                  ),
                                );
                              },
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),
                  
                  // Color legend
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 24.0),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Container(
                          width: 16,
                          height: 16,
                          decoration: BoxDecoration(
                            color: const Color(0xFFE8F5E9),
                            border: Border.all(color: Colors.green.shade400),
                            borderRadius: BorderRadius.circular(4),
                          ),
                        ),
                        const SizedBox(width: 8),
                        const Text('रिकॉर्ड उपस्थित (Record Saved)', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF5D4037))),
                        const SizedBox(width: 24),
                        Container(
                          width: 16,
                          height: 16,
                          decoration: BoxDecoration(
                            color: Colors.transparent,
                            border: Border.all(color: Colors.grey.shade300),
                            borderRadius: BorderRadius.circular(4),
                          ),
                        ),
                        const SizedBox(width: 8),
                        const Text('कोई रिकॉर्ड नहीं', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.grey)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}
