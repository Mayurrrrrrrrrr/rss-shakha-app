import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/providers/providers.dart';

class PanchangScreen extends ConsumerStatefulWidget {
  const PanchangScreen({super.key});

  @override
  ConsumerState<PanchangScreen> createState() => _PanchangScreenState();
}

class _PanchangScreenState extends ConsumerState<PanchangScreen> {
  DateTime _selectedDate = DateTime.now();
  bool _isLoading = false;
  Map<String, dynamic>? _panchang;
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
      final apiClient = ref.read(apiClientProvider);
      final dateStr = DateFormat('yyyy-MM-dd').format(_selectedDate);
      final response = await apiClient.get(
        '/api/fetch_panchang.php',
        queryParameters: {'date': dateStr},
      );

      if (response.statusCode == 200 && response.data != null && response.data['status'] == 'success') {
        setState(() {
          _panchang = response.data['panchang'] as Map<String, dynamic>?;
        });
      } else {
        setState(() {
          _error = response.data?['message'] ?? 'पंचांग जानकारी प्राप्त करने में विफल।';
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
                                  if (_panchang!['utsav'] != null && _panchang!['utsav'].toString().trim().isNotEmpty)
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
                                                    _panchang!['utsav'].toString(),
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

                                  // Traditional Panchang Card
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
                                          const Text(
                                            '✨ राष्ट्रीय पंचांग विवरण ✨',
                                            style: TextStyle(
                                              fontSize: 18,
                                              fontWeight: FontWeight.bold,
                                              color: Color(0xFFFF6B00),
                                            ),
                                          ),
                                          const SizedBox(height: 16),
                                          const Divider(color: Color(0xFFFFB74D)),
                                          _buildPanchangDetailRow('युगाब्द (Yugabdh)', _panchang!['yugabdha']?.toString() ?? '५१२८'),
                                          _buildPanchangDetailRow('विक्रम संवत (Vikram Samvat)', _panchang!['vikram_samvat']?.toString() ?? '-'),
                                          _buildPanchangDetailRow('शालिवाहन शक संवत (Shaka Samvat)', _panchang!['shaka_samvat']?.toString() ?? '-'),
                                          _buildPanchangDetailRow('मास (Hindi Month)', _panchang!['vikram_month']?.toString() ?? '-'),
                                          _buildPanchangDetailRow('पक्ष (Paksha)', _panchang!['paksha']?.toString() ?? '-'),
                                          _buildPanchangDetailRow('तिथि (Tithi)', _panchang!['tithi']?.toString() ?? '-'),
                                        ],
                                      ),
                                    ),
                                  ),
                                  const SizedBox(height: 24),
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
