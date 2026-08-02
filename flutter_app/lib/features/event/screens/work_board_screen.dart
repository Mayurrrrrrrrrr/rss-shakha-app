import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/event_models.dart';
import '../providers/event_providers.dart';

class WorkBoardScreen extends ConsumerStatefulWidget {
  const WorkBoardScreen({super.key});

  @override
  ConsumerState<WorkBoardScreen> createState() => _WorkBoardScreenState();
}

class _WorkBoardScreenState extends ConsumerState<WorkBoardScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  
  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9F6F0),
      appBar: AppBar(
        title: const Text('📌 कार्य व्यवस्था', style: TextStyle(fontFamily: 'Noto Sans Devanagari')),
        backgroundColor: const Color(0xFFFF6B00),
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          indicatorColor: Colors.white,
          tabs: const [
            Tab(text: 'सभी कार्य'),
            Tab(text: 'मेरे कार्य'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildWorkList(isMine: false),
          _buildWorkList(isMine: true),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showAddWorkDialog(),
        backgroundColor: const Color(0xFFFF6B00),
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text('नया कार्य', style: TextStyle(color: Colors.white, fontFamily: 'Noto Sans Devanagari', fontSize: 16)),
      ),
    );
  }

  Widget _buildWorkList({required bool isMine}) {
    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: 5,
      itemBuilder: (context, index) {
        return Card(
          elevation: 4,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          margin: const EdgeInsets.only(bottom: 16),
          child: InkWell(
            borderRadius: BorderRadius.circular(12),
            onTap: () {
              // Tap to update status
            },
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFFB300),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: const Text('सजावट', style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold, fontSize: 14)),
                      ),
                      Chip(
                        label: const Text('लंबित', style: TextStyle(color: Colors.white, fontSize: 14)),
                        backgroundColor: Colors.orange,
                        padding: EdgeInsets.zero,
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  const Text(
                    'मंच की सजावट और कुर्सियों की व्यवस्था',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600, fontFamily: 'Noto Sans Devanagari'),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: const [
                      Icon(Icons.person, size: 20, color: Colors.grey),
                      SizedBox(width: 8),
                      Text('रमेश कुमार', style: TextStyle(fontSize: 16, fontFamily: 'Noto Sans Devanagari')),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: const [
                      Icon(Icons.access_time, size: 20, color: Colors.grey),
                      SizedBox(width: 8),
                      Text('आज, 10:00 AM', style: TextStyle(fontSize: 16, fontFamily: 'Noto Sans Devanagari')),
                    ],
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  void _showAddWorkDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('नया कार्य सौंपें', style: TextStyle(fontFamily: 'Noto Sans Devanagari', color: Color(0xFFE55B00))),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                decoration: InputDecoration(
                  labelText: 'कार्य का विवरण',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                decoration: InputDecoration(
                  labelText: 'श्रेणी (उदा. भोजन, आवास)',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                decoration: InputDecoration(
                  labelText: 'आयोजक को सौंपें',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('रद्द करें', style: TextStyle(color: Colors.grey, fontSize: 16)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFFF6B00),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              minimumSize: const Size(100, 48),
            ),
            onPressed: () => Navigator.pop(context),
            child: const Text('सहेजें', style: TextStyle(color: Colors.white, fontSize: 16, fontFamily: 'Noto Sans Devanagari')),
          ),
        ],
      ),
    );
  }
}
