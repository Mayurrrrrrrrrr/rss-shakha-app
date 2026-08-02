import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/event_models.dart';
import '../providers/event_providers.dart';
import 'event_dashboard_screen.dart';
// import 'spot_entry_screen.dart'; // if needed for new event

class EventSelectionScreen extends ConsumerStatefulWidget {
  const EventSelectionScreen({Key? key}) : super(key: key);

  @override
  ConsumerState<EventSelectionScreen> createState() => _EventSelectionScreenState();
}

class _EventSelectionScreenState extends ConsumerState<EventSelectionScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(eventListProvider.notifier).fetchEvents();
    });
  }

  Future<void> _refresh() async {
    await ref.read(eventListProvider.notifier).fetchEvents();
  }

  @override
  Widget build(BuildContext context) {
    final eventState = ref.watch(eventListProvider);
    final sessionState = ref.watch(eventSessionProvider);
    final isAdmin = sessionState.role == 'admin';

    return Scaffold(
      backgroundColor: const Color(0xFFF9F6F0),
      appBar: AppBar(
        title: const Text('📋 कार्यक्रम चुनें', style: TextStyle(fontFamily: 'Noto Sans Devanagari', fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFFFF6B00),
        foregroundColor: Colors.white,
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        color: const Color(0xFFFF6B00),
        child: _buildBody(eventState),
      ),
      floatingActionButton: isAdmin
          ? FloatingActionButton(
              onPressed: () {
                // Navigate to create event (if applicable)
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('नया कार्यक्रम जोड़ने की सुविधा जल्द आ रही है', style: TextStyle(fontFamily: 'Noto Sans Devanagari'))),
                );
              },
              backgroundColor: const Color(0xFFFF6B00),
              child: const Icon(Icons.add, color: Colors.white),
            )
          : null,
    );
  }

  Widget _buildBody(EventListState state) {
    if (state.isLoading) {
      return const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)));
    }
    if (state.error != null) {
      return Center(
        child: Text('त्रुटि: ${state.error}', style: const TextStyle(color: Colors.red, fontSize: 18, fontFamily: 'Noto Sans Devanagari')),
      );
    }
    final events = state.events;
    if (events == null || events.isEmpty) {
      return const Center(
        child: Text('कोई कार्यक्रम नहीं मिला।', style: TextStyle(fontSize: 18, fontFamily: 'Noto Sans Devanagari')),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: events.length,
      itemBuilder: (context, index) {
        final event = events[index];
        final isActive = event.status == 'active';
        return Card(
          margin: const EdgeInsets.only(bottom: 16),
          elevation: isActive ? 8 : 4,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
            side: isActive ? const BorderSide(color: Color(0xFFFF6B00), width: 2) : BorderSide.none,
          ),
          child: InkWell(
            borderRadius: BorderRadius.circular(12),
            onTap: () {
              ref.read(currentEventProvider.notifier).setEvent(event);
              Navigator.push(
                context,
                MaterialPageRoute(builder: (context) => const EventDashboardScreen()),
              );
            },
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          event.name,
                          style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari'),
                        ),
                      ),
                      if (isActive)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: const Color(0xFFFFB300),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: const Text('सक्रिय', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontFamily: 'Noto Sans Devanagari')),
                        ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const Icon(Icons.location_on, color: Color(0xFFE55B00), size: 20),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(event.venue, style: const TextStyle(fontSize: 18, fontFamily: 'Noto Sans Devanagari')),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const Icon(Icons.calendar_today, color: Color(0xFFE55B00), size: 20),
                      const SizedBox(width: 8),
                      Text('${event.startDate} - ${event.endDate}', style: const TextStyle(fontSize: 16, fontFamily: 'Noto Sans Devanagari', color: Colors.black54)),
                    ],
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}
