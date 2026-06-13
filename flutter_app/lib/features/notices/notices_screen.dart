import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/providers/providers.dart';

class NoticesScreen extends ConsumerStatefulWidget {
  const NoticesScreen({super.key});

  @override
  ConsumerState<NoticesScreen> createState() => _NoticesScreenState();
}

class _NoticesScreenState extends ConsumerState<NoticesScreen> {
  bool _isLoading = true;
  String? _error;
  List<dynamic> _notices = [];

  @override
  void initState() {
    super.initState();
    _fetchNotices();
  }

  Future<void> _fetchNotices() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final session = ref.read(sessionProvider);
      final apiClient = ref.read(apiClientProvider);

      final response = await apiClient.post(
        '/api/get_notices.php',
        data: {'shakha_id': session.shakhaId},
      );

      if (response.statusCode == 200 && response.data != null) {
        final data = response.data;
        if (data['success'] == true) {
          setState(() {
            _notices = data['data'] as List<dynamic>? ?? [];
          });
        } else {
          setState(() {
            _error = data['message'] ?? 'सूचनाएँ लोड करने में विफल।';
          });
        }
      } else {
        setState(() {
          _error = 'सर्वर से कनेक्ट करने में विफल (HTTP ${response.statusCode})';
        });
      }
    } catch (e) {
      debugPrint('Error fetching notices: $e');
      setState(() {
        _error = 'नेटवर्क त्रुटि या सर्वर उपलब्ध नहीं है।';
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
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
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '📢 सूचना पट्ट (Notice Board)',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Container(
        color: const Color(0xFFF9F6F0),
        child: RefreshIndicator(
          onRefresh: _fetchNotices,
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
                            const Icon(Icons.error_outline, size: 64, color: Colors.red),
                            const SizedBox(height: 16),
                            Text(
                              _error!,
                              textAlign: TextAlign.center,
                              style: const TextStyle(fontSize: 16, color: Colors.red, fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 24),
                            ElevatedButton.icon(
                              onPressed: _fetchNotices,
                              icon: const Icon(Icons.refresh, color: Colors.white),
                              label: const Text('पुनः प्रयास करें', style: TextStyle(color: Colors.white)),
                              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFFF6B00)),
                            ),
                          ],
                        ),
                      ),
                    )
                  : _notices.isEmpty
                      ? const Center(
                          child: Text(
                            'कोई वर्तमान सूचना उपलब्ध नहीं है।',
                            style: TextStyle(fontSize: 16, color: Colors.grey),
                          ),
                        )
                      : ListView.builder(
                          physics: const AlwaysScrollableScrollPhysics(),
                          padding: const EdgeInsets.all(16.0),
                          itemCount: _notices.length,
                          itemBuilder: (context, index) {
                            final notice = _notices[index] as Map<String, dynamic>;
                            final subject = notice['subject'] as String? ?? 'बिना विषय की सूचना';
                            final message = notice['message'] as String? ?? '';
                            final noticeDate = notice['notice_date'] as String? ?? '';
                            
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
                                        Text(
                                          _formatNoticeDate(noticeDate),
                                          style: const TextStyle(fontSize: 12, color: Colors.grey, fontWeight: FontWeight.bold),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 12),
                                    Text(
                                      subject,
                                      style: const TextStyle(
                                        fontSize: 18,
                                        fontWeight: FontWeight.bold,
                                        color: Color(0xFF5D4037),
                                      ),
                                    ),
                                    const SizedBox(height: 8),
                                    const Divider(),
                                    const SizedBox(height: 8),
                                    Text(
                                      message,
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
                        ),
        ),
      ),
    );
  }
}
