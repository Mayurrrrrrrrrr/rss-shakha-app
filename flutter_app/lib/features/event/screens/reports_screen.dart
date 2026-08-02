import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

class ReportsScreen extends ConsumerWidget {
  const ReportsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9F6F0),
      appBar: AppBar(
        title: const Text('📊 रिपोर्ट', style: TextStyle(fontFamily: 'Noto Sans Devanagari')),
        backgroundColor: const Color(0xFFFF6B00),
        foregroundColor: Colors.white,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('सारांश', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari')),
            const SizedBox(height: 16),
            GridView.count(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisCount: 2,
              crossAxisSpacing: 16,
              mainAxisSpacing: 16,
              childAspectRatio: 1.1,
              children: [
                _buildStatCard('कुल प्रतिभागी', '150', Icons.people, Colors.blue),
                _buildStatCard('हाजिरी दर', '92%', Icons.check_circle, Colors.green),
                _buildStatCard('कार्य पूर्णता', '75%', Icons.task_alt, Colors.orange),
                _buildStatCard('कक्ष उपयोग', '85%', Icons.meeting_room, Colors.purple),
              ],
            ),
            const SizedBox(height: 24),
            const Text('डेटा निर्यात (Export)', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari')),
            const SizedBox(height: 16),
            _buildExportButton(context, 'प्रतिभागी सूची निर्यात (CSV)', Icons.file_download),
            const SizedBox(height: 12),
            _buildExportButton(context, 'हाजिरी निर्यात (CSV)', Icons.list_alt),
            const SizedBox(height: 12),
            _buildExportButton(context, 'सम्पूर्ण डेटा निर्यात (JSON)', Icons.data_usage),
          ],
        ),
      ),
    );
  }

  Widget _buildStatCard(String title, String value, IconData icon, Color color) {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 36, color: color),
            const SizedBox(height: 8),
            Text(value, style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold)),
            const SizedBox(height: 4),
            Text(title, style: const TextStyle(fontSize: 14, fontFamily: 'Noto Sans Devanagari'), textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }

  Widget _buildExportButton(BuildContext context, String text, IconData icon) {
    return SizedBox(
      width: double.infinity,
      height: 56,
      child: ElevatedButton.icon(
        icon: Icon(icon, color: Colors.white),
        label: Text(text, style: const TextStyle(fontSize: 16, color: Colors.white, fontFamily: 'Noto Sans Devanagari')),
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFFFF6B00),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
        onPressed: () {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('डाउनलोड शुरू हो रहा है...')),
          );
        },
      ),
    );
  }
}
