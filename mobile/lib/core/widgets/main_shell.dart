import 'package:flutter/material.dart';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../features/dashboard/presentation/dashboard_screen.dart';
import '../../features/missions/presentation/missions_screen.dart';
import '../../features/notifications/presentation/notifications_screen.dart';
import '../../features/profile/presentation/profile_screen.dart';
import '../../features/missions/data/mission_repository.dart';
import '../../features/notifications/data/notification_repository.dart';
import '../../features/dashboard/data/dashboard_repository.dart';
import '../../services/sync_service.dart';
import '../di/service_locator.dart';
import 'app_drawer.dart';

const _kNavy   = Color(0xFF1F2D3D);
const _kBlue   = Color(0xFF0275D8);
const _kWarning= Color(0xFFF0AD4E);
const _kBorder = Color(0xFFE8ECEF);
const _kMuted  = Color(0xFF7F8FA4);

class MainShell extends StatefulWidget {
  const MainShell({super.key});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();
  int _currentIndex = 0;
  bool _isOffline = false;
  final bool _syncing = false;
  int _unreadNotifications = 0;

  final _pages = const [
    DashboardScreen(),
    MissionsScreen(),
    NotificationsScreen(),
    ProfileScreen(),
  ];

  @override
  void initState() {
    super.initState();
    _checkConnectivity();
    _fetchUnreadCount();
    Connectivity().onConnectivityChanged.listen((results) {
      final offline = !results.any((r) => r != ConnectivityResult.none);
      if (mounted) setState(() => _isOffline = offline);
    });
  }

  Future<void> _checkConnectivity() async {
    final result = await Connectivity().checkConnectivity();
    final offline = !result.any((r) => r != ConnectivityResult.none);
    if (mounted) setState(() => _isOffline = offline);
  }

  Future<void> _fetchUnreadCount() async {
    try {
      final repo = sl<NotificationRepository>();
      final notifications = await repo.getNotifications();
      final unread = notifications.where((n) => n['is_read'] != true && n['is_read'] != 1).length;
      if (mounted) setState(() => _unreadNotifications = unread);
    } catch (_) {}
  }

  void _onNavigate(int index) {
    setState(() => _currentIndex = index);
    // Refresh unread count when navigating to notifications
    if (index == 2) _fetchUnreadCount();
  }

  @override
  Widget build(BuildContext context) {
    return MultiRepositoryProvider(
      providers: [
        RepositoryProvider.value(value: sl<DashboardRepository>()),
        RepositoryProvider.value(value: sl<MissionRepository>()),
        RepositoryProvider.value(value: sl<NotificationRepository>()),
      ],
      child: Scaffold(
        key: _scaffoldKey,
        drawer: AppDrawer(
          selectedIndex: _currentIndex,
          onNavigate: _onNavigate,
          unreadNotifications: _unreadNotifications,
        ),
        body: Column(
          children: [
            // Offline / sync banner
            if (_isOffline)
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                color: _kWarning,
                child: SafeArea(
                  bottom: false,
                  child: Row(children: [
                    const Icon(Icons.cloud_off, size: 16, color: Colors.white),
                    const SizedBox(width: 8),
                    const Expanded(
                      child: Text('Mode hors connexion — les données seront synchronisées à la reconnexion',
                          style: TextStyle(fontFamily: 'Inter', fontSize: 12, fontWeight: FontWeight.w600, color: Colors.white)),
                    ),
                    if (_syncing)
                      const SizedBox(
                        width: 14, height: 14,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                      ),
                  ]),
                ),
              ),
            // Active page
            Expanded(child: _pages[_currentIndex]),
          ],
        ),
        bottomNavigationBar: Container(
          decoration: const BoxDecoration(
            border: Border(top: BorderSide(color: _kBorder)),
          ),
          child: BottomNavigationBar(
            currentIndex: _currentIndex,
            onTap: _onNavigate,
            type: BottomNavigationBarType.fixed,
            backgroundColor: Colors.white,
            selectedItemColor: _kBlue,
            unselectedItemColor: _kMuted,
            selectedFontSize: 12,
            unselectedFontSize: 12,
            selectedLabelStyle: const TextStyle(fontFamily: 'Inter', fontWeight: FontWeight.w700),
            unselectedLabelStyle: const TextStyle(fontFamily: 'Inter'),
            items: [
              const BottomNavigationBarItem(
                icon: Icon(Icons.dashboard_rounded),
                label: 'Dashboard',
              ),
              const BottomNavigationBarItem(
                icon: Icon(Icons.route_rounded),
                label: 'Missions',
              ),
              BottomNavigationBarItem(
                icon: _unreadNotifications > 0
                    ? Badge(
                        label: Text(
                          _unreadNotifications > 9 ? '9+' : '$_unreadNotifications',
                          style: const TextStyle(fontSize: 10),
                        ),
                        child: const Icon(Icons.notifications_outlined),
                      )
                    : const Icon(Icons.notifications_outlined),
                label: 'Notifications',
              ),
              const BottomNavigationBarItem(
                icon: Icon(Icons.person_outline),
                label: 'Profil',
              ),
            ],
          ),
        ),
      ),
    );
  }
}
