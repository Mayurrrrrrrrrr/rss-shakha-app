import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show rootBundle;
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'personality_grid_item.dart';

class VyaktitvScreen extends ConsumerStatefulWidget {
  const VyaktitvScreen({super.key});

  @override
  ConsumerState<VyaktitvScreen> createState() => _VyaktitvScreenState();
}

class _VyaktitvScreenState extends ConsumerState<VyaktitvScreen> {
  bool _isLoading = true;
  String? _error;
  List<dynamic> _personalities = [];

  @override
  void initState() {
    super.initState();
    _loadPersonalities();
  }

  Future<void> _loadPersonalities() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final jsonStr = await rootBundle.loadString('assets/data/personalities.json');
      final data = jsonDecode(jsonStr) as List<dynamic>;
      setState(() {
        _personalities = data;
        _isLoading = false;
      });
    } catch (e) {
      debugPrint('Error loading personalities: $e');
      setState(() {
        _error = 'व्यक्तित्व जानकारी लोड करने में विफल।';
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '🚩 प्रेरक व्यक्तित्व (Vyaktitv)',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
        elevation: 0,
      ),
      body: Container(
        color: const Color(0xFFF9F6F0),
        child: _isLoading
            ? const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)))
            : _error != null
                ? Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24.0),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.error_outline, size: 64, color: Colors.orange),
                          const SizedBox(height: 16),
                          Text(
                            _error!,
                            textAlign: TextAlign.center,
                            style: const TextStyle(fontSize: 16, color: Color(0xFF5D4037), fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 24),
                          ElevatedButton.icon(
                            onPressed: _loadPersonalities,
                            icon: const Icon(Icons.refresh, color: Colors.white),
                            label: const Text('पुनः प्रयास करें', style: TextStyle(color: Colors.white)),
                            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFFF6B00)),
                          ),
                        ],
                      ),
                    ),
                  )
                : _personalities.isEmpty
                    ? const Center(
                        child: Text(
                          'कोई व्यक्तित्व जानकारी उपलब्ध नहीं है।',
                          style: TextStyle(fontSize: 16, color: Colors.grey),
                        ),
                      )
                    : GridView.builder(
                        padding: const EdgeInsets.all(16.0),
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 2,
                          crossAxisSpacing: 16,
                          mainAxisSpacing: 16,
                          childAspectRatio: 0.72,
                        ),
                        itemCount: _personalities.length,
                        itemBuilder: (context, index) {
                          final p = _personalities[index] as Map<String, dynamic>;
                          return PersonalityGridItem(personality: p);
                        },
                      ),
      ),
    );
  }
}
