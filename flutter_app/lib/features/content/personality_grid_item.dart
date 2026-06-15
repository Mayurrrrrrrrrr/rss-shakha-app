import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'personality_detail_screen.dart';

class PersonalityGridItem extends ConsumerWidget {
  final Map<String, dynamic> personality;

  const PersonalityGridItem({super.key, required this.personality});

  String? _getLocalFallbackAsset(String name) {
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

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final id = personality['id']?.toString() ?? '';
    final name = personality['name'] as String? ?? 'अनाम';
    final title = personality['title'] as String? ?? '';
    final desc = personality['description'] as String? ?? '';
    final imagePath = personality['image_path'] as String? ?? '';

    Widget defaultFallback() {
      return Container(
        color: const Color(0xFFFFE0B2),
        child: const Icon(
          Icons.person,
          color: Color(0xFFFF6B00),
          size: 48,
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
      final fallbackAsset = _getLocalFallbackAsset(name);

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
                width: 24,
                height: 24,
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

    return Card(
      elevation: 4,
      shadowColor: Colors.orange.withValues(alpha: 0.2),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      color: Colors.white,
      child: InkWell(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => PersonalityDetailScreen(personality: personality),
            ),
          );
        },
        borderRadius: BorderRadius.circular(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(
              child: ClipRRect(
                borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                child: Hero(
                  tag: 'personality-image-$id',
                  child: buildImage(),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 10.0, vertical: 12.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF5D4037),
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 11,
                      color: Color(0xFFFF6B00),
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    desc,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: 11,
                      color: Colors.grey.shade600,
                      height: 1.3,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
