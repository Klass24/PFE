import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../data/mission_repository.dart';
import '../domain/mission.dart';
import 'mission_bloc.dart';

const _kNavy   = Color(0xFF1F2D3D);
const _kBlue   = Color(0xFF0275D8);
const _kGreen  = Color(0xFF5CB85C);
const _kRed    = Color(0xFFD9534F);
const _kWarning= Color(0xFFF0AD4E);
const _kBgGray = Color(0xFFF0F3F4);
const _kBorder = Color(0xFFE8ECEF);
const _kMuted  = Color(0xFF7F8FA4);

class MissionDetailScreen extends StatefulWidget {
  final String missionUuid;
  const MissionDetailScreen({super.key, required this.missionUuid});

  @override
  State<MissionDetailScreen> createState() => _MissionDetailScreenState();
}

class _MissionDetailScreenState extends State<MissionDetailScreen> {
  Mission? _mission;

  @override
  void initState() {
    super.initState();
    _loadMission();
  }

  Future<void> _loadMission() async {
    final repo = context.read<MissionRepository>();
    final missions = await repo.getMissions();
    final found = missions.where((m) => m.uuid == widget.missionUuid).firstOrNull;
    if (mounted) setState(() => _mission = found);
  }

  static const _statusMap = {
    'planned':     ('Planifiée',   Color(0xFF5BC0DE), Color(0xFFD1ECF1), Icons.schedule),
    'in_progress': ('En cours',    Color(0xFF0275D8), Color(0xFFCCE5FF), Icons.play_circle_fill),
    'completed':   ('Terminée',    Color(0xFF5CB85C), Color(0xFFD4EDDA), Icons.check_circle),
    'cancelled':   ('Annulée',     Color(0xFFD9534F), Color(0xFFF8D7DA), Icons.cancel),
  };

