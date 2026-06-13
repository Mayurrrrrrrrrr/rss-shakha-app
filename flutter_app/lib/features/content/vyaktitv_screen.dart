import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/api/api_client.dart';
import '../../core/providers/providers.dart';

class VyaktitvScreen extends ConsumerStatefulWidget {
  const VyaktitvScreen({super.key});

  @override
  ConsumerState<VyaktitvScreen> createState() => _VyaktitvScreenState();
}

class _SnapshotImage extends StatelessWidget {
  final String? path;
  const _SnapshotImage(this.path);

  @override
  Widget build(BuildContext context) {
    if (path == null || path!.isEmpty) {
      return Container(
        color: const Color(0xFFFFE0B2),
        child: const Icon(Icons.person, color: Color(0xFFFF6B00), size: 40),
      );
    }
    
    final fullUrl = path!.startsWith('http') ? path! : '${ApiClient.baseUrl}$path';
    return Image.network(
      fullUrl,
      fit: BoxFit.cover,
      errorBuilder: (context, error, stackTrace) {
        return Container(
          color: const Color(0xFFFFE0B2),
          child: const Icon(Icons.person, color: Color(0xFFFF6B00), size: 40),
        );
      },
    );
  }
}

class _VyaktitvScreenState extends ConsumerState<VyaktitvScreen> {
  bool _isLoading = true;
  String? _error;
  List<dynamic> _personalities = [];

  @override
  void initState() {
    super.initState();
    _fetchPersonalities();
  }

  Future<void> _fetchPersonalities() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.get('/api/get_personalities.php');

      if (response.statusCode == 200 && response.data != null) {
        final data = response.data;
        if (data['success'] == true) {
          setState(() {
            _personalities = data['data'] as List<dynamic>? ?? [];
          });
        } else {
          setState(() {
            _error = data['message'] ?? 'व्यक्तित्व सूची लोड करने में विफल।';
          });
        }
      } else {
        setState(() {
          _error = 'सर्वर से कनेक्ट करने में विफल (HTTP ${response.statusCode})';
        });
      }
    } catch (e) {
      debugPrint('Error fetching personalities: $e');
      setState(() {
        _error = 'नेटवर्क त्रुटि या सर्वर उपलब्ध नहीं है।';
      });
    } finally {
      setState(() {
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
      ),
      body: Container(
        color: const Color(0xFFF9F6F0),
        child: RefreshIndicator(
          onRefresh: _fetchPersonalities,
          color: const Color(0xFFFF6B00),
          child: _isLoading
              ? const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00)))
              : _error != null
                  ? SingleChildScrollView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      child: Container(
                        height: MediaQuery.of(context).size.height * 0.7,
                        alignment: Alignment.center,
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
                              onPressed: _fetchPersonalities,
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
                      : ListView.builder(
                          physics: const AlwaysScrollableScrollPhysics(),
                          padding: const EdgeInsets.all(16.0),
                          itemCount: _personalities.length,
                          itemBuilder: (context, index) {
                            final p = _personalities[index] as Map<String, dynamic>;
                            final name = p['name'] as String? ?? 'अनाम';
                            final title = p['title'] as String? ?? '';
                            final desc = p['description'] as String? ?? '';
                            final imagePath = p['image_path'] as String?;

                            return Card(
                              elevation: 3,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                              margin: const EdgeInsets.only(bottom: 16),
                              color: Colors.white,
                              child: InkWell(
                                onTap: () => _openDetailScreen(name, title, desc, imagePath),
                                borderRadius: BorderRadius.circular(16),
                                child: Padding(
                                  padding: const EdgeInsets.all(12.0),
                                  child: Row(
                                    children: [
                                      ClipRRect(
                                        borderRadius: BorderRadius.circular(12),
                                        child: SizedBox(
                                          width: 80,
                                          height: 80,
                                          child: _SnapshotImage(imagePath),
                                        ),
                                      ),
                                      const SizedBox(width: 16),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              name,
                                              style: const TextStyle(
                                                fontSize: 18,
                                                fontWeight: FontWeight.bold,
                                                color: Color(0xFF5D4037),
                                              ),
                                            ),
                                            if (title.isNotEmpty) ...[
                                              const SizedBox(height: 4),
                                              Text(
                                                title,
                                                style: const TextStyle(
                                                  fontSize: 14,
                                                  color: Colors.grey,
                                                  fontWeight: FontWeight.bold,
                                                ),
                                              ),
                                            ],
                                            const SizedBox(height: 6),
                                            Text(
                                              desc,
                                              maxLines: 2,
                                              overflow: TextOverflow.ellipsis,
                                              style: TextStyle(
                                                fontSize: 13,
                                                color: Colors.grey.shade700,
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                      const Icon(Icons.chevron_right, color: Colors.grey),
                                    ],
                                  ),
                                ),
                              ),
                            );
                          },
                        ),
        ),
      ),
    );
  }

  void _openDetailScreen(String name, String title, String desc, String? imagePath) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (ctx) => Scaffold(
          appBar: AppBar(
            title: Text(name, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
            backgroundColor: const Color(0xFFFF6B00),
            iconTheme: const IconThemeData(color: Colors.white),
          ),
          body: Container(
            color: const Color(0xFFFFF9F2),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(20.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (imagePath != null && imagePath.isNotEmpty) ...[
                    Center(
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(20),
                        child: Container(
                          width: 250,
                          height: 250,
                          decoration: BoxDecoration(
                            border: Border.all(color: const Color(0xFFFFB74D), width: 2),
                          ),
                          child: _SnapshotImage(imagePath),
                        ),
                      ),
                    ),
                    const SizedBox(height: 24),
                  ],
                  Center(
                    child: Text(
                      name,
                      style: const TextStyle(
                        fontSize: 26,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFFE65100),
                      ),
                    ),
                  ),
                  if (title.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Center(
                      child: Text(
                        title,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Colors.brown,
                        ),
                      ),
                    ),
                  ],
                  const SizedBox(height: 16),
                  const Divider(color: Color(0xFFFFB74D)),
                  const SizedBox(height: 16),
                  Text(
                    desc,
                    style: const TextStyle(
                      fontSize: 16,
                      color: Color(0xFF3E2723),
                      height: 1.6,
                      fontFamily: 'Noto Sans Devanagari',
                    ),
                  ),
                  const SizedBox(height: 40),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
