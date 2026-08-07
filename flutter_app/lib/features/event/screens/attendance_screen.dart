import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/event_models.dart';
import '../providers/event_providers.dart';
import 'spot_entry_screen.dart';

class AttendanceScreen extends ConsumerStatefulWidget {
  const AttendanceScreen({Key? key}) : super(key: key);

  @override
  ConsumerState<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends ConsumerState<AttendanceScreen> {
  final Map<String, bool> _attendanceMap = {};
  String _searchQuery = '';
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.invalidate(participantListProvider(const {}));
    });
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(participantListProvider(const {}));
    List<Participant> participants = (state.value?['data'] as List<dynamic>?)?.map((e) => Participant.fromJson(e)).toList() ?? [];
    
    if (_searchQuery.isNotEmpty) {
      participants = participants.where((p) => p.name.toLowerCase().contains(_searchQuery.toLowerCase())).toList();
    }

    int presentCount = _attendanceMap.values.where((v) => v).length;

    return Scaffold(
      backgroundColor: const Color(0xFFF9F6F0),
      appBar: AppBar(
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('हाजिरी', style: TextStyle(fontFamily: 'Noto Sans Devanagari', fontWeight: FontWeight.bold)),
            Text('प्रातः सत्र (आज)', style: TextStyle(fontSize: 14, fontFamily: 'Noto Sans Devanagari', fontWeight: FontWeight.normal)),
          ],
        ),
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
                hintText: 'नाम से खोजें',
                hintStyle: const TextStyle(fontFamily: 'Noto Sans Devanagari', fontSize: 18),
                prefixIcon: const Icon(Icons.search, color: Color(0xFFFF6B00)),
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
              ),
              onChanged: (val) => setState(() => _searchQuery = val),
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('उपस्थित: $presentCount/${participants.length}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari')),
                TextButton(
                  onPressed: () {
                    setState(() {
                      for (var p in participants) {
                        _attendanceMap[p.id.toString()] = true;
                      }
                    });
                  },
                  child: const Text('सभी चिन्हित करें', style: TextStyle(color: Color(0xFFE55B00), fontSize: 16, fontFamily: 'Noto Sans Devanagari', fontWeight: FontWeight.bold)),
                ),
              ],
            ),
          ),
          Expanded(
            child: state.isLoading
                ? const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)))
                : ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: participants.length,
                    itemBuilder: (context, index) {
                      final p = participants[index];
                      final pId = p.id.toString();
                      final isPresent = _attendanceMap[pId] ?? false;
                      
                      return Card(
                        elevation: 2,
                        margin: const EdgeInsets.only(bottom: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        child: CheckboxListTile(
                          activeColor: const Color(0xFFFF6B00),
                          title: Text(p.name, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari')),
                          subtitle: Text('📞 ${p.phone} | ${(p.groupNumber?.toString()) ?? "समूह नहीं"}', style: const TextStyle(fontSize: 16, fontFamily: 'Noto Sans Devanagari')),
                          value: isPresent,
                          onChanged: (val) {
                            setState(() {
                              _attendanceMap[pId] = val ?? false;
                            });
                          },
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
      bottomNavigationBar: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 4, offset: const Offset(0, -2))],
        ),
        child: SizedBox(
          height: 56,
          child: ElevatedButton(
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('हाजिरी सहेजी गई', style: TextStyle(fontFamily: 'Noto Sans Devanagari')), backgroundColor: Colors.green));
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFF6B00),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            child: const Text('सहेजें', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white, fontFamily: 'Noto Sans Devanagari')),
          ),
        ),
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          Navigator.push(context, MaterialPageRoute(builder: (context) => const SpotEntryScreen()));
        },
        backgroundColor: const Color(0xFFFFB300),
        child: const Icon(Icons.person_add, color: Colors.white),
      ),
    );
  }
}