  @override
  Widget build(BuildContext context) {
    if (_mission == null) {
      return Scaffold(
        backgroundColor: _kBgGray,
        appBar: AppBar(backgroundColor: Colors.white, elevation: 0),
        body: const Center(child: CircularProgressIndicator(color: _kBlue)),
      );
    }

    final mission = _mission!;
    final statusInfo = _statusMap[mission.status] ?? ('Inconnu', _kMuted, _kBgGray, Icons.help);
    final (statusLabel, statusFg, statusBg, statusIcon) = statusInfo;

    return Scaffold(
      backgroundColor: _kBgGray,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text('Détail Mission',
            style: TextStyle(color: _kNavy, fontWeight: FontWeight.w700)),
        iconTheme: const IconThemeData(color: _kNavy),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: _kBorder, height: 1),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Status Banner ──────────────────────────────────────────────
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: statusBg,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: statusFg.withOpacity(0.3)),
              ),
              child: Row(
                children: [
                  Icon(statusIcon, color: statusFg, size: 32),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(statusLabel,
                            style: TextStyle(fontFamily: 'Inter', fontSize: 16, fontWeight: FontWeight.w700, color: statusFg)),
                        const SizedBox(height: 2),
                        Text('Mission ${mission.uuid.substring(0, 8)}…',
                            style: TextStyle(fontFamily: 'Inter', fontSize: 12, color: statusFg.withOpacity(0.7))),
                      ],
                    ),
                  ),
                  if (!mission.synced)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: _kWarning.withOpacity(0.15),
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: const Text('Non sync.',
                          style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: _kWarning)),
                    ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // ── Route Card ─────────────────────────────────────────────────
            _Card(
              children: [
                const _SectionTitle(icon: Icons.route, title: 'Itinéraire'),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Column(
                      children: [
                        Container(
                          width: 10, height: 10,
                          decoration: const BoxDecoration(color: _kBlue, shape: BoxShape.circle),
                        ),
                        Container(width: 2, height: 30, color: _kBorder),
                        Container(
                          width: 10, height: 10,
                          decoration: const BoxDecoration(color: _kGreen, shape: BoxShape.circle),
                        ),
                      ],
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(mission.origin,
                              style: const TextStyle(fontFamily: 'Inter', fontSize: 14, fontWeight: FontWeight.w600, color: _kNavy)),
                          const SizedBox(height: 20),
                          Text(mission.destination,
                              style: const TextStyle(fontFamily: 'Inter', fontSize: 14, fontWeight: FontWeight.w600, color: _kNavy)),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 12),

            // ── Dates Card ─────────────────────────────────────────────────
            _Card(
              children: [
                const _SectionTitle(icon: Icons.calendar_today, title: 'Dates'),
                const SizedBox(height: 12),
                _InfoRow(label: 'Départ', value: _formatDate(mission.startDate)),
                const SizedBox(height: 8),
                _InfoRow(label: 'Arrivée', value: _formatDate(mission.endDate)),
              ],
            ),
            const SizedBox(height: 12),

            // ── Notes ──────────────────────────────────────────────────────
            if (mission.notes != null && mission.notes!.isNotEmpty) ...[
              _Card(
                children: [
                  const _SectionTitle(icon: Icons.notes, title: 'Notes'),
                  const SizedBox(height: 8),
                  Text(mission.notes!,
                      style: const TextStyle(fontFamily: 'Inter', fontSize: 13, color: _kMuted)),
                ],
              ),
              const SizedBox(height: 12),
            ],

            // ── Actions Quick Links ────────────────────────────────────────
            _Card(
              children: [
                const _SectionTitle(icon: Icons.apps, title: 'Actions'),
                const SizedBox(height: 12),
                _ActionTile(
                  icon: Icons.checklist_rounded,
                  color: _kBlue,
                  title: 'Check-list de départ',
                  subtitle: 'Vérification obligatoire avant démarrage',
                  onTap: () async {
                    final result = await Navigator.pushNamed(context, '/checklist-mission', arguments: mission.uuid);
                    if (result == true) _loadMission();
                  },
                ),
                const Divider(height: 1, color: _kBorder),
                _ActionTile(
                  icon: Icons.receipt_long_rounded,
                  color: _kGreen,
                  title: 'Dépenses',
                  subtitle: 'Carburant, péages, réparations…',
                  onTap: () => Navigator.pushNamed(context, '/expenses-mission', arguments: mission.uuid),
                ),
                const Divider(height: 1, color: _kBorder),
                _ActionTile(
                  icon: Icons.warning_amber_rounded,
                  color: _kRed,
                  title: 'Incidents',
                  subtitle: 'Pannes, accidents, contrôles…',
                  onTap: () => Navigator.pushNamed(context, '/incidents-mission', arguments: mission.uuid),
                ),
              ],
            ),
            const SizedBox(height: 20),

            // ── Status Action Buttons ──────────────────────────────────────
            if (mission.status == 'planned')
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () => _changeMissionStatus('in_progress'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _kBlue,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  icon: const Icon(Icons.play_arrow_rounded),
                  label: const Text('Démarrer la mission',
                      style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                ),
              ),

            if (mission.status == 'in_progress') ...[
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () => _changeMissionStatus('completed'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _kGreen,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  icon: const Icon(Icons.check_circle_outline),
                  label: const Text('Terminer la mission',
                      style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                ),
              ),
              const SizedBox(height: 10),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () => _changeMissionStatus('cancelled'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: _kRed,
                    side: const BorderSide(color: _kRed),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  icon: const Icon(Icons.cancel_outlined),
                  label: const Text('Annuler la mission',
                      style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                ),
              ),
            ],
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  void _changeMissionStatus(String newStatus) async {
    final repo = context.read<MissionRepository>();
    final updated = {
      ..._mission!.toMap(),
      'status': newStatus,
      'synced': 0,
      'updated_at': DateTime.now().toIso8601String(),
    };
    await repo.createOffline(updated);
    await _loadMission();
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Mission ${newStatus == 'in_progress' ? 'démarrée' : newStatus == 'completed' ? 'terminée' : 'annulée'}'),
          backgroundColor: _kGreen,
        ),
      );
    }
  }

  String _formatDate(String? raw) {
    if (raw == null) return 'Non définie';
    try {
      final dt = DateTime.parse(raw).toLocal();
      return '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')}/${dt.year} ${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return raw;
    }
  }
}

// ── Shared Widgets ────────────────────────────────────────────────────────────

class _Card extends StatelessWidget {
  final List<Widget> children;
  const _Card({required this.children});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: _kBorder),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: children),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  final IconData icon;
  final String title;
  const _SectionTitle({required this.icon, required this.title});

  @override
  Widget build(BuildContext context) {
    return Row(children: [
      Icon(icon, size: 18, color: _kBlue),
      const SizedBox(width: 8),
      Text(title, style: const TextStyle(fontFamily: 'Inter', fontSize: 14, fontWeight: FontWeight.w700, color: _kNavy)),
    ]);
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  const _InfoRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontFamily: 'Inter', fontSize: 13, color: _kMuted)),
        Text(value, style: const TextStyle(fontFamily: 'Inter', fontSize: 13, fontWeight: FontWeight.w600, color: _kNavy)),
      ],
    );
  }
}

class _ActionTile extends StatelessWidget {
  final IconData icon;
  final Color color;
  final String title;
  final String subtitle;
  final VoidCallback onTap;
  const _ActionTile({required this.icon, required this.color, required this.title, required this.subtitle, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 12),
        child: Row(
          children: [
            Container(
              width: 40, height: 40,
              decoration: BoxDecoration(
                color: color.withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(icon, color: color, size: 20),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(title, style: const TextStyle(fontFamily: 'Inter', fontSize: 14, fontWeight: FontWeight.w600, color: _kNavy)),
                Text(subtitle, style: const TextStyle(fontFamily: 'Inter', fontSize: 12, color: _kMuted)),
              ]),
            ),
            const Icon(Icons.chevron_right, color: _kMuted, size: 20),
          ],
        ),
      ),
    );
  }
}
