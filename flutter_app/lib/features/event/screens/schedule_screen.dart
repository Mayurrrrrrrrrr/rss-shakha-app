import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/event_models.dart';
import '../providers/event_providers.dart';

class ScheduleScreen extends ConsumerStatefulWidget {
  const ScheduleScreen({super.key});

  @override
  ConsumerState<ScheduleScreen> createState() => _ScheduleScreenState();
}

class _ScheduleScreenState extends ConsumerState<ScheduleScreen> {
  int _selectedDay = 0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9F6F0),
      appBar: AppBar(
        title: const Text('📅 कार्यक्रम अनुसूची', style: TextStyle(fontFamily: 'Noto Sans Devanagari')),
        backgroundColor: const Color(0xFFFF6B00),
        foregroundColor: Colors.white,
      ),
      body: Column(
        children: [
          _buildDayTabs(),
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: 4,
              itemBuilder: (context, index) => _buildTimelineItem(index),
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showAddScheduleDialog(),
        backgroundColor: const Color(0xFFFF6B00),
        child: const Icon(Icons.add, color: Colors.white),
      ),
    );
  }

  Widget _buildDayTabs() {
    return Container(
      color: Colors.white,
      height: 60,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        itemCount: 3,
        itemBuilder: (context, index) {
          final isSelected = _selectedDay == index;
          return GestureDetector(
            onTap: () => setState(() => _selectedDay = index),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              alignment: Alignment.center,
              decoration: BoxDecoration(
                border: Border(
                  bottom: BorderSide(
                    color: isSelected ? const Color(0xFFFF6B00) : Colors.transparent,
                    width: 3,
                  ),
                ),
              ),
              child: Text(
                'दिन ${index + 1}',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                  color: isSelected ? const Color(0xFFFF6B00) : Colors.grey,
                  fontFamily: 'Noto Sans Devanagari',
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildTimelineItem(int index) {
    final isCurrent = index == 1;
    final isPast = index == 0;

    return IntrinsicHeight(
      child: Row(
        children: [
          SizedBox(
            width: 80,
            child: Column(
              children: [
                Text('10:00 AM', style: TextStyle(fontWeight: FontWeight.bold, color: isPast ? Colors.grey : Colors.black87)),
                Text('11:30 AM', style: TextStyle(fontSize: 12, color: isPast ? Colors.grey : Colors.black54)),
              ],
            ),
          ),
          Column(
            children: [
              Container(
                width: 16,
                height: 16,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: isCurrent ? const Color(0xFFFF6B00) : (isPast ? Colors.grey : const Color(0xFFFFB300)),
                  border: isCurrent ? Border.all(color: const Color(0xFFFFB300), width: 3) : null,
                ),
              ),
              Expanded(
                child: Container(
                  width: 2,
                  color: isPast ? Colors.grey : const Color(0xFFFFB300),
                ),
              ),
            ],
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Card(
              elevation: isCurrent ? 8 : 2,
              margin: const EdgeInsets.only(bottom: 24),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
                side: isCurrent ? const BorderSide(color: Color(0xFFFF6B00), width: 2) : BorderSide.none,
              ),
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'उद्घाटन सत्र',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: isPast ? Colors.grey : Colors.black87,
                        fontFamily: 'Noto Sans Devanagari',
                      ),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Icon(Icons.location_on, size: 16, color: isPast ? Colors.grey : Colors.redAccent),
                        const SizedBox(width: 4),
                        Text('मुख्य सभागार', style: TextStyle(color: isPast ? Colors.grey : Colors.black87)),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Icon(Icons.person, size: 16, color: isPast ? Colors.grey : Colors.blue),
                        const SizedBox(width: 4),
                        Text('प्रभारी: रमेश जी', style: TextStyle(color: isPast ? Colors.grey : Colors.black87, fontFamily: 'Noto Sans Devanagari')),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _showAddScheduleDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('नया कार्यक्रम जोड़ें', style: TextStyle(fontFamily: 'Noto Sans Devanagari', color: Color(0xFFFF6B00))),
        content: const Text('कार्यक्रम प्रविष्टि फॉर्म'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('रद्द करें')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFFF6B00)),
            onPressed: () => Navigator.pop(context),
            child: const Text('सहेजें', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }
}
