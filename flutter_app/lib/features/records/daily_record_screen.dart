import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/models/models.dart';
import '../../core/providers/providers.dart';

class DailyRecordScreen extends ConsumerStatefulWidget {
  const DailyRecordScreen({super.key});

  @override
  ConsumerState<DailyRecordScreen> createState() => _DailyRecordScreenState();
}

class _DailyRecordScreenState extends ConsumerState<DailyRecordScreen> {
  DateTime _selectedDate = DateTime.now();
  
  // Form Key for native validation
  final _formKey = GlobalKey<FormState>();
  
  // Dropdown options in Devanagari Hindi
  final List<String> _tithiOptions = ['प्रतिपदा', 'द्वितीया', 'तृतीया', 'चतुर्थी', 'पंचमी', 'षष्ठी', 'सप्तमी', 'अष्टमी', 'नवमी', 'दशमी', 'एकादशी', 'द्वादशी', 'त्रयोदशी', 'चतुर्दशी', 'पूर्णिमा', 'अमावस्या'];
  final List<String> _pakshOptions = ['शुक्ल पक्ष', 'कृष्ण पक्ष'];
  final List<String> _monthOptions = ['चैत्र', 'वैशाख', 'ज्येष्ठ', 'आषाढ़', 'श्रावण', 'भाद्रपद', 'आश्विन', 'कार्तिक', 'मार्गशीर्ष', 'पौष', 'माघ', 'फाल्गुन'];

  void _updateTithi(String val) {
    if (val.isNotEmpty && !_tithiOptions.contains(val)) {
      setState(() {
        _tithiOptions.add(val);
      });
    }
    _tithiController.text = val;
  }

  void _updatePaksh(String val) {
    if (val.isNotEmpty && !_pakshOptions.contains(val)) {
      setState(() {
        _pakshOptions.add(val);
      });
    }
    _pakshController.text = val;
  }

  void _updateMonth(String val) {
    if (val.isNotEmpty && !_monthOptions.contains(val)) {
      setState(() {
        _monthOptions.add(val);
      });
    }
    _monthController.text = val;
  }

