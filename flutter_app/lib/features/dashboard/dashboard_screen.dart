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
  final Panchang? panchangData;

  DashboardData({
    required this.totalSwayamsevaks,
    required this.totalRecords,
    this.todayAttendanceText,
    required this.recentNotices,
    required this.recentRecords,
    this.todayRecord,
    this.cachedPanchang,
    this.panchangData,
  });
}

class DashboardScreen extends ConsumerStatefulWidget {
  const DashboardScreen({super.key});

  @override
  ConsumerState<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends ConsumerState<DashboardScreen> {
  Future<DashboardData>? _dashboardDataFuture;
  int _currentTab = 0;

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
    final recentNotices = events.take(2).toList();

    // 4. Fetch cached panchang details
    Map<String, String>? panchang;
    Panchang? panchangObj;
    try {
      // Try from panchang_cache first (has all new fields)
      final cachedPanchang = await repo.getCachedPanchang(todayStr);
      if (cachedPanchang != null) {
        panchangObj = Panchang.fromJson(cachedPanchang);
        panchang = cachedPanchang.map((k, v) => MapEntry(k, v.toString()));
      } else {
        // Fallback to shared preferences
        final prefs = await SharedPreferences.getInstance();
        final cached = prefs.getString('cached_panchang');
        if (cached != null) {
          final decoded = Map<String, dynamic>.from(jsonDecode(cached));
          panchang = decoded.map((k, v) => MapEntry(k, v.toString()));
        }
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
      panchangData: panchangObj,
    );
  }

  void _showLogoutDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('लॉगआउट करें?', style: TextStyle(fontSize: 20)),
        content: const Text('क्या आप वाकई संघस्थान से लॉगआउट करना चाहते हैं?', style: TextStyle(fontSize: 16)),
        actions: [
          TextButton(
            child: const Text('रद्द करें', style: TextStyle(fontSize: 16)),
            onPressed: () => Navigator.pop(ctx),
          ),
          TextButton(
            child: const Text('लॉगआउट', style: TextStyle(color: Colors.red, fontSize: 16)),
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
    return Scaffold(
      body: _currentTab == 0
          ? _buildHomeTab()
          : _currentTab == 1
              ? const PanchangScreen()
              : _buildMoreTab(),
      bottomNavigationBar: _buildBottomNav(),
    );
  }

  Widget _buildBottomNav() {
    return Container(
      decoration: const BoxDecoration(
        boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 8, offset: Offset(0, -2))],
      ),
      child: BottomNavigationBar(
        currentIndex: _currentTab,
        onTap: (index) {
          setState(() {
            _currentTab = index;
          });
        },
        type: BottomNavigationBarType.fixed,
        backgroundColor: Colors.white,
        selectedItemColor: const Color(0xFFFF6B00),
        unselectedItemColor: Colors.grey,
        selectedFontSize: 15,
        unselectedFontSize: 13,
        iconSize: 30,
        selectedLabelStyle: const TextStyle(fontWeight: FontWeight.bold),
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.home_rounded),
            label: 'होम',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.brightness_5_rounded),
            label: 'पंचांग',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.menu_rounded),
            label: 'अन्य',
          ),
        ],
      ),
    );
  }

  // =============================================
  // TAB 1: HOME
  // =============================================
  Widget _buildHomeTab() {
    final session = ref.watch(sessionProvider);
    final syncEngine = ref.watch(syncEngineProvider);

    return RefreshIndicator(
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

          return CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: [
              // Collapsible Pinned SliverAppBar
              SliverAppBar(
                expandedHeight: 160.0,
                floating: false,
                pinned: true,
                backgroundColor: const Color(0xFFFF6B00),
                actions: [
                  IconButton(
                    icon: const Icon(Icons.sync, color: Colors.white, size: 28),
                    tooltip: 'सिंक करें',
                    onPressed: () async {
                      await syncEngine.sync();
                      _refreshDashboardData();
                    },
                  ),
                  IconButton(
                    icon: const Icon(Icons.logout, color: Colors.white, size: 28),
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
                        'संघस्थान',
                        style: TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 20,
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
                            size: 160,
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
                                'नमस्ते, ${session.userName ?? "मुख्य शिक्षक"} जी 🙏',
                                style: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.95),
                                  fontSize: 15,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                'शाखा आईडी: ${session.shakhaId ?? 0}',
                                style: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.85),
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
                padding: const EdgeInsets.all(14.0),
                sliver: SliverList(
                  delegate: SliverChildListDelegate([
                    // Sync status (compact)
                    _buildCompactSyncStatus(syncEngine),
                    const SizedBox(height: 14),

                    // Enhanced Panchang card
                    _buildEnhancedPanchangCard(data),
                    const SizedBox(height: 14),

                    // Quick Actions (only 2 primary)
                    _buildQuickActions(context),
                    const SizedBox(height: 14),

                    // Today's attendance status
                    _buildTodayAttendanceCard(data),
                    const SizedBox(height: 14),

                    // Recent notices
                    if (recentNotices.isNotEmpty) ...[
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            '📢 हालिया सूचनाएं',
                            style: TextStyle(fontSize: 19, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
                          ),
                          TextButton(
                            onPressed: () => Navigator.push(
                              context,
                              MaterialPageRoute(builder: (ctx) => const NoticesScreen()),
                            ),
                            child: const Text('सभी देखें ❯', style: TextStyle(color: Color(0xFFFF6B00), fontWeight: FontWeight.bold, fontSize: 15)),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      _buildRecentNoticesList(recentNotices),
                    ],
                    const SizedBox(height: 16),
                  ]),
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _buildCompactSyncStatus(SyncEngine syncEngine) {
    return ValueListenableBuilder<bool>(
      valueListenable: syncEngine.isSyncing,
      builder: (context, syncing, _) {
        return ValueListenableBuilder<String?>(
          valueListenable: syncEngine.syncError,
          builder: (context, error, _) {
            final hasError = error != null && error.isNotEmpty;
            // When synced and no error, show minimal green dot
            if (!syncing && !hasError) {
              return ValueListenableBuilder<String?>(
                valueListenable: syncEngine.lastSyncTime,
                builder: (context, lastSync, _) {
                  return Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    decoration: BoxDecoration(
                      color: const Color(0xFFE8F5E9),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.green.shade200),
                    ),
                    child: Row(
                      children: [
                        Container(
                          width: 10, height: 10,
                          decoration: const BoxDecoration(shape: BoxShape.circle, color: Colors.green),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: Text(
                            lastSync != null ? 'सिंक्ड ✓  •  $lastSync' : 'डेटा सिंक्ड ✓',
                            style: TextStyle(fontSize: 14, color: Colors.green.shade800, fontWeight: FontWeight.w500),
                          ),
                        ),
                        InkWell(
                          onTap: () => syncEngine.sync().then((_) => _refreshDashboardData()),
                          child: const Icon(Icons.sync, color: Colors.green, size: 22),
                        ),
                      ],
                    ),
                  );
                },
              );
            }

            // Syncing or error state
            return Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: hasError ? Colors.red.shade50 : Colors.amber.shade50,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: hasError ? Colors.red.shade200 : Colors.amber.shade200),
              ),
              child: Row(
                children: [
                  if (syncing)
                    const SizedBox(
                      width: 20, height: 20,
                      child: CircularProgressIndicator(color: Colors.amber, strokeWidth: 2),
                    )
                  else
                    Icon(Icons.error_outline, color: Colors.red.shade700, size: 22),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      syncing ? 'सिंक चालू है...' : 'सिंक विफल: $error',
                      style: TextStyle(fontSize: 14, fontWeight: FontWeight.w500,
                        color: syncing ? Colors.amber.shade800 : Colors.red.shade800),
                    ),
                  ),
                  if (hasError)
                    TextButton(
                      onPressed: () => syncEngine.sync().then((_) => _refreshDashboardData()),
                      child: const Text('पुनः', style: TextStyle(color: Color(0xFFFF6B00), fontWeight: FontWeight.bold)),
                    ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildEnhancedPanchangCard(DashboardData? data) {
    final panchangObj = data?.panchangData;
    final todayRecord = data?.todayRecord;
    final cachedPanchang = data?.cachedPanchang;

    // Resolve panchang values
    final hasTodayRecordPanchang = todayRecord != null &&
        (todayRecord.tithi != null || todayRecord.paksh != null || todayRecord.hindiMonth != null);

    final tithi = panchangObj?.tithi ?? (hasTodayRecordPanchang ? todayRecord.tithi : cachedPanchang?['tithi']);
    final paksha = panchangObj?.paksha ?? (hasTodayRecordPanchang ? todayRecord.paksh : cachedPanchang?['paksha']);
    final nakshatra = panchangObj?.nakshatra ?? cachedPanchang?['nakshatra'];
    final yoga = panchangObj?.yoga ?? cachedPanchang?['yoga'];
    final sunrise = panchangObj?.sunrise ?? cachedPanchang?['sunrise'];
    final sunset = panchangObj?.sunset ?? cachedPanchang?['sunset'];
    final rahukaal = panchangObj?.rahukaal ?? cachedPanchang?['rahukaal'];

    final isDataAvailable = tithi != null && tithi.isNotEmpty;

    if (!isDataAvailable) {
      return Card(
        elevation: 4,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        color: const Color(0xFFFFF8E1),
        child: InkWell(
          onTap: () => setState(() => _currentTab = 1),
          borderRadius: BorderRadius.circular(16),
          child: Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20.0),
            child: const Column(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Text('🗓️ दैनिक पंचांग', style: TextStyle(fontSize: 19, fontWeight: FontWeight.bold, color: Color(0xFFFF6B00))),
                SizedBox(height: 12),
                Text(
                  'पंचांग डेटा उपलब्ध नहीं है। सिंक करने पर डेटा आ जाएगा।',
                  style: TextStyle(fontSize: 16, color: Colors.brown, fontWeight: FontWeight.w500),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
        ),
      );
    }

    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: InkWell(
        onTap: () => setState(() => _currentTab = 1),
        borderRadius: BorderRadius.circular(16),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            gradient: const LinearGradient(
              colors: [Color(0xFFFFF8E1), Color(0xFFFFFDF5)],
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
            ),
          ),
          width: double.infinity,
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text('🗓️ दैनिक पंचांग', style: TextStyle(fontSize: 19, fontWeight: FontWeight.bold, color: Color(0xFFFF6B00))),
                  const Spacer(),
                  Icon(Icons.arrow_forward_ios, color: Colors.orange.shade300, size: 18),
                ],
              ),
              const SizedBox(height: 14),
              // Row 1: Tithi + Nakshatra
              Row(
                children: [
                  Expanded(child: _buildPanchangMiniItem('तिथि', tithi, const Color(0xFFFF6B00))),
                  const SizedBox(width: 8),
                  Expanded(child: _buildPanchangMiniItem('नक्षत्र', nakshatra ?? '-', const Color(0xFFE65100))),
                ],
              ),
              const SizedBox(height: 10),
              // Row 2: Yoga + Paksha
              Row(
                children: [
                  Expanded(child: _buildPanchangMiniItem('योग', yoga ?? '-', const Color(0xFF6A1B9A))),
                  const SizedBox(width: 8),
                  Expanded(child: _buildPanchangMiniItem('पक्ष', paksha ?? '-', const Color(0xFF00695C))),
                ],
              ),
              const SizedBox(height: 10),
              // Row 3: Sunrise, Sunset, Rahukaal
              Container(
                padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.7),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceAround,
                  children: [
                    _buildPanchangTimeMini('🌅', sunrise ?? '-'),
                    Container(height: 24, width: 1, color: Colors.orange.shade200),
                    _buildPanchangTimeMini('🌇', sunset ?? '-'),
                    Container(height: 24, width: 1, color: Colors.orange.shade200),
                    _buildPanchangTimeMini('⏰', rahukaal ?? '-', isRahu: true),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPanchangMiniItem(String label, String value, Color color) {
    final displayVal = (value.isEmpty || value == '-' || value == '—') ? '—' : value;
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.6),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontSize: 13, color: Colors.brown, fontWeight: FontWeight.w600)),
          const SizedBox(height: 2),
          Text(displayVal, style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: color)),
        ],
      ),
    );
  }

  Widget _buildPanchangTimeMini(String emoji, String value, {bool isRahu = false}) {
    final displayVal = (value.isEmpty || value == '-' || value == '—') ? '—' : value;
    return Column(
      children: [
        Text(emoji, style: const TextStyle(fontSize: 16)),
        const SizedBox(height: 2),
        Text(
          displayVal,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.bold,
            color: isRahu ? const Color(0xFFC62828) : const Color(0xFF5D4037),
          ),
        ),
      ],
    );
  }

  Widget _buildQuickActions(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _buildQuickActionButton(
            context,
            title: 'उपस्थिति भरें',
            subtitle: 'Attendance',
            icon: Icons.assignment_outlined,
            color: const Color(0xFFE55B00),
            onTap: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (ctx) => const DailyRecordScreen()),
            ).then((_) => _refreshDashboardData()),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _buildQuickActionButton(
            context,
            title: 'रिकॉर्ड कैलेंडर',
            subtitle: 'Calendar',
            icon: Icons.calendar_month,
            color: const Color(0xFF3F51B5),
            onTap: () => Navigator.push(
              context,
              MaterialPageRoute(builder: (ctx) => const RecordsCalendarScreen()),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildQuickActionButton(
    BuildContext context, {
    required String title,
    required String subtitle,
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
          padding: const EdgeInsets.symmetric(vertical: 20.0, horizontal: 14.0),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, size: 32, color: color),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
                    ),
                    Text(
                      subtitle,
                      style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                    ),
                  ],
                ),
              ),
              Icon(Icons.arrow_forward_ios, color: Colors.grey.shade400, size: 16),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTodayAttendanceCard(DashboardData? data) {
    final todayAttendanceText = data?.todayAttendanceText ?? (data?.todayRecord != null ? 'पूर्ण' : 'उपस्थिति अपूर्ण ⏳');
    final hasRecord = data?.todayRecord != null;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      decoration: BoxDecoration(
        color: hasRecord ? const Color(0xFFE8F5E9) : const Color(0xFFFFF3E0),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: hasRecord ? Colors.green.shade200 : Colors.amber.shade200, width: 1),
      ),
      child: Row(
        children: [
          Icon(
            hasRecord ? Icons.check_circle_outline : Icons.pending_actions_outlined,
            color: hasRecord ? Colors.green.shade800 : Colors.amber.shade800,
            size: 28,
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'आज की उपस्थिति',
                  style: TextStyle(
                    fontSize: 14, fontWeight: FontWeight.bold,
                    color: hasRecord ? Colors.green.shade800 : Colors.amber.shade800,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  todayAttendanceText,
                  style: TextStyle(
                    fontSize: 17, fontWeight: FontWeight.bold,
                    color: hasRecord ? Colors.green.shade900 : Colors.amber.shade900,
                  ),
                ),
              ],
            ),
          ),
          // Quick stats
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text('👥 ${data?.totalSwayamsevaks ?? 0}', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF5D4037))),
              Text('📊 ${data?.totalRecords ?? 0} रिकॉर्ड', style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildRecentNoticesList(List<Event> notices) {
    return ListView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: notices.length,
      itemBuilder: (ctx, index) {
        final notice = notices[index];
        return Card(
          elevation: 2,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          margin: const EdgeInsets.only(bottom: 10),
          color: Colors.white,
          child: ListTile(
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            leading: const CircleAvatar(
              backgroundColor: Color(0xFFFFF3E0),
              radius: 22,
              child: Icon(Icons.campaign, color: Color(0xFFFF6B00), size: 22),
            ),
            title: Text(
              notice.title,
              style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF5D4037), fontSize: 16),
            ),
            subtitle: Text(
              notice.eventDate.isNotEmpty ? _formatNoticeDate(notice.eventDate) : '',
              style: const TextStyle(fontSize: 13, color: Colors.grey),
            ),
            trailing: const Icon(Icons.chevron_right, color: Colors.grey),
            onTap: () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const NoticesScreen())),
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

  // =============================================
  // TAB 3: MORE (अन्य)
  // =============================================
  Widget _buildMoreTab() {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '☰ सभी सेवाएं',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 20),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        automaticallyImplyLeading: false,
      ),
      body: Container(
        color: const Color(0xFFF9F6F0),
        child: ListView(
          padding: const EdgeInsets.all(12),
          children: [
            // Section: Daily Tasks
            _buildMenuSectionHeader('📋 दैनिक कार्य'),
            _buildMenuItem(
              icon: Icons.assignment_outlined,
              color: const Color(0xFFE55B00),
              title: 'उपस्थिति भरें',
              subtitle: 'दैनिक हाजिरी भरें और गतिविधि दर्ज करें',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const DailyRecordScreen())).then((_) => _refreshDashboardData()),
            ),
            _buildMenuItem(
              icon: Icons.calendar_month,
              color: const Color(0xFF3F51B5),
              title: 'रिकॉर्ड कैलेंडर',
              subtitle: 'कैलेंडर दृश्य में रिकॉर्ड देखें',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const RecordsCalendarScreen())),
            ),
            _buildMenuItem(
              icon: Icons.calendar_today_outlined,
              color: const Color(0xFF1E88E5),
              title: 'समय-सारणी',
              subtitle: 'साप्ताहिक शाखा कार्यक्रम सूची',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const TimetableScreen())),
            ),
            _buildMenuItem(
              icon: Icons.timer_outlined,
              color: const Color(0xFFF4511E),
              title: 'शाखा टाइमर',
              subtitle: 'शाखा संचालन हेतु सीटी टाइमर',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const ShakhaTimerScreen())),
            ),

            const SizedBox(height: 8),
            const Divider(),

            // Section: Reports
            _buildMenuSectionHeader('📊 रिपोर्ट एवं रिकॉर्ड'),
            _buildMenuItem(
              icon: Icons.history_edu_outlined,
              color: const Color(0xFF7B1FA2),
              title: 'रिकॉर्ड इतिहास',
              subtitle: 'सभी दैनिक रिकॉर्ड्स की सूची',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const RecordsListScreen())).then((_) => _refreshDashboardData()),
            ),
            _buildMenuItem(
              icon: Icons.analytics_outlined,
              color: const Color(0xFF00796B),
              title: 'मासिक रिपोर्ट',
              subtitle: 'मासिक उपस्थिति एवं गतिविधि सारांश',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const MonthlyReportScreen())),
            ),

            const SizedBox(height: 8),
            const Divider(),

            // Section: Information
            _buildMenuSectionHeader('📢 सूचना'),
            _buildMenuItem(
              icon: Icons.campaign_outlined,
              color: const Color(0xFFD32F2F),
              title: 'सूचना पट्ट',
              subtitle: 'शाखा से संबंधित सभी सूचनाएं',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const NoticesScreen())),
            ),
            _buildMenuItem(
              icon: Icons.people_outline,
              color: const Color(0xFF43A047),
              title: 'स्वयंसेवक सूची',
              subtitle: 'सभी स्वयंसेवकों की विस्तृत सूची',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const SwayamsevakScreen())).then((_) => _refreshDashboardData()),
            ),

            const SizedBox(height: 8),
            const Divider(),

            // Section: Content
            _buildMenuSectionHeader('📖 बौद्धिक सामग्री'),
            _buildMenuItem(
              icon: Icons.menu_book_outlined,
              color: const Color(0xFF8D6E63),
              title: 'बौद्धिक सामग्री',
              subtitle: 'सुभाषित, अमृतवचन, गीत एवं घोषणाएं',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const ContentScreen())),
            ),
            _buildMenuItem(
              icon: Icons.flag_outlined,
              color: const Color(0xFFFF8F00),
              title: 'प्रेरक व्यक्तित्व',
              subtitle: 'महान व्यक्तित्वों की प्रेरणादायक कथाएं',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const VyaktitvScreen())),
            ),
            _buildMenuItem(
              icon: Icons.print_outlined,
              color: const Color(0xFF00838F),
              title: 'पत्रक शाखा',
              subtitle: 'शाखा कार्यक्रम का प्रिंट करने योग्य प्रारूप',
              onTap: () => Navigator.push(context, MaterialPageRoute(
                builder: (ctx) => const NativePlaceholderScreen(type: PlaceholderType.paperShakha, title: '🖨️ पत्रक शाखा'),
              )),
            ),
            _buildMenuItem(
              icon: Icons.menu_book,
              color: const Color(0xFF2E7D32),
              title: 'डिजिटल फ्लिपबुक',
              subtitle: 'डिजिटल वृत्त - स्वाइप करके पढ़ें',
              onTap: () => Navigator.push(context, MaterialPageRoute(
                builder: (ctx) => const NativePlaceholderScreen(type: PlaceholderType.flipbook, title: '📱 डिजिटल वृत्त'),
              )),
            ),

            const SizedBox(height: 8),
            const Divider(),

            // Section: Tools
            _buildMenuSectionHeader('🔧 उपकरण'),
            _buildMenuItem(
              icon: Icons.brightness_5_outlined,
              color: const Color(0xFFE65100),
              title: 'दैनिक पंचांग',
              subtitle: 'तिथि, नक्षत्र, योग, राहुकाल एवं शुभ मुहूर्त',
              onTap: () => setState(() => _currentTab = 1),
            ),
            _buildMenuItem(
              icon: Icons.card_giftcard_outlined,
              color: const Color(0xFFC2185B),
              title: 'बधाई पत्रक',
              subtitle: 'शुभकामना संदेश बनाएं और साझा करें',
              onTap: () => Navigator.push(context, MaterialPageRoute(
                builder: (ctx) => const NativePlaceholderScreen(type: PlaceholderType.greetings, title: '🎨 बधाई जनरेटर'),
              )),
            ),

            const SizedBox(height: 8),
            const Divider(),

            // Section: Settings
            _buildMenuSectionHeader('⚙️ सेटिंग्स'),
            _buildMenuItem(
              icon: Icons.settings_outlined,
              color: const Color(0xFF37474F),
              title: 'शाखा सेटिंग्स',
              subtitle: 'ऐप की सेटिंग्स और विकल्प',
              onTap: () => Navigator.push(context, MaterialPageRoute(
                builder: (ctx) => const NativePlaceholderScreen(type: PlaceholderType.settings, title: '⚙️ शाखा सेटिंग्स'),
              )),
            ),
            _buildMenuItem(
              icon: Icons.logout,
              color: Colors.red.shade700,
              title: 'लॉगआउट',
              subtitle: 'संघस्थान से बाहर निकलें',
              onTap: () => _showLogoutDialog(context),
            ),

            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  Widget _buildMenuSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(top: 16.0, bottom: 8.0, left: 4.0),
      child: Text(
        title,
        style: const TextStyle(
          fontSize: 18,
          fontWeight: FontWeight.bold,
          color: Color(0xFFE65100),
        ),
      ),
    );
  }

  Widget _buildMenuItem({
    required IconData icon,
    required Color color,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return Card(
      elevation: 2,
      margin: const EdgeInsets.only(bottom: 6),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        leading: Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, color: color, size: 28),
        ),
        title: Text(
          title,
          style: const TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: Color(0xFF5D4037)),
        ),
        subtitle: Text(
          subtitle,
          style: const TextStyle(fontSize: 13, color: Colors.grey),
        ),
        trailing: const Icon(Icons.chevron_right, color: Colors.grey),
        onTap: onTap,
      ),
    );
  }
}
