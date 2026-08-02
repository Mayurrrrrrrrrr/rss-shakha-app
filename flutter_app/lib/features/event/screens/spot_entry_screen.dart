import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../providers/event_providers.dart';

class SpotEntryScreen extends ConsumerStatefulWidget {
  const SpotEntryScreen({Key? key}) : super(key: key);

  @override
  ConsumerState<SpotEntryScreen> createState() => _SpotEntryScreenState();
}

class _SpotEntryScreenState extends ConsumerState<SpotEntryScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _cityController = TextEditingController();
  final _ageController = TextEditingController();
  final _notesController = TextEditingController();
  String _gender = 'पुरुष';

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9F6F0),
      appBar: AppBar(
        title: const Text('➕ स्पॉट एंट्री', style: TextStyle(fontFamily: 'Noto Sans Devanagari', fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFFFF6B00),
        foregroundColor: Colors.white,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _buildTextField(_nameController, 'पूरा नाम', Icons.person, true),
              const SizedBox(height: 16),
              _buildTextField(_phoneController, 'फोन नंबर', Icons.phone, false, isNumber: true),
              const SizedBox(height: 16),
              _buildTextField(_cityController, 'शहर/गाँव', Icons.location_city, false),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(child: _buildTextField(_ageController, 'आयु', Icons.calendar_today, false, isNumber: true)),
                  const SizedBox(width: 16),
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      value: _gender,
                      decoration: InputDecoration(
                        filled: true,
                        fillColor: Colors.white,
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      items: ['पुरुष', 'महिला'].map((g) => DropdownMenuItem(value: g, child: Text(g, style: const TextStyle(fontFamily: 'Noto Sans Devanagari', fontSize: 18)))).toList(),
                      onChanged: (val) => setState(() => _gender = val!),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              _buildTextField(_notesController, 'टिप्पणी', Icons.note, false, maxLines: 3),
              const SizedBox(height: 32),
              SizedBox(
                height: 56,
                child: ElevatedButton(
                  onPressed: _submit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFFF6B00),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: const Text('रजिस्टर करें', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white, fontFamily: 'Noto Sans Devanagari')),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTextField(TextEditingController controller, String label, IconData icon, bool required, {bool isNumber = false, int maxLines = 1}) {
    return TextFormField(
      controller: controller,
      keyboardType: isNumber ? TextInputType.number : TextInputType.text,
      maxLines: maxLines,
      style: const TextStyle(fontSize: 18, fontFamily: 'Noto Sans Devanagari'),
      decoration: InputDecoration(
        labelText: label,
        labelStyle: const TextStyle(fontFamily: 'Noto Sans Devanagari', fontSize: 18),
        prefixIcon: Icon(icon, color: const Color(0xFFFF6B00)),
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
      ),
      validator: required ? (val) => val == null || val.isEmpty ? 'यह जानकारी आवश्यक है' : null : null,
    );
  }

  void _submit() {
    if (_formKey.currentState!.validate()) {
      // API call to add spot entry
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('${_nameController.text} का पंजीकरण सफल रहा', style: const TextStyle(fontFamily: 'Noto Sans Devanagari', fontSize: 16)),
          backgroundColor: Colors.green,
        ),
      );
      _formKey.currentState!.reset();
      _nameController.clear();
      _phoneController.clear();
      _cityController.clear();
      _ageController.clear();
      _notesController.clear();
    }
  }
}
