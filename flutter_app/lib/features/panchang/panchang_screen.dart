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
      debugPrint('Error fetching panchang details: $e');
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
      
      final response = await apiClient.get('/api/v1/panchang.php', queryParameters: {
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
      debugPrint('Sync Panchang fetch failed: $e');
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

  @override
  Widget build(BuildContext context) {
    final dateDisplay = DateFormat('dd MMMM yyyy').format(_selectedDate);

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '🕉️ दैनिक पंचांग',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Container(
        color: const Color(0xFFFFF9F2), // warm gold/cream background
        child: Column(
          children: [
            // Date Navigation Header
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: Card(
                elevation: 3,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                color: Colors.white,
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8.0, horizontal: 16.0),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      IconButton(
                        icon: const Icon(Icons.arrow_back_ios, color: Color(0xFFFF6B00)),
                        onPressed: _prevDay,
                      ),
                      TextButton.icon(
                        onPressed: _pickDate,
                        icon: const Icon(Icons.calendar_month, color: Color(0xFFFF6B00)),
                        label: Text(
                          dateDisplay,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF5D4037),
                          ),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.arrow_forward_ios, color: Color(0xFFFF6B00)),
                        onPressed: _nextDay,
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
                                  style: const TextStyle(fontSize: 16, color: Color(0xFF5D4037), fontWeight: FontWeight.bold),
                                ),
                                const SizedBox(height: 24),
                                ElevatedButton(
                                  onPressed: _fetchPanchang,
                                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFFF6B00)),
                                  child: const Text('पुनः प्रयास करें', style: TextStyle(color: Colors.white)),
                                ),
                              ],
                            ),
                          ),
                        )
                      : _panchang == null
                          ? const Center(child: Text('पंचांग उपलब्ध नहीं है।'))
                          : SingleChildScrollView(
                                padding: const EdgeInsets.symmetric(horizontal: 16.0),
                                child: Column(
                                  children: [
                                    // Utsav Banner if any
                                    if (_panchang!.utsav != null && _panchang!.utsav!.trim().isNotEmpty)
                                      Card(
                                        elevation: 3,
                                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                                        color: const Color(0xFFFFE0B2), // Light orange
                                        child: Padding(
                                          padding: const EdgeInsets.all(16.0),
                                          child: Row(
                                            children: [
                                              const Icon(Icons.star, color: Colors.orange, size: 32),
                                              const SizedBox(width: 12),
                                              Expanded(
                                                child: Column(
                                                  crossAxisAlignment: CrossAxisAlignment.start,
                                                  children: [
                                                    const Text(
                                                      'आज का उत्सव / विशेष दिन',
                                                      style: TextStyle(
                                                        fontSize: 12,
                                                        fontWeight: FontWeight.bold,
                                                        color: Colors.brown,
                                                      ),
                                                    ),
                                                    const SizedBox(height: 4),
                                                    Text(
                                                      _panchang!.utsav!,
                                                      style: const TextStyle(
                                                        fontSize: 18,
                                                        fontWeight: FontWeight.bold,
                                                        color: Color(0xFFE65100),
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ),
                                    const SizedBox(height: 16),

                                    // Tithi & Nakshatra Native Cards
                                    Row(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Expanded(
                                          child: Card(
                                            elevation: 4,
                                            shape: RoundedRectangleBorder(
                                              borderRadius: BorderRadius.circular(16),
                                            ),
                                            child: Container(
                                              decoration: BoxDecoration(
                                                borderRadius: BorderRadius.circular(16),
                                                gradient: const LinearGradient(
                                                  colors: [Color(0xFFFFE0B2), Colors.white],
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
                                                        color: const Color(0xFFFF6B00).withValues(alpha: 0.1),
                                                        shape: BoxShape.circle,
                                                      ),
                                                      child: const Icon(
                                                        Icons.brightness_5_rounded,
                                                        color: Color(0xFFFF6B00),
                                                        size: 28,
                                                      ),
                                                    ),
                                                    const SizedBox(height: 12),
                                                    const Text(
                                                      'तिथि / Tithi',
                                                      style: TextStyle(
                                                        fontSize: 13,
                                                        fontWeight: FontWeight.bold,
                                                        color: Color(0xFF5D4037),
                                                      ),
                                                    ),
                                                    const SizedBox(height: 4),
                                                    Text(
                                                      _panchang!.tithi.isNotEmpty ? _panchang!.tithi : '-',
                                                      style: const TextStyle(
                                                        fontSize: 18,
                                                        fontWeight: FontWeight.bold,
                                                        color: Color(0xFFFF6B00),
                                                      ),
                                                    ),
                                                    const SizedBox(height: 4),
                                                    Text(
                                                      _panchang!.paksha.isNotEmpty ? '${_panchang!.paksha} पक्ष' : '-',
                                                      style: const TextStyle(
                                                        fontSize: 12,
                                                        color: Colors.brown,
                                                        fontWeight: FontWeight.w500,
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                            ),
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        Expanded(
                                          child: Card(
                                            elevation: 4,
                                            shape: RoundedRectangleBorder(
                                              borderRadius: BorderRadius.circular(16),
                                            ),
                                            child: Container(
                                              decoration: BoxDecoration(
                                                borderRadius: BorderRadius.circular(16),
                                                gradient: const LinearGradient(
                                                  colors: [Color(0xFFFFF3E0), Colors.white],
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
                                                        color: const Color(0xFFE65100).withValues(alpha: 0.1),
                                                        shape: BoxShape.circle,
                                                      ),
                                                      child: const Icon(
                                                        Icons.auto_awesome_rounded,
                                                        color: Color(0xFFE65100),
                                                        size: 28,
                                                      ),
                                                    ),
                                                    const SizedBox(height: 12),
                                                    const Text(
                                                      'नक्षत्र / Nakshatra',
                                                      style: TextStyle(
                                                        fontSize: 13,
                                                        fontWeight: FontWeight.bold,
                                                        color: Color(0xFF5D4037),
                                                      ),
                                                    ),
                                                    const SizedBox(height: 4),
                                                    Text(
                                                      _panchang!.nakshatra.isNotEmpty ? _panchang!.nakshatra : '-',
                                                      style: const TextStyle(
                                                        fontSize: 18,
                                                        fontWeight: FontWeight.bold,
                                                        color: Color(0xFFE65100),
                                                      ),
                                                    ),
                                                    const SizedBox(height: 4),
                                                    Text(
                                                      _panchang!.shakaMonth.isNotEmpty ? '${_panchang!.shakaMonth} (शक)' : '-',
                                                      style: const TextStyle(
                                                        fontSize: 12,
                                                        color: Colors.brown,
                                                        fontWeight: FontWeight.w500,
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 16),

                                    // Sunrise & Sunset Side-by-Side Card
                                    Card(
                                      elevation: 4,
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(16),
                                      ),
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
                                          padding: const EdgeInsets.symmetric(vertical: 16.0, horizontal: 8.0),
                                          child: Row(
                                            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                                            children: [
                                              Expanded(
                                                child: Column(
                                                  children: [
                                                    Container(
                                                      padding: const EdgeInsets.all(8),
                                                      decoration: BoxDecoration(
                                                        color: Colors.amber.withValues(alpha: 0.1),
                                                        shape: BoxShape.circle,
                                                      ),
                                                      child: const Icon(
                                                        Icons.wb_sunny_rounded,
                                                        color: Colors.amber,
                                                        size: 32,
                                                      ),
                                                    ),
                                                    const SizedBox(height: 8),
                                                    const Text(
                                                      'सूर्योदय / Sunrise',
                                                      style: TextStyle(
                                                        fontSize: 13,
                                                        fontWeight: FontWeight.bold,
                                                        color: Color(0xFF5D4037),
                                                      ),
                                                    ),
                                                    const SizedBox(height: 4),
                                                    Text(
                                                      _panchang!.sunrise.isNotEmpty ? _panchang!.sunrise : '-',
                                                      style: const TextStyle(
                                                        fontSize: 20,
                                                        fontWeight: FontWeight.bold,
                                                        color: Color(0xFFE65100),
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                              Container(
                                                height: 50,
                                                width: 1,
                                                color: const Color(0xFFFFD54F),
                                              ),
                                              Expanded(
                                                child: Column(
                                                  children: [
                                                    Container(
                                                      padding: const EdgeInsets.all(8),
                                                      decoration: BoxDecoration(
                                                        color: const Color(0xFFFF7043).withValues(alpha: 0.1),
                                                        shape: BoxShape.circle,
                                                      ),
                                                      child: const Icon(
                                                        Icons.wb_twilight_rounded,
                                                        color: Color(0xFFFF7043),
                                                        size: 32,
                                                      ),
                                                    ),
                                                    const SizedBox(height: 8),
                                                    const Text(
                                                      'सूर्यास्त / Sunset',
                                                      style: TextStyle(
                                                        fontSize: 13,
                                                        fontWeight: FontWeight.bold,
                                                        color: Color(0xFF5D4037),
                                                      ),
                                                    ),
                                                    const SizedBox(height: 4),
                                                    Text(
                                                      _panchang!.sunset.isNotEmpty ? _panchang!.sunset : '-',
                                                      style: const TextStyle(
                                                        fontSize: 20,
                                                        fontWeight: FontWeight.bold,
                                                        color: Color(0xFFE65100),
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ),
                                    ),
                                    const SizedBox(height: 16),

                                    // Traditional Samvats Card
                                    Card(
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
                                                  '✨ राष्ट्रीय पंचांग विवरण ✨',
                                                  style: TextStyle(
                                                    fontSize: 18,
                                                    fontWeight: FontWeight.bold,
                                                    color: Color(0xFFFF6B00),
                                                  ),
                                                ),
                                              ],
                                            ),
                                            const SizedBox(height: 16),
                                            const Divider(color: Color(0xFFFFB74D)),
                                            _buildPanchangDetailRow('युगाब्द (Yugabdh)', _panchang!.yugabdha.isNotEmpty ? _panchang!.yugabdha : '५१२८'),
                                            _buildPanchangDetailRow('विक्रम संवत (Vikram Samvat)', _panchang!.vikramSamvat.isNotEmpty ? _panchang!.vikramSamvat : '-'),
                                            _buildPanchangDetailRow('शालिवाहन शक संवत (Shaka Samvat)', _panchang!.shakaSamvat.isNotEmpty ? _panchang!.shakaSamvat : '-'),
                                            _buildPanchangDetailRow('मास (Hindi Month)', _panchang!.vikramMonth.isNotEmpty ? _panchang!.vikramMonth : '-'),
                                            _buildPanchangDetailRow('पक्ष (Paksha)', _panchang!.paksha.isNotEmpty ? _panchang!.paksha : '-'),
                                          ],
                                        ),
                                      ),
                                    ),
                                ],
                              ),
                            ),
            ),
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
            style: const TextStyle(fontSize: 13, color: Colors.grey, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Color(0xFF5D4037),
            ),
          ),
          const SizedBox(height: 4),
          const Divider(height: 1, color: Color(0xFFEEEEEE)),
        ],
      ),
    );
  }
}
