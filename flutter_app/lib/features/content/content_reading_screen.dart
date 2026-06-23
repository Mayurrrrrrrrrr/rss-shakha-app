import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:share_plus/share_plus.dart';

enum ContentType {
  prarthna,
  subhashit,
  amritVachan,
  geet,
  ghoshna,
}

class ContentReadingScreen extends StatefulWidget {
  final ContentType type;
  final String title;
  final String content;
  final String? extra;
  final dynamic listData;

  const ContentReadingScreen({
    super.key,
    required this.type,
    required this.title,
    required this.content,
    this.extra,
    this.listData,
  });

  @override
  State<ContentReadingScreen> createState() => _ContentReadingScreenState();
}

class _ContentReadingScreenState extends State<ContentReadingScreen> {
  double _fontSize = 18.0;

  // RSS Prarthna Local Complete Dataset
  static const String prarthnaHistory = 
      'राष्ट्रीय स्वयंसेवक संघ की प्रार्थना की रचना व प्रारूप सर्वप्रथम फरवरी 1939 में नागपुर के पास सिन्दी में हुई बैठक में तैयार किया गया।\n\nइस ऐतिहासिक बैठक में संघ के आद्य सरसंघचालक डॉ. केशव बलिराम हेडगेवार, श्री गुरुजी, श्री बाबासाहब आपटे, श्री बालासाहब देवरस, श्री अप्पाजी जोशी व श्री नानासाहब टालाटुले जैसे प्रमुख मनीषी सहभागी थे।';

  static const List<Map<String, String>> prarthnaVerses = [
    {
      'sanskrit': 'नमस्ते सदा वत्सले मातृभूमे,\nत्वया हिन्दुभूमे सुखं वर्धितोऽहम्।\nमहामङ्गले पुण्यभूमे त्वदर्थे।\nपतत्वेष कायो नमस्ते नमस्ते ॥१॥',
      'meaning': 'हे वत्सलमयि मातृभूमि! मैं तुझे निरन्तर प्रणाम करता हूँ। हे हिन्दुभूमि! तूने ही मुझे सुख में बढ़ाया है। हे महामङ्गलमयि पुण्यभूमि। तेरे ही कारण मेरी यह काया (शरीर) अर्पित (समर्पित) हो। तुझे मैं अनन्त बार प्रणाम करता हूँ।'
    },
    {
      'sanskrit': 'प्रभो शक्तिमन् हिन्दुराष्ट्राङ्गभूता,\nइमे सादरं त्वां नमामो वयम्।\nत्वदीयाय कार्याय बद्धा कटीयम्,\nशुभामाशिषं देहि तत्पूर्तये।\nअजय्यां च विश्वस्य देहीश शक्तिम्,\nसुशीलं जगद् येन नम्रं भवेत्।\nश्रुतं चैव यत् कण्टकाकीर्णमार्गम्,\nस्वयं स्वीकृतं नः सुगं कारयेत् ॥२॥',
      'meaning': 'हे सर्वशक्तिमान् परमेश्वर! ये हम हिन्दुराष्ट्र के अंगभूत घटक, तुझे आदरपूर्वक प्रणाम करते हैं। तेरे ही कार्य के लिए हमने अपनी कमर कसी है। उसकी पूर्ति के लिए हमें शुभ आशीर्वाद दे ॥ विश्व के लिए अजेय ऐसी शक्ति, सारा जगत् विनम्र हो ऐसा विशुद्ध शील (चरित्र) तथा बुद्धिपूर्वक स्वयं स्वीकृत हमारे कण्टकमय मार्ग को सुगम करे, ऐसा ज्ञान भी हमें दे ॥'
    },
    {
      'sanskrit': 'समुत्कर्षनिःश्रेयसस्यैकमुग्रम्,\nपरं साधनं नाम वीरव्रतम्।\nतदन्तः स्फुरत्वक्षया ध्येयनिष्ठा,\nहृदन्तः प्रजागर्तु तीव्राऽनिशम्।\nविजेत्री च नः संहता कार्यशक्तिर,\nविधायास्य धर्मस्य संरक्षणम्।\nपरं वैभवं नेतुमेतत् स्वराष्ट्रम्,\nसमर्था भवत्वाशिषा ते भृशम् ॥३॥',
      'meaning': 'अभ्युदय सहित निःश्रेयस् की प्राप्ति का वीरव्रत नामक जो एकमेव श्रेष्ठ उग्र साधन है, उसका हम लोगों के अन्तः करण में स्फुरण हो। हमारे हृदय में अक्षय तथा तीव्र ध्येयनिष्ठा सदैव जागृत रहे। तेरे आशीर्वाद से हमारी विजयशालिनी संगठित कार्यशक्ति स्वधर्म का रक्षण कर अपने इस राष्ट्र को परम वैभव की स्थिति पर ले जाने में अतीव समर्थ हो।'
    }
  ];

