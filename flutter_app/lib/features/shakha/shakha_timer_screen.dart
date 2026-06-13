import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:audioplayers/audioplayers.dart';
import '../../core/models/models.dart';
import '../../core/providers/providers.dart';

class ShakhaTimerScreen extends ConsumerStatefulWidget {
  const ShakhaTimerScreen({super.key});

  @override
  ConsumerState<ShakhaTimerScreen> createState() => _ShakhaTimerScreenState();
}

class _ShakhaTimerScreenState extends ConsumerState<ShakhaTimerScreen> {
  Timer? _timer;
  int _elapsedSeconds = 0;
  bool _isRunning = false;

  List<Map<String, dynamic>> _slots = [];
  bool _isLoading = true;
  final AudioPlayer _audioPlayer = AudioPlayer();

  @override
  void initState() {
    super.initState();
    _loadTodaySlots();
  }

  @override
  void dispose() {
    _timer?.cancel();
    _audioPlayer.dispose();
    super.dispose();
  }

  Future<void> _loadTodaySlots() async {
    final repo = ref.read(localRepoProvider);
    final session = ref.read(sessionProvider);
    final today = DateTime.now();
    
    // Get override for today
    final todayStr = DateFormat('yyyy-MM-dd').format(today);
    final override = await repo.getTimetableOverrideForDate(todayStr);

    String slotsJson = '[]';
    if (override != null) {
      slotsJson = override.slots;
    } else {
      // Get default for today's day of week
      final defaults = await repo.getTimetableDefaults();
      final dayOfWeek = today.weekday % 7; // Sunday = 0, Monday = 1... weekday uses 1=Mon, 7=Sun
      final match = defaults.firstWhere(
        (element) => element.dayOfWeek == dayOfWeek,
        orElse: () => TimetableDefault(shakhaId: 0, dayOfWeek: 0, slots: '[]', isActive: 1),
      );
      slotsJson = match.slots;
    }

    try {
      final decoded = List<dynamic>.from(jsonDecode(slotsJson));
      _slots = decoded.map((e) {
        final map = Map<String, dynamic>.from(e);
        return {
          'start_min': map['start_min'] as num,
          'end_min': map['end_min'] as num,
          'topic': map['topic'] as String,
        };
      }).toList();

      // Sort by start_min
      _slots.sort((a, b) => a['start_min'].compareTo(b['start_min']));
    } catch (e) {
      debugPrint('Error loading slots: $e');
    }

    setState(() => _isLoading = false);
  }

