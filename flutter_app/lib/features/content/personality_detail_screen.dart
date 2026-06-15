import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:cached_network_image/cached_network_image.dart';

class PersonalityDetailScreen extends ConsumerWidget {
  final Map<String, dynamic> personality;

  const PersonalityDetailScreen({super.key, required this.personality});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final id = personality['id']?.toString() ?? '';
    final name = personality['name'] as String? ?? 'अनाम';
    final title = personality['title'] as String? ?? '';
    final biography = (personality['biography'] as String?) ?? (personality['description'] as String?) ?? '';
    final imagePath = personality['image_path'] as String? ?? '';

    String? getLocalFallbackAsset(String name) {
      final lowercaseName = name.toLowerCase();
      if (lowercaseName.contains('hedgewar') || lowercaseName.contains('हेडगेवार')) {
        return 'assets/images/personalities/hedgewar.png';
      }
      if (lowercaseName.contains('golwalkar') || lowercaseName.contains('गोलवलकर')) {
        return 'assets/images/personalities/golwalkar.png';
      }
      if (lowercaseName.contains('deoras') || lowercaseName.contains('देवरस')) {
        return 'assets/images/personalities/deoras.png';
      }
      if (lowercaseName.contains('vivekananda') || lowercaseName.contains('विवेकानंद')) {
        return 'assets/images/personalities/vivekananda.png';
      }
      return null;
    }

    Widget defaultFallback() {
      return Container(
        color: const Color(0xFFFFE0B2),
        child: const Icon(
          Icons.person,
          color: Color(0xFFFF6B00),
          size: 100,
        ),
      );
    }

    Widget loadAsset(String assetPath) {
      return Image.asset(
        assetPath,
        fit: BoxFit.cover,
        errorBuilder: (ctx, err, stack) => defaultFallback(),
      );
    }

    Widget buildImage() {
      final fallbackAsset = getLocalFallbackAsset(name);

      if (imagePath.isEmpty) {
        if (fallbackAsset != null) return loadAsset(fallbackAsset);
        return defaultFallback();
      }

      String imageUrl = imagePath;
      if (imagePath.startsWith('/')) {
        imageUrl = 'https://sanghasthan.yuktaa.com$imagePath';
      }

      if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
        return CachedNetworkImage(
          imageUrl: imageUrl,
          fit: BoxFit.cover,
          placeholder: (ctx, url) => Container(
            color: const Color(0xFFFFE0B2),
            child: const Center(
              child: SizedBox(
                width: 32,
                height: 32,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Color(0xFFFF6B00),
                ),
              ),
            ),
          ),
          errorWidget: (ctx, url, err) {
            if (fallbackAsset != null) return loadAsset(fallbackAsset);
            return defaultFallback();
          },
        );
      } else {
        return Image.asset(
          imagePath,
          fit: BoxFit.cover,
          errorBuilder: (ctx, err, stack) {
            if (fallbackAsset != null) return loadAsset(fallbackAsset);
            return defaultFallback();
          },
        );
      }
    }

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
                      child: buildImage(),
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
