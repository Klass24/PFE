class MaintenanceSchedule {
  final String uuid;
  final String vehicleUuid;
  final String vehiclePlate;
  final String maintenanceType;
  final String status;
  final int? frequencyKm;
  final int? frequencyMonths;
  final DateTime? lastServiceDate;
  final int? lastServiceKm;
  final DateTime? nextDueDate;
  final int? nextDueKm;
  final String? notes;
  final DateTime createdAt;

  MaintenanceSchedule({
    required this.uuid,
    required this.vehicleUuid,
    required this.vehiclePlate,
    required this.maintenanceType,
    required this.status,
    this.frequencyKm,
    this.frequencyMonths,
    this.lastServiceDate,
    this.lastServiceKm,
    this.nextDueDate,
    this.nextDueKm,
    this.notes,
    required this.createdAt,
  });

  bool get isOverdue {
    if (nextDueDate != null && nextDueDate!.isBefore(DateTime.now())) return true;
    if (nextDueKm != null && lastServiceKm != null) return true; // TODO: vérifier avec mileage du véhicule
    return false;
  }

  factory MaintenanceSchedule.fromJson(Map<String, dynamic> json) {
    try {
      return MaintenanceSchedule(
        uuid: (json['uuid'] as String?) ?? '',
        vehicleUuid: (json['vehicle_uuid'] as String?) ?? '',
        vehiclePlate: (json['vehicle_plate'] as String?) ?? '',
        maintenanceType: (json['maintenance_type'] as String?) ?? 'other',
        status: (json['status'] as String?) ?? 'pending',
        frequencyKm: json['frequency_km'] as int?,
        frequencyMonths: json['frequency_months'] as int?,
        lastServiceDate: json['last_service_date'] != null
            ? DateTime.tryParse(json['last_service_date'] as String)
            : null,
        lastServiceKm: json['last_service_km'] as int?,
        nextDueDate: json['next_due_date'] != null
            ? DateTime.tryParse(json['next_due_date'] as String)
            : null,
        nextDueKm: json['next_due_km'] as int?,
        notes: json['notes'] as String?,
        createdAt: json['created_at'] != null
            ? DateTime.tryParse(json['created_at'] as String) ?? DateTime.now()
            : DateTime.now(),
      );
    } catch (e) {
      print('[MaintenanceSchedule.fromJson] Error: $e');
      rethrow;
    }
  }

  Map<String, dynamic> toJson() => {
        'uuid': uuid,
        'vehicle_uuid': vehicleUuid,
        'vehicle_plate': vehiclePlate,
        'maintenance_type': maintenanceType,
        'status': status,
        'frequency_km': frequencyKm,
        'frequency_months': frequencyMonths,
        'last_service_date': lastServiceDate?.toIso8601String(),
        'last_service_km': lastServiceKm,
        'next_due_date': nextDueDate?.toIso8601String(),
        'next_due_km': nextDueKm,
        'notes': notes,
        'created_at': createdAt.toIso8601String(),
      };
}
