import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:share_plus/share_plus.dart';
import '../../core/models/models.dart';
import '../../core/providers/providers.dart';
import '../../core/db/database_helper.dart';

enum PlaceholderType {
  snapshot,
  paperShakha,
  flipbook,
  greetings,
  settings,
}

class NativePlaceholderScreen extends ConsumerStatefulWidget {
  final PlaceholderType type;
  final String title;
  final int? recordId;
  final String? dateStr;
  final String? formattedDate;

  const NativePlaceholderScreen({
    super.key,
    required this.type,
    required this.title,
    this.recordId,
    this.dateStr,
    this.formattedDate,
  });

  @override
  ConsumerState<NativePlaceholderScreen> createState() => _NativePlaceholderScreenState();
}

class _NativePlaceholderScreenState extends ConsumerState<NativePlaceholderScreen> {
  // Common states
  bool _isLoading = false;

  // Snapshot Specific State
  DailyRecord? _record;
  int _presentCount = 0;
  int _absentCount = 0;
  int _totalCount = 0;
  int _activitiesCount = 0;
  String _utsav = '';
  String _customMessage = '';
  String _shakhaName = 'संघस्थान';
  int _baalCount = 0;
  int _tarunCount = 0;
  int _praudhCount = 0;
  int _abhyagatCount = 0;
  List<Map<String, String>> _conductedActivities = [];

  // Greetings Specific State
  final TextEditingController _greetingMsgController = TextEditingController(
    text: 'नव वर्ष की हार्दिक शुभकामनाएं! भगवान राम की कृपा आप और आपके परिवार पर बनी रहे।',
  );
  int _selectedColorIndex = 0;
  final List<List<Color>> _cardGradients = [
    [const Color(0xFFFF6B00), const Color(0xFFFF9E00)], // Saffron
    [const Color(0xFF8D0B0B), const Color(0xFFC2185B)], // Maroon
    [const Color(0xFF005C53), const Color(0xFF042940)], // Teal Dark
    [const Color(0xFFFFB300), const Color(0xFFFF8F00)], // Amber Gold
  ];

  // Settings Specific State
  bool _darkMode = false;
  bool _soundEffects = true;
  bool _notifications = true;
  bool _autoSync = true;
  double _syncInterval = 6.0;

  @override
  void initState() {
    super.initState();
    if (widget.type == PlaceholderType.snapshot) {
      _loadSnapshotData();
    }
  }

  @override
  void dispose() {
    _greetingMsgController.dispose();
    super.dispose();
  }

