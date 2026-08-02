import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/providers/providers.dart';
import '../../core/api/api_client.dart';
import '../../core/config/app_config.dart';
import '../dashboard/dashboard_screen.dart';
import '../event/providers/event_providers.dart';
import '../event/screens/event_selection_screen.dart';

enum LoginPortal { shakha, event }

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();
  
  LoginPortal _selectedPortal = LoginPortal.shakha;
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
      
      if (_selectedPortal == LoginPortal.shakha) {
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
            await ref.read(sessionProvider.notifier).login(userData);
            ref.read(syncEngineProvider).sync();
            
            if (mounted) {
              Navigator.of(context).pushReplacement(
                MaterialPageRoute(builder: (_) => const DashboardScreen()),
              );
            }
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
      } else {
        // Event Portal Login
        final response = await apiClient.post(
          '/api/v1/event/auth/login.php',
          data: {
            'username': _usernameController.text.trim(),
            'password': _passwordController.text,
          },
        );

        if (response.statusCode == 200 && response.data != null) {
          final data = response.data;
          if (data['success'] == true) {
            final userData = data['data'] as Map<String, dynamic>;
            await ref.read(eventSessionProvider.notifier).login(userData);
            
            if (mounted) {
              Navigator.of(context).pushReplacement(
                MaterialPageRoute(builder: (_) => const EventSelectionScreen()),
              );
            }
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

  Widget _buildPortalCard({
    required String title,
    required String iconText,
    required bool isSelected,
    required VoidCallback onTap,
  }) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 300),
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 8),
          decoration: BoxDecoration(
            color: isSelected ? const Color(0xFFFFF3E0) : Colors.grey.shade50,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: isSelected ? const Color(0xFFFF6B00) : Colors.grey.shade300,
              width: isSelected ? 2 : 1,
            ),
            boxShadow: isSelected
                ? [
                    BoxShadow(
                      color: const Color(0xFFFF6B00).withOpacity(0.2),
                      blurRadius: 8,
                      offset: const Offset(0, 4),
                    )
                  ]
                : [],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                iconText,
                style: const TextStyle(fontSize: 28),
              ),
              const SizedBox(height: 8),
              Text(
                title,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
                  color: isSelected ? const Color(0xFFFF6B00) : Colors.grey.shade700,
                ),
              ),
            ],
          ),
        ),
      ),
    );
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
                      Row(
                        children: [
                          _buildPortalCard(
                            title: 'संघस्थान',
                            iconText: '🚩',
                            isSelected: _selectedPortal == LoginPortal.shakha,
                            onTap: () {
                              if (_selectedPortal != LoginPortal.shakha) {
                                setState(() {
                                  _selectedPortal = LoginPortal.shakha;
                                  _errorMessage = null;
                                });
                              }
                            },
                          ),
                          const SizedBox(width: 16),
                          _buildPortalCard(
                            title: 'आयोजन',
                            iconText: '📋',
                            isSelected: _selectedPortal == LoginPortal.event,
                            onTap: () {
                              if (_selectedPortal != LoginPortal.event) {
                                setState(() {
                                  _selectedPortal = LoginPortal.event;
                                  _errorMessage = null;
                                });
                              }
                            },
                          ),
                        ],
                      ),
                      const SizedBox(height: 24),
                      Text(
                        _selectedPortal == LoginPortal.shakha
                            ? 'दैनिक गतिविधि एवं उपस्थिति प्रबंधन'
                            : 'कार्यक्रम आयोजन एवं प्रबंधन',
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontSize: 16,
                          color: Colors.black54,
                          fontWeight: FontWeight.w500,
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
