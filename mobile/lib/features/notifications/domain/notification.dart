class AppNotification {
  final String uuid;
  final String type;
  final String title;
  final String body;
  final bool isRead;
  final String? readAt;
  final String createdAt;

  const AppNotification({
    required this.uuid,
    required this.type,
    required this.title,
    required this.body,
    this.isRead = false,
    this.readAt,
    required this.createdAt,
  });

  factory AppNotification.fromMap(Map<String, dynamic> map) {
    try {
      return AppNotification(
        uuid: (map['uuid'] as String?) ?? '',
        type: (map['type'] as String?) ?? 'info',
        title: (map['title'] as String?) ?? 'Notification',
        body: (map['body'] as String?) ?? '',
        isRead: map['is_read'] == true || map['is_read'] == 1,
        readAt: map['read_at'] as String?,
        createdAt: (map['created_at'] as String?) ?? DateTime.now().toIso8601String(),
      );
    } catch (e) {
      print('[AppNotification.fromMap] Error: $e');
      rethrow;
    }
  }
}
