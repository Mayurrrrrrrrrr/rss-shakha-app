import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/providers.dart';
import 'content_reading_screen.dart';

class ContentScreen extends ConsumerStatefulWidget {
  const ContentScreen({super.key});

  @override
  ConsumerState<ContentScreen> createState() => _ContentScreenState();
}

class _ContentScreenState extends ConsumerState<ContentScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 5, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '📖 बौद्धिक सामग्री संग्रह',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'प्रार्थना'),
            Tab(text: 'सुभाषित'),
            Tab(text: 'अमृत वचन'),
            Tab(text: 'गीत'),
            Tab(text: 'घोषणाएँ'),
          ],
        ),
      ),
      body: Container(
        color: const Color(0xFFF9F6F0),
        child: Column(
          children: [
            // Search field
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: TextField(
                onChanged: (val) => setState(() => _searchQuery = val.trim()),
                decoration: InputDecoration(
                  hintText: 'सामग्री खोजें...',
                  prefixIcon: const Icon(Icons.search, color: Color(0xFFFF6B00)),
                  filled: true,
                  fillColor: Colors.white,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: BorderSide.none,
                  ),
                  contentPadding: const EdgeInsets.symmetric(vertical: 12),
                ),
              ),
            ),
            Expanded(
              child: TabBarView(
                controller: _tabController,
                children: [
                  _buildPrarthnaTab(),
                  _buildSubhashitsTab(),
                  _buildAmritVachansTab(),
                  _buildGeetsTab(),
                  _buildGhoshnayeinTab(),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPrarthnaTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Card(
        elevation: 4,
        shadowColor: const Color(0xFFFF6B00).withValues(alpha: 0.15),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        color: Colors.white,
        child: Column(
          children: [
            Container(
              decoration: const BoxDecoration(
                color: Color(0xFFFF6B00),
                borderRadius: BorderRadius.only(
                  topLeft: Radius.circular(16),
                  topRight: Radius.circular(16),
                ),
              ),
              padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
              width: double.infinity,
              child: const Column(
                children: [
                  Text(
                    '🚩 राष्ट्रीय स्वयंसेवक संघ',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                  SizedBox(height: 6),
                  Text(
                    'संघ प्रार्थना',
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                children: [
                  const Text(
                    'नमस्ते सदा वत्सले मातृभूमे,\nत्वया हिन्दुभूमे सुखं वर्धितोऽहम्।\nमहामङ्गले पुण्यभूमे त्वदर्थे।\nपतत्वेष कायो नमस्ते नमस्ते ॥१॥',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF4E342E),
                      height: 1.6,
                    ),
                  ),
                  const SizedBox(height: 20),
                  Text(
                    'राष्ट्रीय स्वयंसेवक संघ की प्रार्थना पूर्णतः राष्ट्रभक्ति से ओतप्रोत है। यह संस्कृत भाषा में है और इसमें तीन श्लोक हैं।',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 14,
                      color: Colors.grey.shade700,
                      height: 1.5,
                    ),
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFFF6B00),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    icon: const Icon(Icons.chrome_reader_mode),
                    label: const Text(
                      'पूर्ण प्रार्थना, अनुवाद व शब्दार्थ पढ़ें',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                    ),
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const ContentReadingScreen(
                            type: ContentType.prarthna,
                            title: 'संघ प्रार्थना',
                            content: '',
                          ),
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSubhashitsTab() {
    final asyncData = ref.watch(subhashitsListProvider);
    return asyncData.when(
      data: (list) {
        final filtered = list.where((x) =>
            x.sanskritText.toLowerCase().contains(_searchQuery.toLowerCase()) ||
            x.hindiMeaning.toLowerCase().contains(_searchQuery.toLowerCase())).toList();

        if (filtered.isEmpty) return const Center(child: Text('कोई सुभाषित नहीं मिला।'));

        return ListView.builder(
          itemCount: filtered.length,
          padding: const EdgeInsets.all(16),
          itemBuilder: (ctx, index) {
            final sub = filtered[index];
            return Card(
              elevation: 2,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              margin: const EdgeInsets.only(bottom: 16),
              color: Colors.white,
              child: InkWell(
                borderRadius: BorderRadius.circular(16),
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => ContentReadingScreen(
                        type: ContentType.subhashit,
                        title: sub.panchangText != null && sub.panchangText!.isNotEmpty
                            ? sub.panchangText!
                            : 'सुभाषित श्लोक',
                        content: sub.sanskritText,
                        extra: sub.hindiMeaning,
                        listData: sub.shabdarth,
                      ),
                    ),
                  );
                },
                child: Padding(
                  padding: const EdgeInsets.all(18.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            sub.panchangText != null && sub.panchangText!.isNotEmpty
                                ? sub.panchangText!
                                : '📖 सुभाषित श्लोक',
                            style: const TextStyle(
                              fontWeight: FontWeight.bold,
                              color: Color(0xFFFF6B00),
                              fontSize: 14,
                            ),
                          ),
                          const Icon(Icons.arrow_forward_ios, size: 14, color: Colors.grey),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Text(
                        sub.sanskritText,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF5D4037),
                          height: 1.4,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        sub.hindiMeaning,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.grey.shade700,
                          height: 1.4,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
      loading: () => const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00))),
      error: (e, _) => Center(child: Text('त्रुटि: $e')),
    );
  }

  Widget _buildAmritVachansTab() {
    final asyncData = ref.watch(amritVachansListProvider);
    return asyncData.when(
      data: (list) {
        final filtered = list.where((x) =>
            x.content.toLowerCase().contains(_searchQuery.toLowerCase()) ||
            (x.author ?? '').toLowerCase().contains(_searchQuery.toLowerCase())).toList();

        if (filtered.isEmpty) return const Center(child: Text('कोई अमृत वचन नहीं मिला।'));

        return ListView.builder(
          itemCount: filtered.length,
          padding: const EdgeInsets.all(16),
          itemBuilder: (ctx, index) {
            final vachan = filtered[index];
            return Card(
              elevation: 2,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              margin: const EdgeInsets.only(bottom: 16),
              color: const Color(0xFFFFFDE7), // Light yellow card
              child: InkWell(
                borderRadius: BorderRadius.circular(16),
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => ContentReadingScreen(
                        type: ContentType.amritVachan,
                        title: 'अमृत वचन',
                        content: vachan.content,
                        extra: vachan.author,
                      ),
                    ),
                  );
                },
                child: Padding(
                  padding: const EdgeInsets.all(20.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Icon(Icons.format_quote, size: 36, color: Color(0xFFFF6B00)),
                          Icon(Icons.arrow_forward_ios, size: 14, color: Colors.grey),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        vachan.content,
                        maxLines: 3,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                          color: Color(0xFF3E2723),
                          height: 1.5,
                        ),
                      ),
                      const SizedBox(height: 12),
                      Align(
                        alignment: Alignment.bottomRight,
                        child: Text(
                          '- ${vachan.author ?? "अज्ञात"}',
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFFFF6B00),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
      loading: () => const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00))),
      error: (e, _) => Center(child: Text('त्रुटि: $e')),
    );
  }

  Widget _buildGeetsTab() {
    final asyncData = ref.watch(geetsListProvider);
    return asyncData.when(
      data: (list) {
        final filtered = list.where((x) =>
            x.title.toLowerCase().contains(_searchQuery.toLowerCase()) ||
            x.lyrics.toLowerCase().contains(_searchQuery.toLowerCase())).toList();

        if (filtered.isEmpty) return const Center(child: Text('कोई गीत नहीं मिला।'));

        return ListView.builder(
          itemCount: filtered.length,
          padding: const EdgeInsets.all(16),
          itemBuilder: (ctx, index) {
            final geet = filtered[index];
            return Card(
              elevation: 2,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              margin: const EdgeInsets.only(bottom: 16),
              color: Colors.white,
              child: InkWell(
                borderRadius: BorderRadius.circular(16),
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => ContentReadingScreen(
                        type: ContentType.geet,
                        title: geet.title,
                        content: geet.lyrics,
                        extra: geet.geetType,
                      ),
                    ),
                  );
                },
                child: ListTile(
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  leading: const CircleAvatar(
                    backgroundColor: Color(0xFFFFF3E0),
                    child: Icon(Icons.music_note, color: Color(0xFFFF6B00)),
                  ),
                  title: Text(
                    geet.title,
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF5D4037)),
                  ),
                  subtitle: Text(
                    'श्रेणी: ${geet.geetType == "Sanghik" ? "संघिक गीत" : "एकल गीत"}',
                    style: TextStyle(color: Colors.grey.shade600),
                  ),
                  trailing: const Icon(Icons.arrow_forward_ios, size: 14, color: Colors.grey),
                ),
              ),
            );
          },
        );
      },
      loading: () => const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00))),
      error: (e, _) => Center(child: Text('त्रुटि: $e')),
    );
  }

  Widget _buildGhoshnayeinTab() {
    final asyncData = ref.watch(ghoshnayeinListProvider);
    return asyncData.when(
      data: (list) {
        final filtered = list.where((x) =>
            x.sloganSanskrit.toLowerCase().contains(_searchQuery.toLowerCase()) ||
            x.sloganHindi.toLowerCase().contains(_searchQuery.toLowerCase()) ||
            (x.context ?? '').toLowerCase().contains(_searchQuery.toLowerCase())).toList();

        if (filtered.isEmpty) return const Center(child: Text('कोई घोषणा नहीं मिली।'));

        return ListView.builder(
          itemCount: filtered.length,
          padding: const EdgeInsets.all(16),
          itemBuilder: (ctx, index) {
            final gh = filtered[index];
            return Card(
              elevation: 2,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              margin: const EdgeInsets.only(bottom: 12),
              color: Colors.white,
              child: InkWell(
                borderRadius: BorderRadius.circular(16),
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => ContentReadingScreen(
                        type: ContentType.ghoshna,
                        title: 'घोषणा (Ghoshna)',
                        content: gh.sloganSanskrit,
                        extra: gh.sloganHindi,
                      ),
                    ),
                  );
                },
                child: Padding(
                  padding: const EdgeInsets.all(18.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              CircleAvatar(
                                backgroundColor: Color(0xFFFFE0B2),
                                child: Text('📣', style: TextStyle(fontSize: 16)),
                              ),
                              SizedBox(width: 12),
                              Text(
                                'घोषणा (Slogan)',
                                style: TextStyle(fontWeight: FontWeight.bold, color: Colors.grey),
                              ),
                            ],
                          ),
                          Icon(Icons.arrow_forward_ios, size: 14, color: Colors.grey),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Text(
                        gh.sloganSanskrit,
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                          color: Color(0xFFFF6B00),
                          height: 1.4,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        gh.sloganHindi,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 14,
                          color: Color(0xFF388E3C),
                          fontWeight: FontWeight.bold,
                          height: 1.4,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
      loading: () => const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00))),
      error: (e, _) => Center(child: Text('त्रुटि: $e')),
    );
  }
}
