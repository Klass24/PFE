import 'package:flutter/foundation.dart';
import '../../../services/api_service.dart';

class NotificationRepository {
  final ApiService _api;

  NotificationRepository({required ApiService api}) : _api = api;

  Future<List<Map<String, dynamic>>> getNotifications() async {
    final response = await _api.get('/notifications');
    debugPrint('[NotificationRepository] API response: $response');
    
    // Handle both nested {data: {data: [...]}} and flat {data: [...]} structures
    final data = response['data'];
    List<dynamic> items;
    if (data is List) {
      items = data;
    } else if (data is Map && data['data'] is List) {
      items = data['data'] as List;
    } else {
      items = [];
    }
    
    debugPrint('[NotificationRepository] Found ${items.length} notifications');
    return items.map((e) => Map<String, dynamic>.from(e as Map)).toList();
  }

  Future<void> markAsRead(String uuid) async {
    await _api.put('/notifications/$uuid/read', {});
  }

  Future<void> markAllAsRead() async {
    await _api.post('/notifications/read-all', {});
  }
}
