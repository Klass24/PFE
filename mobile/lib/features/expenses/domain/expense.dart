class Expense {
  final String uuid;
  final String missionUuid;
  final String type;
  final double amount;
  final double? liters;
  final int? mileage;
  final String? notes;
  final String? receiptImage;
  final String status; // pending, approved, rejected
  final bool synced;
  final String? createdAt;

  const Expense({
    required this.uuid,
    required this.missionUuid,
    required this.type,
    required this.amount,
    this.liters,
    this.mileage,
    this.notes,
    this.receiptImage,
    this.status = 'pending',
    this.synced = false,
    this.createdAt,
  });

  factory Expense.fromMap(Map<String, dynamic> map) {
    try {
      return Expense(
        uuid: (map['uuid'] as String?) ?? '',
        missionUuid: (map['mission_uuid'] as String?) ?? '',
        type: (map['type'] as String?) ?? 'other',
        amount: map['amount'] != null ? (map['amount'] as num).toDouble() : 0.0,
        liters: map['liters'] != null ? (map['liters'] as num).toDouble() : null,
        mileage: map['mileage'] as int?,
        notes: map['notes'] as String?,
        receiptImage: map['receipt_image'] as String?,
        status: (map['status'] as String?) ?? 'pending',
        synced: (map['synced'] as int?) == 1 || map['synced'] == true,
        createdAt: map['created_at'] as String?,
      );
    } catch (e) {
      print('[Expense.fromMap] Error: $e');
      rethrow;
    }
  }

  Map<String, dynamic> toMap() => {
        'uuid': uuid,
        'mission_uuid': missionUuid,
        'type': type,
        'amount': amount,
        'liters': liters,
        'mileage': mileage,
        'notes': notes,
        'receipt_image': receiptImage,
        'status': status,
        'synced': synced ? 1 : 0,
        'created_at': createdAt,
      };
}
