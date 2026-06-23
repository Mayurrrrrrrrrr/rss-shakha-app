import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/providers.dart';
import '../../core/config/app_config.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;
  String? _errorMessage;

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final apiClient = ref.read(apiClientProvider);
      final response = await apiClient.post(
        '/api/login.php',
        data: {
          'username': _usernameController.text.trim(),
          'password': _passwordController.text,
        },
      );

      if (response.statusCode == 200 && response.data != null) {
        final data = response.data;
        if (data['success'] == true) {
          final userData = data['data'] as Map<String, dynamic>;
          // Save session using Riverpod SessionNotifier
          await ref.read(sessionProvider.notifier).login(userData);
          
          // Trigger first-time delta sync
          ref.read(syncEngineProvider).sync();
        } else {
          setState(() {
            _errorMessage = data['message'] ?? 'लॉगिन विफल। कृपया पुनः प्रयास करें।';
          });
        }
      } else {
        setState(() {
          _errorMessage = 'सर्वर कनेक्शन विफल। कृपया इंटरनेट जांचें।';
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'त्रुटि: ${e.toString()}';
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              Color(0xFFFF8C00), // Dark Orange
              Color(0xFFFF6B00), // Saffron
              Color(0xFFE55B00), // Deep Saffron
            ],
          ),
        ),
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24.0),
            child: Card(
              elevation: 12,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
              color: Colors.white.withOpacity(0.95),
              child: Padding(
                padding: const EdgeInsets.all(32.0),
                child: Form(
                  key: _formKey,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Text(
                        '🚩 संघस्थान',
                        style: TextStyle(
                          fontSize: 32,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFFFF6B00),
                        ),
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'दैनिक गतिविधि एवं उपस्थिति प्रबंधन',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 16,
                          color: Colors.black54,
                        ),
                      ),
                      const SizedBox(height: 32),
                      if (_errorMessage != null) ...[
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.red.shade50,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: Colors.red.shade200),
                          ),
                          child: Row(
                            children: [
                              Icon(Icons.error_outline, color: Colors.red.shade700),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Text(
                                  _errorMessage!,
                                  style: TextStyle(color: Colors.red.shade900),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(height: 20),
                      ],
                      TextFormField(
                        controller: _usernameController,
                        decoration: InputDecoration(
                          labelText: 'उपयोगकर्ता नाम (Username)',
                          prefixIcon: const Icon(Icons.person_outline),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        validator: (value) =>
                            value == null || value.isEmpty ? 'कृपया उपयोगकर्ता नाम दर्ज करें' : null,
                      ),
                      const SizedBox(height: 20),
                      TextFormField(
                        controller: _passwordController,
                        obscureText: true,
                        decoration: InputDecoration(
                          labelText: 'पासवर्ड (Password)',
                          prefixIcon: const Icon(Icons.lock_outline),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        validator: (value) =>
                            value == null || value.isEmpty ? 'कृपया पासवर्ड दर्ज करें' : null,
                      ),
                      const SizedBox(height: 32),
                      SizedBox(
                        width: double.infinity,
                        height: 54,
                        child: ElevatedButton(
                          onPressed: _isLoading ? null : _handleLogin,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFFFF6B00),
                            foregroundColor: Colors.white,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            elevation: 4,
                          ),
                          child: _isLoading
                              ? const CircularProgressIndicator(color: Colors.white)
                              : const Text(
                                  '🔑 लॉगिन करें',
                                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                                ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      const Text(
                        'संस्करण (Version) ${AppConfig.versionName}+${AppConfig.versionCode}',
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.black38,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
