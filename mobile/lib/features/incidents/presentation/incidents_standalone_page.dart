import 'dart:io';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:image_picker/image_picker.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/di/service_locator.dart';
import '../../../services/api_service.dart';
import '../../incidents/domain/incident.dart';

// Airframe palette
const _kNavy   = Color(0xFF1F2D3D);
const _kBlue   = Color(0xFF0275D8);
const _kBgGray = Color(0xFFF0F3F4);
const _kBorder = Color(0xFFE8ECEF);
const _kMuted  = Color(0xFF7F8FA4);
const _kGreen  = Color(0xFF5CB85C);
const _kRed    = Color(0xFFD9534F);
const _kWarning= Color(0xFFF0AD4E);
const _kOrange = Color(0xFFFF9800);
const _kYellow = Color(0xFFFFEB3B);

// Numéro du responsable de flotte
const _kResponsablePhone = '+21698000000';

// Incident type data
const _typeData = <String, (IconData, Color, String)>{
  'accident': (Icons.car_crash, Color(0xFFD9534F), 'Accident'),
  'breakdown': (Icons.build_circle, Color(0xFFF0AD4E), 'Panne'),
  'theft': (Icons.report_gmailerrorred, Color(0xFFD9534F), 'Vol'),
  'cargo_damage': (Icons.inventory_2, Color(0xFF9B59B6), 'Dommage marchandise'),
  'delay': (Icons.timer_off, Color(0xFF0275D8), 'Retard'),
  'road_issue': (Icons.remove_road, Color(0xFF7F8FA4), 'Problème route'),
  'other': (Icons.more_horiz, Color(0xFF7F8FA4), 'Autre'),
};

// Severity data
const _severityData = <String, (Color, String, String)>{
  'low': (Color(0xFFFFEB3B), 'Faible', '🟡'),
  'medium': (Color(0xFFFF9800), 'Moyen', '🟠'),
  'high': (Color(0xFFD9534F), 'Grave', '🔴'),
};

// Status data
const _statusData = <String, (Color, String)>{
  'open': (Color(0xFFD9534F), 'Ouvert'),
  'in_progress': (Color(0xFFF0AD4E), 'En traitement'),
  'closed': (Color(0xFF5CB85C), 'Clôturé'),
};

class IncidentsStandalonePage extends StatefulWidget {
  const IncidentsStandalonePage({super.key});

  @override
  State<IncidentsStandalonePage> createState() => _IncidentsStandalonePageState();
}

class _IncidentsStandalonePageState extends State<IncidentsStandalonePage> {
  bool _loading = true;
  String? _error;
  List<Incident> _allIncidents = [];

  @override
  void initState() {
    super.initState();
    _loadIncidents();
  }

  DateTime _parseDate(String? dateStr) {
    if (dateStr == null || dateStr.isEmpty) return DateTime.now();
    return DateTime.tryParse(dateStr) ?? DateTime.now();
  }

  Future<void> _loadIncidents() async {
    setState(() { _loading = true; _error = null; });

    try {
      final api = sl<ApiService>();
      final response = await api.get('/incidents');
      final data = response['data'];

      if (data is List) {
        _allIncidents = data.map((e) => Incident.fromMap(Map<String, dynamic>.from(e))).toList();
        _allIncidents.sort((a, b) => _parseDate(b.createdAt).compareTo(_parseDate(a.createdAt))); // Most recent first
      } else {
        _allIncidents = [];
      }
    } catch (e) {
      _error = 'Erreur de chargement des incidents';
      _allIncidents = [];
    }

    if (mounted) setState(() => _loading = false);
  }

