import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../core/di/service_locator.dart';
import '../data/mission_repository.dart';
import '../domain/mission.dart';
import 'mission_bloc.dart';

// Airframe palette
const _kNavy   = Color(0xFF1F2D3D);
const _kBlue   = Color(0xFF0275D8);
const _kBgGray = Color(0xFFF0F3F4);
const _kBorder = Color(0xFFE8ECEF);
const _kMuted  = Color(0xFF7F8FA4);

class MissionsScreen extends StatelessWidget {
  const MissionsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => MissionBloc(repository: sl<MissionRepository>())
        ..add(RefreshMissions()),
      child: const _MissionsView(),
    );
  }
}

class _MissionsView extends StatelessWidget {
  const _MissionsView();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _kBgGray,

      // AppBar blanc style Airframe
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.menu, color: _kNavy),
          onPressed: () => Scaffold.of(context).openDrawer(),
        ),
        title: Row(
          children: [
            Container(
              width: 30, height: 30,
              decoration: BoxDecoration(
                color: _kBlue,
                borderRadius: BorderRadius.circular(6),
              ),
              child: const Icon(Icons.local_shipping_rounded, size: 18, color: Colors.white),
            ),
            const SizedBox(width: 10),
            const Text('FleetControl',
                style: TextStyle(
                    fontFamily: 'Inter', fontSize: 16, fontWeight: FontWeight.w700, color: _kNavy)),
          ],
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: _kBorder, height: 1),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: _kMuted),
            tooltip: 'Rafraîchir',
            onPressed: () => context.read<MissionBloc>().add(RefreshMissions()),
          ),
        ],
      ),

      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // ── Section header ───────────────────────────────────────────────
          Container(
            color: Colors.white,
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
            child: Row(
              children: [
                const Icon(Icons.route_rounded, size: 20, color: _kBlue),
                const SizedBox(width: 8),
                const Text('Mes missions',
                    style: TextStyle(
                        fontFamily: 'Inter', fontSize: 16, fontWeight: FontWeight.w700, color: _kNavy)),
                const Spacer(),
                BlocBuilder<MissionBloc, MissionState>(
                  builder: (context, state) {
                    if (state is MissionLoaded) {
                      return Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                        decoration: BoxDecoration(
                          color: const Color(0xFFE8F0FE),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          '${state.missions.length}',
                          style: const TextStyle(
                              fontFamily: 'Inter', fontSize: 13, fontWeight: FontWeight.w700, color: _kBlue),
                        ),
                      );
                    }
                    return const SizedBox.shrink();
                  },
                ),
              ],
            ),
          ),
          Container(color: _kBorder, height: 1),

          // ── Mission list ─────────────────────────────────────────────────
          Expanded(
            child: BlocBuilder<MissionBloc, MissionState>(
              builder: (context, state) {
                if (state is MissionLoading) {
                  return const Center(
                    child: CircularProgressIndicator(color: _kBlue, strokeWidth: 2),
                  );
                }
                if (state is MissionError) {
                  return _ErrorState(message: state.message,
                      onRetry: () => context.read<MissionBloc>().add(RefreshMissions()));
                }
                if (state is MissionLoaded) {
                  if (state.missions.isEmpty) {
                    return const _EmptyState();
                  }
                  return RefreshIndicator(
                    color: _kBlue,
                    onRefresh: () async =>
                        context.read<MissionBloc>().add(RefreshMissions()),
                    child: ListView.separated(
                      padding: const EdgeInsets.all(16),
                      itemCount: state.missions.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 10),
                      itemBuilder: (_, i) => _MissionCard(mission: state.missions[i]),
                    ),
                  );
                }
                return const SizedBox.shrink();
              },
            ),
          ),
        ],
      ),
    );
  }
}

// ── Mission Card ──────────────────────────────────────────────────────────────
class _MissionCard extends StatelessWidget {
  final Mission mission;
  const _MissionCard({required this.mission});

  static const _statusMap = {
    'planned':     ('Planifiée',   Color(0xFF5BC0DE), Color(0xFFD1ECF1)),
    'in_progress': ('En cours',    Color(0xFF0275D8), Color(0xFFCCE5FF)),
    'completed':   ('Terminée',    Color(0xFF5CB85C), Color(0xFFD4EDDA)),
    'cancelled':   ('Annulée',     Color(0xFFD9534F), Color(0xFFF8D7DA)),
  };

