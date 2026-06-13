import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/models/models.dart';
import '../../core/providers/providers.dart';

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
    _tabController = TabController(length: 4, vsync: this);
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
            List<dynamic> shabdarthList = [];
            if (sub.shabdarth != null) {
              try {
                shabdarthList = jsonDecode(sub.shabdarth!);
              } catch (_) {}
            }

            return Card(
              elevation: 3,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              margin: const EdgeInsets.only(bottom: 16),
              color: Colors.white,
              child: Padding(
                padding: const EdgeInsets.all(18.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (sub.panchangText != null && sub.panchangText!.isNotEmpty) ...[
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFFF8E1),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: Colors.amber.shade200),
                        ),
                        child: Text(
                          sub.panchangText!,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF5D4037),
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                    ],
                    const Text('संस्कृत श्लोक:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.amber)),
                    const SizedBox(height: 6),
                    Text(
                      sub.sanskritText,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF5D4037),
                        height: 1.5,
                      ),
                    ),
                    const Divider(height: 24),
                    const Text('हिंदी भावार्थ:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.orange)),
                    const SizedBox(height: 6),
                    Text(
                      sub.hindiMeaning,
                      style: const TextStyle(fontSize: 15, color: Colors.black87, height: 1.4),
                    ),
                    if (shabdarthList.isNotEmpty) ...[
                      const Divider(height: 24),
                      const Text('शब्दार्थ (Glossary):', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.green)),
                      const SizedBox(height: 8),
                      Table(
                        border: TableBorder.all(color: Colors.grey.shade200, width: 1, borderRadius: BorderRadius.circular(8)),
                        columnWidths: const {
                          0: FixedColumnWidth(100),
                          1: FlexColumnWidth(),
                        },
                        children: shabdarthList.map((item) {
                          final word = item['shabd'] ?? '';
                          final meaning = item['arth'] ?? '';
                          return TableRow(
                            children: [
                              Padding(
                                padding: const EdgeInsets.all(8.0),
                                child: Text(word, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.brown)),
                              ),
                              Padding(
                                padding: const EdgeInsets.all(8.0),
                                child: Text(meaning),
                              ),
                            ],
                          );
                        }).toList(),
                      ),
                    ],
                  ],
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
              child: Padding(
                padding: const EdgeInsets.all(20.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.format_quote, size: 36, color: Color(0xFFFF6B00)),
                    Text(
                      vachan.content,
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
              child: ExpansionTile(
                title: Text(
                  geet.title,
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF5D4037)),
                ),
                subtitle: Text('श्रेणी: ${geet.geetType == "Sanghik" ? "संघिक" : "एकल"}'),
                leading: const Icon(Icons.music_note, color: Color(0xFFFF6B00)),
                children: [
                  Padding(
                    padding: const EdgeInsets.all(16.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('गीत पंक्तियाँ:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.grey)),
                        const SizedBox(height: 8),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.orange.shade50,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            geet.lyrics,
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w500,
                              color: Color(0xFF5D4037),
                              height: 1.6,
                            ),
                          ),
                        ),
                        if (geet.meaningOrContext != null && geet.meaningOrContext!.isNotEmpty) ...[
                          const SizedBox(height: 16),
                          const Text('विषय / संदर्भ:', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.grey)),
                          const SizedBox(height: 6),
                          Text(
                            geet.meaningOrContext!,
                            style: const TextStyle(fontSize: 14, color: Colors.black87),
                          ),
                        ]
                      ],
                    ),
                  ),
                ],
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
              child: Padding(
                padding: const EdgeInsets.all(18.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const CircleAvatar(
                          backgroundColor: Color(0xFFFFE0B2),
                          child: Text('📣', style: TextStyle(fontSize: 16)),
                        ),
                        const SizedBox(width: 12),
                        Text(
                          gh.ghoshnaDate,
                          style: const TextStyle(color: Colors.grey, fontSize: 13, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Text(
                      gh.sloganSanskrit,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 18,
                        color: Color(0xFFFF6B00),
                        height: 1.4,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      gh.sloganHindi,
                      style: const TextStyle(
                        fontSize: 15,
                        color: Color(0xFF388E3C),
                        fontWeight: FontWeight.bold,
                        height: 1.4,
                      ),
                    ),
                    if (gh.context != null && gh.context!.isNotEmpty) ...[
                      const Divider(height: 18),
                      Text(
                        gh.context!,
                        style: const TextStyle(
                          fontSize: 14,
                          color: Colors.black54,
                          fontStyle: FontStyle.italic,
                        ),
                      ),
                    ],
                  ],
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
