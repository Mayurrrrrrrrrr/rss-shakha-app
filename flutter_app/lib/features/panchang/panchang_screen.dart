import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/providers/providers.dart';
import '../../core/models/models.dart';

class PanchangScreen extends ConsumerStatefulWidget {
  const PanchangScreen({super.key});

  @override
  ConsumerState<PanchangScreen> createState() => _PanchangScreenState();
}

class _PanchangScreenState extends ConsumerState<PanchangScreen> {
  DateTime _selectedDate = DateTime.now();
  bool _isLoading = false;
  Panchang? _panchang;
  String? _error;

  @override
  void initState() {
    super.initState();
    _fetchPanchang();
  }

  Future<void> _fetchPanchang() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final panchang = await _getPanchang(_selectedDate);
      if (panchang != null) {
        setState(() {
          _panchang = panchang;
        });
      } else {
        setState(() {
          _error = 'पंचांग जानकारी प्राप्त करने में विफल।';
        });
      }
    } catch (e) {
      debugPrint('Error fetching panchang details: \$e');
      setState(() {
        _error = 'नेटवर्क त्रुटि या सर्वर उपलब्ध नहीं है।';
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<Panchang?> _getPanchang(DateTime date) async {
    final repo = ref.read(localRepoProvider);
    final dateStr = DateFormat('yyyy-MM-dd').format(date);
    
    // 1. Try to load from SQLite cache
    final cached = await repo.getCachedPanchang(dateStr);
    if (cached != null) {
      return Panchang.fromJson(cached);
    }
    
    // 2. Fetch the whole month and cache it
    try {
      final apiClient = ref.read(apiClientProvider);
      final year = date.year.toString();
      final month = date.month.toString();
      
      final response = await apiClient.get('/api/fetch_panchang.php', queryParameters: {
        'year': year,
        'month': month,
      });
      
      if (response.statusCode == 200 && response.data != null && response.data['status'] == 'success') {
        final List<dynamic> list = response.data['panchang_list'] ?? [];
        if (list.isNotEmpty) {
          await repo.cachePanchangList(list);
          final freshCached = await repo.getCachedPanchang(dateStr);
          if (freshCached != null) {
            return Panchang.fromJson(freshCached);
          }
        }
      }
    } catch (e) {
      debugPrint('Sync Panchang fetch failed: \$e');
    }
    return null;
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime(2025),
      lastDate: DateTime.now().add(const Duration(days: 365)),
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
      _fetchPanchang();
    }
  }

  void _nextDay() {
    setState(() {
      _selectedDate = _selectedDate.add(const Duration(days: 1));
    });
    _fetchPanchang();
  }

  void _prevDay() {
    setState(() {
      _selectedDate = _selectedDate.subtract(const Duration(days: 1));
    });
    _fetchPanchang();
  }

  String _getHindiDay(int weekday) {
    const days = ['सोमवार', 'मंगलवार', 'बुधवार', 'गुरुवार', 'शुक्रवार', 'शनिवार', 'रविवार'];
    return days[weekday - 1];
  }

  @override
  Widget build(BuildContext context) {
    final dateDisplay = DateFormat('dd MMMM yyyy').format(_selectedDate);
    final hindiDay = _getHindiDay(_selectedDate.weekday);

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '🕉️ दैनिक पंचांग',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 20),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Container(
        color: const Color(0xFFFFF9F2),
        child: Column(
          children: [
            // Date Navigation Header
            Padding(
              padding: const EdgeInsets.all(12.0),
              child: Card(
                elevation: 3,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                color: Colors.white,
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8.0, horizontal: 8.0),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      IconButton(
                        icon: const Icon(Icons.arrow_back_ios, color: Color(0xFFFF6B00), size: 28),
                        onPressed: _prevDay,
                        padding: const EdgeInsets.all(12),
                      ),
                      Expanded(
                        child: InkWell(
                          onTap: _pickDate,
                          borderRadius: BorderRadius.circular(12),
                          child: Padding(
                            padding: const EdgeInsets.symmetric(vertical: 8.0),
                            child: Column(
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    const Icon(Icons.calendar_month, color: Color(0xFFFF6B00), size: 22),
                                    const SizedBox(width: 8),
                                    Text(
                                      dateDisplay,
                                      style: const TextStyle(
                                        fontSize: 17,
                                        fontWeight: FontWeight.bold,
                                        color: Color(0xFF5D4037),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  hindiDay,
                                  style: const TextStyle(fontSize: 14, color: Colors.brown, fontWeight: FontWeight.w500),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.arrow_forward_ios, color: Color(0xFFFF6B00), size: 28),
                        onPressed: _nextDay,
                        padding: const EdgeInsets.all(12),
                      ),
                    ],
                  ),
                ),
              ),
            ),

            // Panchang Contents
            Expanded(
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)))
                  : _error != null
                      ? Center(
                          child: Padding(
                            padding: const EdgeInsets.all(24.0),
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Icon(Icons.info_outline, size: 64, color: Colors.orange),
                                const SizedBox(height: 16),
                                Text(
                                  _error!,
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(fontSize: 18, color: Color(0xFF5D4037), fontWeight: FontWeight.bold),
                                ),
                                const SizedBox(height: 24),
                                ElevatedButton(
                                  onPressed: _fetchPanchang,
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: const Color(0xFFFF6B00),
                                    foregroundColor: Colors.white,
                                    minimumSize: const Size(200, 56),
                                  ),
                                  child: const Text('पुनः प्रयास करें', style: TextStyle(fontSize: 18)),
                                ),
                              ],
                            ),
                          ),
                        )
                      : _panchang == null
                          ? const Center(child: Text('पंचांग उपलब्ध नहीं है।', style: TextStyle(fontSize: 18)))
                          : _buildPanchangContent(),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPanchangContent() {
    final p = _panchang!;
    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(horizontal: 12.0),
      child: Column(
        children: [
          // Section 1: Utsav Banner
          if (p.utsav != null && p.utsav!.trim().isNotEmpty)
            _buildUtsavBanner(p.utsav!),
          const SizedBox(height: 12),

          // Section 2: Core Panchang — 2x2 Grid
          _buildSectionLabel('📿 मुख्य पंचांग'),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(child: _buildCorePanchangCard('तिथि', p.tithi, '${p.paksha} पक्ष', Icons.brightness_5_rounded, const Color(0xFFFF6B00), const Color(0xFFFFE0B2))),
              const SizedBox(width: 8),
              Expanded(child: _buildCorePanchangCard('नक्षत्र', p.nakshatra, '', Icons.auto_awesome_rounded, const Color(0xFFE65100), const Color(0xFFFFF3E0))),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(child: _buildCorePanchangCard('योग', p.yoga, '', Icons.all_inclusive_rounded, const Color(0xFF6A1B9A), const Color(0xFFF3E5F5))),
              const SizedBox(width: 8),
              Expanded(child: _buildCorePanchangCard('करण', p.karana, '', Icons.hexagon_outlined, const Color(0xFF00695C), const Color(0xFFE0F2F1))),
            ],
          ),
          const SizedBox(height: 16),

          // Section 3: Sun & Moon Times
          _buildSectionLabel('☀️ सूर्य-चंद्र काल'),
          const SizedBox(height: 8),
          _buildSunMoonCard(p),
          const SizedBox(height: 16),

          // Section 4: Astro Details (Moon Sign + Rahukaal)
          _buildSectionLabel('🌟 ज्योतिष विवरण'),
          const SizedBox(height: 8),
          _buildAstroCard(p),
          const SizedBox(height: 16),

          // Section 5: Shubh Muhurt
          if (p.shubhMuhurt != null && p.shubhMuhurt!.isNotEmpty) ...[
            _buildSectionLabel('✨ शुभ मुहूर्त'),
            const SizedBox(height: 8),
            _buildShubhMuhurtCard(p.shubhMuhurt!),
            const SizedBox(height: 16),
          ],

          // Section 6: Samvats
          _buildSectionLabel('🏛️ संवत् विवरण'),
          const SizedBox(height: 8),
          _buildSamvatsCard(p),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  // --- Widget Builders ---

  Widget _buildSectionLabel(String label) {
    return Align(
      alignment: Alignment.centerLeft,
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 19,
          fontWeight: FontWeight.bold,
          color: Color(0xFFE65100),
        ),
      ),
    );
  }

  Widget _buildUtsavBanner(String utsav) {
    return Card(
      elevation: 3,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      color: const Color(0xFFFFE0B2),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: Colors.orange.shade100,
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.star_rounded, color: Colors.deepOrange, size: 32),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'आज का उत्सव / विशेष दिन',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.brown),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    utsav,
                    style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFFE65100)),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCorePanchangCard(String label, String value, String subtitle, IconData icon, Color iconColor, Color bgColor) {
    final displayValue = (value.isEmpty || value == '-' || value == '—') ? '—' : value;
    final displaySubtitle = (subtitle.isEmpty || subtitle == '- पक्ष') ? '' : subtitle;
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: LinearGradient(
            colors: [bgColor, Colors.white],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: iconColor.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: iconColor, size: 28),
              ),
              const SizedBox(height: 10),
              Text(
                label,
                style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
              ),
              const SizedBox(height: 4),
              Text(
                displayValue,
                style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: iconColor),
              ),
              if (displaySubtitle.isNotEmpty) ...[
                const SizedBox(height: 2),
                Text(
                  displaySubtitle,
                  style: const TextStyle(fontSize: 13, color: Colors.brown, fontWeight: FontWeight.w500),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSunMoonCard(Panchang p) {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: const LinearGradient(
            colors: [Color(0xFFFFFDE7), Colors.white],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            children: [
              // Sun Row
              Row(
                children: [
                  Expanded(
                    child: _buildTimeItem('🌅 सूर्योदय', p.sunrise, Colors.amber),
                  ),
                  Container(height: 40, width: 1, color: const Color(0xFFFFD54F)),
                  Expanded(
                    child: _buildTimeItem('🌇 सूर्यास्त', p.sunset, const Color(0xFFFF7043)),
                  ),
                ],
              ),
              const Divider(height: 24, color: Color(0xFFFFE082)),
              // Moon Row
              Row(
                children: [
                  Expanded(
                    child: _buildTimeItem('🌙 चन्द्रोदय', p.chandraUdaya, const Color(0xFF5C6BC0)),
                  ),
                  Container(height: 40, width: 1, color: const Color(0xFFFFD54F)),
                  Expanded(
                    child: _buildTimeItem('🌑 चंद्रास्त', p.chandraAsta, const Color(0xFF78909C)),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTimeItem(String label, String value, Color color) {
    final displayVal = (value.isEmpty || value == '—' || value == '-') ? '—' : value;
    return Column(
      children: [
        Text(
          label,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
        ),
        const SizedBox(height: 6),
        Text(
          displayVal,
          style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: color),
        ),
      ],
    );
  }

  Widget _buildAstroCard(Panchang p) {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            _buildAstroRow('♑ चंद्र राशि', p.chandraRashi, const Color(0xFF1565C0)),
            const Divider(height: 20, color: Color(0xFFE0E0E0)),
            _buildAstroRow('⏰ राहुकाल', p.rahukaal, const Color(0xFFC62828)),
          ],
        ),
      ),
    );
  }

  Widget _buildAstroRow(String label, String value, Color valueColor) {
    final displayVal = (value.isEmpty || value == '—' || value == '-') ? '—' : value;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: const TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
          ),
          Flexible(
            child: Text(
              displayVal,
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: valueColor),
              textAlign: TextAlign.end,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildShubhMuhurtCard(Map<String, String> muhurts) {
    final muhurtLabels = {
      'abhijit': '🔱 अभिजित मुहूर्त',
      'vijay': '🏆 विजय मुहूर्त',
      'amrit_kaal': '💧 अमृत काल',
      'ravi_yoga': '☀️ रवि योग',
      'sarvarth_siddhi': '⭐ सर्वार्थ सिद्धि योग',
    };

    // Filter out null/empty values
    final activeMuhurts = muhurts.entries
        .where((e) => e.value.isNotEmpty && e.value != 'null' && e.value != '—')
        .toList();

    if (activeMuhurts.isEmpty) return const SizedBox.shrink();

    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: const BorderSide(color: Color(0xFFA5D6A7), width: 1.5),
      ),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: const LinearGradient(
            colors: [Color(0xFFE8F5E9), Colors.white],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            children: activeMuhurts.map((entry) {
              final label = muhurtLabels[entry.key] ?? entry.key;
              return Padding(
                padding: const EdgeInsets.symmetric(vertical: 8.0),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        label,
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF2E7D32)),
                      ),
                    ),
                    Text(
                      entry.value,
                      style: const TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: Color(0xFF1B5E20)),
                    ),
                  ],
                ),
              );
            }).toList(),
          ),
        ),
      ),
    );
  }

  Widget _buildSamvatsCard(Panchang p) {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20),
        side: const BorderSide(color: Color(0xFFFFB74D), width: 1.5),
      ),
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          children: [
            const Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.gavel_rounded, color: Color(0xFFFF6B00)),
                SizedBox(width: 8),
                Text(
                  '✨ राष्ट्रीय पंचांग ✨',
                  style: TextStyle(fontSize: 19, fontWeight: FontWeight.bold, color: Color(0xFFFF6B00)),
                ),
              ],
            ),
            const SizedBox(height: 12),
            const Divider(color: Color(0xFFFFB74D)),
            _buildPanchangDetailRow('युगाब्द', p.yugabdha.isNotEmpty ? p.yugabdha : '५१२८'),
            _buildPanchangDetailRow('विक्रम संवत', p.vikramSamvat.isNotEmpty ? p.vikramSamvat : '-'),
            _buildPanchangDetailRow('शालिवाहन शक', p.shakaSamvat.isNotEmpty ? p.shakaSamvat : '-'),
            _buildPanchangDetailRow('मास', p.vikramMonth.isNotEmpty ? p.vikramMonth : '-'),
            _buildPanchangDetailRow('पक्ष', p.paksha.isNotEmpty ? p.paksha : '-'),
          ],
        ),
      ),
    );
  }

  Widget _buildPanchangDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(fontSize: 14, color: Colors.grey, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(fontSize: 19, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
          ),
          const SizedBox(height: 4),
          const Divider(height: 1, color: Color(0xFFEEEEEE)),
        ],
      ),
    );
  }
}
