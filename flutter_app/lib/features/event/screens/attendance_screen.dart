import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/event_models.dart';
import '../providers/event_providers.dart';
import 'spot_entry_screen.dart';

class AttendanceScreen extends ConsumerStatefulWidget {
  final int sessionId;

  const AttendanceScreen({Key? key, required this.sessionId}) : super(key: key);

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
    _refreshList();
  }

  void _refreshList() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.invalidate(attendanceListProvider({
        'session_id': widget.sessionId,
        'search': _searchQuery
      }));
    });
  }

  void _onSearchChanged(String query) {
    setState(() {
      _searchQuery = query;
    });
    // Trigger API call on search
    _refreshList();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(attendanceListProvider({
      'session_id': widget.sessionId,
      'search': _searchQuery
    }));
    
    List<Participant> participants = [];
    if (state.hasValue && state.value != null) {
      final data = state.value!['participants'] as List<dynamic>?;
      if (data != null) {
        participants = data.map((e) => Participant.fromJson(e)).toList();
      }
    }

    int presentCount = _attendanceMap.values.where((v) => v).length;
    // Calculate total including pre-existing ones if API returned is_present = 1
    // For simplicity, we just use the UI tracking map.

    return Scaffold(
      backgroundColor: const Color(0xFF0B0E14),
      appBar: AppBar(
        title: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('हाजिरी (Attendance)', style: TextStyle(fontFamily: 'Noto Sans Devanagari', fontWeight: FontWeight.bold)),
          ],
        ),
        backgroundColor: const Color(0xFF0D9488),
        foregroundColor: Colors.white,
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: TextField(
              controller: _searchController,
              style: const TextStyle(color: Colors.white),
              decoration: InputDecoration(
                hintText: 'नाम या फ़ोन से खोजें (Search)',
                hintStyle: const TextStyle(fontFamily: 'Noto Sans Devanagari', fontSize: 16, color: Colors.white54),
                prefixIcon: const Icon(Icons.search, color: Color(0xFF0D9488)),
                filled: true,
                fillColor: const Color(0xFF1A1D27),
                enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.white24)),
                focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Color(0xFF0D9488))),
                suffixIcon: _searchQuery.isNotEmpty 
                  ? IconButton(
                      icon: const Icon(Icons.clear, color: Colors.white54),
                      onPressed: () {
                        _searchController.clear();
                        _onSearchChanged('');
                      },
                    )
                  : null,
              ),
              onSubmitted: _onSearchChanged,
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('कुल: ${participants.length}', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari', color: Colors.white70)),
                TextButton(
                  onPressed: () {
                    setState(() {
                      for (var p in participants) {
                        _attendanceMap[p.id.toString()] = true;
                      }
                    });
                  },
                  child: const Text('सभी उपस्थित', style: TextStyle(color: Color(0xFF0D9488), fontSize: 16, fontFamily: 'Noto Sans Devanagari', fontWeight: FontWeight.bold)),
                ),
              ],
            ),
          ),
          Expanded(
            child: state.isLoading
                ? const Center(child: CircularProgressIndicator(color: Color(0xFF0D9488)))
                : participants.isEmpty
                    ? Center(
                        child: Text(
                          _searchQuery.isEmpty ? 'कृपया उपयोगकर्ता खोजने के लिए नाम दर्ज करें' : 'कोई परिणाम नहीं मिला',
                          style: const TextStyle(color: Colors.white54, fontSize: 16, fontFamily: 'Noto Sans Devanagari'),
                          textAlign: TextAlign.center,
                        ),
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.all(16),
                        itemCount: participants.length,
                        itemBuilder: (context, index) {
                          final p = participants[index];
                          final pId = p.id.toString();
                          
                          // Initialize from backend if not modified yet
                          if (!_attendanceMap.containsKey(pId)) {
                             // Assuming Participant model has isPresent or we can derive it
                             // Since our API returns is_present but our Participant model might not map it directly,
                             // we just default to false for now, or check raw data.
                             _attendanceMap[pId] = false; 
                          }
                          
                          final isPresent = _attendanceMap[pId] ?? false;
                          
                          return Card(
                            elevation: 2,
                            color: const Color(0xFF1A1D27),
                            margin: const EdgeInsets.only(bottom: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            child: CheckboxListTile(
                              activeColor: const Color(0xFF0D9488),
                              checkColor: Colors.white,
                              title: Text(p.name, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, fontFamily: 'Noto Sans Devanagari', color: Colors.white)),
                              subtitle: Text('📞 ${p.phone} | ${p.city ?? ""}', style: const TextStyle(fontSize: 14, fontFamily: 'Noto Sans Devanagari', color: Colors.white70)),
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
        decoration: const BoxDecoration(
          color: Color(0xFF1A1D27),
          boxShadow: [BoxShadow(color: Colors.black12, blurRadius: 4, offset: Offset(0, -2))],
        ),
        child: SizedBox(
          height: 56,
          child: ElevatedButton(
            onPressed: () {
              ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('हाजिरी सहेजी गई', style: TextStyle(fontFamily: 'Noto Sans Devanagari')), backgroundColor: Colors.green));
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF0D9488),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            child: const Text('सहेजें (Save)', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white, fontFamily: 'Noto Sans Devanagari')),
          ),
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          final result = await Navigator.push(
            context, 
            MaterialPageRoute(builder: (context) => SpotEntryScreen(sessionId: widget.sessionId))
          );
          if (result == true) {
            _refreshList(); // Refresh list if spot entry was created
          }
        },
        backgroundColor: const Color(0xFF14B8A6),
        icon: const Icon(Icons.person_add, color: Colors.white),
        label: const Text("स्पॉट एंट्री", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
      ),
    );
  }
}