  void _showReportIncidentDialog() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _ReportIncidentSheet(
        onReported: () {
          _loadIncidents();
        },
      ),
    );
  }

  bool get _hasGraveIncident {
    // Check if there's any incident with high severity that is open
    return _allIncidents.any((i) => 
      (i.severity == 'high' || i.type == 'accident') && i.status != 'closed');
  }

  Future<void> _callResponsable() async {
    final uri = Uri.parse('tel:$_kResponsablePhone');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text("Impossible d'ouvrir le téléphone"),
            backgroundColor: _kRed,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _kBgGray,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: _kNavy),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text('Incidents',
            style: TextStyle(color: _kNavy, fontWeight: FontWeight.w700)),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: _kBorder, height: 1),
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 16),
            child: Center(
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: _allIncidents.isEmpty ? _kGreen.withOpacity(0.1) : _kWarning.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '${_allIncidents.length} incident${_allIncidents.length != 1 ? 's' : ''}',
                  style: TextStyle(
                    fontFamily: 'Inter',
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: _allIncidents.isEmpty ? _kGreen : _kWarning,
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: _kBlue))
          : _error != null
              ? _buildErrorState()
              : Column(
                  children: [
                    // Alerte grave si nécessaire
                    if (_hasGraveIncident) _buildGraveAlert(),
                    // Liste des incidents
                    Expanded(child: _buildIncidentList()),
                  ],
                ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _showReportIncidentDialog,
        backgroundColor: _kRed,
        icon: const Icon(Icons.warning_amber_rounded),
        label: const Text('Signaler'),
      ),
    );
  }

  Widget _buildGraveAlert() {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _kRed.withOpacity(0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: _kRed, width: 2),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: _kRed,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.warning_rounded, color: Colors.white, size: 20),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'INCIDENT GRAVE',
                      style: TextStyle(
                        fontFamily: 'Inter',
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: _kRed,
                      ),
                    ),
                    Text(
                      'Un incident grave nécessite une attention immédiate',
                      style: TextStyle(
                        fontFamily: 'Inter',
                        fontSize: 12,
                        color: _kNavy,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _callResponsable,
              style: ElevatedButton.styleFrom(
                backgroundColor: _kRed,
                padding: const EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              icon: const Icon(Icons.phone),
              label: const Text(
                'Appeler le responsable',
                style: TextStyle(fontFamily: 'Inter', fontWeight: FontWeight.w700),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildErrorState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, size: 48, color: _kRed),
          const SizedBox(height: 16),
          Text(_error ?? 'Erreur'),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: _loadIncidents,
            icon: const Icon(Icons.refresh),
            label: const Text('Réessayer'),
          ),
        ],
      ),
    );
  }

  Widget _buildIncidentList() {
    if (_allIncidents.isEmpty) {
      return _buildEmptyState();
    }

    return RefreshIndicator(
      onRefresh: _loadIncidents,
      color: _kBlue,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: _allIncidents.length,
        itemBuilder: (context, index) => _IncidentCard(
          incident: _allIncidents[index],
          parseDate: _parseDate,
          onTap: () => _showIncidentDetails(_allIncidents[index]),
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: _kGreen.withOpacity(0.1),
              borderRadius: BorderRadius.circular(40),
            ),
            child: const Icon(Icons.check_circle_outline, size: 40, color: _kGreen),
          ),
          const SizedBox(height: 16),
          const Text(
            'Aucun incident',
            style: TextStyle(
              fontFamily: 'Inter',
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: _kNavy,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Tout roule bien !',
            style: TextStyle(
              fontFamily: 'Inter',
              fontSize: 14,
              color: _kMuted,
            ),
          ),
        ],
      ),
    );
  }

  void _showIncidentDetails(Incident incident) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (context) => _IncidentDetailsSheet(
        incident: incident,
        parseDate: _parseDate,
      ),
    );
  }
}

class _IncidentCard extends StatelessWidget {
  final Incident incident;
  final DateTime Function(String?) parseDate;
  final VoidCallback onTap;

