import 'package:flutter/material.dart';

class AttendanceDutyScreen extends StatelessWidget {
  const AttendanceDutyScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9F6F0),
      appBar: AppBar(
        title: const Text('उपस्थिति ड्यूटी', style: TextStyle(fontFamily: 'Noto Sans Devanagari', fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFFFF6B00),
        foregroundColor: Colors.white,
      ),
      body: ListView(
        padding: const EdgeInsets.all(16.0),
        children: [
          _buildSessionCard('प्रातः सत्र', '10:00 AM - 12:00 PM', 'समूह 1, 2'),
          _buildSessionCard('दोपहर सत्र', '02:00 PM - 04:00 PM', 'समूह 3, 4'),
          _buildSessionCard('सांय सत्र', '05:00 PM - 07:00 PM', 'सभी समूह'),
        ],
      ),
    );
  }

  Widget _buildSessionCard(String title, String time, String groups) {
    return Card(
      elevation: 4,
      margin: const EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari', color: Color(0xFFFF6B00))),
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.access_time, color: Colors.grey, size: 20),
                const SizedBox(width: 8),
                Text(time, style: const TextStyle(fontSize: 16, fontFamily: 'Noto Sans Devanagari')),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.group, color: Colors.grey, size: 20),
                const SizedBox(width: 8),
                Text('आवंटित समूह: $groups', style: const TextStyle(fontSize: 16, fontFamily: 'Noto Sans Devanagari')),
              ],
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton(
                onPressed: () {},
                style: OutlinedButton.styleFrom(
                  foregroundColor: const Color(0xFFFF6B00), side: const BorderSide(color: Color(0xFFFF6B00)),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: const Text('ड्यूटी प्रबंधित करें', style: TextStyle(fontSize: 18, fontFamily: 'Noto Sans Devanagari')),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
