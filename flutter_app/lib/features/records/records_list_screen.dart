import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/providers.dart';
import 'record_detail_screen.dart';

class RecordsListScreen extends ConsumerStatefulWidget {
  const RecordsListScreen({super.key});

  @override
  ConsumerState<RecordsListScreen> createState() => _RecordsListScreenState();
}

class _RecordsListScreenState extends ConsumerState<RecordsListScreen> {
  List<Map<String, dynamic>> _records = [];
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
      _records = await repo.getAllDailyRecords();
    } catch (e) {
      debugPrint('Error loading records: $e');
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  String _formatHindiDate(String dateStr) {
    try {
      final date = DateTime.parse(dateStr);
      final List<String> hindiMonths = [
        'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून',
        'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'
      ];
      final List<String> hindiDays = [
        'रविवार', 'सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार'
      ];
      final dayName = hindiDays[date.weekday % 7];
      final monthName = hindiMonths[date.month - 1];
      return '$dayName, ${date.day} $monthName ${date.year}';
    } catch (_) {
      return dateStr;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '📄 रिकॉर्ड इतिहास (History)',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Container(
        color: const Color(0xFFF9F6F0),
        child: _isLoading
            ? const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)))
            : _records.isEmpty
                ? const Center(
                    child: Text(
                      'कोई उपस्थिति रिकॉर्ड नहीं मिला।',
                      style: TextStyle(fontSize: 16, color: Colors.grey),
                    ),
                  )
                : RefreshIndicator(
                    onRefresh: _loadRecords,
                    color: const Color(0xFFFF6B00),
                    child: ListView.builder(
                      itemCount: _records.length,
                      padding: const EdgeInsets.all(16),
                      itemBuilder: (ctx, index) {
                        final rec = _records[index];
                        final recordDate = rec['record_date'] as String;
                        final formattedDate = _formatHindiDate(recordDate);
                        
                        final presentCount = rec['present_count'] ?? 0;
                        final totalCount = rec['total_count'] ?? 0;
                        
                        final activitiesDone = rec['activities_done'] ?? 0;
                        final activitiesTotal = rec['activities_total'] ?? 0;
                        
                        final customMessage = rec['custom_message'] as String?;
                        final utsav = rec['utsav'] as String?;

                        return Card(
                          elevation: 3,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                          margin: const EdgeInsets.only(bottom: 16),
                          color: Colors.white,
                          child: InkWell(
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (ctx) => RecordDetailScreen(
                                    recordId: rec['id'] as int,
                                    dateStr: recordDate,
                                    formattedDate: formattedDate,
                                  ),
                                ),
                              ).then((_) => _loadRecords());
                            },
                            borderRadius: BorderRadius.circular(16),
                            child: Padding(
                              padding: const EdgeInsets.all(16.0),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Expanded(
                                        child: Text(
                                          formattedDate,
                                          style: const TextStyle(
                                            fontSize: 16,
                                            fontWeight: FontWeight.bold,
                                            color: Theme.of(context).colorScheme.onSurface,
                                          ),
                                        ),
                                      ),
                                      const Icon(Icons.chevron_right, color: Colors.grey),
                                    ],
                                  ),
                                  if (utsav != null && utsav.trim().isNotEmpty) ...[
                                    const SizedBox(height: 6),
                                    Text(
                                      '🌺 उत्सव: ${utsav.trim()}',
                                      style: const TextStyle(
                                        fontSize: 13,
                                        fontWeight: FontWeight.bold,
                                        color: Color(0xFFE65100),
                                      ),
                                    ),
                                  ],
                                  const Divider(height: 20),
                                  Row(
                                    children: [
                                      // Attendance badge
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFE8F5E9),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Text(
                                          '👥 उपस्थिति: $presentCount / $totalCount',
                                          style: const TextStyle(
                                            fontSize: 13,
                                            color: Color(0xFF2E7D32),
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ),
                                      const SizedBox(width: 12),
                                      // Activities badge
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFFFF3E0),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Text(
                                          '🚩 गतिविधियाँ: $activitiesDone / $activitiesTotal',
                                          style: const TextStyle(
                                            fontSize: 13,
                                            color: Color(0xFFE65100),
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ),
                                      const Spacer(),
                                      if (customMessage != null && customMessage.trim().isNotEmpty)
                                        const Tooltip(
                                          message: 'विशेष संदेश उपलब्ध है',
                                          child: Icon(Icons.chat_bubble_outline, size: 18, color: Colors.blueGrey),
                                        ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ),
      ),
    );
  }
}
