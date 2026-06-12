import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/providers.dart';
import '../../core/sync/sync_engine.dart';
import '../swayamsevaks/swayamsevak_screen.dart';
import '../records/daily_record_screen.dart';
import '../content/content_screen.dart';
import '../shakha/shakha_timer_screen.dart';
import '../shakha/timetable_screen.dart';

class DashboardScreen extends ConsumerStatefulWidget {
  const DashboardScreen({super.key});

  @override
  ConsumerState<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends ConsumerState<DashboardScreen> {
  Map<String, String>? _panchangData;
  bool _isLoadingPanchang = false;

  @override
  void initState() {
    super.initState();
    _loadCachedPanchang();
    _fetchPanchangFromServer();
  }

  Future<void> _loadCachedPanchang() async {
    final prefs = await SharedPreferences.getInstance();
    final cached = prefs.getString('cached_panchang');
    if (cached != null) {
      final decoded = Map<String, dynamic>.from(jsonDecode(cached));
      setState(() {
        _panchangData = decoded.map((k, v) => MapEntry(k, v.toString()));
      });
    }
  }

  Future<void> _fetchPanchangFromServer() async {
    setState(() {
      _isLoadingPanchang = true;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      final todayStr = DateTime.now().toIso8601String().substring(0, 10);
      final response = await apiClient.get('/api/fetch_panchang.php', queryParameters: {'date': todayStr});

      if (response.statusCode == 200 && response.data != null && response.data['status'] == 'success') {
        final panchang = response.data['panchang'] as Map<String, dynamic>;
        final data = {
          'tithi': panchang['tithi']?.toString() ?? '',
          'paksha': panchang['paksha']?.toString() ?? '',
          'vikram_month': panchang['vikram_month']?.toString() ?? '',
          'vikram_samvat': panchang['vikram_samvat']?.toString() ?? '',
          'shaka_samvat': panchang['shaka_samvat']?.toString() ?? '',
        };

        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('cached_panchang', jsonEncode(data));

        setState(() {
          _panchangData = data;
        });
      }
    } catch (e) {
      debugPrint('Failed to load Panchang: $e');
    } finally {
      if (mounted) {
        setState(() {
          _isLoadingPanchang = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final session = ref.watch(sessionProvider);
    final syncEngine = ref.watch(syncEngineProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '🚩 संघस्थान डैशबोर्ड',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        elevation: 0,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout, color: Colors.white),
            tooltip: 'लॉगआउट',
            onPressed: () {
              showDialog(
                context: context,
                builder: (ctx) => AlertDialog(
                  title: const Text('लॉगआउट करें?'),
                  content: const Text('क्या आप वाकई संघस्थान से लॉगआउट करना चाहते हैं?'),
                  actions: [
                    TextButton(
                      child: const Text('रद्द करें'),
                      onPressed: () => Navigator.pop(ctx),
                    ),
                    TextButton(
                      child: const Text('लॉगआउट', style: TextStyle(color: Colors.red)),
                      onPressed: () {
                        Navigator.pop(ctx);
                        ref.read(sessionProvider.notifier).logout();
                      },
                    ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
      body: Container(
        color: const Color(0xFFF9F6F0), // Cream background
        child: RefreshIndicator(
          onRefresh: () async {
            await _fetchPanchangFromServer();
            await syncEngine.sync();
          },
          color: const Color(0xFFFF6B00),
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Welcome and Sync State Card
                _buildSyncStatusCard(syncEngine),
                const SizedBox(height: 16),

                // Panchang Card
                _buildPanchangCard(),
                const SizedBox(height: 24),

                const Text(
                  'मुख्य गतिविधियां',
                  style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
                ),
                const SizedBox(height: 12),

                // Menu Grid
                GridView.count(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  crossAxisCount: 2,
                  crossAxisSpacing: 16,
                  mainAxisSpacing: 16,
                  childAspectRatio: 1.15,
                  children: [
                    _buildMenuCard(
                      context,
                      title: 'उपस्थिति भरें\n(Attendance)',
                      icon: Icons.assignment_outlined,
                      color: const Color(0xFFE55B00),
                      onTap: () => Navigator.push(
                        context,
                        MaterialPageRoute(builder: (ctx) => const DailyRecordScreen()),
                      ),
                    ),
                    _buildMenuCard(
                      context,
                      title: 'स्वयंसेवक निर्देशिका\n(Directory)',
                      icon: Icons.people_outline,
                      color: const Color(0xFF43A047),
                      onTap: () => Navigator.push(
                        context,
                        MaterialPageRoute(builder: (ctx) => const SwayamsevakScreen()),
                      ),
                    ),
                    _buildMenuCard(
                      context,
                      title: 'समय-सारणी\n(Timetable)',
                      icon: Icons.calendar_today_outlined,
                      color: const Color(0xFF1E88E5),
                      onTap: () => Navigator.push(
                        context,
                        MaterialPageRoute(builder: (ctx) => const TimetableScreen()),
                      ),
                    ),
                    _buildMenuCard(
                      context,
                      title: 'शाखा टाइमर\n(Timer)',
                      icon: Icons.timer_outlined,
                      color: const Color(0xFFF4511E),
                      onTap: () => Navigator.push(
                        context,
                        MaterialPageRoute(builder: (ctx) => const ShakhaTimerScreen()),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                
                // Full-width Content Library Card
                _buildMenuCard(
                  context,
                  title: '📖 बौद्धिक सामग्री संग्रह (Content Library)',
                  icon: Icons.menu_book_outlined,
                  color: const Color(0xFF8D6E63),
                  isFullWidth: true,
                  onTap: () => Navigator.push(
                    context,
                    MaterialPageRoute(builder: (ctx) => const ContentScreen()),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildSyncStatusCard(SyncEngine syncEngine) {
    return ValueListenableBuilder<bool>(
      valueListenable: syncEngine.isSyncing,
      builder: (context, syncing, _) {
        return ValueListenableBuilder<String?>(
          valueListenable: syncEngine.syncError,
          builder: (context, error, _) {
            final hasError = error != null && error.isNotEmpty;
            return Card(
              elevation: 4,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              color: hasError ? Colors.red.shade50 : Colors.white,
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'नमस्ते, मुख्य शिक्षक जी 🙏',
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
                          ),
                          const SizedBox(height: 6),
                          Row(
                            children: [
                              Container(
                                width: 10,
                                height: 10,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  color: syncing
                                      ? Colors.amber
                                      : (hasError ? Colors.red : Colors.green),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  syncing
                                      ? 'सिंक्रनाइज़ेशन चालू है...'
                                      : (hasError ? 'सिंक विफल: $error' : 'डेटाबेस सिंक्रनाइज़्ड है'),
                                  style: TextStyle(
                                    fontSize: 14,
                                    color: syncing
                                        ? Colors.amber.shade800
                                        : (hasError ? Colors.red.shade800 : Colors.green.shade800),
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 4),
                          ValueListenableBuilder<String?>(
                            valueListenable: syncEngine.lastSyncTime,
                            builder: (context, lastSync, _) {
                              return Text(
                                lastSync != null ? 'अंतिम सिंक: $lastSync' : 'सिंक नहीं हुआ है',
                                style: const TextStyle(fontSize: 12, color: Colors.grey),
                              );
                            },
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    ElevatedButton.icon(
                      onPressed: syncing ? null : () => syncEngine.sync(),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: hasError ? Colors.red.shade700 : const Color(0xFFFF6B00),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      icon: syncing
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                            )
                          : const Icon(Icons.sync, size: 16),
                      label: Text(hasError ? 'पुनः प्रयास' : 'सिंक करें'),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildPanchangCard() {
    if (_panchangData == null) {
      return Card(
        elevation: 2,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        color: const Color(0xFFFFF8E1), // light gold
        child: const Padding(
          padding: EdgeInsets.all(24.0),
          child: Center(
            child: CircularProgressIndicator(color: Color(0xFFFF6B00)),
          ),
        ),
      );
    }

    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      color: const Color(0xFFFFF8E1), // Warm gold-cream
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            const Text(
              '🗓️ दैनिक पंचांग',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFFFF6B00)),
            ),
            const SizedBox(height: 16),
            Wrap(
              alignment: WrapAlignment.center,
              spacing: 24,
              runSpacing: 12,
              children: [
                _buildPanchangDetailItem('तिथि', _panchangData!['tithi']!),
                _buildPanchangDetailItem('पक्ष', _panchangData!['paksha']!),
                _buildPanchangDetailItem('मास', _panchangData!['vikram_month']!),
                _buildPanchangDetailItem('युगाब्द', '५१२८'), // Constant/Estimated
                _buildPanchangDetailItem('विक्रम संवत', _panchangData!['vikram_samvat']!),
                _buildPanchangDetailItem('शालिवाहन शक', _panchangData!['shaka_samvat']!),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPanchangDetailItem(String label, String value) {
    return Column(
      children: [
        Text(
          label,
          style: const TextStyle(fontSize: 12, color: Colors.brown, fontWeight: FontWeight.w500),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
        ),
      ],
    );
  }

  Widget _buildMenuCard(
    BuildContext context, {
    required String title,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
    bool isFullWidth = false,
  }) {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        splashColor: color.withOpacity(0.1),
        child: Container(
          padding: const EdgeInsets.all(16.0),
          width: isFullWidth ? double.infinity : null,
          height: isFullWidth ? 80 : null,
          child: isFullWidth
              ? Row(
                  children: [
                    Icon(icon, size: 36, color: color),
                    const SizedBox(width: 16),
                    Text(
                      title,
                      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
                    ),
                    const Spacer(),
                    const Icon(Icons.chevron_right, color: Colors.grey),
                  ],
                )
              : Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Icon(icon, size: 40, color: color),
                    const SizedBox(height: 12),
                    Text(
                      title,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF5D4037),
                      ),
                    ),
                  ],
                ),
        ),
      ),
    );
  }
}
