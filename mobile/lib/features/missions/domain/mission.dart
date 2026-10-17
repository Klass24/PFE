class Mission {
  final String uuid;
  final String origin;
  final String destination;
  final String? startDate;
  final String? endDate;
  final String status;
  final String? notes;
  final bool synced;

  const Mission({
    required this.uuid,
    required this.origin,
    required this.destination,
    this.startDate,
    this.endDate,
    this.status = 'planned',
    this.notes,
    this.synced = false,
  });

  factory Mission.fromMap(Map<String, dynamic> map) {
    try {
      return Mission(
        uuid: (map['uuid'] as String?) ?? '',
        origin: (map['origin'] as String?) ?? '-',
        destination: (map['destination'] as String?) ?? '-',
        startDate: map['start_date'] as String?,
        endDate: map['end_date'] as String?,
        status: (map['status'] as String?) ?? 'planned',
        notes: map['notes'] as String?,
        synced: (map['synced'] as int?) == 1,
      );
    } catch (e) {
      print('[Mission.fromMap] Error: $e');
      rethrow;
    }
  }

  Map<String, dynamic> toMap() => {
        'uuid': uuid,
        'origin': origin,
        'destination': destination,
        'start_date': startDate,
        'end_date': endDate,
        'status': status,
        'notes': notes,
        'synced': synced ? 1 : 0,
      };
}
