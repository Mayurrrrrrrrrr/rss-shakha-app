import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/event_models.dart';
import '../providers/event_providers.dart';
import 'participant_detail_screen.dart';

class ParticipantListScreen extends ConsumerStatefulWidget {
  const ParticipantListScreen({Key? key}) : super(key: key);

  @override
  ConsumerState<ParticipantListScreen> createState() => _ParticipantListScreenState();
}

class _ParticipantListScreenState extends ConsumerState<ParticipantListScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';
  String _selectedGroup = 'सभी';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(participantListProvider.notifier).fetchParticipants();
    });
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(participantListProvider);
    
    // Simulate local filtering
    List<Participant> participants = state.participants ?? [];
    if (_searchQuery.isNotEmpty) {
      participants = participants.where((p) => p.name.toLowerCase().contains(_searchQuery.toLowerCase())).toList();
    }
    if (_selectedGroup != 'सभी') {
      participants = participants.where((p) => p.group == _selectedGroup).toList();
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF9F6F0),
      appBar: AppBar(
        title: const Text('👥 प्रतिभागी सूची', style: TextStyle(fontFamily: 'Noto Sans Devanagari', fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFFFF6B00),
        foregroundColor: Colors.white,
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'नाम, फोन या शहर से खोजें',
                hintStyle: const TextStyle(fontFamily: 'Noto Sans Devanagari', fontSize: 18),
                prefixIcon: const Icon(Icons.search, color: Color(0xFFFF6B00)),
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                contentPadding: const EdgeInsets.symmetric(vertical: 16),
              ),
              onChanged: (val) => setState(() => _searchQuery = val),
            ),
          ),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              children: ['सभी', 'समूह 1', 'समूह 2', 'समूह 3'].map((group) {
                return Padding(
                  padding: const EdgeInsets.only(right: 8.0),
                  child: FilterChip(
                    label: Text(group, style: const TextStyle(fontFamily: 'Noto Sans Devanagari', fontSize: 16)),
                    selected: _selectedGroup == group,
                    selectedColor: const Color(0xFFFFB300),
                    onSelected: (bool selected) {
                      setState(() => _selectedGroup = selected ? group : 'सभी');
                    },
                  ),
                );
              }).toList(),
            ),
          ),
          const SizedBox(height: 8),
          Expanded(
            child: state.isLoading
                ? const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)))
                : ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: participants.length,
                    itemBuilder: (context, index) {
                      final p = participants[index];
                      return Card(
                        elevation: 4,
                        margin: const EdgeInsets.only(bottom: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        child: ListTile(
                          contentPadding: const EdgeInsets.all(16),
                          title: Text(p.name, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari')),
                          subtitle: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const SizedBox(height: 8),
                              Text('📞 ${p.phone} | 🏙️ ${p.city}', style: const TextStyle(fontSize: 16, fontFamily: 'Noto Sans Devanagari')),
                              const SizedBox(height: 8),
                              Row(
                                children: [
                                  Chip(label: Text(p.group ?? 'N/A', style: const TextStyle(fontFamily: 'Noto Sans Devanagari')), backgroundColor: const Color(0xFFF9F6F0)),
                                  const SizedBox(width: 8),
                                  if (p.entryType == 'spot')
                                    const Chip(label: Text('स्पॉट', style: TextStyle(color: Colors.white, fontFamily: 'Noto Sans Devanagari')), backgroundColor: Colors.red),
                                ],
                              ),
                            ],
                          ),
                          onTap: () {
                            showModalBottomSheet(
                              context: context,
                              isScrollControlled: true,
                              shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
                              builder: (_) => ParticipantDetailScreen(participant: p),
                            );
                          },
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }
}
