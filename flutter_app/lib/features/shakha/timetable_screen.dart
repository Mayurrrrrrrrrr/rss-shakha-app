import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/models/models.dart';
import '../../core/providers/providers.dart';
import '../../core/db/database_helper.dart';

class TimetableScreen extends ConsumerStatefulWidget {
  const TimetableScreen({super.key});

  @override
  ConsumerState<TimetableScreen> createState() => _TimetableScreenState();
}

class _TimetableScreenState extends ConsumerState<TimetableScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  
  // Default tab state
  int _selectedDayOfWeek = 0; // Sunday
  List<Map<String, dynamic>> _defaultSlots = [];

  // Override tab state
  DateTime _overrideDate = DateTime.now();
  List<Map<String, dynamic>> _overrideSlots = [];

  bool _isLoading = true;

  final List<String> _daysList = [
    'रविवार (Sunday)',
    'सोमवार (Monday)',
    'मंगलवार (Tuesday)',
    'बुधवार (Wednesday)',
    'गुरुवार (Thursday)',
    'शुक्रवार (Friday)',
    'शनिवार (Saturday)'
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadSlotsData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadSlotsData() async {
    setState(() => _isLoading = true);
    final repo = ref.read(localRepoProvider);

    // 1. Load default slots for chosen day of week
    final defaultsList = await repo.getTimetableDefaults();
    final match = defaultsList.firstWhere(
      (element) => element.dayOfWeek == _selectedDayOfWeek,
      orElse: () => TimetableDefault(shakhaId: 0, dayOfWeek: 0, slots: '[]', isActive: 1),
    );
    try {
      final decoded = List<dynamic>.from(jsonDecode(match.slots));
      _defaultSlots = decoded.map((e) => Map<String, dynamic>.from(e)).toList();
    } catch (_) {
      _defaultSlots = [];
    }

    // 2. Load override slots for chosen date
    final dateStr = DateFormat('yyyy-MM-dd').format(_overrideDate);
    final override = await repo.getTimetableOverrideForDate(dateStr);
    if (override != null) {
      try {
        final decoded = List<dynamic>.from(jsonDecode(override.slots));
        _overrideSlots = decoded.map((e) => Map<String, dynamic>.from(e)).toList();
      } catch (_) {
        _overrideSlots = [];
      }
    } else {
      _overrideSlots = [];
    }

    setState(() => _isLoading = false);
  }

  void _addSlot(bool isDefault) {
    setState(() {
      final target = isDefault ? _defaultSlots : _overrideSlots;
      target.add({
        'start_min': target.isEmpty ? 0 : target.last['end_min'],
        'end_min': target.isEmpty ? 10 : (target.last['end_min'] as num) + 10,
        'topic': '',
      });
    });
  }

  void _removeSlot(bool isDefault, int index) {
    setState(() {
      final target = isDefault ? _defaultSlots : _overrideSlots;
      target.removeAt(index);
    });
  }

  Future<void> _saveTimetable(bool isDefault) async {
    final repo = ref.read(localRepoProvider);
    final session = ref.read(sessionProvider);
    final helper = ref.read(localRepoProvider);

    final targetSlots = isDefault ? _defaultSlots : _overrideSlots;
    
    // Sort slots by start min
    targetSlots.sort((a, b) => a['start_min'].compareTo(b['start_min']));
    
    final slotsJson = jsonEncode(targetSlots);

    // 1. Prepare raw inputs structure for the sync queue
    final List<double> startMins = [];
    final List<double> endMins = [];
    final List<String> topics = [];
    for (var s in targetSlots) {
      startMins.add((s['start_min'] as num).toDouble());
      endMins.add((s['end_min'] as num).toDouble());
      topics.add(s['topic'] as String);
    }

    final syncPayload = {
      'save_type': isDefault ? 'default' : 'override',
      'slots': {
        'start_min': startMins,
        'end_min': endMins,
        'topic': topics,
      }
    };

    if (isDefault) {
      syncPayload['day_of_week'] = _selectedDayOfWeek;
      
      // Update local SQLite db
      final db = await DatabaseHelper.instance.database;
      await db.delete('timetable_defaults',
          where: 'shakha_id = ? AND day_of_week = ?', whereArgs: [session.shakhaId, _selectedDayOfWeek]);
      await db.insert('timetable_defaults', {
        'shakha_id': session.shakhaId,
        'day_of_week': _selectedDayOfWeek,
        'slots': slotsJson,
        'is_active': 1,
        'updated_at': DateTime.now().toIso8601String(),
      });
    } else {
      final dateStr = DateFormat('yyyy-MM-dd').format(_overrideDate);
      syncPayload['override_date'] = dateStr;

      // Update local SQLite db
      final db = await DatabaseHelper.instance.database;
      await db.delete('timetable_overrides',
          where: 'shakha_id = ? AND override_date = ?', whereArgs: [session.shakhaId, dateStr]);
      await db.insert('timetable_overrides', {
        'shakha_id': session.shakhaId,
        'override_date': dateStr,
        'slots': slotsJson,
        'is_active': 1,
        'updated_at': DateTime.now().toIso8601String(),
      });
    }

    // Queue action
    await helper.queueAction(
      'save_timetable',
      '/api/actions/timetable_save.php',
      syncPayload,
    );

    // Trigger sync
    ref.read(syncEngineProvider).sync();

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('🚩 समय-सारणी विभाजन स्थानीय रूप से सुरक्षित किया गया!')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '📅 समय-सारणी व्यवस्थापक',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'दैनिक स्लॉट (Defaults)'),
            Tab(text: 'दिनांक अधिलेखन (Overrides)'),
          ],
        ),
      ),
      body: Container(
        color: const Color(0xFFF9F6F0),
        child: _isLoading
            ? const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)))
            : TabBarView(
                controller: _tabController,
                children: [
                  _buildDefaultsTab(),
                  _buildOverridesTab(),
                ],
              ),
      ),
    );
  }

  Widget _buildDefaultsTab() {
    return Column(
      children: [
        // Day selector dropdown
        Padding(
          padding: const EdgeInsets.all(16.0),
          child: DropdownButtonFormField<int>(
            value: _selectedDayOfWeek,
            onChanged: (val) {
              if (val != null) {
                setState(() => _selectedDayOfWeek = val);
                _loadSlotsData();
              }
            },
            decoration: const InputDecoration(
              labelText: 'दिन चुनें (Choose Day)',
              fillColor: Colors.white,
              filled: true,
              border: OutlineInputBorder(),
            ),
            items: List.generate(
              7,
              (index) => DropdownMenuItem(
                value: index,
                child: Text(_daysList[index]),
              ),
            ),
          ),
        ),
        
        Expanded(child: _buildSlotsEditorList(true)),
      ],
    );
  }

  Widget _buildOverridesTab() {
    final displayDate = DateFormat('dd MMMM yyyy').format(_overrideDate);

    return Column(
      children: [
        // Date selector tile
        Padding(
          padding: const EdgeInsets.all(16.0),
          child: Card(
            elevation: 2,
            color: Colors.white,
            child: ListTile(
              leading: const Icon(Icons.date_range, color: Color(0xFFFF6B00)),
              title: Text('दिनांक: $displayDate', style: const TextStyle(fontWeight: FontWeight.bold)),
              trailing: ElevatedButton(
                onPressed: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: _overrideDate,
                    firstDate: DateTime.now(),
                    lastDate: DateTime.now().add(const Duration(days: 365)),
                  );
                  if (picked != null) {
                    setState(() => _overrideDate = picked);
                    _loadSlotsData();
                  }
                },
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFFF6B00)),
                child: const Text('तिथि बदलें', style: TextStyle(color: Colors.white)),
              ),
            ),
          ),
        ),

        Expanded(child: _buildSlotsEditorList(false)),
      ],
    );
  }

  Widget _buildSlotsEditorList(bool isDefault) {
    final list = isDefault ? _defaultSlots : _overrideSlots;

    return Column(
      children: [
        Expanded(
          child: list.isEmpty
              ? const Center(
                  child: Text('कोई स्लॉट निर्धारित नहीं है। स्लॉट जोड़ने के लिए नीचे दिए गए बटन पर क्लिक करें।'),
                )
              : ListView.builder(
                  itemCount: list.length,
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemBuilder: (ctx, index) {
                    final slot = list[index];

                    return Card(
                      elevation: 2,
                      color: Colors.white,
                      margin: const EdgeInsets.only(bottom: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      child: Padding(
                        padding: const EdgeInsets.all(12.0),
                        child: Row(
                          children: [
                            // Start Min field
                            SizedBox(
                              width: 65,
                              child: TextFormField(
                                initialValue: slot['start_min'].toString(),
                                keyboardType: TextInputType.number,
                                decoration: const InputDecoration(labelText: 'प्रारंभ (मि)', border: OutlineInputBorder()),
                                onChanged: (val) {
                                  slot['start_min'] = num.tryParse(val) ?? 0;
                                },
                              ),
                            ),
                            const SizedBox(width: 8),
                            const Text('-'),
                            const SizedBox(width: 8),
                            // End Min field
                            SizedBox(
                              width: 65,
                              child: TextFormField(
                                initialValue: slot['end_min'].toString(),
                                keyboardType: TextInputType.number,
                                decoration: const InputDecoration(labelText: 'अंत (मि)', border: OutlineInputBorder()),
                                onChanged: (val) {
                                  slot['end_min'] = num.tryParse(val) ?? 0;
                                },
                              ),
                            ),
                            const SizedBox(width: 12),
                            // Topic field
                            Expanded(
                              child: TextFormField(
                                initialValue: slot['topic'] as String,
                                decoration: const InputDecoration(labelText: 'विषय / गतिविधि (Topic)', border: OutlineInputBorder()),
                                onChanged: (val) {
                                  slot['topic'] = val;
                                },
                              ),
                            ),
                            IconButton(
                              icon: const Icon(Icons.delete, color: Colors.red),
                              onPressed: () => _removeSlot(isDefault, index),
                            )
                          ],
                        ),
                      ),
                    );
                  },
                ),
        ),
        
        // Save & Add buttons
        Padding(
          padding: const EdgeInsets.all(16.0),
          child: Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => _addSlot(isDefault),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    side: const BorderSide(color: Color(0xFFFF6B00)),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  icon: const Icon(Icons.add, color: Color(0xFFFF6B00)),
                  label: const Text('स्लॉट जोड़ें', style: TextStyle(color: Color(0xFFFF6B00), fontWeight: FontWeight.bold)),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _saveTimetable(isDefault),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFFF6B00),
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  icon: const Icon(Icons.save),
                  label: const Text('सुरक्षित करें', style: TextStyle(fontWeight: FontWeight.bold)),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
