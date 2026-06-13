import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../core/models/models.dart';
import '../../core/providers/providers.dart';
import '../../core/sync/sync_engine.dart';
import '../swayamsevaks/swayamsevak_screen.dart';
import '../records/daily_record_screen.dart';
import '../records/records_list_screen.dart';
import '../records/record_detail_screen.dart';
import '../content/content_screen.dart';
import '../shakha/shakha_timer_screen.dart';
import '../shakha/timetable_screen.dart';
import '../records/records_calendar_screen.dart';
import '../reports/monthly_report_screen.dart';
import '../notices/notices_screen.dart';
import '../panchang/panchang_screen.dart';
import '../content/vyaktitv_screen.dart';
import '../webview/native_placeholder_screen.dart';

class DashboardData {
  final int totalSwayamsevaks;
  final int totalRecords;
  final String? todayAttendanceText;
  final List<Event> recentNotices;
  final List<Map<String, dynamic>> recentRecords;
  final DailyRecord? todayRecord;
  final Map<String, String>? cachedPanchang;

  DashboardData({
    required this.totalSwayamsevaks,
    required this.totalRecords,
    this.todayAttendanceText,
    required this.recentNotices,
    required this.recentRecords,
    this.todayRecord,
    this.cachedPanchang,
  });
}

class DashboardScreen extends ConsumerStatefulWidget {
  const DashboardScreen({super.key});