  void _toggleTimer() {
    if (_isRunning) {
      _timer?.cancel();
      setState(() {
        _isRunning = false;
      });
      SystemSound.play(SystemSoundType.click);
    } else {
      // Play initial whistle/bell click
      SystemSound.play(SystemSoundType.click);
      _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
        setState(() {
          _elapsedSeconds++;
        });
        _checkAlerts();
      });
      setState(() {
        _isRunning = true;
      });
    }
  }

  void _resetTimer() {
    _timer?.cancel();
    setState(() {
      _elapsedSeconds = 0;
      _isRunning = false;
    });
    SystemSound.play(SystemSoundType.click);
  }

  Future<void> _playWhistle({bool doubleBlast = false}) async {
    try {
      await _audioPlayer.stop();
      await _audioPlayer.play(AssetSource('audio/whistle.mp3'));
      if (doubleBlast) {
        await Future.delayed(const Duration(milliseconds: 1200));
        await _audioPlayer.stop();
        await _audioPlayer.play(AssetSource('audio/whistle.mp3'));
      }
    } catch (e) {
      debugPrint('Error playing whistle: $e');
    }
  }

  void _checkAlerts() {
    // Check if we hit any transition timestamp
    for (var slot in _slots) {
      final startSec = (slot['start_min'] * 60).toInt();
      final endSec = (slot['end_min'] * 60).toInt();

      // Whistle at slot boundary transitions
      if (_elapsedSeconds == startSec || _elapsedSeconds == endSec) {
        HapticFeedback.vibrate();
        _playWhistle(doubleBlast: false);
        break;
      }
      // Warning double-whistle 30 seconds before any slot ends
      if (_elapsedSeconds == endSec - 30) {
        HapticFeedback.lightImpact();
        _playWhistle(doubleBlast: true);
        break;
      }
    }
  }

  String _formatTime(int totalSeconds) {
    final int m = totalSeconds ~/ 60;
    final int s = totalSeconds % 60;
    final String minutes = m < 10 ? '0$m' : '$m';
    final String seconds = s < 10 ? '0$s' : '$s';
    return '$minutes:$seconds';
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00))),
      );
    }

    int activeIdx = -1;
    for (int i = 0; i < _slots.length; i++) {
      final startSec = (_slots[i]['start_min'] * 60).toInt();
      final endSec = (_slots[i]['end_min'] * 60).toInt();
      if (_elapsedSeconds >= startSec && _elapsedSeconds < endSec) {
        activeIdx = i;
        break;
      }
    }

    final activeTopic = activeIdx != -1 ? _slots[activeIdx]['topic'] : 'प्रतीक्षा या अंतराल...';

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '⏱️ शाखा टाइमर (Shakha Timer)',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Container(
        color: const Color(0xFFF9F6F0),
        child: Column(
          children: [
            const SizedBox(height: 24),
            // Large visual timer box
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24.0),
              child: Card(
                elevation: 6,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
                color: const Color(0xFF212121), // Charcoal dark timer box
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 40.0),
                  child: Column(
                    children: [
                      Text(
                        _formatTime(_elapsedSeconds),
                        style: const TextStyle(
                          fontSize: 72,
                          fontFamily: 'monospace',
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                          letterSpacing: 2,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        activeTopic,
                        style: const TextStyle(
                          fontSize: 18,
                          color: Color(0xFFFFB300),
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(height: 20),

            // Controls Row
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24.0),
              child: Row(
                children: [
                  Expanded(
                    flex: 2,
                    child: ElevatedButton.icon(
                      onPressed: _toggleTimer,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: _isRunning ? Colors.red : const Color(0xFFFF6B00),
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      ),
                      icon: Icon(_isRunning ? Icons.pause : Icons.play_arrow),
                      label: Text(
                        _isRunning ? 'शाखा रोकें' : 'शाखा शुरू करें',
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: _resetTimer,
                      style: OutlinedButton.styleFrom(
                        foregroundColor: Colors.grey.shade800,
                        side: BorderSide(color: Colors.grey.shade400),
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      ),
                      icon: const Icon(Icons.refresh),
                      label: const Text('रीसेट'),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Slots list title
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 24.0),
              child: Align(
                alignment: Alignment.centerLeft,
                child: Text(
                  'समय-सारणी विभाजन (Timetable Slots)',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
                ),
              ),
            ),
            const SizedBox(height: 10),

            // Slots Timeline
            Expanded(
              child: _slots.isEmpty
                  ? const Center(child: Text('कोई स्लॉट निर्धारित नहीं है।'))
                  : ListView.builder(
                      itemCount: _slots.length,
                      padding: const EdgeInsets.symmetric(horizontal: 24),
                      itemBuilder: (ctx, index) {
                        final slot = _slots[index];
                        final isSlotActive = index == activeIdx;

                        return Container(
                          margin: const EdgeInsets.only(bottom: 10),
                          decoration: BoxDecoration(
                            color: isSlotActive ? const Color(0xFFFFE0B2) : Colors.white,
                            border: Border.all(
                              color: isSlotActive ? const Color(0xFFFF6B00) : Colors.grey.shade300,
                              width: isSlotActive ? 2 : 1,
                            ),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: ListTile(
                            leading: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                              decoration: BoxDecoration(
                                color: isSlotActive ? const Color(0xFFFF6B00) : Colors.grey.shade200,
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Text(
                                '${slot['start_min']}-${slot['end_min']} मि.',
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  color: isSlotActive ? Colors.white : Colors.black87,
                                ),
                              ),
                            ),
                            title: Text(
                              slot['topic'] as String,
                              style: TextStyle(
                                fontWeight: isSlotActive ? FontWeight.bold : FontWeight.normal,
                                color: const Color(0xFF5D4037),
                              ),
                            ),
                          ),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }
}
