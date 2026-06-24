import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:table_calendar/table_calendar.dart';
import '../../core/models/models.dart';
import '../../core/providers/providers.dart';
import '../../core/sync/sync_engine.dart';
import '../../core/config/app_config.dart';
import '../auth/login_screen.dart';
import '../swayamsevaks/swayamsevak_screen.dart';
import '../records/daily_record_screen.dart';
import '../content/content_screen.dart';
import '../records/records_calendar_screen.dart';
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
  final Map<String, Map<String, dynamic>> recordsMap;

  DashboardData({
    required this.totalSwayamsevaks,
    required this.totalRecords,
    this.todayAttendanceText,
    required this.recentNotices,
    required this.recentRecords,
    this.todayRecord,
    this.cachedPanchang,
    this.panchangData,
    required this.recordsMap,
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
      // Auto-sync in the background on startup if logged in
      if (ref.read(sessionProvider).isLoggedIn) {
        syncEngine.sync();
      }
      
      // Check for updates
      _checkForUpdates();
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

  Future<void> _checkForUpdates() async {
    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get('/api/app_version.php');
      if (response.statusCode == 200 && response.data != null) {
        final data = response.data;
        if (data['status'] == 'success') {
          final serverVersionCode = data['version_code'] as int;
          final serverVersionName = data['version_name'] as String;
          final downloadUrl = data['download_url'] as String;
          final forceUpdate = data['force_update'] as bool? ?? false;
          final message = data['message'] as String? ?? 'नया संस्करण उपलब्ध है। कृपया अपडेट करें।';

          if (serverVersionCode > AppConfig.versionCode) {
            if (!mounted) return;
            
            showDialog(
              context: context,
              barrierDismissible: !forceUpdate,
              builder: (context) {
                return PopScope(
                  canPop: !forceUpdate,
                  child: AlertDialog(
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                    title: const Row(
                      children: [
                        Icon(Icons.system_update_alt, color: Color(0xFFFF6B00), size: 28),
                        SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            'नया अपडेट उपलब्ध है 🚩',
                            style: TextStyle(
                              fontSize: 22,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFFFF6B00),
                            ),
                          ),
                        ),
                      ],
                    ),
                    content: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'संस्करण (Version): $serverVersionName',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                        ),
                        const SizedBox(height: 12),
                        Text(
                          message,
                          style: const TextStyle(fontSize: 16),
                        ),
                        if (forceUpdate) ...[
                          const SizedBox(height: 16),
                          Text(
                            '* सुचारू संचालन के लिए यह अपडेट अनिवार्य है।',
                            style: TextStyle(color: Colors.red.shade700, fontSize: 14, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ],
                    ),
                    actions: [
                      if (!forceUpdate)
                        TextButton(
                          onPressed: () => Navigator.pop(context),
                          child: const Text(
                            'बाद में (Later)',
                            style: TextStyle(color: Colors.grey, fontSize: 16),
                          ),
                        ),
                      ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFFFF6B00),
                          foregroundColor: Colors.white,
                          minimumSize: const Size(120, 48),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                        onPressed: () async {
                          final Uri url = Uri.parse(downloadUrl);
                          if (await canLaunchUrl(url)) {
                            await launchUrl(url, mode: LaunchMode.externalApplication);
                          } else {
                            if (context.mounted) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('अपडेट लिंक खोलने में असमर्थ।')),
                              );
                            }
                          }
                        },
                        child: const Text(
                          'अभी अपडेट करें (Update Now)',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                );
              },
            );
          }
        }
      }
    } catch (e) {
      debugPrint('Error checking for updates: $e');
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

    // 5. Fetch all records for the calendar
    final Map<String, Map<String, dynamic>> recordsMap = {};
    try {
      final records = await repo.getAllDailyRecords();
      for (var rec in records) {
        final dateStr = rec['record_date'] as String;
        recordsMap[dateStr] = rec;
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
      recordsMap: recordsMap,
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
      drawer: _buildDrawer(),
      body: _buildHomeTab(),
    );
  }

  Widget _buildDrawer() {
    final session = ref.watch(sessionProvider);
    final isGuest = !session.isLoggedIn;
    final isSwayamsevak = session.role == 'swayamsevak';

    return Drawer(
      child: Container(
        color: Theme.of(context).colorScheme.surfaceContainerLowest,
        child: Column(
          children: [
            UserAccountsDrawerHeader(
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [Color(0xFFFF6B00), Color(0xFFFF9E00)],
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                ),
              ),
              accountName: Text(
                isGuest ? "अतिथि स्वयंसेवक" : (session.userName ?? "स्वयंसेवक जी"),
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
              ),
              accountEmail: Text(
                isGuest ? "लॉगिन नहीं है" : 'शाखा आईडी: ${session.shakhaId ?? 0}',
                style: const TextStyle(fontSize: 14),
              ),
              currentAccountPicture: const CircleAvatar(
                backgroundColor: Colors.white,
                child: Text('🚩', style: TextStyle(fontSize: 24)),
              ),
            ),
            Expanded(
              child: ListView(
                padding: EdgeInsets.zero,
                children: [
                  if (isGuest) ...[
                    _buildDrawerItem(
                      Icons.menu_book_outlined,
                      'बौद्धिक सामग्री',
                      () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const ContentScreen())),
                    ),
                    _buildDrawerItem(
                      Icons.calendar_month,
                      'दैनिक पंचांग',
                      () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const PanchangScreen())),
                    ),
                    const Divider(),
                    ListTile(
                      leading: const Icon(Icons.login, color: Color(0xFFFF6B00)),
                      title: const Text('लॉगिन करें (Shakha Login)', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFFF6B00))),
                      onTap: () {
                        Navigator.pop(context);
                        Navigator.push(context, MaterialPageRoute(builder: (ctx) => const LoginScreen()));
                      },
                    ),
                  ] else if (isSwayamsevak) ...[
                    _buildDrawerItem(
                      Icons.people_outline,
                      'स्वयंसेवक एवं गट सूची',
                      () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const SwayamsevakScreen())).then((_) => _refreshDashboardData()),
                    ),
                    _buildDrawerItem(
                      Icons.calendar_month,
                      'रिकॉर्ड कैलेंडर',
                      () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const RecordsCalendarScreen())),
                    ),
                    _buildDrawerItem(
                      Icons.menu_book_outlined,
                      'बौद्धिक सामग्री',
                      () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const ContentScreen())),
                    ),
                    _buildDrawerItem(
                      Icons.explore_outlined,
                      'दैनिक पंचांग',
                      () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const PanchangScreen())),
                    ),
                    const Divider(),
                    ListTile(
                      leading: Icon(Icons.logout, color: Colors.red.shade700),
                      title: Text('लॉगआउट', style: TextStyle(color: Colors.red.shade700, fontWeight: FontWeight.bold)),
                      onTap: () {
                        Navigator.pop(context);
                        _showLogoutDialog(context);
                      },
                    ),
                  ] else ...[
                    // Mukhya Shikshak / Admin
                    _buildDrawerItem(
                      Icons.assignment_outlined,
                      'उपस्थिति भरें',
                      () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const DailyRecordScreen())).then((_) => _refreshDashboardData()),
                    ),
                    _buildDrawerItem(
                      Icons.calendar_month,
                      'रिकॉर्ड कैलेंडर',
                      () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const RecordsCalendarScreen())),
                    ),
                    _buildDrawerItem(
                      Icons.people_outline,
                      'स्वयंसेवक सूची',
                      () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const SwayamsevakScreen())).then((_) => _refreshDashboardData()),
                    ),
                    const Divider(),
                    _buildDrawerItem(
                      Icons.card_giftcard_outlined,
                      'बधाई पत्रक',
                      () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const NativePlaceholderScreen(type: PlaceholderType.greetings, title: '🎨 बधाई जनरेटर'))),
                    ),
                    _buildDrawerItem(
                      Icons.settings_outlined,
                      'शाखा सेटिंग्स',
                      () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const NativePlaceholderScreen(type: PlaceholderType.settings, title: '⚙️ शाखा सेटिंग्स'))),
                    ),
                    const Divider(),
                    ListTile(
                      leading: const Icon(Icons.sync, color: Colors.green),
                      title: const Text('सिंक करें', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.green)),
                      onTap: () {
                        Navigator.pop(context);
                        ref.read(syncEngineProvider).sync().then((_) => _refreshDashboardData());
                      },
                    ),
                    ListTile(
                      leading: Icon(Icons.logout, color: Colors.red.shade700),
                      title: Text('लॉगआउट', style: TextStyle(color: Colors.red.shade700, fontWeight: FontWeight.bold)),
                      onTap: () {
                        Navigator.pop(context);
                        _showLogoutDialog(context);
                      },
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDrawerItem(IconData icon, String title, VoidCallback onTap) {
    return ListTile(
      leading: Icon(icon, color: Theme.of(context).colorScheme.onSurface),
      title: Text(title, style: TextStyle(fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.onSurface, fontSize: 16)),
      trailing: const Icon(Icons.chevron_right, color: Colors.grey, size: 20),
      onTap: () {
        Navigator.pop(context); // Close drawer
        onTap();
      },
    );
  }

  // =============================================
  // HOME TAB
  // =============================================
  Widget _buildDashboardCalendarCard(DashboardData? data) {
    final session = ref.watch(sessionProvider);
    final recordsMap = data?.recordsMap ?? {};

    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(12.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 8.0, vertical: 4.0),
              child: Row(
                children: [
                  Icon(Icons.calendar_month, color: Color(0xFFFF6B00), size: 24),
                  SizedBox(width: 8),
                  Text(
                    'शाखा उपस्थिति कैलेंडर',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFFFF6B00)),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 8),
            TableCalendar(
              firstDay: DateTime.utc(2025, 1, 1),
              lastDay: DateTime.utc(2035, 12, 31),
              focusedDay: DateTime.now(),
              currentDay: DateTime.now(),
              calendarFormat: CalendarFormat.month,
              headerStyle: const HeaderStyle(
                formatButtonVisible: false,
                titleCentered: true,
                titleTextStyle: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.brown),
              ),
              calendarStyle: CalendarStyle(
                todayDecoration: BoxDecoration(
                  color: const Color(0xFFFF6B00).withOpacity(0.15),
                  shape: BoxShape.circle,
                  border: Border.all(color: const Color(0xFFFF6B00), width: 1.5),
                ),
                todayTextStyle: const TextStyle(color: Color(0xFFFF6B00), fontWeight: FontWeight.bold),
                selectedDecoration: const BoxDecoration(
                  color: Color(0xFFFF6B00),
                  shape: BoxShape.circle,
                ),
                markerSize: 6,
                markersAnchor: 1.4,
              ),
              eventLoader: (day) {
                if (!session.isLoggedIn) return [];
                final dateStr = '${day.year.toString().padLeft(4, '0')}-${day.month.toString().padLeft(2, '0')}-${day.day.toString().padLeft(2, '0')}';
                final record = recordsMap[dateStr];
                return record != null ? [record] : [];
              },
              calendarBuilders: CalendarBuilders(
                markerBuilder: (context, date, events) {
                  if (events.isNotEmpty) {
                    return Positioned(
                      bottom: 4,
                      child: Container(
                        width: 6,
                        height: 6,
                        decoration: const BoxDecoration(
                          shape: BoxShape.circle,
                          color: Color(0xFFFF6B00), // Orange dot for shakha presence
                        ),
                      ),
                    );
                  }
                  return null;
                },
              ),
            ),
            if (session.isLoggedIn) ...[
              const SizedBox(height: 8),
              const Center(
                child: Text(
                  '🟠 बिंदु वाले दिन शाखा संचालित की गई थी।',
                  style: TextStyle(fontSize: 12, color: Colors.grey),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  // =============================================
  // HOME TAB
  // =============================================
  Widget _buildHomeTab() {
    final session = ref.watch(sessionProvider);
    final syncEngine = ref.watch(syncEngineProvider);

    return RefreshIndicator(
      onRefresh: () async {
        if (session.isLoggedIn) {
          await syncEngine.sync();
        }
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
                  if (session.isLoggedIn)
                    IconButton(
                      icon: const Icon(Icons.sync, color: Colors.white, size: 28),
                      tooltip: 'सिंक करें',
                      onPressed: () async {
                        await syncEngine.sync();
                        _refreshDashboardData();
                      },
                    ),
                ],
                flexibleSpace: FlexibleSpaceBar(
                  titlePadding: const EdgeInsets.only(left: 60.0, bottom: 16.0),
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
                    decoration: BoxDecoration(
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
                          top: 80,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                session.isLoggedIn
                                    ? 'नमस्ते, ${session.userName ?? "स्वयंसेवक"} जी 🙏'
                                    : 'नमस्ते, अतिथि जी 🙏',
                                style: TextStyle(
                                  color: Colors.white.withValues(alpha: 0.95),
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
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
                    if (session.isLoggedIn) ...[
                      _buildCompactSyncStatus(syncEngine),
                      const SizedBox(height: 14),
                    ],

                    // 1. Panchang card
                    _buildEnhancedPanchangCard(data),
                    const SizedBox(height: 16),

                    // 2. Embedded Table Calendar (Front Page Always)
                    _buildDashboardCalendarCard(data),
                    const SizedBox(height: 16),

                    // 3. & 4. Baudhik Samagri & Prerak Vyaktitv
                    _buildMenuSectionHeader('📖 ज्ञान और प्रेरणा'),
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
                    
                    // Guest login card
                    if (!session.isLoggedIn) ...[
                      const SizedBox(height: 16),
                      Card(
                        elevation: 4,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        color: const Color(0xFFFFF3E0),
                        child: Padding(
                          padding: const EdgeInsets.all(20.0),
                          child: Column(
                            children: [
                              const Icon(Icons.lock_outline, color: Color(0xFFFF6B00), size: 48),
                              const SizedBox(height: 12),
                              const Text(
                                'सुरक्षित शाखा प्रबंधन पोर्टल',
                                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFFE65100)),
                              ),
                              const SizedBox(height: 8),
                              const Text(
                                'सूचना पट्ट, उपस्थिति रिकॉर्ड और स्वयंसेवक सूची देखने के लिए कृपया लॉगिन करें।',
                                textAlign: TextAlign.center,
                                style: TextStyle(fontSize: 14, color: Colors.black87),
                              ),
                              const SizedBox(height: 16),
                              ElevatedButton(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFFFF6B00),
                                  foregroundColor: Colors.white,
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                ),
                                onPressed: () {
                                  Navigator.push(context, MaterialPageRoute(builder: (ctx) => const LoginScreen()));
                                },
                                child: const Text('लॉगिन करें (Login Now)'),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],

                    // 5. Soochnayein (Notices) - only for logged in users
                    if (session.isLoggedIn) ...[
                      const SizedBox(height: 16),
                      _buildMenuSectionHeader('📢 शाखा की जानकारी'),
                      _buildMenuItem(
                        icon: Icons.campaign_outlined,
                        color: const Color(0xFFD32F2F),
                        title: 'सूचना पट्ट',
                        subtitle: 'शाखा से संबंधित सभी सूचनाएं',
                        onTap: () => Navigator.push(context, MaterialPageRoute(builder: (ctx) => const NoticesScreen())),
                      ),
                      const SizedBox(height: 12),

                      // Recent notices list
                      if (recentNotices.isNotEmpty) ...[
                        Text(
                          ' हालिया सूचनाएं:',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.onSurface),
                        ),
                        const SizedBox(height: 8),
                        _buildRecentNoticesList(recentNotices),
                      ],
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
                          decoration: BoxDecoration(shape: BoxShape.circle, color: Colors.green),
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
        color: Theme.of(context).cardColor,
        child: InkWell(
          onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PanchangScreen())),
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
        onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PanchangScreen())),
        borderRadius: BorderRadius.circular(16),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            gradient: LinearGradient(
              colors: [Theme.of(context).cardColor, Theme.of(context).cardColor],
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
            color: isRahu ? const Color(0xFFC62828) : Theme.of(context).colorScheme.onSurface,
          ),
        ),
      ],
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
              style: TextStyle(fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.onSurface, fontSize: 16),
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
          style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: Theme.of(context).colorScheme.onSurface),
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
