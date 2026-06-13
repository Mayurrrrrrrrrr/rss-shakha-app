import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/models/models.dart';
import '../../core/providers/providers.dart';

class SwayamsevakScreen extends ConsumerStatefulWidget {
  const SwayamsevakScreen({super.key});

  @override
  ConsumerState<SwayamsevakScreen> createState() => _SwayamsevakScreenState();
}

class _SwayamsevakScreenState extends ConsumerState<SwayamsevakScreen> {
  String _searchQuery = '';
  String _selectedCategory = 'सभी';

  final List<String> _categories = ['सभी', 'Bal', 'Tarun', 'Praudh'];

  void _showAddEditModal({Swayamsevak? swayamsevak}) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) => _AddEditSwayamsevakModal(swayamsevak: swayamsevak),
    ).then((value) {
      if (value == true) {
        ref.invalidate(swayamsevaksListProvider);
        ref.read(syncEngineProvider).sync();
      }
    });
  }

  void _handleDelete(Swayamsevak swayamsevak) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('हटाना सुनिश्चित करें?'),
        content: Text('क्या आप वाकई ${swayamsevak.name} को निर्देशिका से हटाना चाहते हैं?'),
        actions: [
          TextButton(
            child: const Text('रद्द करें'),
            onPressed: () => Navigator.pop(ctx),
          ),
          TextButton(
            child: const Text('हटाएं', style: TextStyle(color: Colors.red)),
            onPressed: () async {
              Navigator.pop(ctx);
              final repo = ref.read(localRepoProvider);
              await repo.deleteSwayamsevak(swayamsevak.id!);
              ref.invalidate(swayamsevaksListProvider);
              ref.read(syncEngineProvider).sync();
            },
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final listAsync = ref.watch(swayamsevaksListProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text(
          '👥 स्वयंसेवक निर्देशिका',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: const Color(0xFFFF6B00),
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Container(
        color: const Color(0xFFF9F6F0),
        child: Column(
          children: [
            // Search Bar
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: TextField(
                onChanged: (val) => setState(() => _searchQuery = val.trim()),
                decoration: InputDecoration(
                  hintText: 'नाम या मोबाइल नंबर से खोजें...',
                  prefixIcon: const Icon(Icons.search, color: Color(0xFFFF6B00)),
                  filled: true,
                  fillColor: Colors.white,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: BorderSide.none,
                  ),
                  contentPadding: const EdgeInsets.symmetric(vertical: 16),
                ),
              ),
            ),

            // Category Filter
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16.0),
              child: Row(
                children: _categories.map((cat) {
                  final isSelected = _selectedCategory == cat;
                  String displayLabel = cat;
                  if (cat == 'सभी') displayLabel = 'सभी स्वयंसेवक';
                  if (cat == 'Bal') displayLabel = 'बाल (Bal)';
                  if (cat == 'Tarun') displayLabel = 'तरुण (Tarun)';
                  if (cat == 'Praudh') displayLabel = 'प्रौढ़ (Praudh)';

                  return Padding(
                    padding: const EdgeInsets.only(right: 8.0),
                    child: ChoiceChip(
                      label: Text(displayLabel),
                      selected: isSelected,
                      selectedColor: const Color(0xFFFF6B00),
                      labelStyle: TextStyle(
                        color: isSelected ? Colors.white : Colors.black87,
                        fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                      ),
                      onSelected: (val) {
                        if (val) {
                          setState(() => _selectedCategory = cat);
                        }
                      },
                    ),
                  );
                }).toList(),
              ),
            ),
            const SizedBox(height: 12),

            // Directory List
            Expanded(
              child: listAsync.when(
                data: (list) {
                  // Apply local filters
                  final filtered = list.where((sway) {
                    final matchesQuery = sway.name.toLowerCase().contains(_searchQuery.toLowerCase()) ||
                        (sway.phone ?? '').contains(_searchQuery);
                    final matchesCat = _selectedCategory == 'सभी' || sway.category == _selectedCategory;
                    return matchesQuery && matchesCat;
                  }).toList();

                  if (filtered.isEmpty) {
                    return const Center(
                      child: Text(
                        'कोई स्वयंसेवक नहीं मिला।',
                        style: TextStyle(fontSize: 16, color: Colors.grey),
                      ),
                    );
                  }

                  return ListView.builder(
                    itemCount: filtered.length,
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    itemBuilder: (ctx, index) {
                      final sway = filtered[index];
                      return Card(
                        elevation: 2,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        margin: const EdgeInsets.only(bottom: 12),
                        color: Colors.white,
                        child: ListTile(
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          title: Text(
                            sway.name,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                          ),
                          subtitle: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const SizedBox(height: 4),
                              if (sway.phone != null && sway.phone!.isNotEmpty)
                                Row(
                                  children: [
                                    const Icon(Icons.phone, size: 14, color: Colors.grey),
                                    const SizedBox(width: 6),
                                    Text(sway.phone!),
                                  ],
                                ),
                              const SizedBox(height: 4),
                              Row(
                                children: [
                                  Chip(
                                    label: Text(sway.category),
                                    padding: EdgeInsets.zero,
                                    materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                    labelStyle: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold),
                                  ),
                                  if (sway.gat != null && sway.gat!.isNotEmpty) ...[
                                    const SizedBox(width: 8),
                                    Chip(
                                      label: Text('गट: ${sway.gat}'),
                                      padding: EdgeInsets.zero,
                                      materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                      labelStyle: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold),
                                    ),
                                  ],
                                ],
                              ),
                            ],
                          ),
                          trailing: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              IconButton(
                                icon: const Icon(Icons.edit, color: Colors.blue),
                                onPressed: () => _showAddEditModal(swayamsevak: sway),
                              ),
                              IconButton(
                                icon: const Icon(Icons.delete_outline, color: Colors.red),
                                onPressed: () => _handleDelete(sway),
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  );
                },
                loading: () => const Center(child: CircularProgressIndicator(color: Color(0xFFFF6B00))),
                error: (err, _) => Center(child: Text('त्रुटि: $err')),
              ),
            ),
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showAddEditModal(),
        backgroundColor: const Color(0xFFFF6B00),
        child: const Icon(Icons.add, color: Colors.white),
      ),
    );
  }
}

