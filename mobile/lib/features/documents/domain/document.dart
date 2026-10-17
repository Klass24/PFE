class Document {
  final String uuid;
  final String type;
  final String documentableType;
  final String? filePath;
  final DateTime? issueDate;
  final DateTime? expiryDate;
  final bool alertSent;
  final DateTime createdAt;

  Document({
    required this.uuid,
    required this.type,
    required this.documentableType,
    this.filePath,
    this.issueDate,
    this.expiryDate,
    required this.alertSent,
    required this.createdAt,
  });

  bool get isExpired => expiryDate != null && expiryDate!.isBefore(DateTime.now());
  bool get expiringIn30Days =>
      expiryDate != null &&
      expiryDate!.isAfter(DateTime.now()) &&
      expiryDate!.isBefore(DateTime.now().add(const Duration(days: 30)));

  factory Document.fromJson(Map<String, dynamic> json) {
    try {
      return Document(
        uuid: (json['uuid'] as String?) ?? '',
        type: (json['type'] as String?) ?? 'other',
        documentableType: (json['documentable_type'] as String?) ?? '',
        filePath: json['file_path'] as String?,
        issueDate: json['issue_date'] != null ? DateTime.tryParse(json['issue_date'] as String) : null,
        expiryDate: json['expiry_date'] != null ? DateTime.tryParse(json['expiry_date'] as String) : null,
        alertSent: json['alert_sent'] as bool? ?? false,
        createdAt: json['created_at'] != null 
            ? DateTime.tryParse(json['created_at'] as String) ?? DateTime.now()
            : DateTime.now(),
      );
    } catch (e) {
      print('[Document.fromJson] Error: $e');
      rethrow;
    }
  }

  Map<String, dynamic> toJson() => {
        'uuid': uuid,
        'type': type,
        'documentable_type': documentableType,
        'file_path': filePath,
        'issue_date': issueDate?.toIso8601String(),
        'expiry_date': expiryDate?.toIso8601String(),
        'alert_sent': alertSent,
        'created_at': createdAt.toIso8601String(),
      };
}
