import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:geolocator/geolocator.dart';
import '../data/incident_repository.dart';
import '../domain/incident.dart';
import 'incident_bloc.dart';

// Airframe palette
const _kNavy   = Color(0xFF1F2D3D);
const _kBlue   = Color(0xFF0275D8);
const _kBgGray = Color(0xFFF0F3F4);
const _kBorder = Color(0xFFE8ECEF);
const _kMuted  = Color(0xFF7F8FA4);

const _typeLabels = {
  'accident':        'Accident',
  'breakdown':       'Panne',
  'flat_tire':       'Crevaison',
  'traffic':         'Circulation',
  'police_check':    'Contrôle police',
  'mechanical_issue':'Problème mécanique',
  'other':           'Autre',
};

const _typeColors = {
  'accident':         Color(0xFFD9534F),
  'breakdown':        Color(0xFFF0AD4E),
  'flat_tire':        Color(0xFFF0AD4E),
  'traffic':          Color(0xFF0275D8),
  'police_check':     Color(0xFF0275D8),
  'mechanical_issue': Color(0xFFF0AD4E),
  'other':            Color(0xFF95A5A6),
};

class IncidentsScreen extends StatelessWidget {
  final String missionUuid;
  const IncidentsScreen({super.key, required this.missionUuid});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => IncidentBloc(repository: context.read<IncidentRepository>())
        ..add(LoadIncidents(missionUuid)),
      child: _IncidentsView(missionUuid: missionUuid),
    );
  }
}

class _IncidentsView extends StatelessWidget {
  final String missionUuid;
  const _IncidentsView({required this.missionUuid});

  void _showReportDialog(BuildContext context) {
    final formKey = GlobalKey<FormState>();
    String selectedType = 'breakdown';
    final descCtrl = TextEditingController();
    double? capturedLat;
    double? capturedLng;
    bool locating = false;

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setState) => AlertDialog(
          title: const Row(children: [
            Icon(Icons.warning_amber_rounded, color: Color(0xFFD9534F), size: 22),
            SizedBox(width: 8),
            Text('Signaler un incident'),
          ]),
          content: Form(
            key: formKey,
            child: SingleChildScrollView(
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                DropdownButtonFormField<String>(
                  value: selectedType,
                  decoration: const InputDecoration(labelText: 'Type d\'incident'),
                  items: _typeLabels.entries
                      .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value)))
                      .toList(),
                  onChanged: (v) => setState(() => selectedType = v!),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: descCtrl,
                  decoration: const InputDecoration(
                    labelText: 'Description *',
                    alignLabelWithHint: true,
                  ),
                  maxLines: 3,
                  validator: (v) => (v == null || v.trim().isEmpty) ? 'Obligatoire' : null,
                ),
                const SizedBox(height: 12),
                // Geolocation
                OutlinedButton.icon(
                  onPressed: locating ? null : () async {
                    setState(() => locating = true);
                    try {
                      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
                      if (!serviceEnabled) {
                        setState(() => locating = false);
                        return;
                      }
                      LocationPermission perm = await Geolocator.checkPermission();
                      if (perm == LocationPermission.denied) {
                        perm = await Geolocator.requestPermission();
                        if (perm == LocationPermission.denied || perm == LocationPermission.deniedForever) {
                          setState(() => locating = false);
                          return;
                        }
                      }
                      final pos = await Geolocator.getCurrentPosition(
                        timeLimit: const Duration(seconds: 10),
                      );
                      setState(() {
                        capturedLat = pos.latitude;
                        capturedLng = pos.longitude;
                        locating = false;
                      });
                    } catch (_) {
                      setState(() => locating = false);
                    }
                  },
                  icon: locating
                      ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                      : Icon(capturedLat != null ? Icons.check_circle : Icons.my_location,
                          color: capturedLat != null ? const Color(0xFF5CB85C) : _kMuted),
                  label: Text(capturedLat != null
                      ? '${capturedLat!.toStringAsFixed(4)}, ${capturedLng!.toStringAsFixed(4)}'
                      : 'Capturer la position'),
                ),
              ]),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Annuler'),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFD9534F)),
              onPressed: () {
                if (!formKey.currentState!.validate()) return;
                context.read<IncidentBloc>().add(ReportIncident(
                  missionUuid: missionUuid,
                  type: selectedType,
                  description: descCtrl.text.trim(),
                  latitude: capturedLat,
                  longitude: capturedLng,
                ));
                Navigator.pop(ctx);
              },
              child: const Text('Signaler'),
            ),
          ],
        ),
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
        title: const Text('Incidents', style: TextStyle(color: _kNavy, fontWeight: FontWeight.w700)),
        iconTheme: const IconThemeData(color: _kNavy),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: _kBorder, height: 1),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: _kMuted),
            onPressed: () => context.read<IncidentBloc>().add(LoadIncidents(missionUuid)),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showReportDialog(context),
        backgroundColor: const Color(0xFFD9534F),
        icon: const Icon(Icons.add_alert_rounded),
        label: const Text('Signaler'),
      ),
      body: BlocConsumer<IncidentBloc, IncidentState>(
        listener: (ctx, state) {
          if (state is IncidentReported) {
            ScaffoldMessenger.of(ctx).showSnackBar(
              const SnackBar(
                content: Text('Incident signalé (sync en attente)'),
                backgroundColor: Color(0xFF5CB85C),
              ),
            );
          }
          if (state is IncidentError) {
            ScaffoldMessenger.of(ctx).showSnackBar(
              SnackBar(content: Text(state.message), backgroundColor: const Color(0xFFD9534F)),
            );
          }
        },
        builder: (ctx, state) {
          if (state is IncidentLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          final incidents = switch (state) {
            IncidentLoaded()    => state.incidents,
            IncidentReported()  => state.incidents,
            _ => <Incident>[],
          };

          if (incidents.isEmpty) {
            return Center(
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                Icon(Icons.check_circle_outline_rounded, size: 56, color: _kMuted.withAlpha(128)),
                const SizedBox(height: 12),
                const Text('Aucun incident', style: TextStyle(color: _kMuted, fontSize: 15)),
              ]),
            );
          }

          return ListView.separated(
            padding: const EdgeInsets.all(12),
            itemCount: incidents.length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (_, i) => _IncidentCard(incident: incidents[i]),
          );
        },
      ),
    );
  }
}

