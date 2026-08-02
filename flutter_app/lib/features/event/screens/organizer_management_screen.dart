import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

class OrganizerManagementScreen extends ConsumerStatefulWidget {
  const OrganizerManagementScreen({super.key});

  @override
  ConsumerState<OrganizerManagementScreen> createState() => _OrganizerManagementScreenState();
}

class _OrganizerManagementScreenState extends ConsumerState<OrganizerManagementScreen> {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9F6F0),
      appBar: AppBar(
        title: const Text('👤 आयोजक प्रबंधन', style: TextStyle(fontFamily: 'Noto Sans Devanagari')),
        backgroundColor: const Color(0xFFFF6B00),
        foregroundColor: Colors.white,
      ),
      body: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: 5,
        itemBuilder: (context, index) {
          final isCoord = index == 0;
          return Card(
            elevation: 3,
            margin: const EdgeInsets.only(bottom: 12),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            child: ListTile(
              contentPadding: const EdgeInsets.all(16),
              leading: CircleAvatar(
                backgroundColor: isCoord ? const Color(0xFFFFB300) : Colors.grey[300],
                child: const Icon(Icons.person, color: Colors.white),
              ),
              title: const Text('आयोजक का नाम', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari')),
              subtitle: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 4),
                  const Text('📱 9876543210'),
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: isCoord ? Colors.orange[100] : Colors.blue[100],
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      isCoord ? 'समन्वयक' : 'स्वयंसेवक',
                      style: TextStyle(color: isCoord ? Colors.orange[800] : Colors.blue[800], fontSize: 12),
                    ),
                  ),
                ],
              ),
              trailing: PopupMenuButton(
                itemBuilder: (context) => [
                  const PopupMenuItem(value: 'edit', child: Text('संपादित करें')),
                  const PopupMenuItem(value: 'delete', child: Text('हटाएं', style: TextStyle(color: Colors.red))),
                ],
                onSelected: (value) {
                  if (value == 'delete') {
                    _showDeleteConfirmDialog();
                  }
                },
              ),
            ),
          );
        },
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showAddOrganizerDialog(),
        backgroundColor: const Color(0xFFFF6B00),
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text('नया आयोजक', style: TextStyle(color: Colors.white, fontFamily: 'Noto Sans Devanagari', fontSize: 16)),
      ),
    );
  }

  void _showAddOrganizerDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('नया आयोजक जोड़ें', style: TextStyle(fontFamily: 'Noto Sans Devanagari', color: Color(0xFFFF6B00))),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(decoration: InputDecoration(labelText: 'नाम')),
              const SizedBox(height: 12),
              TextField(decoration: InputDecoration(labelText: 'मोबाइल नंबर')),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                decoration: const InputDecoration(labelText: 'भूमिका'),
                items: const [
                  DropdownMenuItem(value: 'volunteer', child: Text('स्वयंसेवक')),
                  DropdownMenuItem(value: 'coordinator', child: Text('समन्वयक')),
                ],
                onChanged: (value) {},
              ),
            ],
          ),
        ),
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

  void _showDeleteConfirmDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('पुष्टि करें'),
        content: const Text('क्या आप वाकई इस आयोजक को हटाना चाहते हैं?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('रद्द करें')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(context),
            child: const Text('हटाएं', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }
}
