import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../di/service_locator.dart';
import '../../services/api_service.dart';

// Airframe palette
const _kNavy    = Color(0xFF1F2D3D);
const _kBlue    = Color(0xFF0275D8);
const _kBgGray  = Color(0xFFF0F3F4);
const _kBorder  = Color(0xFFE8ECEF);
const _kMuted   = Color(0xFF7F8FA4);
const _kRed     = Color(0xFFD9534F);
const _kGreen   = Color(0xFF5CB85C);

class AppDrawer extends StatefulWidget {
  final int selectedIndex;
  final ValueChanged<int> onNavigate;
  final int unreadNotifications;

  const AppDrawer({
    super.key,
    required this.selectedIndex,
    required this.onNavigate,
    this.unreadNotifications = 0,
  });

  @override
  State<AppDrawer> createState() => _AppDrawerState();
}

class _AppDrawerState extends State<AppDrawer> {
  Map<String, dynamic>? _user;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  Future<void> _loadUser() async {
    try {
      final api = sl<ApiService>();
      final response = await api.get('/auth/me');
      final data = response['data'];
      if (data is Map) {
        setState(() {
          _user = Map<String, dynamic>.from(data);
          _loading = false;
        });
      } else {
        setState(() => _loading = false);
      }
    } catch (_) {
      setState(() => _loading = false);
    }
  }

  String get _initials {
    final name = _user?['name'] as String? ?? '';
    final parts = name.trim().split(' ');
    if (parts.isEmpty || parts[0].isEmpty) return '?';
    if (parts.length == 1) return parts[0].substring(0, 1).toUpperCase();
    return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
  }

  String get _userName => _user?['name'] as String? ?? 'Utilisateur';
  String get _userEmail => _user?['email'] as String? ?? '';
  String get _driverId => _user?['driver_id'] as String? ?? _user?['uuid']?.toString().substring(0, 8) ?? '';