class _IncidentCard extends StatelessWidget {
  final Incident incident;
  const _IncidentCard({required this.incident});

  @override
  Widget build(BuildContext context) {
    final color = _typeColors[incident.type] ?? _kMuted;
    final label = _typeLabels[incident.type] ?? incident.type;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Icon circle
            Container(
              width: 42, height: 42,
              decoration: BoxDecoration(
                color: color.withAlpha(26),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(_typeIcon(incident.type), color: color, size: 20),
            ),
            const SizedBox(width: 12),

            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(label,
                        style: TextStyle(
                            fontWeight: FontWeight.w700, fontSize: 14, color: color)),
                    _SyncChip(synced: incident.synced),
                  ],
                ),
                const SizedBox(height: 4),
                Text(incident.description,
                    style: const TextStyle(fontSize: 13, color: _kNavy)),
                if (incident.latitude != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Row(children: [
                      const Icon(Icons.location_on_rounded, size: 12, color: _kMuted),
                      const SizedBox(width: 4),
                      Text(
                        '${incident.latitude!.toStringAsFixed(4)}, ${incident.longitude!.toStringAsFixed(4)}',
                        style: const TextStyle(fontSize: 11, color: _kMuted),
                      ),
                    ]),
                  ),
              ]),
            ),
          ],
        ),
      ),
    );
  }

  IconData _typeIcon(String type) {
    return switch (type) {
      'accident'         => Icons.car_crash_rounded,
      'breakdown'        => Icons.car_repair_rounded,
      'flat_tire'        => Icons.tire_repair_rounded,
      'traffic'          => Icons.traffic_rounded,
      'police_check'     => Icons.local_police_rounded,
      'mechanical_issue' => Icons.build_circle_rounded,
      _                  => Icons.warning_amber_rounded,
    };
  }
}

class _SyncChip extends StatelessWidget {
  final bool synced;
  const _SyncChip({required this.synced});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: synced
            ? const Color(0xFF5CB85C).withAlpha(26)
            : const Color(0xFFF0AD4E).withAlpha(26),
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        synced ? 'Sync.' : 'En attente',
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: synced ? const Color(0xFF5CB85C) : const Color(0xFFF0AD4E),
        ),
      ),
    );
  }
}