  bool _isFetchingPanchang = false;
  Future<void> _fetchPanchang() async {
    setState(() => _isFetchingPanchang = true);
    final dateStr = DateFormat('yyyy-MM-dd').format(_selectedDate);
    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get('/api/fetch_panchang.php', queryParameters: {'date': dateStr});
      if (response.statusCode == 200 && response.data != null && response.data['status'] == 'success') {
        final panchang = response.data['panchang'];
        setState(() {
          _yugabdhController.text = panchang['yugabdha']?.toString() ?? '५१२८';
          _vikramController.text = panchang['vikram_samvat']?.toString() ?? '';
          _shakaController.text = panchang['shaka_samvat']?.toString() ?? '';
          _updateMonth(panchang['vikram_month']?.toString() ?? '');
          _updatePaksh(panchang['paksha']?.toString() ?? '');
          _updateTithi(panchang['tithi']?.toString() ?? '');
          _utsavController.text = panchang['utsav']?.toString() ?? '';
        });
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('✨ पंचांग सफलतापूर्वक भर गया!')),
          );
        }
      } else {
        throw Exception(response.data?['message'] ?? 'त्रुटि');
      }
    } catch (e) {
      debugPrint('Sync Panchang fetch failed: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('पंचांग भरने में विफल: $e')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isFetchingPanchang = false);
      }
    }
  }

  // Panchang states
  final _yugabdhController = TextEditingController();
  final _vikramController = TextEditingController();
  final _shakaController = TextEditingController();
  final _monthController = TextEditingController();
  final _pakshController = TextEditingController();
  final _tithiController = TextEditingController();
  final _utsavController = TextEditingController();
  final _messageController = TextEditingController();

  // Data states
  List<Swayamsevak> _swayamsevaks = [];
  int? _existingRecordId;
  List<Activity> _activities = [];
  
  // Form selections
  final Map<int, bool> _attendance = {}; // swayamsevak_id -> isPresent
  final Map<int, bool> _activityDone = {}; // activity_id -> isDone
  final Map<int, int?> _conductedBy = {}; // activity_id -> swayamsevak_id

  bool _isLoading = true;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _loadFormData();
  }

  @override
  void dispose() {
    _yugabdhController.dispose();
    _vikramController.dispose();
    _shakaController.dispose();
    _monthController.dispose();
    _pakshController.dispose();
    _tithiController.dispose();
    _utsavController.dispose();
    _messageController.dispose();
    super.dispose();
  }

  Future<void> _loadFormData() async {
    setState(() => _isLoading = true);
    final repo = ref.read(localRepoProvider);
    
    // 1. Fetch swayamsevaks and activities from SQLite
    _swayamsevaks = await repo.getAllSwayamsevaks();
    _activities = await repo.getActiveActivities();

    // 2. Fetch today's Panchang/utsav from API if online, otherwise pre-fill defaults
    final dateStr = DateFormat('yyyy-MM-dd').format(_selectedDate);
    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get('/api/fetch_panchang.php', queryParameters: {'date': dateStr});
      if (response.statusCode == 200 && response.data != null && response.data['status'] == 'success') {
        final panchang = response.data['panchang'];
        _yugabdhController.text = '५१२८';
        _vikramController.text = panchang['vikram_samvat']?.toString() ?? '';
        _shakaController.text = panchang['shaka_samvat']?.toString() ?? '';
        _updateMonth(panchang['vikram_month']?.toString() ?? '');
        _updatePaksh(panchang['paksha']?.toString() ?? '');
        _updateTithi(panchang['tithi']?.toString() ?? '');
        _utsavController.text = panchang['utsav']?.toString() ?? '';
      }
    } catch (e) {
      debugPrint('Sync Panchang fetch failed: $e');
    }

    // 3. Load existing saved record if any
    final existingRecord = await repo.getDailyRecordByDate(dateStr);
    if (existingRecord != null) {
      _existingRecordId = existingRecord.id;
      _yugabdhController.text = existingRecord.yugabdh ?? '';
      _vikramController.text = existingRecord.vikramSamvat ?? '';
      _shakaController.text = existingRecord.shakaSamvat ?? '';
      _updateMonth(existingRecord.hindiMonth ?? '');
      _updatePaksh(existingRecord.paksh ?? '');
      _updateTithi(existingRecord.tithi ?? '');
      _utsavController.text = existingRecord.utsav ?? '';
      _messageController.text = existingRecord.customMessage ?? '';

      // Load attendance
      final attList = await repo.getAttendanceForRecord(existingRecord.id!);
      for (var att in attList) {
        _attendance[att.swayamsevakId] = att.isPresent == 1;
      }

      // Load activities
      final actList = await repo.getActivitiesForRecord(existingRecord.id!);
      for (var act in actList) {
        _activityDone[act.activityId] = act.isDone == 1;
        _conductedBy[act.activityId] = act.conductedBy;
      }
    } else {
      _existingRecordId = null;
      // Setup empty attendance & activities
      _attendance.clear();
      _activityDone.clear();
      _conductedBy.clear();
      for (var s in _swayamsevaks) {
        _attendance[s.id!] = false;
      }
      for (var a in _activities) {
        _activityDone[a.id] = false;
        _conductedBy[a.id] = null;
      }
    }

    setState(() => _isLoading = false);
  }

  Future<void> _handleSave() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    setState(() => _isSaving = true);
    final repo = ref.read(localRepoProvider);
    final session = ref.read(sessionProvider);
    final dateStr = DateFormat('yyyy-MM-dd').format(_selectedDate);

    final record = DailyRecord(
      id: _existingRecordId, // Use existing record ID if editing
      recordDate: dateStr,
      yugabdh: _yugabdhController.text.trim(),
      vikramSamvat: _vikramController.text.trim(),
      shakaSamvat: _shakaController.text.trim(),
      hindiMonth: _monthController.text.trim(),
      paksh: _pakshController.text.trim(),
      tithi: _tithiController.text.trim(),
      utsav: _utsavController.text.trim(),
      customMessage: _messageController.text.trim(),
      shakhaId: session.shakhaId,
      isActive: 1,
      pendingSync: 1,
    );

    final List<Attendance> attendanceList = [];
    _attendance.forEach((swayId, isPresent) {
      attendanceList.add(Attendance(
        dailyRecordId: 0, // Assigned dynamically inside local repo
        swayamsevakId: swayId,
        isPresent: isPresent ? 1 : 0,
      ));
    });

    final List<DailyActivity> activitiesList = [];
    _activityDone.forEach((actId, isDone) {
      activitiesList.add(DailyActivity(
        dailyRecordId: 0,
        activityId: actId,
        isDone: isDone ? 1 : 0,
        conductedBy: _conductedBy[actId],
      ));
    });

    try {
      await repo.saveDailyRecord(
        record: record,
        attendance: attendanceList,
        activities: activitiesList,
      );
      
      // Trigger sync in background
      ref.read(syncEngineProvider).sync();
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('🚩 उपस्थिति और दैनिक गतिविधि सुरक्षित की गई!')),
        );
        Navigator.pop(context);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('त्रुटि: $e')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isSaving = false);
      }
    }
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime(2025),
      lastDate: DateTime.now(),
      builder: (ctx, child) => Theme(
        data: Theme.of(ctx).copyWith(
          colorScheme: const ColorScheme.light(primary: Color(0xFFFF6B00)),
        ),
        child: child!,
      ),
    );
    if (picked != null) {
      setState(() {
        _selectedDate = picked;
      });
      _loadFormData();
    }
  }

  @override
  Widget build(BuildContext context) {
    final displayDate = DateFormat('dd MMMM yyyy').format(_selectedDate);

    // Group Swayamsevaks by category
    final Map<String, List<Swayamsevak>> groupedSwayamsevaks = {
      'Baal': [],
      'Tarun': [],
      'Praudh': [],
      'Abhyagat': [],
    };

    for (final s in _swayamsevaks) {
      final cat = s.category;
      if (groupedSwayamsevaks.containsKey(cat)) {
        groupedSwayamsevaks[cat]!.add(s);
      } else {
        groupedSwayamsevaks['Tarun']!.add(s);
      }
    }

    // Sort alphabetically within each group
    for (final key in groupedSwayamsevaks.keys) {
      groupedSwayamsevaks[key]!.sort((a, b) => a.name.toLowerCase().compareTo(b.name.toLowerCase()));
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '✍️ दैनिक गतिविधि उपस्थिति',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)))
          : Container(
              color: const Color(0xFFF9F6F0),
              child: Form(
                key: _formKey,
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Date Selector
                      Card(
                        elevation: 2,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        color: Colors.white,
                        child: ListTile(
                          leading: const Icon(Icons.calendar_today, color: Color(0xFFFF6B00)),
                          title: Text(
                            'दिनांक: $displayDate',
                            style: const TextStyle(fontWeight: FontWeight.bold),
                          ),
                          trailing: ElevatedButton(
                            onPressed: _pickDate,
                            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFFF6B00)),
                            child: const Text('बदलें', style: TextStyle(color: Colors.white)),
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),

                      // Panchang Expandable Accordion
                      ExpansionTile(
                        title: const Text('🗓️ दैनिक पंचांग विवरण (Panchang Details)'),
                        leading: const Icon(Icons.settings_suggest, color: Colors.amber),
                        initiallyExpanded: false,
                        children: [
                          Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                            child: Column(
                              children: [
                                Row(
                                  children: [
                                    Expanded(
                                      child: DropdownButtonFormField<String>(
                                        key: ValueKey('tithi_${_tithiController.text}'),
                                        initialValue: _tithiOptions.contains(_tithiController.text) ? _tithiController.text : null,
                                        decoration: const InputDecoration(labelText: 'तिथि (Tithi)'),
                                        items: _tithiOptions.map((String val) {
                                          return DropdownMenuItem<String>(
                                            value: val,
                                            child: Text(val),
                                          );
                                        }).toList(),
                                        onChanged: (val) {
                                          if (val != null) {
                                            setState(() {
                                              _tithiController.text = val;
                                            });
                                          }
                                        },
                                      ),
                                    ),
                                    const SizedBox(width: 16),
                                    Expanded(
                                      child: DropdownButtonFormField<String>(
                                        key: ValueKey('paksh_${_pakshController.text}'),
                                        initialValue: _pakshOptions.contains(_pakshController.text) ? _pakshController.text : null,
                                        decoration: const InputDecoration(labelText: 'पक्ष (Paksha)'),
                                        items: _pakshOptions.map((String val) {
                                          return DropdownMenuItem<String>(
                                            value: val,
                                            child: Text(val),
                                          );
                                        }).toList(),
                                        onChanged: (val) {
                                          if (val != null) {
                                            setState(() {
                                              _pakshController.text = val;
                                            });
                                          }
                                        },
                                      ),
                                    ),
                                  ],
                                ),
                                Row(
                                  children: [
                                    Expanded(
                                      child: DropdownButtonFormField<String>(
                                        key: ValueKey('month_${_monthController.text}'),
                                        initialValue: _monthOptions.contains(_monthController.text) ? _monthController.text : null,
                                        decoration: const InputDecoration(labelText: 'मास (Month)'),
                                        items: _monthOptions.map((String val) {
                                          return DropdownMenuItem<String>(
                                            value: val,
                                            child: Text(val),
                                          );
                                        }).toList(),
                                        onChanged: (val) {
                                          if (val != null) {
                                            setState(() {
                                              _monthController.text = val;
                                            });
                                          }
                                        },
                                      ),
                                    ),
                                    const SizedBox(width: 16),
                                    Expanded(
                                      child: TextFormField(
                                        controller: _yugabdhController,
                                        keyboardType: TextInputType.number,
                                        decoration: const InputDecoration(labelText: 'युगाब्द (Yugabdh)'),
                                        validator: (val) {
                                          if (val == null || val.trim().isEmpty) return null;
                                          if (int.tryParse(val.trim()) == null) {
                                            return 'केवल संख्या';
                                          }
                                          return null;
                                        },
                                      ),
                                    ),
                                  ],
                                ),
                                Row(
                                  children: [
                                    Expanded(
                                      child: TextFormField(
                                        controller: _vikramController,
                                        keyboardType: TextInputType.number,
                                        decoration: const InputDecoration(labelText: 'विक्रम संवत'),
                                        validator: (val) {
                                          if (val == null || val.trim().isEmpty) return null;
                                          if (int.tryParse(val.trim()) == null) {
                                            return 'केवल संख्या';
                                          }
                                          return null;
                                        },
                                      ),
                                    ),
                                    const SizedBox(width: 16),
                                    Expanded(
                                      child: TextFormField(
                                        controller: _shakaController,
                                        keyboardType: TextInputType.number,
                                        decoration: const InputDecoration(labelText: 'शालिवाहन शक'),
                                        validator: (val) {
                                          if (val == null || val.trim().isEmpty) return null;
                                          if (int.tryParse(val.trim()) == null) {
                                            return 'केवल संख्या';
                                          }
                                          return null;
                                        },
                                      ),
                                    ),
                                  ],
                                ),
                                TextFormField(
                                  controller: _utsavController,
                                  decoration: const InputDecoration(labelText: 'विशेष उत्सव/जयंती (Festival/Utsav)'),
                                ),
                                const SizedBox(height: 16),
                                ElevatedButton.icon(
                                  onPressed: _isFetchingPanchang ? null : _fetchPanchang,
                                  icon: _isFetchingPanchang
                                      ? const SizedBox(
                                          width: 16,
                                          height: 16,
                                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                                        )
                                      : const Icon(Icons.auto_awesome, color: Colors.white),
                                  label: const Text('✨ ऑटो-फिल पंचांग', style: TextStyle(color: Colors.white)),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: const Color(0xFFFF6B00),
                                    minimumSize: const Size(double.infinity, 44),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                  ),
                                ),
                              ],
                            ),
                          )
                        ],
                      ),
                      const SizedBox(height: 20),

                      // Swayamsevak Attendance List
                      const Text('👥 स्वयंसेवक उपस्थिति (Attendance)',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF5D4037))),
                      const SizedBox(height: 8),
                      Card(
                        elevation: 2,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        color: Colors.white,
                        child: _swayamsevaks.isEmpty
                            ? const Padding(
                                padding: EdgeInsets.all(24),
                                child: Center(
                                  child: Text('निर्देशिका में कोई स्वयंसेवक उपलब्ध नहीं है।', style: TextStyle(color: Colors.grey)),
                                ),
                              )
                            : Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  _buildCategoryGroup('बाल (Baal)', groupedSwayamsevaks['Baal']!),
                                  _buildCategoryGroup('तरुण/युवा (Tarun)', groupedSwayamsevaks['Tarun']!),
                                  _buildCategoryGroup('प्रौढ़ (Praudh)', groupedSwayamsevaks['Praudh']!),
                                  _buildCategoryGroup('अभ्यागत (Abhyagat)', groupedSwayamsevaks['Abhyagat']!),
                                ],
                              ),
                      ),
                      const SizedBox(height: 20),

                      // Activities Performed List
                      const Text('🚩 दैनिक संघ कार्यक्रम (Activities)',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF5D4037))),
                      const SizedBox(height: 8),
                      Card(
                        elevation: 2,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        color: Colors.white,
                        child: _activities.isEmpty
                            ? const Padding(
                                padding: EdgeInsets.all(24),
                                child: Center(
                                  child: Text('कोई सक्रिय गतिविधियाँ नहीं मिली।', style: TextStyle(color: Colors.grey)),
                                ),
                              )
                            : Padding(
                                padding: const EdgeInsets.symmetric(vertical: 8.0),
                                child: Column(
                                  children: _activities.map((act) {
                                    final isDone = _activityDone[act.id] ?? false;
                                    final currentConductor = _conductedBy[act.id];

                                    return Padding(
                                      padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
                                      child: Row(
                                        children: [
                                          Checkbox(
                                            value: isDone,
                                            activeColor: const Color(0xFFFF6B00),
                                            onChanged: (val) {
                                              setState(() {
                                                _activityDone[act.id] = val ?? false;
                                              });
                                            },
                                          ),
                                          Expanded(
                                            child: Text(
                                              act.name,
                                              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
                                            ),
                                          ),
                                          if (isDone)
                                            SizedBox(
                                              width: 140,
                                              child: DropdownButtonHideUnderline(
                                                child: DropdownButtonFormField<int?>(
                                                  key: ValueKey('conductor_${act.id}_$currentConductor'),
                                                  initialValue: currentConductor,
                                                  hint: const Text('शिक्षक चुनें', style: TextStyle(fontSize: 12)),
                                                  decoration: const InputDecoration(
                                                    contentPadding: EdgeInsets.symmetric(horizontal: 8),
                                                    border: OutlineInputBorder(),
                                                  ),
                                                  items: [
                                                    const DropdownMenuItem<int?>(
                                                      value: null,
                                                      child: Text('कोई नहीं', style: TextStyle(fontSize: 12)),
                                                    ),
                                                    ..._swayamsevaks.map((s) => DropdownMenuItem<int?>(
                                                          value: s.id,
                                                          child: Text(
                                                            s.name,
                                                            overflow: TextOverflow.ellipsis,
                                                            style: const TextStyle(fontSize: 12),
                                                          ),
                                                        )),
                                                  ],
                                                  onChanged: (val) {
                                                    setState(() {
                                                      _conductedBy[act.id] = val;
                                                    });
                                                  },
                                                ),
                                              ),
                                            ),
                                        ],
                                      ),
                                    );
                                  }).toList(),
                                ),
                              ),
                      ),
                      const SizedBox(height: 16),

                      // Custom Notes Card
                      Card(
                        elevation: 2,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        color: Colors.white,
                        child: Padding(
                          padding: const EdgeInsets.all(16.0),
                          child: TextFormField(
                            controller: _messageController,
                            maxLines: 3,
                            decoration: const InputDecoration(
                              labelText: 'विशेष टिप्पणी / संदेश (Notes)',
                              alignLabelWithHint: true,
                              border: OutlineInputBorder(),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Save Button
                      SizedBox(
                        width: double.infinity,
                        height: 54,
                        child: ElevatedButton(
                          onPressed: _isSaving ? null : _handleSave,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFFFF6B00),
                            foregroundColor: Colors.white,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          ),
                          child: _isSaving
                              ? const CircularProgressIndicator(color: Colors.white)
                              : const Text('🚩 रिकॉर्ड सुरक्षित करें (Save)',
                                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                        ),
                      ),
                      const SizedBox(height: 32),
                    ],
                  ),
                ),
              ),
            ),
    );
  }

  Widget _buildCategoryGroup(String title, List<Swayamsevak> list) {
    if (list.isEmpty) return const SizedBox.shrink();
    
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
          child: Text(
            title,
            style: const TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 15,
              color: Color(0xFFFF6B00),
            ),
          ),
        ),
        ...list.map((s) {
          final isPresent = _attendance[s.id!] ?? false;
          return CheckboxListTile(
            title: Text(s.name, style: const TextStyle(fontWeight: FontWeight.w500)),
            subtitle: Text('गट: ${s.gat ?? "सामान्य"}'),
            value: isPresent,
            activeColor: const Color(0xFFFF6B00),
            onChanged: (val) {
              setState(() {
                _attendance[s.id!] = val ?? false;
              });
            },
          );
        }).toList(),
        const Divider(height: 1),
      ],
    );
  }
}
