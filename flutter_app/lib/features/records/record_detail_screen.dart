import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/api/api_client.dart';
import '../../core/models/models.dart';
import '../../core/providers/providers.dart';

class RecordDetailScreen extends ConsumerStatefulWidget {
  final int recordId;
  final String dateStr;
  final String formattedDate;

  const RecordDetailScreen({
    super.key,
    required this.recordId,
    required this.dateStr,
    required this.formattedDate,
  });

  @override
  ConsumerState<RecordDetailScreen> createState() => _RecordDetailScreenState();
}

class _RecordDetailScreenState extends ConsumerState<RecordDetailScreen> {
  DailyRecord? _record;
  List<Map<String, dynamic>> _attendanceDetails = [];
  List<Map<String, dynamic>> _activityDetails = [];
  bool _isLoading = true;
  bool _isSyncing = false;

  int _presentCount = 0;
  int _absentCount = 0;
  int _activitiesDone = 0;

  @override
  void initState() {
    super.initState();
    _loadRecordDetails();
  }

  Future<void> _loadRecordDetails() async {
    setState(() => _isLoading = true);
    final repo = ref.read(localRepoProvider);

    try {
      // 1. Fetch base daily record
      _record = await repo.getDailyRecordByDate(widget.dateStr);

      // 2. Fetch all swayamsevaks to match names
      final swayamsevaks = await repo.getAllSwayamsevaks();
      final swMap = {for (var s in swayamsevaks) s.id: s};

      // 3. Fetch attendance for this record
      final attendanceList = await repo.getAttendanceForRecord(widget.recordId);
      
      _presentCount = 0;
      _attendanceDetails = attendanceList.map((att) {
        final sw = swMap[att.swayamsevakId];
        final isPresent = att.isPresent == 1;
        if (isPresent) _presentCount++;
        
        return {
          'name': sw?.name ?? 'अज्ञात स्वयंसेवक',
          'gat': sw?.gat ?? 'सामान्य',
          'category': sw?.category ?? 'Tarun',
          'is_present': isPresent,
        };
      }).toList();
      _absentCount = _attendanceDetails.length - _presentCount;

      // Sort alphabetically by swayamsevak name
      _attendanceDetails.sort((a, b) => (a['name'] as String).compareTo(b['name'] as String));

      // 4. Fetch activities to match names
      final activities = await repo.getActiveActivities();
      final actMap = {for (var a in activities) a.id: a};

      // 5. Fetch daily activities done
      final dailyActivitiesList = await repo.getActivitiesForRecord(widget.recordId);
      
      _activitiesDone = 0;
      _activityDetails = dailyActivitiesList.map((da) {
        final act = actMap[da.activityId];
        final cond = swMap[da.conductedBy];
        final isDone = da.isDone == 1;
        if (isDone) _activitiesDone++;

        return {
          'name': act?.name ?? 'अज्ञात गतिविधि',
          'is_done': isDone,
          'conductor': cond?.name,
        };
      }).toList();

    } catch (e) {
      debugPrint('Error loading record details: $e');
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '📄 रिकॉर्ड विवरण (Record Details)',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Container(
        color: const Color(0xFFF9F6F0),
        child: _isLoading
            ? const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)))
            : _record == null
                ? const Center(child: Text('रिकॉर्ड विवरण लोड करने में असमर्थ।'))
                : SingleChildScrollView(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Date Card
                        Card(
                          elevation: 2,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                          color: Colors.white,
                          child: Padding(
                            padding: const EdgeInsets.all(16.0),
                            child: Row(
                              children: [
                                const Icon(Icons.calendar_today, color: Color(0xFFFF6B00), size: 28),
                                const SizedBox(width: 16),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        widget.formattedDate,
                                        style: const TextStyle(
                                          fontSize: 18,
                                          fontWeight: FontWeight.bold,
                                          color: Color(0xFF5D4037),
                                        ),
                                      ),
                                      if (_record!.utsav != null && _record!.utsav!.trim().isNotEmpty) ...[
                                        const SizedBox(height: 6),
                                        Text(
                                          '🌺 उत्सव: ${_record!.utsav!.trim()}',
                                          style: const TextStyle(
                                            fontSize: 15,
                                            fontWeight: FontWeight.bold,
                                            color: Color(0xFFE65100),
                                          ),
                                        ),
                                      ],
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),

                        // Expandable Panchang Details
                        ExpansionTile(
                          title: const Text('🗓️ दैनिक पंचांग विवरण (Panchang)'),
                          leading: const Icon(Icons.settings_suggest, color: Colors.amber),
                          children: [
                            Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                              child: Table(
                                children: [
                                  _buildPanchangRow('तिथि', _record!.tithi ?? '-'),
                                  _buildPanchangRow('पक्ष', _record!.paksh ?? '-'),
                                  _buildPanchangRow('मास', _record!.hindiMonth ?? '-'),
                                  _buildPanchangRow('युगाब्द', _record!.yugabdh ?? '-'),
                                  _buildPanchangRow('विक्रम संवत', _record!.vikramSamvat ?? '-'),
                                  _buildPanchangRow('शालिवाहन शक', _record!.shakaSamvat ?? '-'),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),

                        // Stats Row
                        Row(
                          children: [
                            Expanded(
                              child: _buildStatCard(
                                '$_presentCount',
                                '✅ उपस्थित',
                                const Color(0xFF2E7D32),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: _buildStatCard(
                                '$_absentCount',
                                '❌ अनुपस्थित',
                                const Color(0xFFC62828),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: _buildStatCard(
                                '$_activitiesDone',
                                '🚩 गतिविधि पूर्ण',
                                const Color(0xFFE65100),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 24),

                        // Attendance section
                        const Text(
                          '👥 स्वयंसेवक उपस्थिति',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF5D4037),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Card(
                          elevation: 2,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                          color: Colors.white,
                          child: _attendanceDetails.isEmpty
                              ? const Padding(
                                  padding: EdgeInsets.all(24),
                                  child: Center(
                                    child: Text('कोई उपस्थिति डेटा नहीं है।', style: TextStyle(color: Colors.grey)),
                                  ),
                                )
                              : ListView.separated(
                                  shrinkWrap: true,
                                  physics: const NeverScrollableScrollPhysics(),
                                  itemCount: _attendanceDetails.length,
                                  separatorBuilder: (c, i) => const Divider(height: 1),
                                  itemBuilder: (ctx, index) {
                                    final att = _attendanceDetails[index];
                                    final isPresent = att['is_present'] as bool;
                                    return ListTile(
                                      leading: CircleAvatar(
                                        backgroundColor: isPresent ? const Color(0xFFE8F5E9) : const Color(0xFFFFEBEE),
                                        child: Icon(
                                          isPresent ? Icons.check_circle : Icons.cancel,
                                          color: isPresent ? Colors.green : Colors.red,
                                        ),
                                      ),
                                      title: Text(
                                        att['name'] as String,
                                        style: const TextStyle(fontWeight: FontWeight.bold),
                                      ),
                                      subtitle: Text(
                                        'गट: ${att['gat']} | ${att['category']}',
                                        style: const TextStyle(fontSize: 12, color: Colors.grey),
                                      ),
                                    );
                                  },
                                ),
                        ),
                        const SizedBox(height: 24),

                        // Activities section
                        const Text(
                          '🚩 दैनिक गतिविधियाँ',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF5D4037),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Card(
                          elevation: 2,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                          color: Colors.white,
                          child: _activityDetails.isEmpty
                              ? const Padding(
                                  padding: EdgeInsets.all(24),
                                  child: Center(
                                    child: Text('कोई गतिविधि डेटा नहीं है।', style: TextStyle(color: Colors.grey)),
                                  ),
                                )
                              : Padding(
                                  padding: const EdgeInsets.symmetric(vertical: 8.0),
                                  child: Column(
                                    children: _activityDetails.map((act) {
                                      final isDone = act['is_done'] as bool;
                                      final conductor = act['conductor'] as String?;

                                      return ListTile(
                                        leading: Icon(
                                          isDone ? Icons.check_circle_outline : Icons.radio_button_unchecked,
                                          color: isDone ? Colors.green : Colors.grey,
                                        ),
                                        title: Text(
                                          act['name'] as String,
                                          style: TextStyle(
                                            fontWeight: isDone ? FontWeight.bold : FontWeight.normal,
                                            decoration: isDone ? TextDecoration.none : TextDecoration.lineThrough,
                                            color: isDone ? Colors.black87 : Colors.grey,
                                          ),
                                        ),
                                        subtitle: conductor != null
                                            ? Text('संचालक: $conductor', style: const TextStyle(color: Color(0xFFFF6B00), fontSize: 13, fontWeight: FontWeight.w600))
                                            : null,
                                      );
                                    }).toList(),
                                  ),
                                ),
                        ),
                        const SizedBox(height: 24),

                        // Custom Notes
                        if (_record!.customMessage != null && _record!.customMessage!.trim().isNotEmpty) ...[
                          const Text(
                            '💬 विशेष संदेश / टिप्पणी',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF5D4037),
                            ),
                          ),
                          const SizedBox(height: 8),
                          Card(
                            elevation: 2,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                            color: Colors.white,
                            child: Container(
                              width: double.infinity,
                              padding: const EdgeInsets.all(16),
                              child: Text(
                                _record!.customMessage!,
                                style: const TextStyle(fontSize: 15, height: 1.4, color: Colors.black87),
                              ),
                            ),
                          ),
                          const SizedBox(height: 24),
                        ],
                      ],
                    ),
                  ),
      ),
      bottomNavigationBar: _isLoading || _record == null
          ? null
          : Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 12.0),
              child: (_record!.id != null && _record!.id! > 0)
                  ? ElevatedButton.icon(
                      onPressed: _openSnapshot,
                      icon: const Icon(Icons.camera_alt, color: Colors.white),
                      label: const Text('📸 स्नैपशॉट (Snapshot)', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFFF6B00),
                        minimumSize: const Size(double.infinity, 50),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        elevation: 3,
                      ),
                    )
                  : ElevatedButton.icon(
                      onPressed: _isSyncing ? null : _syncAndReload,
                      icon: _isSyncing
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                            )
                          : const Icon(Icons.sync, color: Colors.white),
                      label: const Text('🔄 सिंक करें (Sync to get Snapshot)', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.grey[600],
                        minimumSize: const Size(double.infinity, 50),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        elevation: 3,
                      ),
                    ),
            ),
    );
  }

  Future<void> _openSnapshot() async {
    final session = ref.read(sessionProvider);
    final token = session.token ?? '';
    final recordId = _record?.id;
    if (recordId == null || recordId <= 0) return;
    
    final url = '${ApiClient.baseUrl}/pages/snapshot.php?id=$recordId&token=$token';
    final uri = Uri.parse(url);
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      } else {
        throw 'Could not launch $url';
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('स्नैपशॉट खोलने में विफल: $e')),
        );
      }
    }
  }

  Future<void> _syncAndReload() async {
    setState(() => _isSyncing = true);
    try {
      final syncEngine = ref.read(syncEngineProvider);
      await syncEngine.sync();
      
      // Reload record details
      await _loadRecordDetails();
      
      if (mounted) {
        if (_record != null && _record!.id! > 0) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('✨ सिंक पूर्ण! स्नैपशॉट अब उपलब्ध है।')),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('⚠️ सिंक पूर्ण, लेकिन रिकॉर्ड मैप नहीं हो सका। कृपया इंटरनेट चेक करें।')),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('सिंक विफल: $e')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isSyncing = false);
      }
    }
  }

  TableRow _buildPanchangRow(String label, String value) {
    return TableRow(
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 6),
          child: Text(label, style: const TextStyle(fontWeight: FontWeight.w500, color: Colors.brown)),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 6),
          child: Text(value, style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF5D4037))),
        ),
      ],
    );
  }

  Widget _buildStatCard(String value, String label, Color color) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(12.0),
        child: Column(
          children: [
            Text(
              value,
              style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: color),
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: const TextStyle(fontSize: 12, color: Colors.grey, fontWeight: FontWeight.bold),
            ),
          ],
        ),
      ),
    );
  }
}
