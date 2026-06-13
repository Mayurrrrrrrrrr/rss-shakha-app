import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';

class SnapshotWebViewScreen extends StatefulWidget {
  final String url;
  final String title;

  const SnapshotWebViewScreen({
    super.key,
    required this.url,
    this.title = '📸 रिपोर्ट स्नैपशॉट',
  });

  @override
  State<SnapshotWebViewScreen> createState() => _SnapshotWebViewScreenState();
}

class _SnapshotWebViewScreenState extends State<SnapshotWebViewScreen> {
  late final WebViewController _controller;
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _initController();
  }

  void _initController() {
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageStarted: (String url) {
            setState(() {
              _isLoading = true;
              _errorMessage = null;
            });
          },
          onPageFinished: (String url) {
            setState(() {
              _isLoading = false;
            });
          },
          onWebResourceError: (WebResourceError error) {
            setState(() {
              _errorMessage = 'पेज लोड करने में विफल: ${error.description}';
              _isLoading = false;
            });
          },
        ),
      )
      ..addJavaScriptChannel(
        'FlutterShareChannel',
        onMessageReceived: (JavaScriptMessage message) {
          _handleShare(message.message);
        },
      )
      ..loadRequest(Uri.parse(widget.url));
  }

  Future<void> _handleShare(String messageContent) async {
    try {
      final Map<String, dynamic> data = jsonDecode(messageContent);
      final String base64Image = data['image'] as String;
      final String text = data['text'] as String;
      final String filename = data['filename'] as String? ?? 'shakha_report.jpg';

      // Strip base64 prefix
      final base64Data = base64Image.contains(',')
          ? base64Image.substring(base64Image.indexOf(',') + 1)
          : base64Image;

      final bytes = base64Decode(base64Data);

      final tempDir = await getTemporaryDirectory();
      final file = File('${tempDir.path}/$filename');
      await file.writeAsBytes(bytes);

      if (mounted) {
        await Share.shareXFiles(
          [XFile(file.path)],
          text: text,
        );
      }
    } catch (e) {
      debugPrint('Error sharing snapshot: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('साझा करने में विफल: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.title,
          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: Colors.white),
            onPressed: () {
              _controller.reload();
            },
          ),
        ],
      ),
      body: Stack(
        children: [
          if (_errorMessage != null)
            Center(
              child: Padding(
                padding: const EdgeInsets.all(24.0),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.error_outline, size: 64, color: Colors.red),
                    const SizedBox(height: 16),
                    Text(
                      _errorMessage!,
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 16, color: Colors.red, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 24),
                    ElevatedButton(
                      onPressed: () {
                        _controller.reload();
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFFF6B00),
                        foregroundColor: Colors.white,
                      ),
                      child: const Text('पुनः प्रयास करें'),
                    ),
                  ],
                ),
              ),
            )
          else
            WebViewWidget(controller: _controller),
          if (_isLoading)
            const Center(
              child: CircularProgressIndicator(
                color: Color(0xFFFF6B00),
              ),
            ),
        ],
      ),
    );
  }
}
