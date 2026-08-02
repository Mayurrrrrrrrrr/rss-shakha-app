import 'package:flutter/material.dart';
import '../models/event_models.dart';

class ParticipantDetailScreen extends StatelessWidget {
  final Participant participant;
  
  const ParticipantDetailScreen({Key? key, required this.participant}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(24.0),
      decoration: const BoxDecoration(
        color: Color(0xFFF9F6F0),
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(participant.name, style: const TextStyle(fontSize: 26, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari', color: Color(0xFFFF6B00))),
              ),
              if (participant.entryType == 'spot')
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(color: Colors.red, borderRadius: BorderRadius.circular(20)),
                  child: const Text('स्पॉट एंट्री', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari')),
                ),
            ],
          ),
          const SizedBox(height: 24),
          _buildInfoRow(Icons.phone, 'फोन', participant.phone),
          const SizedBox(height: 16),
          _buildInfoRow(Icons.location_city, 'शहर', participant.city),
          const SizedBox(height: 16),
          _buildInfoRow(Icons.group, 'समूह', participant.group ?? 'आवंटित नहीं'),
          const SizedBox(height: 16),
          _buildInfoRow(Icons.room, 'कक्ष', 'कक्ष 102 (भवन अ)'), // Dummy
          const SizedBox(height: 24),
          const Text('उपस्थिति इतिहास', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari')),
          const SizedBox(height: 8),
          const Text('कुल उपस्थिति: 4/5 सत्र', style: TextStyle(fontSize: 18, fontFamily: 'Noto Sans Devanagari')),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            height: 56,
            child: ElevatedButton(
              onPressed: () {
                // Edit action
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFFF6B00),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              child: const Text('संपादित करें', style: TextStyle(fontSize: 18, color: Colors.white, fontFamily: 'Noto Sans Devanagari')),
            ),
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Row(
      children: [
        Icon(icon, color: const Color(0xFFE55B00), size: 28),
        const SizedBox(width: 16),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: const TextStyle(fontSize: 14, color: Colors.grey, fontFamily: 'Noto Sans Devanagari')),
            Text(value, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari')),
          ],
        ),
      ],
    );
  }
}