  @override
  ConsumerState<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends ConsumerState<DashboardScreen> {
  Future<DashboardData>? _dashboardDataFuture;

  @override
  void initState() {
    super.initState();
    _refreshDashboardData();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      final syncEngine = ref.read(syncEngineProvider);
      syncEngine.isSyncing.addListener(_onSyncStateChanged);
      // Auto-sync in the background on startup
      syncEngine.sync();
    });
  }

  @override
  void dispose() {
    try {
      final syncEngine = ref.read(syncEngineProvider);
      syncEngine.isSyncing.removeListener(_onSyncStateChanged);
    } catch (_) {}
    super.dispose();
  }

  void _onSyncStateChanged() {
    final isSyncing = ref.read(syncEngineProvider).isSyncing.value;
    if (!isSyncing) {
      _refreshDashboardData();
    }
  }

  void _refreshDashboardData() {
    setState(() {
      _dashboardDataFuture = _fetchDashboardData();
    });
  }

  Future<DashboardData> _fetchDashboardData() async {
    final repo = ref.read(localRepoProvider);
    
    // 1. Fetch summary stats
    final totalSw = await repo.getTotalSwayamsevaks();
    final totalRec = await repo.getTotalDailyRecords();
    final recentRecs = await repo.getRecentDailyRecords(3);
    
    // 2. Fetch today's record and calculate attendance
    final todayStr = DateTime.now().toIso8601String().substring(0, 10);
    final todayRec = await repo.getDailyRecordByDate(todayStr);
    String? todayAttendance;
    if (todayRec != null && todayRec.id != null) {
      final attendance = await repo.getAttendanceForRecord(todayRec.id!);
      final present = attendance.where((a) => a.isPresent == 1).length;
      final total = attendance.length;
      todayAttendance = '$present / $total उपस्थित';
    }
    
    // 3. Fetch recent notices (synchronized events)
    final events = await repo.getEvents();
    final recentNotices = events.take(3).toList();

    // 4. Fetch cached panchang details
    Map<String, String>? panchang;
    try {
      final prefs = await SharedPreferences.getInstance();
      final cached = prefs.getString('cached_panchang');
      if (cached != null) {
        final decoded = Map<String, dynamic>.from(jsonDecode(cached));
        panchang = decoded.map((k, v) => MapEntry(k, v.toString()));
      }
    } catch (_) {}

    return DashboardData(
      totalSwayamsevaks: totalSw,
      totalRecords: totalRec,
      todayAttendanceText: todayAttendance,
      recentNotices: recentNotices,
      recentRecords: recentRecs,
      todayRecord: todayRec,
      cachedPanchang: panchang,
    );
  }

  void _showLogoutDialog(BuildContext context) {
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
  }

  @override
  Widget build(BuildContext context) {
    final session = ref.watch(sessionProvider);
    final syncEngine = ref.watch(syncEngineProvider);

    return Scaffold(
      body: RefreshIndicator(
        onRefresh: () async {
          await syncEngine.sync();
          _refreshDashboardData();
        },
        color: const Color(0xFFFF6B00),
        child: FutureBuilder<DashboardData>(
          future: _dashboardDataFuture,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting && !snapshot.hasData) {
              return const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)));
            }

            final data = snapshot.data;
            final recentNotices = data?.recentNotices ?? [];
            final recentRecords = data?.recentRecords ?? [];

            return CustomScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                // Collapsible Pinned SliverAppBar
                SliverAppBar(
                  expandedHeight: 180.0,
                  floating: false,
                  pinned: true,
                  backgroundColor: const Color(0xFFFF6B00),
                  actions: [
                    IconButton(
                      icon: const Icon(Icons.logout, color: Colors.white),
                      tooltip: 'लॉगआउट',
                      onPressed: () => _showLogoutDialog(context),
                    ),
                  ],
                  flexibleSpace: FlexibleSpaceBar(
                    titlePadding: const EdgeInsets.only(left: 16.0, bottom: 16.0),
                    title: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text('🚩 ', style: TextStyle(fontSize: 20)),
                        Text(
                          'संघस्थान डैशबोर्ड',
                          style: TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                            fontSize: 18,
                            fontFamily: 'Noto Sans Devanagari',
                          ),
                        ),
                      ],
                    ),
                    background: Container(
                      decoration: const BoxDecoration(
                        gradient: LinearGradient(
                          colors: [Color(0xFFFF6B00), Color(0xFFFF9E00)],
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                        ),
                      ),
                      child: Stack(
                        children: [
                          Positioned(
                            right: -20,
                            bottom: -20,
                            child: Icon(
                              Icons.flag,
                              size: 180,
                              color: Colors.white.withValues(alpha: 0.15),
                            ),
                          ),
                          Positioned(
                            left: 16,
                            top: 50,
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'शाखा आईडी: ${session.shakhaId ?? 0}',
                                  style: TextStyle(
                                    color: Colors.white.withValues(alpha: 0.9),
                                    fontSize: 14,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'मुख्य शिक्षक: ${session.userName ?? "आदरणीय"}',
                                  style: TextStyle(
                                    color: Colors.white.withValues(alpha: 0.9),
                                    fontSize: 13,
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

                // Dashboard scrollable sections
                SliverPadding(
                  padding: const EdgeInsets.all(16.0),
                  sliver: SliverList(
                    delegate: SliverChildListDelegate([
                      // Sync engine card
                      _buildSyncStatusCard(syncEngine),
                      const SizedBox(height: 16),

                      // Panchang card
                      _buildPanchangCard(data?.cachedPanchang, data?.todayRecord),
                      const SizedBox(height: 16),

                      // Metrics summary card
                      _buildStatsSummaryCard(data),
                      const SizedBox(height: 16),

                      // Action grids
                      _buildSectionHeader('दैनिक कार्य (Daily Actions)'),
                      _buildActionsGrid(context),
                      const SizedBox(height: 16),

                      _buildSectionHeader('रिपोर्ट्स और सेटिंग्स (Reports & Settings)'),
                      _buildReportsGrid(context),
                      const SizedBox(height: 16),

                      _buildSectionHeader('बौद्धिक और पठन सामग्री (Content & Reading)'),
                      _buildReadingGrid(context),
                      const SizedBox(height: 16),

                      _buildSectionHeader('उपकरण (Tools)'),
                      _buildToolsGrid(context),
                      const SizedBox(height: 24),

                      // Recent notices card list
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            '📢 हालिया सूचनाएं',
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
                          ),
                          TextButton(
                            onPressed: () => Navigator.push(
                              context,
                              MaterialPageRoute(builder: (ctx) => const NoticesScreen()),
                            ),
                            child: const Text('सभी देखें', style: TextStyle(color: Color(0xFFFF6B00), fontWeight: FontWeight.bold)),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      _buildRecentNoticesList(recentNotices),
                      const SizedBox(height: 24),

                      // Recent daily records list
                      const Text(
                        'हाल के रिकॉर्ड (Recent Records)',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
                      ),
                      const SizedBox(height: 12),
                      _buildRecentRecordsList(recentRecords),
                    ]),
                  ),
                ),
              ],
            );
          },
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
                      onPressed: syncing ? null : () => syncEngine.sync().then((_) => _refreshDashboardData()),
                      onLongPress: syncing ? null : () async {
                        if (mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('⏳ पूर्ण सिंक (Full Sync) शुरू हो रहा है...')),
                          );
                        }
                        await syncEngine.forceFullSync();
                        _refreshDashboardData();
                      },
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

  Widget _buildPanchangCard(Map<String, String>? cachedPanchang, DailyRecord? todayRecord) {
    final hasTodayRecordPanchang = todayRecord != null &&
        (todayRecord.tithi != null || todayRecord.paksh != null || todayRecord.hindiMonth != null);

    final tithi = hasTodayRecordPanchang ? todayRecord.tithi : cachedPanchang?['tithi'];
    final paksha = hasTodayRecordPanchang ? todayRecord.paksh : cachedPanchang?['paksha'];
    final month = hasTodayRecordPanchang ? todayRecord.hindiMonth : cachedPanchang?['vikram_month'];
    final vikram = hasTodayRecordPanchang ? todayRecord.vikramSamvat : cachedPanchang?['vikram_samvat'];
    final shaka = hasTodayRecordPanchang ? todayRecord.shakaSamvat : cachedPanchang?['shaka_samvat'];
    final yugabdh = hasTodayRecordPanchang ? todayRecord.yugabdh : '५१२८';

    final isDataAvailable = tithi != null && tithi.isNotEmpty;

    if (!isDataAvailable) {
      return Card(
        elevation: 4,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        color: const Color(0xFFFFF8E1),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.all(20.0),
          child: const Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Text(
                '🗓️ दैनिक पंचांग',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFFFF6B00)),
              ),
              SizedBox(height: 12),
              Text(
                'पंचांग डेटा ऑफ़लाइन उपलब्ध नहीं है। सिंक करने पर डेटा स्वतः आ जाएगा।',
                style: TextStyle(fontSize: 14, color: Colors.brown, fontWeight: FontWeight.w500),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      );
    }

    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      color: const Color(0xFFFFF8E1),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            const Text(
              '🗓️ दैनिक पंचांग',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFFFF6B00)),
            ),
            const SizedBox(height: 16),
            Wrap(
              alignment: WrapAlignment.center,
              spacing: 24,
              runSpacing: 12,
              children: [
                _buildPanchangDetailItem('तिथि', tithi),
                _buildPanchangDetailItem('पक्ष', paksha ?? '-'),
                _buildPanchangDetailItem('मास', month ?? '-'),
                _buildPanchangDetailItem('युगाब्द', yugabdh ?? '५१२८'),
                _buildPanchangDetailItem('विक्रम संवत', vikram ?? '-'),
                _buildPanchangDetailItem('शालिवाहन शक', shaka ?? '-'),
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

  Widget _buildStatsSummaryCard(DashboardData? data) {
    final totalSw = data?.totalSwayamsevaks ?? 0;
    final totalRec = data?.totalRecords ?? 0;
    final todayAttendanceText = data?.todayAttendanceText ?? (data?.todayRecord != null ? 'पूर्ण' : 'उपस्थिति अपूर्ण ⏳');

    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Row(
              children: [
                Icon(Icons.analytics_outlined, color: Color(0xFFFF6B00)),
                SizedBox(width: 8),
                Text(
                  'शाखा सारांश (Shakha Metrics)',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
                ),
              ],
            ),
            const Divider(height: 24),
            Row(
              children: [
                Expanded(
                  child: _buildStatMiniCard('👥 $totalSw', 'कुल स्वयंसेवक'),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildStatMiniCard('📊 $totalRec', 'कुल रिकॉर्ड'),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: data?.todayRecord != null ? const Color(0xFFE8F5E9) : const Color(0xFFFFF3E0),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: data?.todayRecord != null ? Colors.green.shade200 : Colors.amber.shade200,
                  width: 1,
                ),
              ),
              child: Row(
                children: [
                  Icon(
                    data?.todayRecord != null ? Icons.check_circle_outline : Icons.pending_actions_outlined,
                    color: data?.todayRecord != null ? Colors.green.shade800 : Colors.amber.shade800,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'आज की उपस्थिति (Today\'s Attendance)',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: data?.todayRecord != null ? Colors.green.shade800 : Colors.amber.shade800,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          todayAttendanceText,
                          style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            color: data?.todayRecord != null ? Colors.green.shade900 : Colors.amber.shade900,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatMiniCard(String value, String label) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            value,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: const TextStyle(fontSize: 11, color: Colors.grey, fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  Widget _buildActionsGrid(BuildContext context) {
    return GridView.count(
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
          ).then((_) => _refreshDashboardData()),
        ),
        _buildMenuCard(
          context,
          title: 'रिकॉर्ड कैलेंडर\n(Calendar)',
          icon: Icons.calendar_month,
          color: const Color(0xFF3F51B5),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (ctx) => const RecordsCalendarScreen()),
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
    );
  }

  Widget _buildReportsGrid(BuildContext context) {
    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      crossAxisSpacing: 16,
      mainAxisSpacing: 16,
      childAspectRatio: 1.15,
      children: [
        _buildMenuCard(
          context,
          title: 'रिकॉर्ड इतिहास\n(History)',
          icon: Icons.history_edu_outlined,
          color: const Color(0xFF7B1FA2),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (ctx) => const RecordsListScreen()),
          ).then((_) => _refreshDashboardData()),
        ),
        _buildMenuCard(
          context,
          title: 'मासिक रिपोर्ट\n(Monthly Report)',
          icon: Icons.analytics_outlined,
          color: const Color(0xFF00796B),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (ctx) => const MonthlyReportScreen()),
          ),
        ),
        _buildMenuCard(
          context,
          title: 'सूचना पट्ट\n(Notice Board)',
          icon: Icons.campaign_outlined,
          color: const Color(0xFFD32F2F),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (ctx) => const NoticesScreen()),
          ),
        ),
        _buildMenuCard(
          context,
          title: 'स्वयंसेवक सूची\n(Directory)',
          icon: Icons.people_outline,
          color: const Color(0xFF43A047),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (ctx) => const SwayamsevakScreen()),
          ).then((_) => _refreshDashboardData()),
        ),
      ],
    );
  }

  Widget _buildReadingGrid(BuildContext context) {
    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      crossAxisSpacing: 16,
      mainAxisSpacing: 16,
      childAspectRatio: 1.15,
      children: [
        _buildMenuCard(
          context,
          title: 'बौद्धिक सामग्री\n(Content Library)',
          icon: Icons.menu_book_outlined,
          color: const Color(0xFF8D6E63),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (ctx) => const ContentScreen()),
          ),
        ),
        _buildMenuCard(
          context,
          title: 'प्रेरक व्यक्तित्व\n(Vyaktitv)',
          icon: Icons.flag_outlined,
          color: const Color(0xFFFF8F00),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (ctx) => const VyaktitvScreen()),
          ),
        ),
        _buildMenuCard(
          context,
          title: 'पत्रक शाखा\n(Paper Shakha)',
          icon: Icons.print_outlined,
          color: const Color(0xFF00838F),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(
              builder: (ctx) => const NativePlaceholderScreen(
                type: PlaceholderType.paperShakha,
                title: '🖨️ पत्रक शाखा (Paper Shakha)',
              ),
            ),
          ),
        ),
        _buildMenuCard(
          context,
          title: 'डिजिटल फ्लिपबुक\n(Flipbook)',
          icon: Icons.menu_book,
          color: const Color(0xFF2E7D32),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(
              builder: (ctx) => const NativePlaceholderScreen(
                type: PlaceholderType.flipbook,
                title: '📱 डिजिटल वृत्त (Flipbook)',
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildToolsGrid(BuildContext context) {
    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      crossAxisSpacing: 16,
      mainAxisSpacing: 16,
      childAspectRatio: 1.15,
      children: [
        _buildMenuCard(
          context,
          title: 'दैनिक पंचांग\n(Panchang)',
          icon: Icons.brightness_5_outlined,
          color: const Color(0xFFE65100),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(builder: (ctx) => const PanchangScreen()),
          ),
        ),
        _buildMenuCard(
          context,
          title: 'बधाई पत्रक\n(Greetings)',
          icon: Icons.card_giftcard_outlined,
          color: const Color(0xFFC2185B),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(
              builder: (ctx) => const NativePlaceholderScreen(
                type: PlaceholderType.greetings,
                title: '🎨 बधाई जनरेटर (Greetings)',
              ),
            ),
          ),
        ),
        _buildMenuCard(
          context,
          title: 'शाखा सेटिंग्स\n(Settings)',
          icon: Icons.settings_outlined,
          color: const Color(0xFF37474F),
          onTap: () => Navigator.push(
            context,
            MaterialPageRoute(
              builder: (ctx) => const NativePlaceholderScreen(
                type: PlaceholderType.settings,
                title: '⚙️ शाखा सेटिंग्स',
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(top: 24.0, bottom: 12.0),
      child: Text(
        title,
        style: const TextStyle(
          fontSize: 18,
          fontWeight: FontWeight.bold,
          color: Color(0xFFE65100),
          fontFamily: 'Noto Sans Devanagari',
        ),
      ),
    );
  }

  Widget _buildMenuCard(
    BuildContext context, {
    required String title,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        splashColor: color.withValues(alpha: 0.1),
        child: Container(
          padding: const EdgeInsets.all(16.0),
          child: Column(
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

  Widget _buildRecentNoticesList(List<Event> notices) {
    if (notices.isEmpty) {
      return Card(
        elevation: 2,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        color: Colors.white,
        child: const Padding(
          padding: EdgeInsets.symmetric(vertical: 24.0, horizontal: 16.0),
          child: Center(
            child: Text(
              'कोई हालिया सूचना उपलब्ध नहीं है।',
              style: TextStyle(color: Colors.grey, fontStyle: FontStyle.italic),
            ),
          ),
        ),
      );
    }

    return ListView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: notices.length,
      itemBuilder: (ctx, index) {
        final notice = notices[index];
        return Card(
          elevation: 2,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          margin: const EdgeInsets.only(bottom: 12),
          color: Colors.white,
          child: ExpansionTile(
            leading: const CircleAvatar(
              backgroundColor: Color(0xFFFFF3E0),
              child: Icon(Icons.campaign, color: Color(0xFFFF6B00)),
            ),
            title: Text(
              notice.title,
              style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
            ),
             subtitle: Text(
              notice.eventDate.isNotEmpty
                  ? _formatNoticeDate(notice.eventDate)
                  : 'अज्ञात तिथि',
              style: const TextStyle(fontSize: 12, color: Colors.grey),
            ),
            children: [
              Padding(
                padding: const EdgeInsets.only(left: 16.0, right: 16.0, bottom: 16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Divider(),
                    const SizedBox(height: 6),
                    Text(
                      notice.description ?? 'कोई विवरण नहीं',
                      style: const TextStyle(fontSize: 14, color: Color(0xFF4E342E), height: 1.4),
                    ),
                    if (notice.eventTime.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          const Icon(Icons.access_time, size: 16, color: Color(0xFFFF6B00)),
                          const SizedBox(width: 6),
                          Text('समय: ${notice.eventTime}', style: const TextStyle(fontSize: 13, color: Colors.brown)),
                        ],
                      ),
                    ],
                    if (notice.location != null && notice.location!.isNotEmpty) ...[
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          const Icon(Icons.location_on_outlined, size: 16, color: Color(0xFFFF6B00)),
                          const SizedBox(width: 6),
                          Text('स्थान: ${notice.location}', style: const TextStyle(fontSize: 13, color: Colors.brown)),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  String _formatNoticeDate(String dateStr) {
    try {
      final date = DateTime.parse(dateStr);
      final List<String> hindiMonths = [
        'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून',
        'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'
      ];
      return '${date.day} ${hindiMonths[date.month - 1]} ${date.year}';
    } catch (_) {
      return dateStr;
    }
  }

  Widget _buildRecentRecordsList(List<Map<String, dynamic>> recentRecords) {
    if (recentRecords.isEmpty) {
      return Card(
        elevation: 2,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        color: Colors.white,
        child: const Padding(
          padding: EdgeInsets.all(24.0),
          child: Center(
            child: Text(
              'अभी तक कोई दैनिक रिकॉर्ड उपलब्ध नहीं है।',
              style: TextStyle(color: Colors.grey),
            ),
          ),
        ),
      );
    }

    return ListView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: recentRecords.length,
      itemBuilder: (ctx, index) {
        final rec = recentRecords[index];
        final recordDate = rec['record_date'] as String;
        
        // Format Hindi Date
        String formattedDate = recordDate;
        try {
          final date = DateTime.parse(recordDate);
          final List<String> hindiMonths = [
            'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून',
            'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'
          ];
          final monthName = hindiMonths[date.month - 1];
          formattedDate = '${date.day} $monthName ${date.year}';
        } catch (_) {}

        final present = rec['present_count'] ?? 0;
        final total = rec['total_count'] ?? 0;

        return Card(
          elevation: 2,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          margin: const EdgeInsets.only(bottom: 12),
          color: Colors.white,
          child: ListTile(
            leading: const CircleAvatar(
              backgroundColor: Color(0xFFE8F5E9),
              child: Icon(Icons.assignment_turned_in_outlined, color: Colors.green, size: 20),
            ),
            title: Text(
              formattedDate,
              style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
            ),
            subtitle: Text('उपस्थिति: $present / $total स्वयंसेवक'),
            trailing: const Icon(Icons.chevron_right, color: Colors.grey),
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (ctx) => RecordDetailScreen(
                    recordId: rec['id'] as int,
                    dateStr: recordDate,
                    formattedDate: formattedDate,
                  ),
                ),
              ).then((_) => _refreshDashboardData());
            },
          ),
        );
      },
    );
  }
}
