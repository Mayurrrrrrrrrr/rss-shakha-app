import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show rootBundle;
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:rss_shakha_app/core/providers/providers.dart';
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
    if (_personalities.isEmpty) {
      setState(() {
        _isLoading = true;
        _error = null;
      });
    }

    // 1. Try to load from cache
    List<dynamic>? loadedList;
    try {
      final prefs = await SharedPreferences.getInstance();
      final cachedData = prefs.getString('cached_personalities');
      if (cachedData != null && cachedData.isNotEmpty) {
        loadedList = jsonDecode(cachedData) as List<dynamic>;
      }
    } catch (e) {
      debugPrint('Error loading cached personalities: $e');
    }

    // 2. If cache is empty, load from bundled personalities.json
    if (loadedList == null || loadedList.isEmpty) {
      try {
        final jsonStr = await rootBundle.loadString('assets/data/personalities.json');
        loadedList = jsonDecode(jsonStr) as List<dynamic>;
      } catch (e) {
        debugPrint('Error loading assets personalities: $e');
      }
    }

    if (loadedList != null && loadedList.isNotEmpty) {
      if (mounted) {
        setState(() {
          _personalities = loadedList!;
          _isLoading = false;
        });
      }
    }

    // 3. Fetch latest from Server in background
    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.fetchPersonalities();
      
      if (response.statusCode == 200 && response.data != null) {
        final responseData = response.data;
        if (responseData['success'] == true && responseData['data'] != null) {
          final serverList = responseData['data'] as List<dynamic>;
          
          if (serverList.isNotEmpty) {
            // Save to cache
            final prefs = await SharedPreferences.getInstance();
            await prefs.setString('cached_personalities', jsonEncode(serverList));
            
            if (mounted) {
              setState(() {
                _personalities = serverList;
                _isLoading = false;
                _error = null;
              });
            }
            return;
          }
        }
      }
      // If we failed to get data from API, but we already have local data, we just stop loading silently.
      if (_personalities.isNotEmpty) {
        if (mounted) {
          setState(() {
            _isLoading = false;
          });
        }
      } else {
        if (mounted) {
          setState(() {
            _error = 'व्यक्तित्व जानकारी लोड करने में विफल।';
            _isLoading = false;
          });
        }
      }
    } catch (e) {
      debugPrint('Network error loading personalities: $e');
      if (_personalities.isNotEmpty) {
        if (mounted) {
          setState(() {
            _isLoading = false;
          });
        }
      } else {
        if (mounted) {
          setState(() {
            _error = 'कनेक्शन विफलता। व्यक्तित्व जानकारी लोड करने में असमर्थ।';
            _isLoading = false;
          });
        }
      }
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
        child: _isLoading && _personalities.isEmpty
            ? const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)))
            : _error != null && _personalities.isEmpty
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
                : RefreshIndicator(
                    onRefresh: _loadPersonalities,
                    color: const Color(0xFFFF6B00),
                    child: GridView.builder(
                      physics: const AlwaysScrollableScrollPhysics(),
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
      ),
    );
  }
}
