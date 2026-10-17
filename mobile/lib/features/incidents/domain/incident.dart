class Incident {
  final String uuid;
  final String missionUuid;
  final String type;
  final String description;
  final String severity; // low, medium, high
  final String status; // open, in_progress, closed
  final String? location;
  final double? latitude;
  final double? longitude;
  final List<String> photos;
  final bool synced;
  final String? createdAt;

  const Incident({
    required this.uuid,
    required this.missionUuid,
    required this.type,
    required this.description,
    this.severity = 'medium',
    this.status = 'open',
    this.location,
    this.latitude,
    this.longitude,
    this.photos = const [],
    this.synced = false,
    this.createdAt,
  });

  factory Incident.fromMap(Map<String, dynamic> map) {
    try {
      // Parse photos array
      List<String> photosList = [];
      if (map['photos'] != null) {
        if (map['photos'] is List) {
          photosList = (map['photos'] as List).map((e) => e.toString()).toList();
        } else if (map['photos'] is String && (map['photos'] as String).isNotEmpty) {
          photosList = [map['photos'] as String];
        }
      }

      return Incident(
        uuid: (map['uuid'] as String?) ?? '',
        missionUuid: (map['mission_uuid'] as String?) ?? '',
        type: (map['type'] as String?) ?? 'other',
        description: (map['description'] as String?) ?? '',
        severity: (map['severity'] as String?) ?? 'medium',
        status: (map['status'] as String?) ?? 'open',
        location: map['location'] as String?,
        latitude: map['latitude'] != null ? (map['latitude'] as num).toDouble() : null,
        longitude: map['longitude'] != null ? (map['longitude'] as num).toDouble() : null,
        photos: photosList,
        synced: (map['synced'] as int?) == 1 || map['synced'] == true,
        createdAt: map['created_at'] as String?,
      );
    } catch (e) {
      print('[Incident.fromMap] Error: $e');
      rethrow;
    }
  }

  Map<String, dynamic> toMap() => {
        'uuid': uuid,
        'mission_uuid': missionUuid,
        'type': type,
        'description': description,
        'severity': severity,
        'status': status,
        'location': location,
        'latitude': latitude,
        'longitude': longitude,
        'photos': photos,
        'synced': synced ? 1 : 0,
        'created_at': createdAt,
      };
}
