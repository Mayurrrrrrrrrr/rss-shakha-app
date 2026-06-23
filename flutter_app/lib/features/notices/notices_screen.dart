import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/providers.dart';

class NoticesScreen extends ConsumerStatefulWidget {
  const NoticesScreen({super.key});

  @override
  ConsumerState<NoticesScreen> createState() => _NoticesScreenState();
}

class _NoticesScreenState extends ConsumerState<NoticesScreen> {
  bool _isRefreshing = false;

  @override
  void initState() {
    super.initState();
    _backgroundRefresh();
  }

  /// Trigger a sync in the background to pull latest notices from server
  Future<void> _backgroundRefresh() async {
    setState(() => _isRefreshing = true);
    try {
      final syncEngine = ref.read(syncEngineProvider);
      await syncEngine.sync();
      // Invalidate the provider to refresh the UI with new data
      ref.invalidate(noticesListProvider);
    } catch (e) {
      debugPrint('Background notice refresh failed: $e');
    } finally {
      if (mounted) {
        setState(() => _isRefreshing = false);
      }
    }
  }

  String _formatNoticeDate(String dateStr) {
    try {
      final date = DateTime.parse(dateStr);
      final List<String> hindiMonths = [
        'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून',
        'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'
      ];
      return '${date.day} ${hindiMonths[date.month - 1]} ${date.year}';
    } catch (_) {
      return dateStr;
    }
  }

  @override
  Widget build(BuildContext context) {
    final asyncNotices = ref.watch(noticesListProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '📢 सूचना पट्ट (Notice Board)',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
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
          child: asyncNotices.when(
            data: (notices) {
              if (notices.isEmpty) {
                return SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  child: Container(
                    height: MediaQuery.of(context).size.height * 0.7,
                    alignment: Alignment.center,
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.notifications_none, size: 64, color: Colors.grey.shade400),
                        const SizedBox(height: 16),
                        const Text(
                          'कोई वर्तमान सूचना उपलब्ध नहीं है।',
                          style: TextStyle(fontSize: 16, color: Colors.grey),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          'नई सूचनाएँ स्वचालित रूप से सिंक होंगी।',
                          style: TextStyle(fontSize: 13, color: Colors.grey.shade500),
                        ),
                      ],
                    ),
                  ),
                );
              }

              return ListView.builder(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16.0),
                itemCount: notices.length,
                itemBuilder: (context, index) {
                  final notice = notices[index];
                  return Card(
                    elevation: 3,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    margin: const EdgeInsets.only(bottom: 16),
                    color: Colors.white,
                    child: Padding(
                      padding: const EdgeInsets.all(16.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              const CircleAvatar(
                                backgroundColor: Color(0xFFFFF3E0),
                                child: Icon(Icons.campaign, color: Color(0xFFFF6B00)),
                              ),
                              if (notice.noticeDate != null && notice.noticeDate!.isNotEmpty)
                                Text(
                                  _formatNoticeDate(notice.noticeDate!),
                                  style: const TextStyle(fontSize: 12, color: Colors.grey, fontWeight: FontWeight.bold),
                                ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Text(
                            notice.subject,
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Theme.of(context).colorScheme.onSurface,
                            ),
                          ),
                          if (notice.location != null && notice.location!.isNotEmpty) ...[
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                const Icon(Icons.location_on, size: 14, color: Colors.grey),
                                const SizedBox(width: 4),
                                Text(
                                  notice.location!,
                                  style: const TextStyle(fontSize: 13, color: Colors.grey),
                                ),
                              ],
                            ),
                          ],
                          const SizedBox(height: 8),
                          const Divider(),
                          const SizedBox(height: 8),
                          Text(
                            notice.message,
                            style: const TextStyle(
                              fontSize: 15,
                              color: Color(0xFF4E342E),
                              height: 1.4,
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              );
            },
            loading: () => const Center(
              child: CircularProgressIndicator(color: Color(0xFFFF6B00)),
            ),
            error: (e, _) => SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              child: Container(
                height: MediaQuery.of(context).size.height * 0.7,
                alignment: Alignment.center,
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.error_outline, size: 64, color: Colors.red),
                    const SizedBox(height: 16),
                    Text(
                      'सूचनाएँ लोड करने में त्रुटि: $e',
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 16, color: Colors.red, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 24),
                    ElevatedButton.icon(
                      onPressed: () {
                        ref.invalidate(noticesListProvider);
                        _backgroundRefresh();
                      },
                      icon: const Icon(Icons.refresh, color: Colors.white),
                      label: const Text('पुनः प्रयास करें', style: TextStyle(color: Colors.white)),
                      style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFFF6B00)),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
