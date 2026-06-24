import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'core/providers/providers.dart';
import 'features/dashboard/dashboard_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Set system status bar color overlay
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor: Color(0xFFFF6B00), // Saffron Color
    statusBarIconBrightness: Brightness.light,
  ));

  runApp(
    const ProviderScope(
      child: MyApp(),
    ),
  );
}

class MyApp extends ConsumerWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final themeMode = ref.watch(themeProvider);

    return MaterialApp(
      title: 'संघस्थान - R.S.S. Shakha',
      themeMode: themeMode,
      theme: ThemeData(
        useMaterial3: true,
        primaryColor: const Color(0xFFFF6B00),
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFFFF6B00),
          primary: const Color(0xFFFF6B00),
          secondary: const Color(0xFFFFB300),
          surface: Colors.white,
          surfaceContainerLowest: const Color(0xFFF9F6F0), // Soft cream
        ),
        fontFamily: 'Noto Sans Devanagari',
        // Senior-friendly: larger base font sizes
        textTheme: Theme.of(context).textTheme.copyWith(
          bodyLarge: const TextStyle(fontSize: 18),
          bodyMedium: const TextStyle(fontSize: 16),
          bodySmall: const TextStyle(fontSize: 14),
          titleLarge: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
          titleMedium: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          titleSmall: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
          labelLarge: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
        ),
        // Comfortable density for larger touch targets
        visualDensity: VisualDensity.comfortable,
        materialTapTargetSize: MaterialTapTargetSize.padded,
        cardTheme: const CardThemeData(
          color: Colors.white,
          surfaceTintColor: Colors.transparent,
        ),
        chipTheme: ChipThemeData(
          backgroundColor: Colors.grey.shade200,
          disabledColor: Colors.grey.shade100,
          selectedColor: const Color(0xFFFF6B00),
          secondarySelectedColor: const Color(0xFFFFB300),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
        // Senior-friendly: larger buttons
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            minimumSize: const Size(double.infinity, 56),
            textStyle: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
        ),
      ),
      darkTheme: ThemeData(
        useMaterial3: true,
        brightness: Brightness.dark,
        primaryColor: const Color(0xFFFF8A65),
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFFFF6B00),
          brightness: Brightness.dark,
          primary: const Color(0xFFFF8A65),
          secondary: const Color(0xFFFFCA28),
          surface: const Color(0xFF1E1E1E),
          surfaceContainerLowest: const Color(0xFF121212),
        ),
        fontFamily: 'Noto Sans Devanagari',
        textTheme: ThemeData.dark().textTheme.copyWith(
          bodyLarge: const TextStyle(fontSize: 18),
          bodyMedium: const TextStyle(fontSize: 16),
          bodySmall: const TextStyle(fontSize: 14),
          titleLarge: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
          titleMedium: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          titleSmall: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
          labelLarge: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
        ),
        cardTheme: const CardThemeData(
          color: Color(0xFF2C2C2C),
          surfaceTintColor: Colors.transparent,
        ),
        chipTheme: ChipThemeData(
          backgroundColor: Colors.grey.shade800,
          disabledColor: Colors.grey.shade900,
          selectedColor: const Color(0xFFFF8A65),
          secondarySelectedColor: const Color(0xFFFFCA28),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            minimumSize: const Size(double.infinity, 56),
            textStyle: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
        ),
      ),
      debugShowCheckedModeBanner: false,
      home: const DashboardScreen(),
    );
  }
}