  @override
  Widget build(BuildContext context) {
    return Drawer(
      backgroundColor: Colors.white,
      child: Column(
        children: [
          // ─── Header ────────────────────────────────────────────────────────
          Container(
            width: double.infinity,
            padding: EdgeInsets.only(
              top: MediaQuery.of(context).padding.top + 20,
              bottom: 20,
              left: 20,
              right: 20,
            ),
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
                colors: [Color(0xFF2C3E50), _kNavy],
              ),
            ),
            child: _loading
                ? const Center(
                    child: CircularProgressIndicator(
                      color: Colors.white,
                      strokeWidth: 2,
                    ),
                  )
                : Row(
                    children: [
                      // Avatar
                      CircleAvatar(
                        radius: 28,
                        backgroundColor: _kBlue,
                        child: Text(
                          _initials,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                        ),
                      ),
                      const SizedBox(width: 14),
                      // Info
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _userName,
                              style: const TextStyle(
                                fontFamily: 'Inter',
                                fontSize: 16,
                                fontWeight: FontWeight.w700,
                                color: Colors.white,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 2),
                            Text(
                              _userEmail,
                              style: TextStyle(
                                fontFamily: 'Inter',
                                fontSize: 12,
                                color: Colors.white.withOpacity(0.7),
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            if (_driverId.isNotEmpty) ...[
                              const SizedBox(height: 6),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.15),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: Text(
                                  'ID: $_driverId',
                                  style: TextStyle(
                                    fontFamily: 'Inter',
                                    fontSize: 10,
                                    fontWeight: FontWeight.w600,
                                    color: Colors.white.withOpacity(0.9),
                                  ),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
          ),

          // ─── Navigation Items ──────────────────────────────────────────────
          Expanded(
            child: ListView(
              padding: const EdgeInsets.symmetric(vertical: 8),
              children: [
                _DrawerItem(
                  icon: Icons.dashboard_outlined,
                  label: 'Dashboard',
                  selected: widget.selectedIndex == 0,
                  onTap: () => _navigate(0),
                ),
                _DrawerItem(
                  icon: Icons.map_outlined,
                  label: 'Mes missions',
                  selected: widget.selectedIndex == 1,
                  onTap: () => _navigate(1),
                ),
                _DrawerItem(
                  icon: Icons.notifications_outlined,
                  label: 'Notifications',
                  selected: widget.selectedIndex == 2,
                  badge: widget.unreadNotifications > 0 ? widget.unreadNotifications : null,
                  onTap: () => _navigate(2),
                ),
                _DrawerItem(
                  icon: Icons.account_circle_outlined,
                  label: 'Mon profil',
                  selected: widget.selectedIndex == 3,
                  onTap: () => _navigate(3),
                ),

                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Divider(color: _kBorder),
                ),

                // ─── Standalone Pages ───────────────────────────────────────
                _DrawerItem(
                  icon: Icons.checklist_rounded,
                  label: 'Check-list départ',
                  iconColor: _kGreen,
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.pushNamed(context, '/checklist');
                  },
                ),
                _DrawerItem(
                  icon: Icons.receipt_long_outlined,
                  label: 'Dépenses',
                  iconColor: const Color(0xFFF0AD4E),
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.pushNamed(context, '/depenses');
                  },
                ),
                _DrawerItem(
                  icon: Icons.warning_amber_rounded,
                  label: 'Incidents',
                  iconColor: _kRed,
                  onTap: () {
                    Navigator.pop(context);
                    Navigator.pushNamed(context, '/incidents');
                  },
                ),

                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Divider(color: _kBorder),
                ),

                _DrawerItem(
                  icon: Icons.history,
                  label: 'Historique',
                  onTap: () {
                    Navigator.pop(context);
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('Historique - Bientôt disponible'),
                        duration: Duration(seconds: 2),
                      ),
                    );
                  },
                ),
                _DrawerItem(
                  icon: Icons.settings_outlined,
                  label: 'Paramètres',
                  onTap: () {
                    Navigator.pop(context);
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text('Paramètres - Bientôt disponible'),
                        duration: Duration(seconds: 2),
                      ),
                    );
                  },
                ),

                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Divider(color: _kBorder),
                ),

                _DrawerItem(
                  icon: Icons.help_outline,
                  label: 'Aide / Support',
                  onTap: () {
                    Navigator.pop(context);
                    _showHelpDialog(context);
                  },
                ),
                _DrawerItem(
                  icon: Icons.logout,
                  label: 'Déconnexion',
                  iconColor: _kRed,
                  textColor: _kRed,
                  onTap: () => _handleLogout(context),
                ),
              ],
            ),
          ),

          // ─── Footer ────────────────────────────────────────────────────────
          Container(
            padding: const EdgeInsets.all(16),
            decoration: const BoxDecoration(
              border: Border(top: BorderSide(color: _kBorder)),
            ),
            child: Row(
              children: [
                Container(
                  width: 32,
                  height: 32,
                  decoration: BoxDecoration(
                    color: _kBlue,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(
                    Icons.local_shipping_rounded,
                    size: 18,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(width: 10),
                const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'FleetControl',
                      style: TextStyle(
                        fontFamily: 'Inter',
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: _kNavy,
                      ),
                    ),
                    Text(
                      'Version 1.0.0',
                      style: TextStyle(
                        fontFamily: 'Inter',
                        fontSize: 11,
                        color: _kMuted,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _navigate(int index) {
    Navigator.pop(context);
    widget.onNavigate(index);
  }

  void _showHelpDialog(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Row(
          children: [
            Icon(Icons.help_outline, color: _kBlue),
            SizedBox(width: 10),
            Text('Aide & Support'),
          ],
        ),
        content: const Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Besoin d\'aide ?',
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
            SizedBox(height: 8),
            Text('Email: support@fleetcontrol.com'),
            SizedBox(height: 4),
            Text('Téléphone: +216 XX XXX XXX'),
            SizedBox(height: 12),
            Text(
              'Horaires du support :',
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
            SizedBox(height: 4),
            Text('Lun - Ven : 8h00 - 18h00'),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Fermer'),
          ),
        ],
      ),
    );
  }

  Future<void> _handleLogout(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Déconnexion'),
        content: const Text('Voulez-vous vraiment vous déconnecter ?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Annuler'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: _kRed),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Déconnecter'),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.clear();
      if (context.mounted) {
        Navigator.pushNamedAndRemoveUntil(context, '/login', (route) => false);
      }
    }
  }
}

// ─── Drawer Item Widget ──────────────────────────────────────────────────────
class _DrawerItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool selected;
  final int? badge;
  final Color? iconColor;
  final Color? textColor;
  final VoidCallback onTap;

  const _DrawerItem({
    required this.icon,
    required this.label,
    this.selected = false,
    this.badge,
    this.iconColor,
    this.textColor,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final color = selected ? _kBlue : (textColor ?? _kNavy);
    final bgColor = selected ? _kBlue.withOpacity(0.08) : Colors.transparent;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
      child: Material(
        color: bgColor,
        borderRadius: BorderRadius.circular(8),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(8),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            child: Row(
              children: [
                Icon(
                  icon,
                  size: 22,
                  color: iconColor ?? color,
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Text(
                    label,
                    style: TextStyle(
                      fontFamily: 'Inter',
                      fontSize: 14,
                      fontWeight: selected ? FontWeight.w600 : FontWeight.w500,
                      color: textColor ?? color,
                    ),
                  ),
                ),
                if (badge != null)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: _kRed,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      badge! > 99 ? '99+' : '$badge',
                      style: const TextStyle(
                        fontFamily: 'Inter',
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                      ),
                    ),
                  ),
                if (selected)
                  Container(
                    width: 4,
                    height: 20,
                    margin: const EdgeInsets.only(left: 8),
                    decoration: BoxDecoration(
                      color: _kBlue,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
