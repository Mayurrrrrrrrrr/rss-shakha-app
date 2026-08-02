import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/event_models.dart';
import '../providers/event_providers.dart';

class RoomAllotmentScreen extends ConsumerStatefulWidget {
  const RoomAllotmentScreen({super.key});

  @override
  ConsumerState<RoomAllotmentScreen> createState() => _RoomAllotmentScreenState();
}

class _RoomAllotmentScreenState extends ConsumerState<RoomAllotmentScreen> {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9F6F0),
      appBar: AppBar(
        title: const Text('🏠 कक्ष व्यवस्था', style: TextStyle(fontFamily: 'Noto Sans Devanagari')),
        backgroundColor: const Color(0xFFFF6B00),
        foregroundColor: Colors.white,
      ),
      body: CustomScrollView(
        slivers: [
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: _buildOverviewCards(),
            ),
          ),
          SliverPadding(
            padding: const EdgeInsets.symmetric(horizontal: 16.0),
            sliver: SliverGrid(
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                crossAxisSpacing: 16,
                mainAxisSpacing: 16,
                childAspectRatio: 0.85,
              ),
              delegate: SliverChildBuilderDelegate(
                (context, index) => _buildRoomCard(index),
                childCount: 6,
              ),
            ),
          ),
          const SliverPadding(padding: EdgeInsets.only(bottom: 24)),
        ],
      ),
    );
  }

  Widget _buildOverviewCards() {
    return Row(
      children: [
        Expanded(child: _buildStatCard('कुल कक्ष', '20', Colors.blue)),
        const SizedBox(width: 12),
        Expanded(child: _buildStatCard('उपलब्ध', '5', Colors.green)),
        const SizedBox(width: 12),
        Expanded(child: _buildStatCard('भरे हुए', '15', Colors.orange)),
      ],
    );
  }

  Widget _buildStatCard(String title, String value, Color color) {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 16.0, horizontal: 8.0),
        child: Column(
          children: [
            Text(value, style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: color)),
            const SizedBox(height: 4),
            Text(title, style: const TextStyle(fontSize: 14, fontFamily: 'Noto Sans Devanagari'), textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }

  Widget _buildRoomCard(int index) {
    final occupied = (index % 5) + 1;
    final capacity = 6;
    final isFull = occupied >= capacity;
    final progressColor = isFull ? Colors.red : (occupied > capacity / 2 ? Colors.orange : Colors.green);

    return Card(
      elevation: 6,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => _showAllotmentDialog(),
        child: Padding(
          padding: const EdgeInsets.all(12.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('कक्ष ${101 + index}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  Icon(Icons.meeting_room, color: isFull ? Colors.red : Colors.green),
                ],
              ),
              const SizedBox(height: 4),
              const Text('प्रथम तल, मुख्य भवन', style: TextStyle(fontSize: 12, color: Colors.grey)),
              const Spacer(),
              Text('क्षमता: $occupied / $capacity', style: const TextStyle(fontSize: 14)),
              const SizedBox(height: 8),
              LinearProgressIndicator(
                value: occupied / capacity,
                backgroundColor: Colors.grey[300],
                valueColor: AlwaysStoppedAnimation<Color>(progressColor),
                minHeight: 8,
                borderRadius: BorderRadius.circular(4),
              ),
              const SizedBox(height: 12),
              const Text('निवासी: रमेश, सुरेश...', style: TextStyle(fontSize: 12, fontStyle: FontStyle.italic), maxLines: 1, overflow: TextOverflow.ellipsis),
            ],
          ),
        ),
      ),
    );
  }

  void _showAllotmentDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('कक्ष आवंटन', style: TextStyle(fontFamily: 'Noto Sans Devanagari', color: Color(0xFFFF6B00))),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              decoration: InputDecoration(
                labelText: 'प्रतिभागी / आयोजक खोजें',
                prefixIcon: const Icon(Icons.search),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
            const SizedBox(height: 16),
            const Text('उपलब्ध स्थान: 2', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('रद्द करें', style: TextStyle(color: Colors.grey, fontSize: 16)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFF6B00),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            onPressed: () => Navigator.pop(context),
            child: const Text('आवंटित करें', style: TextStyle(color: Colors.white, fontSize: 16)),
          ),
        ],
      ),
    );
  }
}