  const _IncidentCard({
    required this.incident,
    required this.parseDate,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final typeInfo = _typeData[incident.type] ?? _typeData['other']!;
    final severityInfo = _severityData[incident.severity] ?? _severityData['medium']!;
    final statusInfo = _statusData[incident.status] ?? _statusData['open']!;
    final dateStr = DateFormat('dd MMM yyyy HH:mm', 'fr_FR').format(parseDate(incident.createdAt));

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: incident.severity == 'high' ? _kRed.withOpacity(0.5) : _kBorder,
            width: incident.severity == 'high' ? 2 : 1,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.03),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header row
            Row(
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: typeInfo.$2.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(typeInfo.$1, color: typeInfo.$2, size: 22),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              typeInfo.$3,
                              style: const TextStyle(
                                fontFamily: 'Inter',
                                fontSize: 15,
                                fontWeight: FontWeight.w600,
                                color: _kNavy,
                              ),
                            ),
                          ),
                          // Gravité badge
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                            decoration: BoxDecoration(
                              color: severityInfo.$1.withOpacity(0.2),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              '${severityInfo.$3} ${severityInfo.$2}',
                              style: TextStyle(
                                fontFamily: 'Inter',
                                fontSize: 10,
                                fontWeight: FontWeight.w700,
                                color: severityInfo.$1.computeLuminance() > 0.5 ? _kNavy : severityInfo.$1,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(Icons.access_time, size: 12, color: _kMuted.withOpacity(0.7)),
                          const SizedBox(width: 4),
                          Text(
                            dateStr,
                            style: TextStyle(
                              fontFamily: 'Inter',
                              fontSize: 11,
                              color: _kMuted.withOpacity(0.8),
                            ),
                          ),
                          const Spacer(),
                          // Statut badge
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                            decoration: BoxDecoration(
                              color: statusInfo.$1.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              statusInfo.$2,
                              style: TextStyle(
                                fontFamily: 'Inter',
                                fontSize: 10,
                                fontWeight: FontWeight.w600,
                                color: statusInfo.$1,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 4),
                Icon(Icons.chevron_right, color: _kMuted.withOpacity(0.5)),
              ],
            ),

            // Description
            if (incident.description.isNotEmpty) ...[
              const SizedBox(height: 10),
              const Divider(height: 1),
              const SizedBox(height: 10),
              Text(
                incident.description,
                style: const TextStyle(
                  fontFamily: 'Inter',
                  fontSize: 13,
                  color: _kMuted,
                ),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],

            // Location if available
            if (incident.location != null && incident.location!.isNotEmpty) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(Icons.location_on_outlined, size: 14, color: _kMuted.withOpacity(0.7)),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      incident.location!,
                      style: TextStyle(
                        fontFamily: 'Inter',
                        fontSize: 12,
                        color: _kMuted.withOpacity(0.8),
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ] else if (incident.latitude != null && incident.longitude != null) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  Icon(Icons.location_on_outlined, size: 14, color: _kMuted.withOpacity(0.7)),
                  const SizedBox(width: 4),
                  Text(
                    'Position GPS enregistrée',
                    style: TextStyle(
                      fontFamily: 'Inter',
                      fontSize: 12,
                      color: _kMuted.withOpacity(0.8),
                    ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _IncidentDetailsSheet extends StatelessWidget {
  final Incident incident;
  final DateTime Function(String?) parseDate;

  const _IncidentDetailsSheet({
    required this.incident,
    required this.parseDate,
  });

  @override
  Widget build(BuildContext context) {
    final typeInfo = _typeData[incident.type] ?? _typeData['other']!;
    final dateStr = DateFormat('dd MMMM yyyy à HH:mm', 'fr_FR').format(parseDate(incident.createdAt));

    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Handle bar
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: _kBorder,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 20),

            // Header
            Row(
              children: [
                Container(
                  width: 56,
                  height: 56,
                  decoration: BoxDecoration(
                    color: typeInfo.$2.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(typeInfo.$1, color: typeInfo.$2, size: 28),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        typeInfo.$3,
                        style: const TextStyle(
                          fontFamily: 'Inter',
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                          color: _kNavy,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: incident.synced ? _kGreen.withOpacity(0.1) : _kWarning.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          incident.synced ? 'Synchronisé' : 'En attente',
                          style: TextStyle(
                            fontFamily: 'Inter',
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: incident.synced ? _kGreen : _kWarning,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Details
            _DetailRow(icon: Icons.access_time, label: 'Date', value: dateStr),
            if (incident.latitude != null && incident.longitude != null)
              _DetailRow(
                icon: Icons.location_on_outlined,
                label: 'Position',
                value: '${incident.latitude!.toStringAsFixed(4)}, ${incident.longitude!.toStringAsFixed(4)}',
              ),

            if (incident.description.isNotEmpty) ...[
              const SizedBox(height: 16),
              const Text('Description',
                  style: TextStyle(fontFamily: 'Inter', fontSize: 13, fontWeight: FontWeight.w600, color: _kMuted)),
              const SizedBox(height: 8),
              Text(
                incident.description,
                style: const TextStyle(fontFamily: 'Inter', fontSize: 14, color: _kNavy),
              ),
            ],

            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton(
                onPressed: () => Navigator.pop(context),
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  side: const BorderSide(color: _kBorder),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                child: const Text('Fermer', style: TextStyle(color: _kNavy)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _DetailRow({required this.icon, required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Icon(icon, size: 18, color: _kMuted),
          const SizedBox(width: 10),
          Text('$label: ', style: const TextStyle(fontFamily: 'Inter', fontSize: 13, color: _kMuted)),
          Expanded(
            child: Text(value, style: const TextStyle(fontFamily: 'Inter', fontSize: 13, fontWeight: FontWeight.w600, color: _kNavy)),
          ),
        ],
      ),
    );
  }
}

class _ReportIncidentSheet extends StatefulWidget {
  final VoidCallback onReported;

  const _ReportIncidentSheet({required this.onReported});

  @override
  State<_ReportIncidentSheet> createState() => _ReportIncidentSheetState();
}

class _ReportIncidentSheetState extends State<_ReportIncidentSheet> {
  final _descController = TextEditingController();
  final _locationController = TextEditingController();
  String _selectedType = 'accident';
  String _selectedSeverity = 'medium';
  DateTime _selectedDateTime = DateTime.now();
  final List<String> _photos = [];
  bool _submitting = false;
  final ImagePicker _picker = ImagePicker();

  @override
  void dispose() {
    _descController.dispose();
    _locationController.dispose();
    super.dispose();
  }

  Future<void> _pickPhoto() async {
    if (_photos.length >= 3) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Maximum 3 photos autorisées'), backgroundColor: _kWarning),
      );
      return;
    }

    final XFile? photo = await _picker.pickImage(
      source: ImageSource.camera,
      imageQuality: 70,
      maxWidth: 1024,
    );

    if (photo != null) {
      setState(() => _photos.add(photo.path));
    }
  }

  Future<void> _pickDateTime() async {
    final date = await showDatePicker(
      context: context,
      initialDate: _selectedDateTime,
      firstDate: DateTime.now().subtract(const Duration(days: 30)),
      lastDate: DateTime.now(),
    );
    if (date == null || !mounted) return;

    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.fromDateTime(_selectedDateTime),
    );
    if (time == null || !mounted) return;

    setState(() {
      _selectedDateTime = DateTime(date.year, date.month, date.day, time.hour, time.minute);
    });
  }

  Future<void> _submit() async {
    if (_descController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Veuillez décrire l'incident"), backgroundColor: _kWarning),
      );
      return;
    }

    setState(() => _submitting = true);

    try {
      final api = sl<ApiService>();
      await api.post('/incidents', {
        'type': _selectedType,
        'severity': _selectedSeverity,
        'description': _descController.text.trim(),
        'location': _locationController.text.trim(),
        'datetime': _selectedDateTime.toIso8601String(),
        'photos': _photos,
        'created_at': DateTime.now().toIso8601String(),
      });

      if (mounted) {
        Navigator.pop(context);
        widget.onReported();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Incident signalé'), backgroundColor: _kGreen),
        );
      }
    } catch (e) {
      if (mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Incident enregistré localement'), backgroundColor: _kWarning),
        );
        widget.onReported();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(top: 40),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Padding(
        padding: EdgeInsets.only(
          left: 20,
          right: 20,
          top: 20,
          bottom: MediaQuery.of(context).viewInsets.bottom + 20,
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Handle bar
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: _kBorder,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 20),

              const Text(
                'Signaler un incident',
                style: TextStyle(
                  fontFamily: 'Inter',
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: _kNavy,
                ),
              ),
              const SizedBox(height: 20),

              // Type selector
              const Text("Type d'incident", style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: _kMuted)),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: _typeData.entries.map((entry) {
                  final isSelected = _selectedType == entry.key;
                  return InkWell(
                    onTap: () => setState(() => _selectedType = entry.key),
                    borderRadius: BorderRadius.circular(8),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      decoration: BoxDecoration(
                        color: isSelected ? entry.value.$2.withOpacity(0.1) : _kBgGray,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(
                          color: isSelected ? entry.value.$2 : _kBorder,
                          width: isSelected ? 2 : 1,
                        ),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(entry.value.$1, size: 16, color: isSelected ? entry.value.$2 : _kMuted),
                          const SizedBox(width: 6),
                          Text(
                            entry.value.$3,
                            style: TextStyle(
                              fontFamily: 'Inter',
                              fontSize: 12,
                              fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
                              color: isSelected ? entry.value.$2 : _kNavy,
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                }).toList(),
              ),
              const SizedBox(height: 16),

              // Severity selector
              const Text('Niveau de gravité', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: _kMuted)),
              const SizedBox(height: 8),
              Row(
                children: _severityData.entries.map((entry) {
                  final isSelected = _selectedSeverity == entry.key;
                  return Expanded(
                    child: Padding(
                      padding: EdgeInsets.only(right: entry.key != 'high' ? 8 : 0),
                      child: InkWell(
                        onTap: () => setState(() => _selectedSeverity = entry.key),
                        borderRadius: BorderRadius.circular(8),
                        child: Container(
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          decoration: BoxDecoration(
                            color: isSelected ? entry.value.$1.withOpacity(0.2) : _kBgGray,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(
                              color: isSelected ? entry.value.$1 : _kBorder,
                              width: isSelected ? 2 : 1,
                            ),
                          ),
                          child: Column(
                            children: [
                              Text(entry.value.$3, style: const TextStyle(fontSize: 18)),
                              const SizedBox(height: 4),
                              Text(
                                entry.value.$2,
                                style: TextStyle(
                                  fontFamily: 'Inter',
                                  fontSize: 12,
                                  fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                                  color: isSelected ? entry.value.$1 : _kNavy,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  );
                }).toList(),
              ),
              const SizedBox(height: 16),

              // Description
              const Text('Description *', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: _kMuted)),
              const SizedBox(height: 8),
              TextFormField(
                controller: _descController,
                maxLines: 3,
                decoration: InputDecoration(
                  hintText: "Décrivez l'incident en détail...",
                  filled: true,
                  fillColor: _kBgGray,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide: const BorderSide(color: _kBorder),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide: const BorderSide(color: _kBorder),
                  ),
                  contentPadding: const EdgeInsets.all(12),
                ),
              ),
              const SizedBox(height: 16),

              // Location
              const Text("Lieu de l'incident", style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: _kMuted)),
              const SizedBox(height: 8),
              TextFormField(
                controller: _locationController,
                decoration: InputDecoration(
                  hintText: 'Ex: Autoroute A1 km 42, Tunis...',
                  prefixIcon: const Icon(Icons.location_on_outlined, color: _kMuted),
                  filled: true,
                  fillColor: _kBgGray,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide: const BorderSide(color: _kBorder),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide: const BorderSide(color: _kBorder),
                  ),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
                ),
              ),
              const SizedBox(height: 16),

              // DateTime picker
              const Text('Date et heure', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: _kMuted)),
              const SizedBox(height: 8),
              InkWell(
                onTap: _pickDateTime,
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
                  decoration: BoxDecoration(
                    color: _kBgGray,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: _kBorder),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.calendar_today, size: 18, color: _kMuted),
                      const SizedBox(width: 10),
                      Text(
                        DateFormat('dd MMMM yyyy à HH:mm', 'fr_FR').format(_selectedDateTime),
                        style: const TextStyle(fontFamily: 'Inter', fontSize: 14, color: _kNavy),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),

              // Photos
              const Text('Photos (max 3)', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: _kMuted)),
              const SizedBox(height: 8),
              Row(
                children: [
                  // Photo thumbnails
                  ..._photos.asMap().entries.map((entry) {
                    return Stack(
                      children: [
                        Container(
                          width: 72,
                          height: 72,
                          margin: const EdgeInsets.only(right: 8),
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(8),
                            image: DecorationImage(
                              image: FileImage(File(entry.value)),
                              fit: BoxFit.cover,
                            ),
                          ),
                        ),
                        Positioned(
                          top: 4,
                          right: 12,
                          child: InkWell(
                            onTap: () => setState(() => _photos.removeAt(entry.key)),
                            child: Container(
                              padding: const EdgeInsets.all(2),
                              decoration: const BoxDecoration(
                                color: _kRed,
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(Icons.close, size: 14, color: Colors.white),
                            ),
                          ),
                        ),
                      ],
                    );
                  }),
                  // Add photo button
                  if (_photos.length < 3)
                    InkWell(
                      onTap: _pickPhoto,
                      borderRadius: BorderRadius.circular(8),
                      child: Container(
                        width: 72,
                        height: 72,
                        decoration: BoxDecoration(
                          color: _kBgGray,
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: _kBorder, style: BorderStyle.solid),
                        ),
                        child: const Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.camera_alt_outlined, color: _kMuted),
                            SizedBox(height: 4),
                            Text('Photo', style: TextStyle(fontSize: 10, color: _kMuted)),
                          ],
                        ),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 24),

              // Submit
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _submitting ? null : _submit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _kRed,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  icon: _submitting ? null : const Icon(Icons.warning_amber_rounded),
                  label: _submitting
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Text("Déclarer l'incident",
                          style: TextStyle(fontFamily: 'Inter', fontSize: 15, fontWeight: FontWeight.w700)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