class _AddEditSwayamsevakModal extends ConsumerStatefulWidget {
  final Swayamsevak? swayamsevak;

  const _AddEditSwayamsevakModal({this.swayamsevak});

  @override
  ConsumerState<_AddEditSwayamsevakModal> createState() => _AddEditSwayamsevakModalState();
}

class _AddEditSwayamsevakModalState extends ConsumerState<_AddEditSwayamsevakModal> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _nameController;
  late final TextEditingController _phoneController;
  late final TextEditingController _addressController;
  late final TextEditingController _ageController;
  late final TextEditingController _gatController;
  late String _category;
  late bool _isGatNayak;

  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.swayamsevak?.name ?? '');
    _phoneController = TextEditingController(text: widget.swayamsevak?.phone ?? '');
    _addressController = TextEditingController(text: widget.swayamsevak?.address ?? '');
    _ageController = TextEditingController(text: widget.swayamsevak?.age?.toString() ?? '');
    _gatController = TextEditingController(text: widget.swayamsevak?.gat ?? '');
    _category = widget.swayamsevak?.category ?? 'Tarun';
    _isGatNayak = (widget.swayamsevak?.isGatNayak ?? 0) == 1;
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _ageController.dispose();
    _gatController.dispose();
    super.dispose();
  }

  Future<void> _handleSave() async {
    if (!_formKey.currentState!.validate()) return;

    final session = ref.read(sessionProvider);
    final repo = ref.read(localRepoProvider);

    final sway = Swayamsevak(
      id: widget.swayamsevak?.id,
      name: _nameController.text.trim(),
      phone: _phoneController.text.trim().isEmpty ? null : _phoneController.text.trim(),
      address: _addressController.text.trim().isEmpty ? null : _addressController.text.trim(),
      age: _ageController.text.trim().isEmpty ? null : int.tryParse(_ageController.text.trim()),
      gat: _gatController.text.trim().isEmpty ? null : _gatController.text.trim(),
      category: _category,
      isGatNayak: _isGatNayak ? 1 : 0,
      isActive: widget.swayamsevak?.isActive ?? 1,
      shakhaId: widget.swayamsevak?.shakhaId ?? session.shakhaId,
    );

    await repo.saveSwayamsevak(sway);
    if (mounted) {
      Navigator.pop(context, true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
        top: 24,
        left: 24,
        right: 24,
      ),
      child: Form(
        key: _formKey,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                widget.swayamsevak != null ? '✨ स्वयंसेवक विवरण संशोधित करें' : '✨ नया स्वयंसेवक जोड़ें',
                style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFFFF6B00)),
              ),
              const SizedBox(height: 20),
              TextFormField(
                controller: _nameController,
                decoration: const InputDecoration(labelText: 'नाम (Name) *'),
                validator: (val) => val == null || val.trim().isEmpty ? 'कृपया नाम भरें' : null,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _phoneController,
                decoration: const InputDecoration(labelText: 'मोबाइल नंबर (Phone)'),
                keyboardType: TextInputType.phone,
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _ageController,
                      decoration: const InputDecoration(labelText: 'आयु (Age)'),
                      keyboardType: TextInputType.number,
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: TextFormField(
                      controller: _gatController,
                      decoration: const InputDecoration(labelText: 'गट (Group)'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _addressController,
                decoration: const InputDecoration(labelText: 'पता (Address)'),
              ),
              const SizedBox(height: 20),
              const Text('आयु श्रेणी (Category)', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                initialValue: _category,
                onChanged: (val) => setState(() => _category = val!),
                items: const [
                  DropdownMenuItem(value: 'Bal', child: Text('बाल (Bal - Under 15)')),
                  DropdownMenuItem(value: 'Tarun', child: Text('तरुण (Tarun - 15 to 40)')),
                  DropdownMenuItem(value: 'Praudh', child: Text('प्रौढ़ (Praudh - Above 40)')),
                ],
                decoration: const InputDecoration(border: OutlineInputBorder()),
              ),
              const SizedBox(height: 16),
              SwitchListTile(
                title: const Text('गट नायक है?'),
                value: _isGatNayak,
                onChanged: (val) => setState(() => _isGatNayak = val),
                activeThumbColor: const Color(0xFFFF6B00),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                height: 50,
                child: ElevatedButton(
                  onPressed: _handleSave,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFFF6B00),
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: const Text('सुरक्षित करें (Save)', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                ),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }
}
