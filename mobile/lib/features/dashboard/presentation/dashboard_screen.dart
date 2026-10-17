import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../core/di/service_locator.dart';
import '../data/dashboard_repository.dart';
import 'dashboard_bloc.dart';

// Airframe palette
const _kNavy    = Color(0xFF1F2D3D);
const _kBlue    = Color(0xFF0275D8);
const _kBgGray  = Color(0xFFF0F3F4);
const _kBorder  = Color(0xFFE8ECEF);
const _kMuted   = Color(0xFF7F8FA4);
const _kGreen   = Color(0xFF5CB85C);
const _kRed     = Color(0xFFD9534F);
const _kWarning = Color(0xFFF0AD4E);

class DashboardScreen extends StatelessWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => DashboardBloc(repository: sl<DashboardRepository>())
        ..add(LoadDashboard()),
      child: const _DashboardView(),
    );
  }
}

class _DashboardView extends StatelessWidget {
  const _DashboardView();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _kBgGray,
      body: BlocBuilder<DashboardBloc, DashboardState>(
        builder: (context, state) {
          if (state.isLoading && state.profile == null) {
            return const Center(
              child: CircularProgressIndicator(color: _kBlue, strokeWidth: 2),
            );
          }

          if (state.error != null && state.profile == null) {
            return _ErrorState(
              message: state.error!,
              onRetry: () => context.read<DashboardBloc>().add(RefreshDashboard()),
            );
          }

          return RefreshIndicator(
            color: _kBlue,
            onRefresh: () async {
              context.read<DashboardBloc>().add(RefreshDashboard());
            },
            child: CustomScrollView(
              slivers: [
                // Header
                _buildHeader(context, state),
                
                // Stats Cards
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: _StatsRow(
                      todayMissions: state.todayMissions,
                      completedMissions: state.completedMissions,
                      distanceKm: state.distanceKm,
                      unreadNotifications: state.unreadNotifications,
                    ),
                  ),
                ),

                // Active Missions Section
                SliverToBoxAdapter(
                  child: _SectionHeader(
                    icon: Icons.route_rounded,
                    title: 'Missions en cours',
                    count: state.missions.length,
                    onViewAll: () => _navigateToMissions(context),
                  ),
                ),
                if (state.missions.isEmpty)
                  const SliverToBoxAdapter(
                    child: _EmptyCard(
                      icon: Icons.check_circle_outline,
                      message: 'Aucune mission active',
                    ),
                  )
                else
                  SliverPadding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    sliver: SliverList(
                      delegate: SliverChildBuilderDelegate(
                        (context, index) => Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: _MissionCard(mission: state.missions[index]),
                        ),
                        childCount: state.missions.take(3).length,
                      ),
                    ),
                  ),

                // ─── Graphique Dépenses ─────────────────────────────────────
                SliverToBoxAdapter(
                  child: _SectionHeader(
                    icon: Icons.receipt_long_outlined,
                    title: 'Dépenses du mois',
                    count: state.recentExpenses.length,
                    onViewAll: () => Navigator.pushNamed(context, '/depenses'),
                  ),
                ),
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                    child: _ExpenseChartCard(
                      totalMonth: state.totalExpensesMonth,
                      byType: state.expensesByType,
                    ),
                  ),
                ),

                // ─── Graphique Incidents ────────────────────────────────────
                SliverToBoxAdapter(
                  child: _SectionHeader(
                    icon: Icons.warning_amber_outlined,
                    title: 'Incidents',
                    count: state.recentIncidents.length,
                    onViewAll: () => Navigator.pushNamed(context, '/incidents'),
                  ),
                ),
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                    child: _IncidentChartCard(
                      bySeverity: state.incidentsBySeverity,
                      byStatus: state.incidentsByStatus,
                      total: state.recentIncidents.length,
                    ),
                  ),
                ),

                // Recent Notifications Section
                SliverToBoxAdapter(
                  child: _SectionHeader(
                    icon: Icons.notifications_outlined,
                    title: 'Dernières notifications',
                    count: state.unreadNotifications,
                    onViewAll: () => _navigateToNotifications(context),
                  ),
                ),
                if (state.notifications.isEmpty)
                  const SliverToBoxAdapter(
                    child: _EmptyCard(
                      icon: Icons.notifications_off_outlined,
                      message: 'Aucune notification',
                    ),
                  )
                else
                  SliverPadding(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                    sliver: SliverList(
                      delegate: SliverChildBuilderDelegate(
                        (context, index) => Padding(
                          padding: const EdgeInsets.only(bottom: 8),
                          child: _NotificationCard(notification: state.notifications[index]),
                        ),
                        childCount: state.notifications.length,
                      ),
                    ),
                  ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildHeader(BuildContext context, DashboardState state) {
    final name = state.profile?['name'] as String? ?? 'Utilisateur';
    final initials = _getInitials(name);
    final isOnline = true; // Could be from connectivity state

    return SliverAppBar(
      expandedHeight: 140,
      floating: false,
      pinned: true,
      backgroundColor: _kNavy,
      leading: IconButton(
        icon: const Icon(Icons.menu, color: Colors.white),
        onPressed: () => Scaffold.of(context).openDrawer(),
      ),
      flexibleSpace: FlexibleSpaceBar(
        background: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [Color(0xFF2C3E50), _kNavy],
            ),
          ),
          child: SafeArea(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 20),
              child: Row(
                children: [
                  // Avatar
                  Stack(
                    children: [
                      CircleAvatar(
                        radius: 32,
                        backgroundColor: _kBlue,
                        child: Text(
                          initials,
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                        ),
                      ),
                      Positioned(
                        bottom: 0,
                        right: 0,
                        child: Container(
                          width: 16,
                          height: 16,
                          decoration: BoxDecoration(
                            color: isOnline ? _kGreen : _kMuted,
                            shape: BoxShape.circle,
                            border: Border.all(color: _kNavy, width: 2),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(width: 16),
                  // Name and status
                  Expanded(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Bonjour,',
                          style: TextStyle(
                            fontFamily: 'Inter',
                            fontSize: 14,
                            color: Colors.white.withOpacity(0.7),
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          name,
                          style: const TextStyle(
                            fontFamily: 'Inter',
                            fontSize: 20,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                          decoration: BoxDecoration(
                            color: isOnline ? _kGreen.withOpacity(0.2) : _kMuted.withOpacity(0.2),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Container(
                                width: 6,
                                height: 6,
                                decoration: BoxDecoration(
                                  color: isOnline ? _kGreen : _kMuted,
                                  shape: BoxShape.circle,
                                ),
                              ),
                              const SizedBox(width: 6),
                              Text(
                                isOnline ? 'En ligne' : 'Hors ligne',
                                style: TextStyle(
                                  fontFamily: 'Inter',
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                  color: isOnline ? _kGreen : _kMuted,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  // Refresh button
                  IconButton(
                    icon: const Icon(Icons.refresh_rounded, color: Colors.white70),
                    onPressed: () => context.read<DashboardBloc>().add(RefreshDashboard()),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
      title: const Text(
        'FleetControl',
        style: TextStyle(
          fontFamily: 'Inter',
          fontSize: 16,
          fontWeight: FontWeight.w700,
          color: Colors.white,
        ),
      ),
      actions: [
        IconButton(
          icon: const Icon(Icons.notifications_outlined, color: Colors.white),
          onPressed: () => _navigateToNotifications(context),
        ),
      ],
    );
  }

  String _getInitials(String name) {
    final parts = name.trim().split(' ');
    if (parts.isEmpty) return '?';
    if (parts.length == 1) return parts[0].substring(0, 1).toUpperCase();
    return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
  }

  void _navigateToMissions(BuildContext context) {
    // Find the MainShell and switch to Missions tab
    // For now, we'll use a simple callback approach
  }

  void _navigateToNotifications(BuildContext context) {
    // Find the MainShell and switch to Notifications tab
  }
}

// ─── Stats Row ───────────────────────────────────────────────────────────────
class _StatsRow extends StatelessWidget {
  final int todayMissions;
  final int completedMissions;
  final double distanceKm;
  final int unreadNotifications;

  const _StatsRow({
    required this.todayMissions,
    required this.completedMissions,
    required this.distanceKm,
    required this.unreadNotifications,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _StatCard(
            icon: Icons.assignment_outlined,
            label: 'Missions',
            value: '$completedMissions/$todayMissions',
            color: _kBlue,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _StatCard(
            icon: Icons.speed_rounded,
            label: 'Distance',
            value: '${distanceKm.toStringAsFixed(0)} km',
            color: _kGreen,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _StatCard(
            icon: Icons.notifications_active_outlined,
            label: 'Alertes',
            value: '$unreadNotifications',
            color: unreadNotifications > 0 ? _kWarning : _kMuted,
          ),
        ),
      ],
    );
  }
}

class _StatCard extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final Color color;

  const _StatCard({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: _kBorder),
        boxShadow: const [
          BoxShadow(color: Color(0x08000000), blurRadius: 4, offset: Offset(0, 2)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, size: 18, color: color),
          ),
          const SizedBox(height: 10),
          Text(
            value,
            style: const TextStyle(
              fontFamily: 'Inter',
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: _kNavy,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: const TextStyle(
              fontFamily: 'Inter',
              fontSize: 11,
              color: _kMuted,
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Section Header ──────────────────────────────────────────────────────────
class _SectionHeader extends StatelessWidget {
  final IconData icon;
  final String title;
  final int count;
  final VoidCallback onViewAll;

  const _SectionHeader({
    required this.icon,
    required this.title,
    required this.count,
    required this.onViewAll,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
      child: Row(
        children: [
          Icon(icon, size: 18, color: _kBlue),
          const SizedBox(width: 8),
          Text(
            title,
            style: const TextStyle(
              fontFamily: 'Inter',
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: _kNavy,
            ),
          ),
          if (count > 0) ...[
            const SizedBox(width: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: _kBlue.withOpacity(0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                '$count',
                style: const TextStyle(
                  fontFamily: 'Inter',
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: _kBlue,
                ),
              ),
            ),
          ],
          const Spacer(),
          TextButton(
            onPressed: onViewAll,
            style: TextButton.styleFrom(
              foregroundColor: _kBlue,
              padding: const EdgeInsets.symmetric(horizontal: 8),
              minimumSize: Size.zero,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
            ),
            child: const Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text('Voir tout', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                SizedBox(width: 2),
                Icon(Icons.chevron_right, size: 16),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Mission Card ────────────────────────────────────────────────────────────
class _MissionCard extends StatelessWidget {
  final Map<String, dynamic> mission;

  const _MissionCard({required this.mission});

  static const _statusMap = {
    'planned':     ('Planifiée',   _kBlue,    Color(0xFFCCE5FF)),
    'in_progress': ('En cours',    _kGreen,   Color(0xFFD4EDDA)),
    'completed':   ('Terminée',    _kMuted,   Color(0xFFE8ECEF)),
    'cancelled':   ('Annulée',     _kRed,     Color(0xFFF8D7DA)),
  };

  @override
  Widget build(BuildContext context) {
    final origin = mission['origin'] as String? ?? '-';
    final destination = mission['destination'] as String? ?? '-';
    final status = mission['status'] as String? ?? 'planned';
    final startDate = mission['start_date'] as String?;

    final (label, fgColor, bgColor) = _statusMap[status] ?? ('Inconnu', _kMuted, _kBgGray);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: _kBorder),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: bgColor,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(Icons.directions_car_rounded, size: 20, color: fgColor),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '$origin → $destination',
                  style: const TextStyle(
                    fontFamily: 'Inter',
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: _kNavy,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    const Icon(Icons.access_time, size: 12, color: _kMuted),
                    const SizedBox(width: 4),
                    Text(
                      _formatDate(startDate),
                      style: const TextStyle(
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
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: bgColor,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              label,
              style: TextStyle(
                fontFamily: 'Inter',
                fontSize: 10,
                fontWeight: FontWeight.w700,
                color: fgColor,
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _formatDate(String? dateStr) {
    if (dateStr == null) return 'Non défini';
    try {
      final dt = DateTime.parse(dateStr).toLocal();
      return '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')} à ${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return dateStr;
    }
  }
}

// ─── Notification Card ───────────────────────────────────────────────────────
class _NotificationCard extends StatelessWidget {
  final Map<String, dynamic> notification;

  const _NotificationCard({required this.notification});

  static const _typeIcons = {
    'document_expiry':   Icons.description_outlined,
    'incident_reported': Icons.warning_amber_rounded,
    'expense_threshold': Icons.monetization_on_outlined,
    'mission_started':   Icons.play_circle_outline,
    'mission_start':     Icons.play_circle_outline,
    'license_expiring':  Icons.badge_outlined,
  };

  static const _typeColors = {
    'document_expiry':   _kWarning,
    'incident_reported': _kRed,
    'expense_threshold': _kBlue,
    'mission_started':   _kGreen,
    'mission_start':     _kGreen,
    'license_expiring':  _kWarning,
  };

  @override
  Widget build(BuildContext context) {
    final type = notification['type'] as String? ?? '';
    final title = notification['title'] as String? ?? 'Notification';
    final body = notification['body'] as String? ?? '';
    final createdAt = notification['created_at'] as String?;
    final isRead = notification['is_read'] == true || notification['is_read'] == 1;

    final icon = _typeIcons[type] ?? Icons.notifications_outlined;
    final color = _typeColors[type] ?? _kBlue;

    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isRead ? Colors.white : color.withOpacity(0.05),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: isRead ? _kBorder : color.withOpacity(0.3)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(6),
            ),
            child: Icon(icon, size: 16, color: color),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    fontFamily: 'Inter',
                    fontSize: 12,
                    fontWeight: isRead ? FontWeight.w500 : FontWeight.w700,
                    color: _kNavy,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 2),
                Text(
                  body,
                  style: const TextStyle(
                    fontFamily: 'Inter',
                    fontSize: 11,
                    color: _kMuted,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Text(
            _formatTime(createdAt),
            style: const TextStyle(
              fontFamily: 'Inter',
              fontSize: 10,
              color: _kMuted,
            ),
          ),
        ],
      ),
    );
  }

  String _formatTime(String? dateStr) {
    if (dateStr == null) return '';
    try {
      final dt = DateTime.parse(dateStr).toLocal();
      final now = DateTime.now();
      final diff = now.difference(dt);
      
      if (diff.inMinutes < 60) {
        return '${diff.inMinutes}m';
      } else if (diff.inHours < 24) {
        return '${diff.inHours}h';
      } else {
        return '${diff.inDays}j';
      }
    } catch (_) {
      return '';
    }
  }
}

// ─── Empty Card ──────────────────────────────────────────────────────────────
class _EmptyCard extends StatelessWidget {
  final IconData icon;
  final String message;

  const _EmptyCard({required this.icon, required this.message});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: _kBorder),
        ),
        child: Column(
          children: [
            Icon(icon, size: 32, color: _kMuted),
            const SizedBox(height: 8),
            Text(
              message,
              style: const TextStyle(
                fontFamily: 'Inter',
                fontSize: 13,
                color: _kMuted,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Error State ─────────────────────────────────────────────────────────────
class _ErrorState extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;

  const _ErrorState({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, size: 48, color: _kRed),
          const SizedBox(height: 16),
          const Text(
            'Erreur de chargement',
            style: TextStyle(
              fontFamily: 'Inter',
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: _kNavy,
            ),
          ),
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 32),
            child: Text(
              message,
              style: const TextStyle(fontSize: 12, color: _kMuted),
              textAlign: TextAlign.center,
            ),
          ),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: const Text('Réessayer'),
          ),
        ],
      ),
    );
  }
}

// ═══════════════════════════════════════════════════════════════════════════
// GRAPHIQUE DÉPENSES
// ═══════════════════════════════════════════════════════════════════════════

const _expenseTypeInfo = <String, (String, Color)>{
  'fuel':          ('Carburant',   Color(0xFFF0AD4E)),
  'toll':          ('Péage',       Color(0xFF0275D8)),
  'repair':        ('Réparation',  Color(0xFFD9534F)),
  'food':          ('Repas',       Color(0xFF5CB85C)),
  'parking':       ('Parking',     Color(0xFF9B59B6)),
  'accommodation': ('Hébergement', Color(0xFF00BCD4)),
  'other':         ('Autre',       Color(0xFF95A5A6)),
};

class _ExpenseChartCard extends StatelessWidget {
  final double totalMonth;
  final Map<String, double> byType;

  const _ExpenseChartCard({required this.totalMonth, required this.byType});

  @override
  Widget build(BuildContext context) {
    final hasData = byType.isNotEmpty && totalMonth > 0;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: _kBorder),
        boxShadow: const [BoxShadow(color: Color(0x08000000), blurRadius: 4, offset: Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header total
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: _kBlue.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.receipt_long, size: 20, color: _kBlue),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Total ce mois',
                        style: TextStyle(fontFamily: 'Inter', fontSize: 12, color: _kMuted)),
                    Text(
                      hasData ? '${totalMonth.toStringAsFixed(2)} TND' : '—',
                      style: const TextStyle(
                        fontFamily: 'Inter', fontSize: 22,
                        fontWeight: FontWeight.w700, color: _kNavy,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),

          if (!hasData) ...[
            const SizedBox(height: 20),
            const Center(
              child: Text('Aucune dépense ce mois',
                  style: TextStyle(fontFamily: 'Inter', fontSize: 13, color: _kMuted)),
            ),
            const SizedBox(height: 8),
          ] else ...[
            const SizedBox(height: 16),
            // Barres horizontales par type
            ...byType.entries.map((entry) {
              final info = _expenseTypeInfo[entry.key] ?? ('Autre', _kMuted);
              final pct = totalMonth > 0 ? entry.value / totalMonth : 0.0;
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: _BarRow(
                  label: info.$1,
                  value: entry.value,
                  pct: pct,
                  color: info.$2,
                  suffix: 'TND',
                ),
              );
            }),
          ],
        ],
      ),
    );
  }
}

class _BarRow extends StatelessWidget {
  final String label;
  final double value;
  final double pct;
  final Color color;
  final String suffix;

  const _BarRow({
    required this.label,
    required this.value,
    required this.pct,
    required this.color,
    required this.suffix,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              children: [
                Container(
                  width: 10,
                  height: 10,
                  decoration: BoxDecoration(color: color, shape: BoxShape.circle),
                ),
                const SizedBox(width: 8),
                Text(label,
                    style: const TextStyle(fontFamily: 'Inter', fontSize: 12, color: _kNavy)),
              ],
            ),
            Text(
              '${value.toStringAsFixed(2)} $suffix',
              style: TextStyle(
                  fontFamily: 'Inter', fontSize: 12,
                  fontWeight: FontWeight.w600, color: color),
            ),
          ],
        ),
        const SizedBox(height: 4),
        ClipRRect(
          borderRadius: BorderRadius.circular(4),
          child: LinearProgressIndicator(
            value: pct.clamp(0.0, 1.0),
            backgroundColor: _kBgGray,
            color: color,
            minHeight: 8,
          ),
        ),
      ],
    );
  }
}

// ═══════════════════════════════════════════════════════════════════════════
// GRAPHIQUE INCIDENTS (donut + statuts)
// ═══════════════════════════════════════════════════════════════════════════

class _IncidentChartCard extends StatelessWidget {
  final Map<String, int> bySeverity;
  final Map<String, int> byStatus;
  final int total;

  const _IncidentChartCard({
    required this.bySeverity,
    required this.byStatus,
    required this.total,
  });

  @override
  Widget build(BuildContext context) {
    final totalSev = bySeverity.values.fold(0, (a, b) => a + b);
    final hasData = totalSev > 0;

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: _kBorder),
        boxShadow: const [BoxShadow(color: Color(0x08000000), blurRadius: 4, offset: Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: _kRed.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.warning_amber_rounded, size: 20, color: _kRed),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Total incidents',
                      style: TextStyle(fontFamily: 'Inter', fontSize: 12, color: _kMuted)),
                  Text(
                    '$totalSev incident${totalSev != 1 ? 's' : ''}',
                    style: const TextStyle(
                      fontFamily: 'Inter', fontSize: 22,
                      fontWeight: FontWeight.w700, color: _kNavy,
                    ),
                  ),
                ],
              ),
            ],
          ),

          if (!hasData) ...[
            const SizedBox(height: 20),
            Row(
              children: [
                const Icon(Icons.check_circle_outline, color: _kGreen, size: 20),
                const SizedBox(width: 8),
                const Text('Aucun incident enregistré',
                    style: TextStyle(fontFamily: 'Inter', fontSize: 13, color: _kMuted)),
              ],
            ),
            const SizedBox(height: 8),
          ] else ...[
            const SizedBox(height: 20),
            // Donut + légende
            Row(
              children: [
                SizedBox(
                  width: 110,
                  height: 110,
                  child: CustomPaint(
                    painter: _DonutPainter(
                      data: {
                        'low':    (bySeverity['low'] ?? 0).toDouble(),
                        'medium': (bySeverity['medium'] ?? 0).toDouble(),
                        'high':   (bySeverity['high'] ?? 0).toDouble(),
                      },
                      colors: const [Color(0xFFFFEB3B), Color(0xFFFF9800), Color(0xFFD9534F)],
                    ),
                    child: Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            '$totalSev',
                            style: const TextStyle(
                              fontFamily: 'Inter',
                              fontSize: 22,
                              fontWeight: FontWeight.w700,
                              color: _kNavy,
                            ),
                          ),
                          const Text('total',
                              style: TextStyle(fontFamily: 'Inter', fontSize: 10, color: _kMuted)),
                        ],
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 20),
                // Légende gravité
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _LegendRow(
                        emoji: '🟡',
                        label: 'Faible',
                        count: bySeverity['low'] ?? 0,
                        total: totalSev,
                        color: const Color(0xFFFFEB3B),
                      ),
                      const SizedBox(height: 10),
                      _LegendRow(
                        emoji: '🟠',
                        label: 'Moyen',
                        count: bySeverity['medium'] ?? 0,
                        total: totalSev,
                        color: const Color(0xFFFF9800),
                      ),
                      const SizedBox(height: 10),
                      _LegendRow(
                        emoji: '🔴',
                        label: 'Grave',
                        count: bySeverity['high'] ?? 0,
                        total: totalSev,
                        color: _kRed,
                      ),
                    ],
                  ),
                ),
              ],
            ),

            const SizedBox(height: 16),
            const Divider(height: 1),
            const SizedBox(height: 12),

            // Statuts
            const Text('Par statut',
                style: TextStyle(fontFamily: 'Inter', fontSize: 12,
                    fontWeight: FontWeight.w600, color: _kMuted)),
            const SizedBox(height: 10),
            Row(
              children: [
                _StatusChip(
                  label: 'Ouvert',
                  count: byStatus['open'] ?? 0,
                  color: _kRed,
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  label: 'En cours',
                  count: byStatus['in_progress'] ?? 0,
                  color: _kWarning,
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  label: 'Clôturé',
                  count: byStatus['closed'] ?? 0,
                  color: _kGreen,
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

class _LegendRow extends StatelessWidget {
  final String emoji;
  final String label;
  final int count;
  final int total;
  final Color color;

  const _LegendRow({
    required this.emoji,
    required this.label,
    required this.count,
    required this.total,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    final pct = total > 0 ? (count / total * 100).toStringAsFixed(0) : '0';
    return Row(
      children: [
        Text(emoji, style: const TextStyle(fontSize: 14)),
        const SizedBox(width: 8),
        Expanded(
          child: Text(label,
              style: const TextStyle(fontFamily: 'Inter', fontSize: 13, color: _kNavy)),
        ),
        Text(
          '$count ($pct%)',
          style: TextStyle(
              fontFamily: 'Inter', fontSize: 12,
              fontWeight: FontWeight.w600, color: color),
        ),
      ],
    );
  }
}

class _StatusChip extends StatelessWidget {
  final String label;
  final int count;
  final Color color;

  const _StatusChip({required this.label, required this.count, required this.color});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: color.withOpacity(0.08),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: color.withOpacity(0.3)),
        ),
        child: Column(
          children: [
            Text(
              '$count',
              style: TextStyle(
                fontFamily: 'Inter',
                fontSize: 20,
                fontWeight: FontWeight.w700,
                color: color,
              ),
            ),
            Text(
              label,
              style: TextStyle(
                fontFamily: 'Inter',
                fontSize: 11,
                color: color,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Donut Chart Painter ──────────────────────────────────────────────────────
class _DonutPainter extends CustomPainter {
  final Map<String, double> data;
  final List<Color> colors;

  _DonutPainter({required this.data, required this.colors});

  @override
  void paint(Canvas canvas, Size size) {
    final total = data.values.fold(0.0, (a, b) => a + b);
    if (total == 0) return;

    final center = Offset(size.width / 2, size.height / 2);
    final radius = math.min(size.width, size.height) / 2;
    const strokeWidth = 18.0;

    final rect = Rect.fromCircle(center: center, radius: radius - strokeWidth / 2);

    double startAngle = -math.pi / 2;
    int i = 0;

    for (final entry in data.entries) {
      if (entry.value <= 0) { i++; continue; }
      final sweepAngle = (entry.value / total) * 2 * math.pi;
      final paint = Paint()
        ..color = colors[i % colors.length]
        ..style = PaintingStyle.stroke
        ..strokeWidth = strokeWidth
        ..strokeCap = StrokeCap.butt;

      canvas.drawArc(rect, startAngle, sweepAngle - 0.05, false, paint);
      startAngle += sweepAngle;
      i++;
    }
  }

  @override
  bool shouldRepaint(_DonutPainter old) => old.data != data;
}