  (String, Color, Color) get _status {
    return _statusMap[mission.status] ?? ('Inconnu', _kMuted, _kBgGray);
  }

  String get _formattedDate {
    if (mission.startDate == null) return 'Date non définie';
    try {
      final dt = DateTime.parse(mission.startDate!).toLocal();
      return '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')}/${dt.year}';
    } catch (_) {
      return mission.startDate!;
    }
  }

  @override
  Widget build(BuildContext context) {
    final (label, fgColor, bgColor) = _status;
    return GestureDetector(
      onTap: () => Navigator.pushNamed(context, '/mission-detail', arguments: mission.uuid),
      child: Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: _kBorder),
        boxShadow: const [BoxShadow(color: Color(0x08000000), blurRadius: 4, offset: Offset(0, 2))],
      ),
      child: Column(
        children: [
          // Route header
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
            child: Row(
              children: [
                Container(
                  width: 42, height: 42,
                  decoration: BoxDecoration(
                    color: bgColor,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(Icons.directions_car_rounded, color: fgColor, size: 22),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${mission.origin} → ${mission.destination}',
                        style: const TextStyle(
                            fontFamily: 'Inter', fontSize: 14, fontWeight: FontWeight.w700, color: _kNavy),
                        maxLines: 1, overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 3),
                      Row(
                        children: [
                          const Icon(Icons.calendar_today_outlined, size: 13, color: _kMuted),
                          const SizedBox(width: 4),
                          Text(_formattedDate,
                              style: const TextStyle(fontFamily: 'Inter', fontSize: 12, color: _kMuted)),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                // Status badge
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: bgColor,
                    borderRadius: BorderRadius.circular(50),
                  ),
                  child: Text(label,
                      style: TextStyle(
                          fontFamily: 'Inter', fontSize: 11, fontWeight: FontWeight.w700, color: fgColor)),
                ),
              ],
            ),
          ),

          // Divider + footer
          Container(color: _kBorder, height: 1),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            child: Row(
              children: [
                _infoChip(Icons.location_on_outlined, mission.origin),
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 6),
                  child: Icon(Icons.arrow_forward, size: 14, color: _kMuted),
                ),
                _infoChip(Icons.flag_outlined, mission.destination),
              ],
            ),
          ),
        ],
      ),
    ),
    );
  }

  Widget _infoChip(IconData icon, String label) => Row(
    mainAxisSize: MainAxisSize.min,
    children: [
      Icon(icon, size: 13, color: _kMuted),
      const SizedBox(width: 4),
      ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 100),
        child: Text(label,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontFamily: 'Inter', fontSize: 12, color: _kMuted)),
      ),
    ],
  );
}

// ── Empty state ───────────────────────────────────────────────────────────────
class _EmptyState extends StatelessWidget {
  const _EmptyState();

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 72, height: 72,
            decoration: BoxDecoration(
              color: const Color(0xFFE8ECEF),
              borderRadius: BorderRadius.circular(50),
            ),
            child: const Icon(Icons.inbox_rounded, size: 36, color: _kMuted),
          ),
          const SizedBox(height: 16),
          const Text('Aucune mission',
              style: TextStyle(fontFamily: 'Inter', fontSize: 16, fontWeight: FontWeight.w700, color: _kNavy)),
          const SizedBox(height: 6),
          const Text('Vous n\'avez pas encore de mission assignée.',
              style: TextStyle(fontFamily: 'Inter', fontSize: 13, color: _kMuted)),
        ],
      ),
    );
  }
}

// ── Error state ───────────────────────────────────────────────────────────────
class _ErrorState extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const _ErrorState({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline_rounded, size: 48, color: Color(0xFFD9534F)),
            const SizedBox(height: 12),
            const Text('Erreur de chargement',
                style: TextStyle(fontFamily: 'Inter', fontSize: 15, fontWeight: FontWeight.w700, color: _kNavy)),
            const SizedBox(height: 6),
            Text(message, textAlign: TextAlign.center,
                style: const TextStyle(fontFamily: 'Inter', fontSize: 13, color: _kMuted)),
            const SizedBox(height: 20),
            ElevatedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh, size: 18),
              label: const Text('Réessayer'),
            ),
          ],
        ),
      ),
    );
  }
}
