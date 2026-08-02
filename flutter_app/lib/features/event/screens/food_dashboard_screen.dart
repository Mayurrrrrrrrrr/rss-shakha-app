import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/event_models.dart';
import '../providers/event_providers.dart';

class FoodDashboardScreen extends ConsumerStatefulWidget {
  const FoodDashboardScreen({super.key});

  @override
  ConsumerState<FoodDashboardScreen> createState() => _FoodDashboardScreenState();
}

class _FoodDashboardScreenState extends ConsumerState<FoodDashboardScreen> {
  DateTime _selectedDate = DateTime.now();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9F6F0),
      appBar: AppBar(
        title: const Text('🍽️ भोजन व्यवस्था', style: TextStyle(fontFamily: 'Noto Sans Devanagari')),
        backgroundColor: const Color(0xFFFF6B00),
        foregroundColor: Colors.white,
      ),
      body: Column(
        children: [
          _buildDateSelector(),
          Expanded(
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                _buildSummaryCard(),
                const SizedBox(height: 16),
                _buildMealCard('प्रातः नाश्ता', '08:00 AM', 150, 10, 145),
                _buildMealCard('दोपहर भोजन', '01:00 PM', 160, 5, 0),
                _buildMealCard('रात्रि भोजन', '08:30 PM', 160, 5, 0),
              ],
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () {},
        backgroundColor: const Color(0xFFFF6B00),
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text('भोजन जोड़ें', style: TextStyle(color: Colors.white, fontFamily: 'Noto Sans Devanagari', fontSize: 16)),
      ),
    );
  }

  Widget _buildDateSelector() {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          IconButton(
            icon: const Icon(Icons.arrow_back_ios, size: 20),
            onPressed: () {
              setState(() {
                _selectedDate = _selectedDate.subtract(const Duration(days: 1));
              });
            },
          ),
          Text(
            '${_selectedDate.day}/${_selectedDate.month}/${_selectedDate.year}',
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFFE55B00)),
          ),
          IconButton(
            icon: const Icon(Icons.arrow_forward_ios, size: 20),
            onPressed: () {
              setState(() {
                _selectedDate = _selectedDate.add(const Duration(days: 1));
              });
            },
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryCard() {
    return Card(
      elevation: 4,
      color: const Color(0xFFFFB300).withOpacity(0.2),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12), side: const BorderSide(color: Color(0xFFFFB300))),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: const [
            Text('कुल अनुमानित भोजन', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari')),
            SizedBox(height: 8),
            Text('165', style: TextStyle(fontSize: 36, fontWeight: FontWeight.bold, color: Color(0xFFE55B00))),
            Text('उपस्थित + आने वाले', style: TextStyle(fontSize: 14, color: Colors.grey)),
          ],
        ),
      ),
    );
  }

  Widget _buildMealCard(String title, String time, int expected, int upcoming, int actual) {
    return Card(
      elevation: 6,
      margin: const EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(title, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari')),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(color: Colors.grey[200], borderRadius: BorderRadius.circular(12)),
                  child: Text(time, style: const TextStyle(fontWeight: FontWeight.w600)),
                ),
              ],
            ),
            const Divider(height: 24),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _buildMealStat('अनुमानित', expected.toString(), Colors.blue),
                _buildMealStat('आने वाले', upcoming.toString(), Colors.orange),
                _buildMealStat('वास्तविक', actual > 0 ? actual.toString() : '-', Colors.green),
              ],
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: OutlinedButton(
                style: OutlinedButton.styleFrom(
                  foregroundColor: const Color(0xFFFF6B00), side: const BorderSide(color: Color(0xFFFF6B00)),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                onPressed: () {},
                child: const Text('गणना अपडेट करें / ट्रैकिंग देखें', style: TextStyle(fontSize: 16, fontFamily: 'Noto Sans Devanagari')),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMealStat(String label, String value, Color color) {
    return Column(
      children: [
        Text(value, style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: color)),
        Text(label, style: const TextStyle(fontSize: 14, fontFamily: 'Noto Sans Devanagari')),
      ],
    );
  }
}
