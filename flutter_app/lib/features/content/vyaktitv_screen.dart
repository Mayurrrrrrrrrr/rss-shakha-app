import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show rootBundle;
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:rss_shakha_app/core/providers/providers.dart';
import 'personality_grid_item.dart';

class VyaktitvScreen extends ConsumerStatefulWidget {
  const VyaktitvScreen({super.key});

  @override
  ConsumerState<VyaktitvScreen> createState() => _VyaktitvScreenState();
}

class _VyaktitvScreenState extends ConsumerState<VyaktitvScreen> {
  bool _isRefreshing = false;
  List<dynamic>? _bundledFallback;

  @override
  void initState() {
    super.initState();
    _loadBundledFallback();
    _backgroundRefresh();
  }

  /// Load bundled JSON as a fallback if SQLite is empty (first launch before sync)
  Future<void> _loadBundledFallback() async {
    try {
      final jsonStr = await rootBundle.loadString('assets/data/personalities.json');
      _bundledFallback = jsonDecode(jsonStr) as List<dynamic>;
    } catch (e) {
      debugPrint('Error loading bundled personalities: $e');
    }
  }

  /// Trigger a sync in the background to pull latest personalities from server
  Future<void> _backgroundRefresh() async {
    setState(() => _isRefreshing = true);
    try {
      final syncEngine = ref.read(syncEngineProvider);
      await syncEngine.sync();
      ref.invalidate(personalitiesListProvider);
    } catch (e) {
      debugPrint('Background personality refresh failed: $e');
    } finally {
      if (mounted) {
        setState(() => _isRefreshing = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final asyncPersonalities = ref.watch(personalitiesListProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '🚩 प्रेरक व्यक्तित्व (Vyaktitv)',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
        elevation: 0,
        actions: [
          if (_isRefreshing)
            const Padding(
              padding: EdgeInsets.all(16.0),
              child: SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Colors.white,
                ),
              ),
            ),
        ],
      ),
      body: Container(
        color: const Color(0xFFF9F6F0),
        child: RefreshIndicator(
          onRefresh: _backgroundRefresh,
          color: const Color(0xFFFF6B00),
          child: asyncPersonalities.when(
            data: (personalities) {
              // If SQLite has data, use it
              if (personalities.isNotEmpty) {
                return _buildGrid(personalities.map((p) => p.toJson()).toList());
              }

              // Otherwise use bundled fallback
              if (_bundledFallback != null && _bundledFallback!.isNotEmpty) {
                return _buildGrid(_bundledFallback!.cast<Map<String, dynamic>>());
              }

              return Center(
                child: Padding(
                  padding: const EdgeInsets.all(24.0),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.error_outline, size: 64, color: Colors.orange),
                      const SizedBox(height: 16),
                      Text(
                        'व्यक्तित्व जानकारी लोड करने में विफल।',
                        textAlign: TextAlign.center,
                        style: TextStyle(fontSize: 16, color: Theme.of(context).colorScheme.onSurface, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'सिंक होने पर जानकारी स्वचालित आ जाएगी।',
                        style: TextStyle(fontSize: 13, color: Colors.grey.shade500),
                      ),
                      const SizedBox(height: 24),
                      ElevatedButton.icon(
                        onPressed: () {
                          ref.invalidate(personalitiesListProvider);
                          _backgroundRefresh();
                        },
                        icon: const Icon(Icons.refresh, color: Colors.white),
                        label: const Text('पुनः प्रयास करें', style: TextStyle(color: Colors.white)),
                        style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFFF6B00)),
                      ),
                    ],
                  ),
                ),
              );
            },
            loading: () => const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00))),
            error: (e, _) => Center(child: Text('त्रुटि: $e')),
          ),
        ),
      ),
    );
  }

  Widget _buildGrid(List<dynamic> personalities) {
    return GridView.builder(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16.0),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 16,
        mainAxisSpacing: 16,
        childAspectRatio: 0.72,
      ),
      itemCount: personalities.length,
      itemBuilder: (context, index) {
        final p = personalities[index] as Map<String, dynamic>;
        return PersonalityGridItem(personality: p);
      },
    );
  }
}
