import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../core/di/service_locator.dart';
import '../../../services/api_service.dart';
import '../../auth/data/auth_repository.dart';

const _kNavy   = Color(0xFF1F2D3D);
const _kBlue   = Color(0xFF0275D8);
const _kBgGray = Color(0xFFF0F3F4);
const _kBorder = Color(0xFFE8ECEF);
const _kMuted  = Color(0xFF7F8FA4);
const _kRed    = Color(0xFFD9534F);
const _kGreen  = Color(0xFF5CB85C);

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  Map<String, dynamic>? _user;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    setState(() { _loading = true; _error = null; });
    try {
      final api = sl<ApiService>();
      final response = await api.get('/auth/me');
      debugPrint('[ProfileScreen] API response: $response');
      
      // Handle response.data["data"] structure
      final data = response['data'];
      Map<String, dynamic>? user;
      if (data is Map) {
        user = Map<String, dynamic>.from(data);
      }
      
      debugPrint('[ProfileScreen] User data: $user');
      setState(() { _user = user; _loading = false; });
      
      // Save to SharedPreferences for offline access
      if (user != null) {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_user', user.toString());
      }
    } catch (e) {
      debugPrint('[ProfileScreen] Error: $e');
      // Try loading from cache
      final prefs = await SharedPreferences.getInstance();
      final cached = prefs.getString('auth_user');
      if (cached != null && cached.isNotEmpty) {
        // Basic parsing from toString format
        setState(() { _loading = false; });
      } else {
        setState(() { _error = e.toString(); _loading = false; });
      }
    }
  }

  Widget _buildErrorState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, size: 48, color: _kRed),
          const SizedBox(height: 16),
          const Text('Impossible de charger le profil',
              style: TextStyle(fontFamily: 'Inter', fontSize: 16, fontWeight: FontWeight.w600, color: _kNavy)),
          const SizedBox(height: 8),
          Text(_error ?? '', style: const TextStyle(color: _kMuted, fontSize: 12)),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: _loadProfile,
            icon: const Icon(Icons.refresh),
            label: const Text('Réessayer'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _kBgGray,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.menu, color: _kNavy),
          onPressed: () => Scaffold.of(context).openDrawer(),
        ),
        title: const Text('Mon Profil',
            style: TextStyle(color: _kNavy, fontWeight: FontWeight.w700)),
        iconTheme: const IconThemeData(color: _kNavy),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: _kBorder, height: 1),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: _kMuted),
            onPressed: _loadProfile,
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: _kBlue))
          : _error != null
              ? _buildErrorState()
              : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  // Avatar + Name
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: _kBorder),
                    ),
                    child: Column(
                      children: [
                        CircleAvatar(
                          radius: 40,
                          backgroundColor: _kBlue.withOpacity(0.1),
                          child: Text(
                            _initials,
                            style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w700, color: _kBlue),
                          ),
                        ),
                        const SizedBox(height: 12),
                        Text(_user?['name'] ?? 'Utilisateur',
                            style: const TextStyle(
                                fontFamily: 'Inter', fontSize: 18, fontWeight: FontWeight.w700, color: _kNavy)),
                        const SizedBox(height: 4),
                        Text(_user?['email'] ?? '',
                            style: const TextStyle(fontFamily: 'Inter', fontSize: 14, color: _kMuted)),
                        const SizedBox(height: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                          decoration: BoxDecoration(
                            color: _kBlue.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            _roleName,
                            style: const TextStyle(
                                fontFamily: 'Inter', fontSize: 12, fontWeight: FontWeight.w700, color: _kBlue),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Info rows
                  Container(
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: _kBorder),
                    ),
                    child: Column(
                      children: [
                        _InfoTile(icon: Icons.person_outline, label: 'Nom', value: _user?['name'] ?? '-'),
                        const Divider(height: 1, color: _kBorder),
                        _InfoTile(icon: Icons.email_outlined, label: 'Email', value: _user?['email'] ?? '-'),
                        const Divider(height: 1, color: _kBorder),
                        _InfoTile(icon: Icons.phone_outlined, label: 'Téléphone', value: _user?['phone'] ?? '-'),
                        const Divider(height: 1, color: _kBorder),
                        _InfoTile(icon: Icons.business, label: 'Entreprise', value: _user?['company_name'] ?? _user?['company']?['name'] ?? '-'),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Logout button
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: _handleLogout,
                      style: OutlinedButton.styleFrom(
                        foregroundColor: _kRed,
                        side: const BorderSide(color: _kRed),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                      icon: const Icon(Icons.logout),
                      label: const Text('Se déconnecter',
                          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                    ),
                  ),
                ],
              ),
            ),
    );
  }

  String get _initials {
    final name = _user?['name'] as String? ?? '';
    final parts = name.trim().split(' ');
    if (parts.isEmpty) return '?';
    if (parts.length == 1) return parts[0].substring(0, 1).toUpperCase();
    return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
  }

  String get _roleName {
    final role = _user?['role'] as String? ?? '';
    return switch (role) {
      'admin' => 'Administrateur',
      'manager' => 'Gestionnaire',
      'driver' => 'Chauffeur',
      _ => role,
    };
  }

  Future<void> _handleLogout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Déconnexion'),
        content: const Text('Voulez-vous vraiment vous déconnecter ?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Annuler')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: _kRed),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Déconnecter'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('token');
      final authRepo = sl<AuthRepository>();
      await authRepo.logout();
      if (mounted) {
        Navigator.pushNamedAndRemoveUntil(context, '/login', (_) => false);
      }
    }
  }
}

class _InfoTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  const _InfoTile({required this.icon, required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      child: Row(
        children: [
          Icon(icon, size: 20, color: _kBlue),
          const SizedBox(width: 12),
          Text(label, style: const TextStyle(fontFamily: 'Inter', fontSize: 13, color: _kMuted)),
          const Spacer(),
          Text(value,
              style: const TextStyle(fontFamily: 'Inter', fontSize: 13, fontWeight: FontWeight.w600, color: _kNavy)),
        ],
      ),
    );
  }
}
