import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:share_plus/share_plus.dart';
import 'package:path_provider/path_provider.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  // Set status bar color
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor: Color(0xFFFF6B00), // Saffron color
  ));
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'संघस्थान',
      theme: ThemeData(
        useMaterial3: true,
        primaryColor: const Color(0xFFFF6B00),
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFFFF6B00)),
      ),
      home: const WebViewScreen(),
      debugShowCheckedModeBanner: false,
    );
  }
}

class WebViewScreen extends StatefulWidget {
  const WebViewScreen({super.key});

  @override
  State<WebViewScreen> createState() => _WebViewScreenState();
}

class _WebViewScreenState extends State<WebViewScreen> {
  late final WebViewController controller;
  bool isLoading = true;
  double progress = 0;

  @override
  void initState() {
    super.initState();
    controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(const Color(0x00000000))
      ..addJavaScriptChannel(
        'FlutterShareChannel',
        onMessageReceived: (JavaScriptMessage message) async {
          try {
            final data = jsonDecode(message.message);
            final String base64Image = data['image'].split(',').last;
            final String text = data['text'] ?? '';
            final String filename = data['filename'] ?? 'shakha_report.jpg';
            
            final Uint8List bytes = base64Decode(base64Image);
            final directory = await getTemporaryDirectory();
            final filePath = '${directory.path}/$filename';
            final file = File(filePath);
            await file.writeAsBytes(bytes);
            
            await Share.shareXFiles([XFile(filePath)], text: text);
          } catch (e) {
            debugPrint('Error sharing image: $e');
          }
        },
      )
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (int progress) {
            setState(() {
              this.progress = progress / 100.0;
            });
          },
          onPageStarted: (String url) {
            setState(() {
              isLoading = true;
            });
          },
          onPageFinished: (String url) {
            setState(() {
              isLoading = false;
            });
          },
          onWebResourceError: (WebResourceError error) {},
          onNavigationRequest: (NavigationRequest request) async {
            final uri = Uri.parse(request.url);

            // 1. Handle app-specific schemes (WhatsApp, Email, Phone)
            if (uri.scheme == 'whatsapp' || 
                uri.scheme == 'mailto' || 
                uri.scheme == 'tel') {
              try {
                await launchUrl(uri, mode: LaunchMode.externalApplication);
              } catch (e) {
                debugPrint('Could not launch $uri: $e');
              }
              return NavigationDecision.prevent;
            }

            // 2. Handle HTTP/HTTPS links
            if (uri.scheme == 'http' || uri.scheme == 'https') {
              // Define the internal domain
              final isInternal = uri.host == 'sanghasthan.yuktaa.com' || uri.host.isEmpty;

              // If it's an external link (like Wikipedia, YouTube, Google Meet, WhatsApp Web)
              if (!isInternal) {
                try {
                  await launchUrl(uri, mode: LaunchMode.externalApplication);
                } catch (e) {
                  debugPrint('Could not launch external URL: $e');
                }
                return NavigationDecision.prevent;
              }
            }

            // 3. Allow internal navigation
            return NavigationDecision.navigate;
          },
        ),
      )
      ..loadRequest(Uri.parse('https://sanghasthan.yuktaa.com'));
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvoked: (bool didPop) async {
        if (didPop) return;
        if (await controller.canGoBack()) {
          controller.goBack();
        } else {
          SystemNavigator.pop();
        }
      },
      child: Scaffold(
        body: SafeArea(
          child: Stack(
            children: [
              RefreshIndicator(
                onRefresh: () async {
                  await controller.reload();
                },
                color: const Color(0xFFFF6B00),
                child: WebViewWidget(controller: controller),
              ),
              if (isLoading)
                Align(
                  alignment: Alignment.topCenter,
                  child: LinearProgressIndicator(
                    value: progress,
                    color: const Color(0xFFFF6B00),
                    backgroundColor: Colors.transparent,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