  static const List<Map<String, String>> prarthnaShabdarth = [
    {"word": "वत्सले मातृभूमे।", "meaning": "(ऐ) वात्सल्यमयी मातृभूमि"},
    {"word": "ते", "meaning": "तुझे"},
    {"word": "सदा", "meaning": "निरन्तर"},
    {"word": "नमः", "meaning": "प्रणाम"},
    {"word": "हिन्दुभूमे", "meaning": "हे हिन्दुभूमि!"},
    {"word": "त्वया", "meaning": "तेरे द्वारा"},
    {"word": "अहम्", "meaning": "मैं"},
    {"word": "सुख वर्धितः", "meaning": "सुख में बढ़ाया गया हूँ"},
    {"word": "महामङले पुण्यभूमे", "meaning": "हे परम मंगलमयि पुण्यभूमि"},
    {"word": "त्वदर्थे", "meaning": "तेरे लिए"},
    {"word": "एषः", "meaning": "यह"},
    {"word": "कायः", "meaning": "शरीर"},
    {"word": "पततु", "meaning": "गिरे"},
    {"word": "नमस्ते नमस्ते", "meaning": "तुझे अनेक बार प्रणाम"},
    {"word": "शक्तिमन् प्रभो", "meaning": "शक्तिमान् परमेश्वर"},
    {"word": "हिन्दुराष्ट्राङ्गभूता", "meaning": "हिन्दुराष्ट्र के अंगभूत घटक"},
    {"word": "इमे वयम्", "meaning": "ये हम"},
    {"word": "त्वाम्", "meaning": "तुझे"},
    {"word": "सादरम्", "meaning": "आदर सहित"},
    {"word": "नमामः", "meaning": "प्रणाम करते हैं"},
    {"word": "त्वदीयाय कार्याय", "meaning": "तेरे कार्य के लिए"},
    {"word": "इयम कटि", "meaning": "यह कमर"},
    {"word": "बद्धा", "meaning": "बँधी है"},
    {"word": "तत्पूर्तये", "meaning": "उसकी पूर्ति के लिए"},
    {"word": "शुभां आशिषम्", "meaning": "शुभ आशीर्वाद"},
    {"word": "देहि", "meaning": "दे"},
    {"word": "ईश", "meaning": "हे ईश!"},
    {"word": "विश्वस्य", "meaning": "विश्व के लिए"},
    {"word": "अजय्याम्", "meaning": "जिसे जीतना अशक्य है"},
    {"word": "शक्तिम्", "meaning": "शक्ति"},
    {"word": "येन", "meaning": "जिससे"},
    {"word": "जगद्", "meaning": "जगत्"},
    {"word": "नम्रम्", "meaning": "नम्र"},
    {"word": "भवेत्", "meaning": "हो"},
    {"word": "सुशीलम्", "meaning": "अच्छा शील"},
    {"word": "यत्", "meaning": "जो"},
    {"word": "स्वयं स्वीकृतम्", "meaning": "अपनी प्रेरणा से स्वीकृत"},
    {"word": "नः", "meaning": "हमारे"},
    {"word": "कण्टकाकीर्णमार्गम्", "meaning": "कण्टकमय मार्ग को"},
    {"word": "सुगम् कारयेत्", "meaning": "सुगम करे"},
    {"word": "श्रुतम्", "meaning": "ज्ञान"},
    {"word": "चैव", "meaning": "भी"},
    {"word": "समुत्कर्षनिः श्रेयसस्य", "meaning": "समुत्कर्ष और निःश्रेयस का"},
    {"word": "एकं पर उम्र साधनम्", "meaning": "एकमात्र परम उग्र साधन"},
    {"word": "वीरव्रतं नाम", "meaning": "वीरव्रत नामक"},
    {"word": "तत्", "meaning": "वह"},
    {"word": "अन्तः", "meaning": "अन्तःकरण में"},
    {"word": "स्फुरतु", "meaning": "स्फुरित हो"},
    {"word": "अक्षया", "meaning": "क्षीण न होने वाली"},
    {"word": "तीव्रा ध्येयनिष्ठा", "meaning": "तीव्र ध्येयनिष्ठा"},
    {"word": "अनिशम्", "meaning": "नित्य"},
    {"word": "हृदन्तः", "meaning": "हृदय में"},
    {"word": "प्रजागर्तु", "meaning": "जाग्रत रहे"},
    {"word": "विजेत्री", "meaning": "विजयशालिनी"},
    {"word": "च", "meaning": "और"},
    {"word": "नः", "meaning": "हमारी"},
    {"word": "संहता कार्यशक्तिः", "meaning": "संगठित कार्यशक्ति"},
    {"word": "अस्य धर्मस्य", "meaning": "इस धर्म का"},
    {"word": "संरक्षणम् विधाय", "meaning": "संरक्षण करते हुए"},
    {"word": "एतत् स्वराष्ट्रम", "meaning": "इस हमारे राष्ट्र को"},
    {"word": "परं वैभवम्", "meaning": "परम वैभव"},
    {"word": "नेतुम", "meaning": "ले जाने के लिए"},
    {"word": "ते आशिषा", "meaning": "तेरे आशीर्वाद से"},
    {"word": "भृशम्", "meaning": "प्रचुर"},
    {"word": "समर्था भवतु", "meaning": "समर्थ हो"}
  ];

