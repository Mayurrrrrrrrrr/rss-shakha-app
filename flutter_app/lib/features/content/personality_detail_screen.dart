import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

class PersonalityDetailScreen extends ConsumerWidget {
  final Map<String, dynamic> personality;

  const PersonalityDetailScreen({super.key, required this.personality});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final id = personality['id'] as String;
    final name = personality['name'] as String? ?? 'अनाम';
    final title = personality['title'] as String? ?? '';
    final biography = personality['biography'] as String? ?? '';
    final imagePath = personality['image_path'] as String? ?? '';

    return Scaffold(
      appBar: AppBar(
        title: Text(
          name,
          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
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
              Center(
                child: Hero(
                  tag: 'personality-image-$id',
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(20),
                    child: Container(
                      width: 250,
                      height: 250,
                      decoration: BoxDecoration(
                        border: Border.all(color: const Color(0xFFFFB74D), width: 2),
                      ),
                      child: Image.asset(
                        imagePath,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) {
                          return Container(
                            color: const Color(0xFFFFE0B2),
                            child: const Icon(
                              Icons.person,
                              color: Color(0xFFFF6B00),
                              size: 100,
                            ),
                          );
                        },
                      ),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 24),
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
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFFFE0B2), width: 1.5),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.orange.withValues(alpha: 0.05),
                      blurRadius: 8,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    const Icon(Icons.menu_book, color: Color(0xFFFF6B00), size: 28),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'प्रेरक जीवनी (Inspiring Biography)',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Colors.grey.shade800,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
              Text(
                biography,
                style: const TextStyle(
                  fontSize: 16.5,
                  color: Color(0xFF3E2723),
                  height: 1.75,
                  fontFamily: 'Noto Sans Devanagari',
                ),
              ),
              const SizedBox(height: 40),
            ],
          ),
        ),
      ),
    );
  }
}