  Future<void> _loadSnapshotData() async {
    if (widget.recordId == null || widget.dateStr == null) return;
    setState(() => _isLoading = true);

    try {
      final repo = ref.read(localRepoProvider);
      
      // Load record
      _record = await repo.getDailyRecordByDate(widget.dateStr!);
      if (_record != null) {
        _utsav = _record!.utsav ?? '';
        _customMessage = _record!.customMessage ?? '';

        // Load Shakha details
        if (_record!.shakhaId != null) {
          final shakha = await repo.getShakhaById(_record!.shakhaId!);
          if (shakha != null) {
            _shakhaName = shakha['name'] ?? 'संघस्थान';
          }
        } else {
          // Fallback: load first shakha
          final db = await DatabaseHelper.instance.database;
          final List<Map<String, dynamic>> shakhas = await db.query('shakhas', limit: 1);
          if (shakhas.isNotEmpty) {
            _shakhaName = shakhas.first['name'] ?? 'संघस्थान';
          }
        }

        // Fetch swayamsevaks and activities to map names and categories
        final db = await DatabaseHelper.instance.database;
        final List<Map<String, dynamic>> swayamsevakRows = await db.query('swayamsevaks');
        final Map<int, String> swayamsevakCategories = {};
        final Map<int, String> swayamsevakNames = {};
        for (var row in swayamsevakRows) {
          final id = row['id'] as int?;
          if (id != null) {
            swayamsevakCategories[id] = row['category'] ?? 'Tarun';
            swayamsevakNames[id] = row['name'] ?? '';
          }
        }

        // Load attendance stats
        final attendanceList = await repo.getAttendanceForRecord(widget.recordId!);
        _totalCount = attendanceList.length;
        _presentCount = attendanceList.where((a) => a.isPresent == 1).length;
        _absentCount = _totalCount - _presentCount;

        // Reset category counts
        _baalCount = 0;
        _tarunCount = 0;
        _praudhCount = 0;
        _abhyagatCount = 0;

        for (var att in attendanceList) {
          if (att.isPresent == 1) {
            final cat = swayamsevakCategories[att.swayamsevakId] ?? 'Tarun';
            if (cat == 'Baal') {
              _baalCount++;
            } else if (cat == 'Tarun') {
              _tarunCount++;
            } else if (cat == 'Praudh') {
              _praudhCount++;
            } else if (cat == 'Abhyagat') {
              _abhyagatCount++;
            } else {
              _tarunCount++;
            }
          }
        }

        // Load activities done
        final dailyActivities = await repo.getActivitiesForRecord(widget.recordId!);
        _activitiesCount = dailyActivities.where((a) => a.isDone == 1).length;

        // Load activity names
        final List<Map<String, dynamic>> activityRows = await db.query('activities');
        final Map<int, String> activityNames = {};
        for (var row in activityRows) {
          final id = row['id'] as int?;
          if (id != null) {
            activityNames[id] = row['name'] ?? '';
          }
        }

        // Build conducted activities list
        _conductedActivities = [];
        for (var da in dailyActivities) {
          if (da.isDone == 1) {
            final actName = activityNames[da.activityId] ?? '';
            final conductorName = da.conductedBy != null ? (swayamsevakNames[da.conductedBy] ?? '') : '';
            if (actName.isNotEmpty) {
              _conductedActivities.add({
                'name': actName,
                'conductor': conductorName,
              });
            }
          }
        }
      }
    } catch (e) {
      debugPrint('Error loading snapshot data: $e');
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  // Action methods
  void _shareSnapshot() {
    final date = widget.formattedDate ?? widget.dateStr ?? 'अज्ञात तिथि';
    
    // Construct Panchang string
    final List<String> tithiParts = [];
    if (_record?.hindiMonth != null && _record!.hindiMonth!.isNotEmpty) {
      tithiParts.add(_record!.hindiMonth!);
    }
    if (_record?.paksh != null && _record!.paksh!.isNotEmpty) {
      tithiParts.add(_record!.paksh!);
    }
    if (_record?.tithi != null && _record!.tithi!.isNotEmpty) {
      tithiParts.add(_record!.tithi!);
    }
    final tithiStr = tithiParts.join(' ');
    
    // Build activities list text
    String activitiesText = '';
    if (_conductedActivities.isNotEmpty) {
      activitiesText = '\n📋 *गतिविधियाँ:*\n' + _conductedActivities.map((act) {
        final condStr = (act['conductor'] != null && act['conductor']!.isNotEmpty) ? ' (संचालक: ${act['conductor']})' : '';
        return '  ✅ ${act['name']}$condStr';
      }).join('\n') + '\n';
    }

    final shareText = '''
🚩 *राष्ट्रीय स्वयंसेवक संघ - $_shakhaName* 🚩
━━━━━━━━━━━━━━━━━━━━━━
📅 *दिनांक/तिथि:* $date
${tithiStr.isNotEmpty ? '🕉️ *पंचांग:* $tithiStr\n' : ''}${_record?.yugabdh != null && _record!.yugabdh!.isNotEmpty ? '🔱 *युगाब्द:* ${_record!.yugabdh!}\n' : ''}${_record?.vikramSamvat != null && _record!.vikramSamvat!.isNotEmpty ? '🔱 *विक्रम संवत्:* ${_record!.vikramSamvat!}\n' : ''}${_utsav.isNotEmpty ? '🌺 *उत्सव:* $_utsav\n' : ''}
👥 *उपस्थिति सारांश:*
  ✅ कुल उपस्थित: $_presentCount
  📊 वर्गवार: बाल: $_baalCount, तरुण: $_tarunCount, प्रौढ़: $_praudhCount, अभ्यागत: $_abhyagatCount
  ❌ अनुपस्थित: $_absentCount
  👥 कुल संख्या: $_totalCount
$activitiesText${_customMessage.isNotEmpty ? '\n💬 *विशेष संदेश:*\n$_customMessage\n' : ''}━━━━━━━━━━━━━━━━━━━━━━
_जय श्री राम 🏹_
_संघस्थान ऐप द्वारा प्रेषित_
''';
    SharePlus.instance.share(ShareParams(text: shareText));
  }

  void _sharePaperShakha() {
    const shareText = '''
🖨️ *पत्रक शाखा - दैनिक कार्यक्रम सूची* 🖨️
━━━━━━━━━━━━━━━━━━━━━━
१. ध्वजारोहण व प्रणाम (06:00 - 06:10)
२. शारीरिक कार्यक्रम व व्यायाम (06:10 - 06:30)
३. बौद्धिक चर्चा व सुभाषित (06:30 - 06:45)
४. प्रार्थना व ध्वजावतरण (06:45 - 07:00)
━━━━━━━━━━━━━━━━━━━━━━
''';
    SharePlus.instance.share(ShareParams(text: shareText));
  }

  void _shareGreeting() {
    final message = _greetingMsgController.text;
    final shareText = '''
🚩 *शुभकामना संदेश* 🚩
━━━━━━━━━━━━━━━━━━━━━━
$message

- प्रेषक: मुख्य शिक्षक, संघस्थान शाखा
━━━━━━━━━━━━━━━━━━━━━━
''';
    SharePlus.instance.share(ShareParams(text: shareText));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.title,
          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Container(
        color: const Color(0xFFF9F6F0), // Soft cream background
        child: _isLoading
            ? const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)))
            : _buildBody(),
      ),
    );
  }

  Widget _buildBody() {
    switch (widget.type) {
      case PlaceholderType.snapshot:
        return _buildSnapshotLayout();
      case PlaceholderType.paperShakha:
        return _buildPaperShakhaLayout();
      case PlaceholderType.flipbook:
        return _buildFlipbookLayout();
      case PlaceholderType.greetings:
        return _buildGreetingsLayout();
      case PlaceholderType.settings:
        return _buildSettingsLayout();
    }
  }

  // 1. Snapshot Layout
  Widget _buildSnapshotLayout() {
    final date = widget.formattedDate ?? widget.dateStr ?? 'अज्ञात तिथि';
    
    // Construct Panchang string matching the web snapshot
    final List<String> tithiParts = [];
    if (_record?.hindiMonth != null && _record!.hindiMonth!.isNotEmpty) {
      tithiParts.add(_record!.hindiMonth!);
    }
    if (_record?.paksh != null && _record!.paksh!.isNotEmpty) {
      tithiParts.add(_record!.paksh!);
    }
    if (_record?.tithi != null && _record!.tithi!.isNotEmpty) {
      tithiParts.add(_record!.tithi!);
    }
    final tithiStr = tithiParts.join(' ');

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        children: [
          // Elegant Card mimicking the web snapshot
          Container(
            width: double.infinity,
            decoration: BoxDecoration(
              color: const Color(0xFFFFF9F2), // Light cream background
              border: Border.all(color: const Color(0xFFFF6B00), width: 4), // Orange border
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Stack(
              children: [
                // Corner Mandalas / Ornaments (❁) in 4 corners
                const Positioned(
                  top: 4,
                  left: 8,
                  child: Text('❁', style: TextStyle(color: Color(0xFFFF6B00), fontSize: 24, fontWeight: FontWeight.bold)),
                ),
                const Positioned(
                  top: 4,
                  right: 8,
                  child: Text('❁', style: TextStyle(color: Color(0xFFFF6B00), fontSize: 24, fontWeight: FontWeight.bold)),
                ),
                const Positioned(
                  bottom: 4,
                  left: 8,
                  child: Text('❁', style: TextStyle(color: Color(0xFFFF6B00), fontSize: 24, fontWeight: FontWeight.bold)),
                ),
                const Positioned(
                  bottom: 4,
                  right: 8,
                  child: Text('❁', style: TextStyle(color: Color(0xFFFF6B00), fontSize: 24, fontWeight: FontWeight.bold)),
                ),
                
                // Content Column
                Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // Top Panchang/Samvat Header box (matches web's top banner)
                    if ((_record?.yugabdh != null && _record!.yugabdh!.isNotEmpty) || tithiStr.isNotEmpty)
                      Container(
                        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 20),
                        decoration: const BoxDecoration(
                          color: Color(0xFFFFE0B2),
                          border: Border(bottom: BorderSide(color: Color(0xFFFFB74D), width: 2)),
                        ),
                        child: Column(
                          children: [
                            Text(
                              '${_record?.yugabdh != null && _record!.yugabdh!.isNotEmpty ? "युगाब्द: ${_record!.yugabdh!} | " : ""}'
                              '${_record?.vikramSamvat != null && _record!.vikramSamvat!.isNotEmpty ? "विक्रम संवत्: ${_record!.vikramSamvat!}" : ""}',
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFFD84315),
                              ),
                              textAlign: TextAlign.center,
                            ),
                            if (tithiStr.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                tithiStr,
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFFD84315),
                                ),
                                textAlign: TextAlign.center,
                              ),
                            ],
                            const SizedBox(height: 4),
                            Text(
                              '($date)',
                              style: const TextStyle(
                                fontSize: 12,
                                color: Color(0xFF8D6E63),
                              ),
                              textAlign: TextAlign.center,
                            ),
                          ],
                        ),
                      )
                    else
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: const BoxDecoration(
                          color: Color(0xFFFFE0B2),
                          border: Border(bottom: BorderSide(color: Color(0xFFFFB74D), width: 2)),
                        ),
                        child: Text(
                          date,
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFFD84315),
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ),

                    // Main Saffron Header
                    Container(
                      color: const Color(0xFFFF6B00),
                      padding: const EdgeInsets.symmetric(vertical: 15, horizontal: 20),
                      child: Column(
                        children: [
                          const Icon(Icons.flag, color: Colors.white, size: 36),
                          const SizedBox(height: 6),
                          Text(
                            '🚩 $_shakhaName 🚩',
                            style: const TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                              color: Colors.white,
                            ),
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 2),
                          Text(
                            'घाटकोपर पूर्व, मुंबई',
                            style: TextStyle(
                              fontSize: 13,
                              color: Colors.white.withValues(alpha: 0.9),
                              fontWeight: FontWeight.normal,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    ),

                    // Inner Body
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 15, horizontal: 20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          // Attendance Summary box
                          Container(
                            padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 15),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFFE0B2),
                              border: Border.all(color: const Color(0xFFFFB74D)),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Column(
                              children: [
                                Text(
                                  '✅ कुल उपस्थित: $_presentCount',
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                    color: Color(0xFFBF360C),
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'बाल-$_baalCount, तरुण-$_tarunCount, प्रौढ़-$_praudhCount, अभ्यागत-$_abhyagatCount',
                                  style: const TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                    color: Color(0xFFE65100),
                                  ),
                                ),
                              ],
                            ),
                          ),

                          // Activities section
                          const SizedBox(height: 15),
                          _buildSectionTitle('📋 दैनिक गतिविधियाँ'),
                          const SizedBox(height: 6),
                          if (_conductedActivities.isNotEmpty)
                            ..._conductedActivities.map((act) => Container(
                                  margin: const EdgeInsets.only(bottom: 5),
                                  padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 10),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFF1F8E9), // Light green background for done
                                    border: Border.all(color: const Color(0xFFC5E1A5)),
                                    borderRadius: BorderRadius.circular(6),
                                  ),
                                  child: Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Expanded(
                                        child: Row(
                                          children: [
                                            const Text('✅ ', style: TextStyle(fontSize: 12)),
                                            Expanded(
                                              child: Text(
                                                act['name'] ?? '',
                                                style: const TextStyle(
                                                  fontWeight: FontWeight.bold,
                                                  color: Color(0xFF2E7D32),
                                                  fontSize: 14,
                                                ),
                                                overflow: TextOverflow.ellipsis,
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                      if (act['conductor'] != null && act['conductor']!.isNotEmpty)
                                        Container(
                                          padding: const EdgeInsets.symmetric(vertical: 2, horizontal: 8),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFFFFF3E0),
                                            borderRadius: BorderRadius.circular(12),
                                          ),
                                          child: Text(
                                            '👤 ${act['conductor']}',
                                            style: const TextStyle(
                                              fontSize: 12,
                                              color: Color(0xFF5D4037),
                                            ),
                                          ),
                                        ),
                                    ],
                                  ),
                                ))
                          else
                            const Padding(
                              padding: EdgeInsets.symmetric(vertical: 6),
                              child: Text(
                                'कोई गतिविधि पूर्ण नहीं हुई',
                                style: TextStyle(fontStyle: FontStyle.italic, color: Colors.grey),
                                textAlign: TextAlign.center,
                              ),
                            ),

                          // Special notes section
                          if (_customMessage.isNotEmpty) ...[
                            const SizedBox(height: 15),
                            _buildSectionTitle('💬 विशेष संदेश'),
                            const SizedBox(height: 6),
                            Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: const Color(0xFFFFF3E0),
                                border: Border.all(color: const Color(0xFFFFB74D)),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                _customMessage,
                                style: const TextStyle(
                                  fontSize: 14,
                                  fontStyle: FontStyle.italic,
                                  color: Color(0xFF4E342E),
                                ),
                              ),
                            ),
                          ],

                          // Footer
                          const SizedBox(height: 20),
                          const Divider(color: Color(0xFFFFCC80), thickness: 2),
                          const SizedBox(height: 5),
                          const Center(
                            child: Text(
                              'जय श्री राम 🏹',
                              style: TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFFFF3D00),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          
          const SizedBox(height: 24),
          ElevatedButton.icon(
            onPressed: _shareSnapshot,
            icon: const Icon(Icons.share, color: Colors.white),
            label: const Text(
              'वृत्त साझा करें (Share Report)',
              style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFF6B00),
              minimumSize: const Size(double.infinity, 50),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Container(
      decoration: const BoxDecoration(
        border: Border(left: BorderSide(color: Color(0xFFFF6B00), width: 4)),
      ),
      padding: const EdgeInsets.only(left: 10),
      child: Text(
        title,
        style: const TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.bold,
          color: Color(0xFFE64A19),
        ),
      ),
    );
  }

  Widget _buildSnapshotRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF5D4037))),
          Text(value, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.black87)),
        ],
      ),
    );
  }

  // 2. Paper Shakha Layout
  Widget _buildPaperShakhaLayout() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        children: [
          Card(
            elevation: 3,
            color: const Color(0xFFFFFDF9), // parchment style color
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
              side: BorderSide(color: Colors.brown.shade200, width: 1),
            ),
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.print_outlined, color: Colors.brown.shade700),
                      const SizedBox(width: 8),
                      Text(
                        'शाखा पत्रक (Paper Shakha Program)',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.brown.shade800),
                      ),
                    ],
                  ),
                  const Divider(height: 24, color: Colors.brown),
                  
                  _buildPaperItem('१. ध्वजारोहण व प्रणाम', '06:00 AM - 06:10 AM', 'ध्वज फहराना, सूर्य प्रणाम व संगठन का पहला प्रणाम।'),
                  _buildPaperItem('२. शारीरिक कार्यक्रम', '06:10 AM - 06:30 AM', 'योगासन, सूर्य नमस्कार व स्फूर्तिदायक खेल।'),
                  _buildPaperItem('३. बौद्धिक कार्यक्रम', '06:30 AM - 06:45 AM', 'अमृतवचन पाठन, सुभाषित गान व सामयिक विषयों पर चर्चा।'),
                  _buildPaperItem('४. प्रार्थना व ध्वजावतरण', '06:45 AM - 07:00 AM', 'राष्ट्र कल्याण प्रार्थना व पूर्ण निष्ठा के साथ ध्वज को उतारना।'),
                  
                  const SizedBox(height: 20),
                  Text(
                    'नोट: शाखा पत्रक का प्रिंट या साझा करने के लिए नीचे दिए बटन का उपयोग करें। यह 100% शुद्ध स्थानीय रूप से उपलब्ध है।',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 12, fontStyle: FontStyle.italic, color: Colors.grey.shade600),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),
          ElevatedButton.icon(
            onPressed: _sharePaperShakha,
            icon: const Icon(Icons.share, color: Colors.white),
            label: const Text('पत्रक साझा करें (Share Program)', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF00838F),
              minimumSize: const Size(double.infinity, 50),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPaperItem(String title, String time, String desc) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16.0),
      child: Align(
        alignment: Alignment.centerLeft,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF5D4037))),
                Text(time, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Colors.brown)),
              ],
            ),
            const SizedBox(height: 4),
            Text(desc, style: const TextStyle(fontSize: 13, color: Colors.black54)),
            const SizedBox(height: 6),
            const Divider(height: 1, thickness: 0.5),
          ],
        ),
      ),
    );
  }

  // 3. Digital Flipbook Layout
  Widget _buildFlipbookLayout() {
    final PageController controller = PageController();
    return Column(
      children: [
        const Padding(
          padding: EdgeInsets.all(12.0),
          child: Text(
            '↔️ पेजों को पलटने के लिए स्वाइप करें (Swipe left/right to flip pages)',
            style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.brown),
          ),
        ),
        Expanded(
          child: PageView(
            controller: controller,
            children: [
              _buildFlipbookPage(
                gradient: const [Color(0xFFFF6B00), Color(0xFFFF9E00)],
                icon: Icons.menu_book,
                title: 'डिजिटल वृत्त फ्लिपबुक\n(Digital Flipbook)',
                body: 'नमस्ते स्वयंसेवक बंधु!\nयह आपके दैनिक बौद्धिक विकास के लिए संकलित प्रेरणा पुस्तक है। पन्ने पलटें और सुभाषित, अमृतवचन व गीतों को आत्मसात करें।',
                pageNo: '१ / ४',
              ),
              _buildFlipbookPage(
                gradient: const [Color(0xFF8D0B0B), Color(0xFFC2185B)],
                icon: Icons.brightness_high,
                title: 'आज का सुभाषित\n(Subhashit)',
                body: 'सत्यं ब्रूयात् प्रियं ब्रूयात् न ब्रूयात् सत्यमप्रियम्।\nप्रियं च नानृतं ब्रूयात् एष धर्मः सनातनः॥\n\nअर्थ: सत्य बोलना चाहिए, प्रिय बोलना चाहिए, लेकिन अप्रिय सत्य नहीं बोलना चाहिए और प्रिय असत्य भी नहीं बोलना चाहिए। यही सनातन धर्म है।',
                pageNo: '२ / ४',
              ),
              _buildFlipbookPage(
                gradient: const [Color(0xFF005C53), Color(0xFF042940)],
                icon: Icons.format_quote,
                title: 'अमृत वचन\n(Inspirational Quote)',
                body: '"मनुष्य अपने विचारों से निर्मित एक प्राणी है, वह जैसा सोचता है वैसा ही बन जाता है।"\n\n- महात्मा गांधी',
                pageNo: '३ / ४',
              ),
              _buildFlipbookPage(
                gradient: const [Color(0xFF00796B), Color(0xFF004D40)],
                icon: Icons.music_note,
                title: 'शाखा गीत\n(Shakha Geet)',
                body: 'चलो जलाएं दीप वहाँ, जहाँ अभी भी अंधियारा है।\nअपने ज्ञान और पुरुषार्थ से, चमकेगा भारत प्यारा है।\n\nएक-एक घर में राष्ट्रभक्ति का अलख जगाना लक्ष्य हमारा है।',
                pageNo: '४ / ४',
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildFlipbookPage({
    required List<Color> gradient,
    required IconData icon,
    required String title,
    required String body,
    required String pageNo,
  }) {
    return Card(
      elevation: 6,
      margin: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(20),
          gradient: LinearGradient(colors: gradient, begin: Alignment.topLeft, end: Alignment.bottomRight),
        ),
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 54, color: Colors.white),
            const SizedBox(height: 16),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: Colors.white),
            ),
            const Divider(color: Colors.white54, height: 32, thickness: 1),
            Expanded(
              child: SingleChildScrollView(
                child: Text(
                  body,
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 16, color: Colors.white, height: 1.5, fontWeight: FontWeight.w500),
                ),
              ),
            ),
            Text(
              'पृष्ठ संख्या: $pageNo',
              style: const TextStyle(fontSize: 12, color: Colors.white70, fontWeight: FontWeight.bold),
            ),
          ],
        ),
      ),
    );
  }

  // 4. Greetings Generator Layout
  Widget _buildGreetingsLayout() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Preview Card
          Card(
            elevation: 5,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
              side: const BorderSide(color: Color(0xFFFFB300), width: 2),
            ),
            child: Container(
              width: double.infinity,
              height: 220,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(16),
                gradient: LinearGradient(
                  colors: _cardGradients[_selectedColorIndex],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
              ),
              padding: const EdgeInsets.all(20),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Icon(Icons.flag, color: Colors.white, size: 28),
                      Text(
                        'बधाई पत्रक (Greeting Card)',
                        style: TextStyle(fontSize: 12, color: Colors.white.withValues(alpha: 0.9), fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                  Expanded(
                    child: Center(
                      child: SingleChildScrollView(
                        child: Text(
                          _greetingMsgController.text,
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white, height: 1.4),
                        ),
                      ),
                    ),
                  ),
                  Text(
                    '- प्रेषक: मुख्य शिक्षक, संघस्थान',
                    style: TextStyle(fontSize: 13, color: Colors.white.withValues(alpha: 0.9), fontWeight: FontWeight.bold),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),
          
          // Theme selector
          const Text('🎨 कार्ड बैकग्राउंड चुनें (Select Theme Color):', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF5D4037))),
          const SizedBox(height: 10),
          Row(
            children: List.generate(_cardGradients.length, (index) {
              final isSelected = index == _selectedColorIndex;
              return GestureDetector(
                onTap: () => setState(() => _selectedColorIndex = index),
                child: Container(
                  margin: const EdgeInsets.only(right: 12),
                  width: 38,
                  height: 38,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: LinearGradient(colors: _cardGradients[index]),
                    border: isSelected ? Border.all(color: Colors.black, width: 2) : null,
                  ),
                  child: isSelected ? const Icon(Icons.check, color: Colors.white, size: 20) : null,
                ),
              );
            }),
          ),
          
          const SizedBox(height: 20),
          
          // Edit Message
          const Text('✍️ शुभकामना संदेश संपादित करें (Edit Message):', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF5D4037))),
          const SizedBox(height: 8),
          TextField(
            controller: _greetingMsgController,
            maxLines: 3,
            onChanged: (text) => setState(() {}),
            decoration: InputDecoration(
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              fillColor: Colors.white,
              filled: true,
              hintText: 'संदेश लिखें...',
            ),
          ),
          
          const SizedBox(height: 24),
          ElevatedButton.icon(
            onPressed: _shareGreeting,
            icon: const Icon(Icons.share, color: Colors.white),
            label: const Text('बधाई पत्रक साझा करें (Share Greeting)', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFF6B00),
              minimumSize: const Size(double.infinity, 50),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
        ],
      ),
    );
  }

  // 5. Settings Layout
  Widget _buildSettingsLayout() {
    return ListView(
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
      children: [
        _buildSettingsHeader('सामान्य सेटिंग्स (General Settings)'),
        _buildSwitchTile(
          icon: Icons.dark_mode_outlined,
          title: 'डार्क मोड (Dark Mode Theme)',
          subtitle: 'रात में उपयोग को सुगम बनाने के लिए',
          value: _darkMode,
          onChanged: (val) => setState(() => _darkMode = val),
        ),
        _buildSwitchTile(
          icon: Icons.volume_up_outlined,
          title: 'ध्वनि प्रभाव (Sound Effects)',
          subtitle: 'शाखा टाइमर के दौरान सिटी ध्वनि प्रभाव',
          value: _soundEffects,
          onChanged: (val) => setState(() => _soundEffects = val),
        ),
        _buildSwitchTile(
          icon: Icons.notifications_none_outlined,
          title: 'दैनिक सूचनाएं (Push Notifications)',
          subtitle: 'शाखा उपस्थिति भरने के लिए रिमाइंडर संदेश',
          value: _notifications,
          onChanged: (val) => setState(() => _notifications = val),
        ),
        
        const Divider(height: 32),
        _buildSettingsHeader('सिंक्रनाइज़ेशन सेटिंग्स (Sync Settings)'),
        _buildSwitchTile(
          icon: Icons.wifi_outlined,
          title: 'ऑटो-सिंक वाई-फाई पर',
          subtitle: 'सिर्फ वाई-फाई कनेक्शन होने पर बैकअप लें',
          value: _autoSync,
          onChanged: (val) => setState(() => _autoSync = val),
        ),
        
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('सिंक अंतराल (Sync Interval)', style: TextStyle(fontWeight: FontWeight.bold)),
                  Text('${_syncInterval.toInt()} घंटे', style: const TextStyle(color: Color(0xFFFF6B00), fontWeight: FontWeight.bold)),
                ],
              ),
              Slider(
                value: _syncInterval,
                min: 1.0,
                max: 24.0,
                divisions: 23,
                activeColor: const Color(0xFFFF6B00),
                inactiveColor: Colors.amber.shade100,
                onChanged: (val) => setState(() => _syncInterval = val),
              ),
            ],
          ),
        ),
        
        const Divider(height: 32),
        _buildSettingsHeader('डेटाबेस और जानकारी (System Data)'),
        ListTile(
          leading: const Icon(Icons.info_outline, color: Colors.blue),
          title: const Text('संघस्थान ऐप संस्करण (App Version)', style: TextStyle(fontWeight: FontWeight.bold)),
          subtitle: const Text('1.0.5 (Build 6) | 100% Native Build'),
          trailing: Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(color: Colors.green.shade50, borderRadius: BorderRadius.circular(10)),
            child: Text('स्थिर (Stable)', style: TextStyle(color: Colors.green.shade800, fontSize: 11, fontWeight: FontWeight.bold)),
          ),
        ),
        ListTile(
          leading: const Icon(Icons.delete_forever_outlined, color: Colors.red),
          title: const Text('स्थानीय डेटा साफ़ करें', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
          subtitle: const Text('सभी ऑफ़लाइन सेव्ड डेटा हटा दिया जाएगा'),
          onTap: () {
            showDialog(
              context: context,
              builder: (ctx) => AlertDialog(
                title: const Text('चेतावनी ⚠️'),
                content: const Text('क्या आप वाकई अपना सारा स्थानीय डेटा साफ़ करना चाहते हैं? इसे पूर्ववत नहीं किया जा सकता।'),
                actions: [
                  TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('रद्द करें')),
                  TextButton(
                    onPressed: () {
                      Navigator.pop(ctx);
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('सफलतापूर्वक स्थानीय डेटा रीसेट किया गया।')),
                      );
                    },
                    child: const Text('साफ़ करें', style: TextStyle(color: Colors.red)),
                  ),
                ],
              ),
            );
          },
        ),
      ],
    );
  }

  Widget _buildSettingsHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(left: 16, bottom: 8, top: 4),
      child: Text(
        title,
        style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.grey),
      ),
    );
  }

  Widget _buildSwitchTile({
    required IconData icon,
    required String title,
    required String subtitle,
    required bool value,
    required ValueChanged<bool> onChanged,
  }) {
    return SwitchListTile(
      secondary: Icon(icon, color: const Color(0xFFFF6B00)),
      title: Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
      subtitle: Text(subtitle, style: const TextStyle(fontSize: 12)),
      value: value,
      activeThumbColor: const Color(0xFFFF6B00),
      onChanged: onChanged,
    );
  }
}
