import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'features/auth/presentation/login_screen.dart';
import 'features/missions/presentation/missions_screen.dart';
import 'features/expenses/presentation/expenses_screen.dart';
import 'features/expenses/presentation/expenses_standalone_page.dart';
import 'features/incidents/presentation/incidents_screen.dart';
import 'features/incidents/presentation/incidents_standalone_page.dart';
import 'features/checklist/presentation/checklist_screen.dart';
import 'features/checklist/presentation/checklist_standalone_page.dart';
import 'features/missions/presentation/mission_detail_screen.dart';
import 'features/notifications/presentation/notifications_screen.dart';
import 'features/profile/presentation/profile_screen.dart';
import 'core/widgets/main_shell.dart';

// Airframe color palette
const _kPrimary     = Color(0xFF0275D8);
const _kSidebarBg   = Color(0xFF1F2D3D);
const _kSuccess     = Color(0xFF5CB85C);
const _kWarning     = Color(0xFFF0AD4E);
const _kDanger      = Color(0xFFD9534F);
const _kBgDefault   = Color(0xFFF0F3F4);
const _kTextPrimary = Color(0xFF2F3640);
const _kTextMuted   = Color(0xFF8E9BB0);

class FleetControlApp extends StatelessWidget {
  const FleetControlApp({super.key});

  @override
  Widget build(BuildContext context) {
    // Status bar style transparent
    SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.dark,
    ));

    return MaterialApp(
      title: 'FleetControl',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        fontFamily: 'Inter',
        colorScheme: const ColorScheme.light(
          primary:          _kPrimary,
          secondary:        _kSuccess,
          error:            _kDanger,
          surface:          Colors.white,
          surfaceContainerHighest: _kBgDefault,
        ),
        scaffoldBackgroundColor: _kBgDefault,

        // AppBar — match la topbar Airframe
        appBarTheme: const AppBarTheme(
          backgroundColor:  Colors.white,
          foregroundColor:  _kTextPrimary,
          elevation:        0,
          scrolledUnderElevation: 1,
          centerTitle:      false,
          titleTextStyle:   TextStyle(
            fontFamily: 'Inter',
            fontSize: 17,
            fontWeight: FontWeight.w700,
            color: _kTextPrimary,
          ),
          systemOverlayStyle: SystemUiOverlayStyle(
            statusBarColor: Colors.transparent,
            statusBarIconBrightness: Brightness.dark,
          ),
        ),

        // Navigation Drawer — sidebar dark
        drawerTheme: const DrawerThemeData(
          backgroundColor: _kSidebarBg,
          width: 260,
        ),

        // Cards — style Airframe (légère ombre + border)
        cardTheme: CardThemeData(
          elevation: 0,
          color: Colors.white,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(8),
            side: const BorderSide(color: Color(0xFFE8ECEF)),
          ),
          margin: EdgeInsets.zero,
        ),

        // Buttons
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: _kPrimary,
            foregroundColor: Colors.white,
            elevation: 0,
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
            textStyle: const TextStyle(fontFamily: 'Inter', fontWeight: FontWeight.w600, fontSize: 14),
          ),
        ),

        outlinedButtonTheme: OutlinedButtonThemeData(
          style: OutlinedButton.styleFrom(
            foregroundColor: _kPrimary,
            side: const BorderSide(color: _kPrimary),
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 11),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
            textStyle: const TextStyle(fontFamily: 'Inter', fontWeight: FontWeight.w600, fontSize: 14),
          ),
        ),

        // Input fields
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: Colors.white,
          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(6),
            borderSide: const BorderSide(color: Color(0xFFDDE3EC)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(6),
            borderSide: const BorderSide(color: Color(0xFFDDE3EC)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(6),
            borderSide: const BorderSide(color: _kPrimary, width: 1.5),
          ),
          labelStyle: const TextStyle(color: _kTextMuted, fontSize: 14),
          hintStyle: const TextStyle(color: _kTextMuted, fontSize: 14),
        ),

        // List tiles
        listTileTheme: const ListTileThemeData(
          contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 4),
          titleTextStyle: TextStyle(fontFamily: 'Inter', fontSize: 14, fontWeight: FontWeight.w500, color: _kTextPrimary),
          subtitleTextStyle: TextStyle(fontFamily: 'Inter', fontSize: 12, color: _kTextMuted),
        ),

        // Text
        textTheme: const TextTheme(
          headlineMedium: TextStyle(fontSize: 22, fontWeight: FontWeight.w700, color: _kTextPrimary),
          titleLarge:     TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: _kTextPrimary),
          titleMedium:    TextStyle(fontSize: 15, fontWeight: FontWeight.w600, color: _kTextPrimary),
          bodyMedium:     TextStyle(fontSize: 14, color: _kTextPrimary),
          bodySmall:      TextStyle(fontSize: 12, color: _kTextMuted),
          labelSmall:     TextStyle(fontSize: 11, color: _kTextMuted, letterSpacing: 0.5),
        ),

        dividerColor: const Color(0xFFE8ECEF),
      ),
      initialRoute: '/login',
      routes: {
        '/login':    (_) => const LoginScreen(),
        '/home':     (_) => const MainShell(),
        '/missions': (_) => const MissionsScreen(),
        '/mission-detail': (ctx) {
          final uuid = ModalRoute.of(ctx)!.settings.arguments as String;
          return MissionDetailScreen(missionUuid: uuid);
        },
        '/expenses-mission': (ctx) {
          final uuid = ModalRoute.of(ctx)!.settings.arguments as String;
          return ExpensesScreen(missionUuid: uuid);
        },
        '/incidents-mission': (ctx) {
          final uuid = ModalRoute.of(ctx)!.settings.arguments as String;
          return IncidentsScreen(missionUuid: uuid);
        },
        '/checklist-mission': (ctx) {
          final uuid = ModalRoute.of(ctx)!.settings.arguments as String;
          return ChecklistScreen(missionUuid: uuid);
        },
        // Standalone pages (accessible from drawer)
        '/checklist': (_) => const ChecklistStandalonePage(),
        '/depenses': (_) => const ExpensesStandalonePage(),
        '/incidents': (_) => const IncidentsStandalonePage(),
        '/notifications': (_) => const NotificationsScreen(),
        '/profile': (_) => const ProfileScreen(),
      },
    );
  }
}
