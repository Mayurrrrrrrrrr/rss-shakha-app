import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:share_plus/share_plus.dart';
import '../../core/providers/providers.dart';
import '../../core/models/models.dart';

class MonthlyReportScreen extends ConsumerStatefulWidget {
  const MonthlyReportScreen({super.key});

  @override
  ConsumerState<MonthlyReportScreen> createState() => _MonthlyReportScreenState();
}

class _MonthlyReportScreenState extends ConsumerState<MonthlyReportScreen> {
  int _selectedMonth = DateTime.now().month;
  int _selectedYear = DateTime.now().year;
  bool _isLoading = false;

  int _totalDays = 0;
  double _avgAttendance = 0.0;
  int _maxAttendance = 0;
  String _maxAttendanceDate = '-';

  // Category counts
  int _baalCount = 0;
  int _tarunCount = 0;
  int _praudhCount = 0;
  int _abhyagatCount = 0;

  // Activities completed
  Map<String, int> _activityCounts = {};
  int _totalSwayamsevaksInShakha = 0;

  final List<String> _hindiMonths = [
    'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून',
    'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'
  ];

  @override
  void initState() {
    super.initState();
    _generateReport();
  }

  Future<void> _generateReport() async {
    setState(() => _isLoading = true);
    final repo = ref.read(localRepoProvider);

    try {
      _totalSwayamsevaksInShakha = await repo.getTotalSwayamsevaks();
      
      final records = await repo.getAllDailyRecords();
      final monthStr = '${_selectedYear}-${_selectedMonth.toString().padLeft(2, '0')}';
      
      final monthRecords = records.where((r) => (r['record_date'] as String).startsWith(monthStr)).toList();
      
      _totalDays = monthRecords.length;
      if (_totalDays == 0) {
        _avgAttendance = 0.0;
        _maxAttendance = 0;
        _maxAttendanceDate = '-';
        _baalCount = 0;
        _tarunCount = 0;
        _praudhCount = 0;
        _abhyagatCount = 0;
        _activityCounts.clear();
        setState(() => _isLoading = false);
        return;
      }

      int totalAttendanceSum = 0;
      int maxAtt = 0;
      String maxAttDate = '-';
      int bSum = 0, tSum = 0, pSum = 0, aSum = 0;
      final Map<String, int> actCounts = {};

      for (var r in monthRecords) {
        final recordId = r['id'] as int;
        final dateStr = r['record_date'] as String;
        final presentCount = r['present_count'] as int? ?? 0;
        totalAttendanceSum += presentCount;

        if (presentCount > maxAtt) {
          maxAtt = presentCount;
          maxAttDate = dateStr;
        }

        // Get detailed attendance breakdown
        final attendance = await repo.getAttendanceForRecord(recordId);
        final swList = await repo.getAllSwayamsevaks();
        final swMap = {for (var s in swList) s.id: s};

        for (var att in attendance) {
          if (att.isPresent == 1) {
            final sw = swMap[att.swayamsevakId];
            if (sw != null) {
              if (sw.category == 'Baal') bSum++;
              else if (sw.category == 'Tarun') tSum++;
              else if (sw.category == 'Praudh') pSum++;
              else if (sw.category == 'Abhyagat') aSum++;
            }
          }
        }

        // Get activities completed
        final dailyActs = await repo.getActivitiesForRecord(recordId);
        final acts = await repo.getActiveActivities();
        final actMap = {for (var a in acts) a.id: a.name};

        for (var da in dailyActs) {
          if (da.isDone == 1) {
            final actName = actMap[da.activityId] ?? 'अन्य';
            actCounts[actName] = (actCounts[actName] ?? 0) + 1;
          }
        }
      }

      setState(() {
        _avgAttendance = totalAttendanceSum / _totalDays;
        _maxAttendance = maxAtt;
        _maxAttendanceDate = maxAttDate;
        _baalCount = bSum;
        _tarunCount = tSum;
        _praudhCount = pSum;
        _abhyagatCount = aSum;
        _activityCounts = actCounts;
      });

    } catch (e) {
      debugPrint('Error generating monthly report: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  void _shareReport() {
    final monthName = _hindiMonths[_selectedMonth - 1];
    final reportText = '''
🚩 *शाखा मासिक रिपोर्ट (${monthName} ${_selectedYear})* 🚩

*कुल संघस्थान दिन:* ${_totalDays} दिन
*औसत उपस्थिति:* ${_avgAttendance.toStringAsFixed(1)} स्वयंसेवक
*अधिकतम उपस्थिति:* ${_maxAttendance} स्वयंसेवक (${_maxAttendanceDate})

*श्रेणीवार कुल उपस्थिति (समेकित):*
- बाल (Baal): ${_baalCount}
- तरुण (Tarun): ${_tarunCount}
- प्रौढ़ (Praudh): ${_praudhCount}
- अभ्यागत (Guest): ${_abhyagatCount}

*प्रमुख गतिविधियाँ (संचालन संख्या):*
${_activityCounts.entries.map((e) => '- ${e.key}: ${e.value} बार').join('\n')}

_यह रिपोर्ट राष्ट्रीय स्वयंसेवक संघ शाखा ऐप से भेजी गई है।_
''';
    Share.share(reportText);
  }

  @override
  Widget build(BuildContext context) {
    final monthName = _hindiMonths[_selectedMonth - 1];

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '📊 मासिक रिपोर्ट',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: const Icon(Icons.share, color: Colors.white),
            onPressed: _isLoading || _totalDays == 0 ? null : _shareReport,
          ),
        ],
      ),
      body: Container(
        color: const Color(0xFFF9F6F0),
        child: Column(
          children: [
            // Selectors Bar
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: Row(
                children: [
                  Expanded(
                    child: DropdownButtonFormField<int>(
                      value: _selectedMonth,
                      decoration: InputDecoration(
                        labelText: 'महीना',
                        filled: true,
                        fillColor: Colors.white,
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      items: List.generate(12, (index) {
                        return DropdownMenuItem(
                          value: index + 1,
                          child: Text(_hindiMonths[index]),
                        );
                      }),
                      onChanged: (val) {
                        if (val != null) {
                          setState(() => _selectedMonth = val);
                          _generateReport();
                        }
                      },
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: DropdownButtonFormField<int>(
                      value: _selectedYear,
                      decoration: InputDecoration(
                        labelText: 'वर्ष',
                        filled: true,
                        fillColor: Colors.white,
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      items: List.generate(5, (index) {
                        final yr = DateTime.now().year - 2 + index;
                        return DropdownMenuItem(
                          value: yr,
                          child: Text(yr.toString()),
                        );
                      }),
                      onChanged: (val) {
                        if (val != null) {
                          setState(() => _selectedYear = val);
                          _generateReport();
                        }
                      },
                    ),
                  ),
                ],
              ),
            ),

            // Content
            Expanded(
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)))
                  : _totalDays == 0
                      ? const Center(
                          child: Text(
                            'इस महीने में कोई रिकॉर्ड सुरक्षित नहीं है।',
                            style: TextStyle(fontSize: 16, color: Colors.grey),
                          ),
                        )
                      : SingleChildScrollView(
                          padding: const EdgeInsets.symmetric(horizontal: 16.0),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Summary Widgets
                              GridView.count(
                                shrinkWrap: true,
                                physics: const NeverScrollableScrollPhysics(),
                                crossAxisCount: 2,
                                crossAxisSpacing: 12,
                                mainAxisSpacing: 12,
                                childAspectRatio: 1.5,
                                children: [
                                  _buildStatCard(
                                    title: 'कुल शाखा दिन',
                                    value: '$_totalDays दिन',
                                    icon: Icons.calendar_today,
                                    color: Colors.orange.shade800,
                                  ),
                                  _buildStatCard(
                                    title: 'औसत उपस्थिति',
                                    value: '${_avgAttendance.toStringAsFixed(1)}',
                                    icon: Icons.people,
                                    color: Colors.green.shade800,
                                  ),
                                  _buildStatCard(
                                    title: 'अधिकतम उपस्थिति',
                                    value: '$_maxAttendance',
                                    icon: Icons.trending_up,
                                    color: Colors.purple.shade800,
                                  ),
                                  _buildStatCard(
                                    title: 'कुल स्वयंसेवक',
                                    value: '$_totalSwayamsevaksInShakha',
                                    icon: Icons.home,
                                    color: Colors.blue.shade800,
                                  ),
                                ],
                              ),
                              const SizedBox(height: 24),

                              // Category Breakdown
                              const Text(
                                '👥 श्रेणीवार उपस्थिति (कुल समेकित)',
                                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.onSurface),
                              ),
                              const SizedBox(height: 12),
                              Card(
                                elevation: 2,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                                color: Colors.white,
                                child: Padding(
                                  padding: const EdgeInsets.all(16.0),
                                  child: Column(
                                    children: [
                                      _buildCategoryProgress('बाल (Baal)', _baalCount, Colors.amber),
                                      const SizedBox(height: 12),
                                      _buildCategoryProgress('तरुण (Tarun)', _tarunCount, Colors.green),
                                      const SizedBox(height: 12),
                                      _buildCategoryProgress('प्रौढ़ (Praudh)', _praudhCount, Colors.orange),
                                      const SizedBox(height: 12),
                                      _buildCategoryProgress('अभ्यागत (Abhyagat)', _abhyagatCount, Colors.blue),
                                    ],
                                  ),
                                ),
                              ),
                              const SizedBox(height: 24),

                              // Activities Done
                              const Text(
                                '🚩 गतिविधियों का संचालन',
                                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.onSurface),
                              ),
                              const SizedBox(height: 12),
                              Card(
                                elevation: 2,
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                                color: Colors.white,
                                child: Padding(
                                  padding: const EdgeInsets.all(16.0),
                                  child: _activityCounts.isEmpty
                                      ? const Center(child: Text('कोई गतिविधि संचालन नहीं हुआ है।', style: TextStyle(color: Colors.grey)))
                                      : Column(
                                          children: _activityCounts.entries.map((entry) {
                                            return ListTile(
                                              leading: const Icon(Icons.check_circle_outline, color: Colors.green),
                                              title: Text(entry.key, style: const TextStyle(fontWeight: FontWeight.bold)),
                                              trailing: Container(
                                                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                                decoration: BoxDecoration(
                                                  color: const Color(0xFFFFF3E0),
                                                  borderRadius: BorderRadius.circular(20),
                                                ),
                                                child: Text(
                                                  '${entry.value} बार',
                                                  style: const TextStyle(color: Colors.orange, fontWeight: FontWeight.bold),
                                                ),
                                              ),
                                            );
                                          }).toList(),
                                        ),
                                ),
                              ),
                              const SizedBox(height: 32),
                            ],
                          ),
                        ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatCard({required String title, required String value, required IconData icon, required Color color}) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(12.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: color, size: 28),
            const SizedBox(height: 8),
            Text(value, style: TextStyle(fontSize: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.onSurface), fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.onSurface)),
            const SizedBox(height: 2),
            Text(title, style: const TextStyle(fontSize: 11, color: Colors.grey, fontWeight: FontWeight.bold)),
          ],
        ),
      ),
    );
  }

  Widget _buildCategoryProgress(String category, int count, Color color) {
    final total = _baalCount + _tarunCount + _praudhCount + _abhyagatCount;
    final ratio = total == 0 ? 0.0 : count / total;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(category, style: TextStyle(fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.onSurface)),
            Text('$count स्वयंसेवक', style: const TextStyle(fontWeight: FontWeight.bold)),
          ],
        ),
        const SizedBox(height: 6),
        LinearProgressIndicator(
          value: ratio,
          backgroundColor: Colors.grey.shade100,
          color: color,
          minHeight: 8,
          borderRadius: BorderRadius.circular(4),
        ),
      ],
    );
  }
}
