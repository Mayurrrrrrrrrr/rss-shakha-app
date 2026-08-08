import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/event_providers.dart';
import 'participant_list_screen.dart';
import 'attendance_screen.dart';
import 'spot_entry_screen.dart';
import 'attendance_duty_screen.dart';

class EventDashboardScreen extends ConsumerStatefulWidget {
  const EventDashboardScreen({Key? key}) : super(key: key);

  @override
  ConsumerState<EventDashboardScreen> createState() => _EventDashboardScreenState();
}

class _EventDashboardScreenState extends ConsumerState<EventDashboardScreen> {
  Future<void> _refresh() async {
    ref.invalidate(eventDashboardProvider);
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.invalidate(eventDashboardProvider);
    });
  }

  @override
  Widget build(BuildContext context) {
    final currentEvent = ref.watch(eventSessionProvider);
    final dashboardState = ref.watch(eventDashboardProvider);

    return Scaffold(
      backgroundColor: const Color(0xFFF9F6F0),
      appBar: AppBar(
        title: Text(currentEvent?.eventName ?? 'डैशबोर्ड', style: const TextStyle(fontFamily: 'Noto Sans Devanagari', fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFFFF6B00),
        foregroundColor: Colors.white,
      ),
      drawer: _buildDrawer(context),
      body: RefreshIndicator(
        onRefresh: _refresh,
        color: const Color(0xFFFF6B00),
        child: dashboardState.when(
          loading: () => const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00))),
          error: (err, stack) => Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Icon(Icons.error_outline, color: Colors.red, size: 48),
                const SizedBox(height: 16),
                Text('त्रुटि: $err', textAlign: TextAlign.center, style: const TextStyle(color: Colors.red)),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: _refresh,
                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFFF6B00), foregroundColor: Colors.white),
                  child: const Text('पुनः प्रयास करें'),
                ),
              ],
            ),
          ),
          data: (state) => SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.all(16.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _buildStatsRow(state),
                const SizedBox(height: 16),
                _buildFoodSection(),
                const SizedBox(height: 16),
                _buildNextActivity(),
                const SizedBox(height: 16),
                _buildQuickActions(context),
                const SizedBox(height: 16),
                _buildPendingTasks(),
              ],
            ),
          ),
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {
          Navigator.push(context, MaterialPageRoute(builder: (context) => const SpotEntryScreen(sessionId: 1)));
        },
        backgroundColor: const Color(0xFFFF6B00),
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text('स्पॉट एंट्री', style: TextStyle(color: Colors.white, fontSize: 18, fontFamily: 'Noto Sans Devanagari')),
      ),
    );
  }

  Widget _buildDrawer(BuildContext context) {
    return Drawer(
      backgroundColor: const Color(0xFFF9F6F0),
      child: ListView(
        padding: EdgeInsets.zero,
        children: [
          const DrawerHeader(
            decoration: BoxDecoration(color: Color(0xFFFF6B00)),
            child: Text('आयोजन मेनू', style: TextStyle(color: Colors.white, fontSize: 24, fontFamily: 'Noto Sans Devanagari')),
          ),
          ListTile(
            leading: const Icon(Icons.dashboard, color: Color(0xFFE55B00)),
            title: const Text('डैशबोर्ड', style: TextStyle(fontSize: 18, fontFamily: 'Noto Sans Devanagari')),
            onTap: () => Navigator.pop(context),
          ),
          ListTile(
            leading: const Icon(Icons.people, color: Color(0xFFE55B00)),
            title: const Text('प्रतिभागी', style: TextStyle(fontSize: 18, fontFamily: 'Noto Sans Devanagari')),
            onTap: () {
              Navigator.pop(context);
              Navigator.push(context, MaterialPageRoute(builder: (context) => const ParticipantListScreen()));
            },
          ),
          ListTile(
            leading: const Icon(Icons.check_circle, color: Color(0xFFE55B00)),
            title: const Text('हाजिरी', style: TextStyle(fontSize: 18, fontFamily: 'Noto Sans Devanagari')),
            onTap: () {
              Navigator.pop(context);
              Navigator.push(context, MaterialPageRoute(builder: (context) => const AttendanceScreen(sessionId: 1)));
            },
          ),
          ListTile(
            leading: const Icon(Icons.assignment_ind, color: Color(0xFFE55B00)),
            title: const Text('उपस्थिति ड्यूटी', style: TextStyle(fontSize: 18, fontFamily: 'Noto Sans Devanagari')),
            onTap: () {
              Navigator.pop(context);
              Navigator.push(context, MaterialPageRoute(builder: (context) => const AttendanceDutyScreen()));
            },
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.exit_to_app, color: Colors.red),
            title: const Text('लॉगआउट', style: TextStyle(fontSize: 18, fontFamily: 'Noto Sans Devanagari', color: Colors.red)),
            onTap: () {
              ref.read(eventSessionProvider.notifier).logout();
              Navigator.of(context).popUntil((route) => route.isFirst);
            },
          ),
        ],
      ),
    );
  }

  Widget _buildStatsRow(dynamic state) {
    // state is a DashboardStats object here
    int total = state.participantCount;
    int present = 0;
    if (state.todayAttendance != null && state.todayAttendance is Map) {
       present = (state.todayAttendance['present_count'] as int?) ?? 0;
    }
    String attPct = total > 0 ? ((present / total) * 100).toStringAsFixed(1) : '0';

    return Row(
      children: [
        Expanded(child: _buildStatCard('कुल प्रतिभागी', '$total', Colors.blue)),
        const SizedBox(width: 8),
        Expanded(child: _buildStatCard('आज की हाजिरी', '$attPct%', Colors.green)),
      ],
    );
  }

  Widget _buildStatCard(String title, String value, Color color) {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            Text(value, style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: color)),
            const SizedBox(height: 8),
            Text(title, textAlign: TextAlign.center, style: const TextStyle(fontSize: 16, fontFamily: 'Noto Sans Devanagari')),
          ],
        ),
      ),
    );
  }

  Widget _buildFoodSection() {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: const [
            Text('आज का भोजन', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari')),
            SizedBox(height: 12),
            Text('नाश्ता: 200/250', style: TextStyle(fontSize: 18, fontFamily: 'Noto Sans Devanagari')),
            Text('दोपहर का भोजन: 150/250', style: TextStyle(fontSize: 18, fontFamily: 'Noto Sans Devanagari')),
          ],
        ),
      ),
    );
  }

  Widget _buildNextActivity() {
    return Card(
      elevation: 4,
      color: const Color(0xFFFFB300),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Row(
          children: const [
            Icon(Icons.schedule, size: 40, color: Colors.white),
            SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('अगली गतिविधि', style: TextStyle(fontSize: 16, color: Colors.white, fontFamily: 'Noto Sans Devanagari')),
                  Text('उद्घाटन सत्र - 10:00 AM', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white, fontFamily: 'Noto Sans Devanagari')),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPendingTasks() {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('मेरे लंबित कार्य', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari')),
            const SizedBox(height: 8),
            ListTile(
              leading: const Icon(Icons.check_box_outline_blank, color: Color(0xFFFF6B00)),
              title: const Text('कक्ष निरीक्षण', style: TextStyle(fontSize: 18, fontFamily: 'Noto Sans Devanagari')),
            ),
            ListTile(
              leading: const Icon(Icons.check_box_outline_blank, color: Color(0xFFFF6B00)),
              title: const Text('भोजन व्यवस्था', style: TextStyle(fontSize: 18, fontFamily: 'Noto Sans Devanagari')),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildQuickActions(BuildContext context) {
    return Wrap(
      spacing: 16,
      runSpacing: 16,
      alignment: WrapAlignment.center,
      children: [
        _buildActionBtn(context, 'हाजिरी', Icons.check_circle, () {
          Navigator.push(context, MaterialPageRoute(builder: (context) => const AttendanceScreen(sessionId: 1)));
        }),
        _buildActionBtn(context, 'प्रतिभागी', Icons.search, () {
          Navigator.push(context, MaterialPageRoute(builder: (context) => const ParticipantListScreen()));
        }),
        _buildActionBtn(context, 'ड्यूटी', Icons.assignment_ind, () {
          Navigator.push(context, MaterialPageRoute(builder: (context) => const AttendanceDutyScreen()));
        }),
      ],
    );
  }

  Widget _buildActionBtn(BuildContext context, String title, IconData icon, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      child: Column(
        children: [
          CircleAvatar(
            radius: 30,
            backgroundColor: const Color(0xFFFF6B00).withOpacity(0.1),
            child: Icon(icon, size: 30, color: const Color(0xFFFF6B00)),
          ),
          const SizedBox(height: 8),
          Text(title, style: const TextStyle(fontSize: 16, fontFamily: 'Noto Sans Devanagari')),
        ],
      ),
    );
  }
}