  void _shareContent() {
    String shareText = '';
    switch (widget.type) {
      case ContentType.prarthna:
        shareText = '🚩 *राष्ट्रीय स्वयंसेवक संघ - संघ प्रार्थना* 🚩\n\n'
            'नमस्ते सदा वत्सले मातृभूमे,\n'
            'त्वया हिन्दुभूमे सुखं वर्धितोऽहम्।\n'
            'महामङ्गले पुण्यभूमे त्वदर्थे।\n'
            'पतत्वेष कायो नमस्ते नमस्ते ॥१॥\n\n'
            'पूर्ण प्रार्थना, इतिहास एवं शब्दार्थ ऐप पर देखें।';
        break;
      case ContentType.geet:
        shareText = '🎵 *गीत: ${widget.title}*\n\n'
            '${widget.content}\n\n'
            '${widget.extra != null ? "विवरण/संदर्भ: ${widget.extra}\n\n" : ""}'
            'संघस्थान ऐप से साझा किया गया 🚩';
        break;
      case ContentType.amritVachan:
        shareText = '💭 *अमृत वचन*\n\n'
            '"${widget.content}"\n\n'
            '- *${widget.extra ?? "अज्ञात"}*\n\n'
            'संघस्थान ऐप से साझा किया गया 🚩';
        break;
      case ContentType.subhashit:
        shareText = '📖 *सुभाषित श्लोक*\n\n'
            '🚩 *संस्कृत श्लोक:*\n${widget.content}\n\n'
            '📙 *हिंदी भावार्थ:*\n${widget.extra ?? ""}\n\n'
            'संघस्थान ऐप से साझा किया गया 🚩';
        break;
      case ContentType.ghoshna:
        shareText = '📣 *घोषणा (Slogan)*\n\n'
            'Sanskrit: ${widget.content}\n'
            'Hindi: ${widget.extra ?? ""}\n\n'
            'संघस्थान ऐप से साझा किया गया 🚩';
        break;
    }
    SharePlus.instance.share(ShareParams(text: shareText));
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
            icon: const Icon(Icons.share, color: Colors.white),
            tooltip: 'साझा करें',
            onPressed: _shareContent,
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(50),
          child: Container(
            color: Colors.black.withValues(alpha: 0.08),
            padding: const EdgeInsets.symmetric(horizontal: 16),
            height: 50,
            child: Row(
              children: [
                const Icon(Icons.format_size, size: 16, color: Colors.white70),
                Expanded(
                  child: Slider(
                    value: _fontSize,
                    min: 14.0,
                    max: 32.0,
                    divisions: 18,
                    activeColor: Colors.white,
                    inactiveColor: Colors.white30,
                    onChanged: (val) {
                      setState(() {
                        _fontSize = val;
                      });
                    },
                  ),
                ),
                const Icon(Icons.format_size, size: 24, color: Colors.white),
                const SizedBox(width: 8),
                Text(
                  '${_fontSize.toInt()}pt',
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                ),
              ],
            ),
          ),
        ),
      ),
      body: Container(
        color: Theme.of(context).brightness == Brightness.dark
            ? Theme.of(context).colorScheme.surfaceContainerLowest
            : const Color(0xFFFFFDF9), // Warm reading parchment style
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20.0),
            child: _buildReadingContent(),
          ),
        ),
      ),
    );
  }

  Widget _buildReadingContent() {
    switch (widget.type) {
      case ContentType.prarthna:
        return _buildPrarthnaReader();
      case ContentType.geet:
        return _buildGeetReader();
      case ContentType.amritVachan:
        return _buildAmritVachanReader();
      case ContentType.subhashit:
        return _buildSubhashitReader();
      case ContentType.ghoshna:
        return _buildGhoshnaReader();
    }
  }

  // 1. Sangh Prarthna Reader
  Widget _buildPrarthnaReader() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Title banner
        Center(
          child: Column(
            children: [
              Text(
                '🚩 संघ प्रार्थना 🚩',
                style: TextStyle(
                  fontSize: _fontSize + 4,
                  fontWeight: FontWeight.bold,
                  color: const Color(0xFFFF6B00),
                ),
              ),
              const SizedBox(height: 6),
              Text(
                'नमस्ते सदा वत्सले मातृभूमे',
                style: TextStyle(
                  fontSize: _fontSize - 2,
                  fontStyle: FontStyle.italic,
                  color: Colors.brown,
                ),
              ),
            ],
          ),
        ),
        const Divider(height: 32, thickness: 1.5, color: Color(0xFFFF6B00)),

        // History Section
        _buildSectionHeader('📜 प्रार्थना का इतिहास'),
        Text(
          prarthnaHistory,
          style: TextStyle(fontSize: _fontSize - 2, height: 1.5, color: Theme.of(context).colorScheme.onSurface),
        ),
        const Divider(height: 40),

        // Verses and Meanings Section
        _buildSectionHeader('🚩 शुद्ध श्लोक एवं भावार्थ'),
        ...List.generate(prarthnaVerses.length, (index) {
          final verse = prarthnaVerses[index];
          return Padding(
            padding: const EdgeInsets.only(bottom: 24.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'श्लोक ${index + 1}:',
                  style: TextStyle(
                    fontSize: _fontSize - 2,
                    fontWeight: FontWeight.bold,
                    color: Colors.brown,
                  ),
                ),
                const SizedBox(height: 8),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.orange.shade50,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.orange.shade100),
                  ),
                  child: Text(
                    verse['sanskrit']!,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: _fontSize,
                      fontWeight: FontWeight.bold,
                      color: const Color(0xFF4E342E),
                      height: 1.6,
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  'हिंदी भावार्थ:',
                  style: TextStyle(
                    fontSize: _fontSize - 4,
                    fontWeight: FontWeight.bold,
                    color: Colors.orange.shade800,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  verse['meaning']!,
                  style: TextStyle(
                    fontSize: _fontSize - 2,
                    color: Theme.of(context).colorScheme.onSurface,
                    height: 1.5,
                  ),
                ),
              ],
            ),
          );
        }),
        const Center(
          child: Padding(
            padding: EdgeInsets.symmetric(vertical: 16.0),
            child: Text(
              '॥ भारत माता की जय ॥',
              style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: Color(0xFFFF6B00)),
            ),
          ),
        ),
        const Divider(height: 40),

        // Word Meanings Section
        _buildSectionHeader('📖 कठिन शब्दार्थ'),
        Table(
          border: TableBorder.all(color: Colors.grey.shade200, width: 1, borderRadius: BorderRadius.circular(8)),
          columnWidths: const {
            0: FlexColumnWidth(1),
            1: FlexColumnWidth(1.5),
          },
          children: prarthnaShabdarth.map((item) {
            return TableRow(
              children: [
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 10.0, vertical: 8.0),
                  child: Text(
                    item['word']!,
                    style: TextStyle(fontWeight: FontWeight.bold, color: Colors.brown, fontSize: _fontSize - 2),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 10.0, vertical: 8.0),
                  child: Text(
                    item['meaning']!,
                    style: TextStyle(fontSize: _fontSize - 2, color: Theme.of(context).colorScheme.onSurface),
                  ),
                ),
              ],
            );
          }).toList(),
        ),
      ],
    );
  }

  // 2. Geet Reader
  Widget _buildGeetReader() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Center(
          child: Text(
            widget.title,
            style: TextStyle(
              fontSize: _fontSize + 4,
              fontWeight: FontWeight.bold,
              color: const Color(0xFFFF6B00),
            ),
            textAlign: TextAlign.center,
          ),
        ),
        if (widget.extra != null && widget.extra!.isNotEmpty) ...[
          const SizedBox(height: 6),
          Center(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
              decoration: BoxDecoration(color: Colors.orange.shade100, borderRadius: BorderRadius.circular(10)),
              child: Text(
                'श्रेणी: ${widget.extra == "Sanghik" ? "संघिक गीत" : "एकल गीत"}',
                style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFFF6B00), fontSize: 12),
              ),
            ),
          ),
        ],
        const Divider(height: 32, thickness: 1.5, color: Color(0xFFFF6B00)),
        
        Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 16),
          decoration: BoxDecoration(
            color: Colors.orange.shade50,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Colors.orange.shade100),
          ),
          child: Text(
            widget.content,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: _fontSize,
              fontWeight: FontWeight.w500,
              color: const Color(0xFF5D4037),
              height: 1.7,
            ),
          ),
        ),
      ],
    );
  }

  // 3. Amrit Vachan Reader
  Widget _buildAmritVachanReader() {
    return Column(
      children: [
        const SizedBox(height: 20),
        const Icon(Icons.format_quote, size: 64, color: Color(0xFFFF6B00)),
        const SizedBox(height: 10),
        Card(
          elevation: 0,
          color: Theme.of(context).brightness == Brightness.dark
              ? Theme.of(context).cardColor
              : const Color(0xFFFFFDE7),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
            side: const BorderSide(color: Color(0xFFFFD54F), width: 1.5),
          ),
          child: Padding(
            padding: const EdgeInsets.all(24.0),
            child: Text(
              '"${widget.content}"',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: _fontSize,
                fontWeight: FontWeight.w600,
                color: const Color(0xFF3E2723),
                height: 1.6,
              ),
            ),
          ),
        ),
        const SizedBox(height: 16),
        Align(
          alignment: Alignment.centerRight,
          child: Text(
            '- ${widget.extra ?? "अज्ञात"}',
            style: TextStyle(
              fontSize: _fontSize - 2,
              fontWeight: FontWeight.bold,
              color: const Color(0xFFFF6B00),
            ),
          ),
        ),
      ],
    );
  }

  // 4. Subhashit Reader
  Widget _buildSubhashitReader() {
    List<dynamic> shabdarthList = [];
    if (widget.listData != null && widget.listData is String) {
      try {
        shabdarthList = jsonDecode(widget.listData as String);
      } catch (_) {}
    } else if (widget.listData != null && widget.listData is List) {
      shabdarthList = widget.listData as List;
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionHeader('📖 संस्कृत श्लोक'),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.orange.shade50,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.orange.shade100),
          ),
          child: Text(
            widget.content,
            style: TextStyle(
              fontSize: _fontSize,
              fontWeight: FontWeight.bold,
              color: const Color(0xFF5D4037),
              height: 1.6,
            ),
          ),
        ),
        const SizedBox(height: 24),
        
        _buildSectionHeader('📙 हिंदी भावार्थ'),
        Text(
          widget.extra ?? 'कोई भावार्थ उपलब्ध नहीं है।',
          style: TextStyle(
            fontSize: _fontSize - 2,
            color: Theme.of(context).colorScheme.onSurface,
            height: 1.5,
          ),
        ),
        
        if (shabdarthList.isNotEmpty) ...[
          const SizedBox(height: 32),
          _buildSectionHeader('📖 शब्दार्थ (Glossary)'),
          Table(
            border: TableBorder.all(color: Colors.grey.shade200, width: 1, borderRadius: BorderRadius.circular(8)),
            columnWidths: const {
              0: FlexColumnWidth(1),
              1: FlexColumnWidth(1.5),
            },
            children: shabdarthList.map((item) {
              final word = item['shabd'] ?? '';
              final meaning = item['arth'] ?? '';
              return TableRow(
                children: [
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 10.0, vertical: 8.0),
                    child: Text(
                      word,
                      style: TextStyle(fontWeight: FontWeight.bold, color: Colors.brown, fontSize: _fontSize - 2),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 10.0, vertical: 8.0),
                    child: Text(
                      meaning,
                      style: TextStyle(fontSize: _fontSize - 2, color: Theme.of(context).colorScheme.onSurface),
                    ),
                  ),
                ],
              );
            }).toList(),
          ),
        ],
      ],
    );
  }

  // 5. Slogan / Ghoshna Reader
  Widget _buildGhoshnaReader() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionHeader('📣 संस्कृत घोषणा'),
        Text(
          widget.content,
          style: TextStyle(
            fontSize: _fontSize,
            fontWeight: FontWeight.bold,
            color: const Color(0xFFFF6B00),
            height: 1.5,
          ),
        ),
        const SizedBox(height: 24),
        _buildSectionHeader('📙 हिंदी अनुवाद'),
        Text(
          widget.extra ?? '',
          style: TextStyle(
            fontSize: _fontSize - 2,
            fontWeight: FontWeight.bold,
            color: const Color(0xFF388E3C),
            height: 1.5,
          ),
        ),
      ],
    );
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10.0, top: 12.0),
      child: Text(
        title,
        style: TextStyle(
          fontSize: _fontSize - 3,
          fontWeight: FontWeight.bold,
          color: const Color(0xFFE65100),
        ),
      ),
    );
  }
}
