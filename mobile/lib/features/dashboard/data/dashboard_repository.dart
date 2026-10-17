import 'package:flutter/foundation.dart';
import '../../../services/api_service.dart';

class DashboardRepository {
  final ApiService _api;

  DashboardRepository({required ApiService api}) : _api = api;

  /// Fetch user profile
  Future<Map<String, dynamic>?> getProfile() async {
    try {
      final response = await _api.get('/auth/me');
      debugPrint('[DashboardRepository] Profile response: $response');
      final data = response['data'];
      if (data is Map) {
        return Map<String, dynamic>.from(data);
      }
      return null;
    } catch (e) {
      debugPrint('[DashboardRepository] Profile error: $e');
      return null;
    }
  }

  /// Fetch missions (today's missions + active ones)
  Future<List<Map<String, dynamic>>> getMissions() async {
    try {
      final response = await _api.get('/missions');
      debugPrint('[DashboardRepository] Missions response: $response');
      final data = response['data'];
      List<dynamic> items;
      if (data is List) {
        items = data;
      } else if (data is Map && data['data'] is List) {
        items = data['data'] as List;
      } else {
        items = [];
      }
      return items.map((e) => Map<String, dynamic>.from(e as Map)).toList();
    } catch (e) {
      debugPrint('[DashboardRepository] Missions error: $e');
      return [];
    }
  }

  /// Fetch notifications
  Future<List<Map<String, dynamic>>> getNotifications() async {
    try {
      final response = await _api.get('/notifications');
      debugPrint('[DashboardRepository] Notifications response: $response');
      final data = response['data'];
      List<dynamic> items;
      if (data is List) {
        items = data;
      } else if (data is Map && data['data'] is List) {
        items = data['data'] as List;
      } else {
        items = [];
      }
      return items.map((e) => Map<String, dynamic>.from(e as Map)).toList();
    } catch (e) {
      debugPrint('[DashboardRepository] Notifications error: $e');
      return [];
    }
  }

  /// Fetch expenses
  Future<List<Map<String, dynamic>>> getExpenses() async {
    try {
      final response = await _api.get('/expenses');
      final data = response['data'];
      List<dynamic> items;
      if (data is List) {
        items = data;
      } else if (data is Map && data['data'] is List) {
        items = data['data'] as List;
      } else {
        items = [];
      }
      return items.map((e) => Map<String, dynamic>.from(e as Map)).toList();
    } catch (e) {
      debugPrint('[DashboardRepository] Expenses error: $e');
      return [];
    }
  }

  /// Fetch incidents
  Future<List<Map<String, dynamic>>> getIncidents() async {
    try {
      final response = await _api.get('/incidents');
      final data = response['data'];
      List<dynamic> items;
      if (data is List) {
        items = data;
      } else if (data is Map && data['data'] is List) {
        items = data['data'] as List;
      } else {
        items = [];
      }
      return items.map((e) => Map<String, dynamic>.from(e as Map)).toList();
    } catch (e) {
      debugPrint('[DashboardRepository] Incidents error: $e');
      return [];
    }
  }

  /// Fetch dashboard stats
  Future<Map<String, dynamic>> getStats() async {
    try {
      // Try to get stats from a dedicated endpoint if it exists
      final response = await _api.get('/dashboard/stats');
      final data = response['data'];
      if (data is Map) {
        return Map<String, dynamic>.from(data);
      }
      return {};
    } catch (e) {
      // Stats endpoint might not exist, return empty
      debugPrint('[DashboardRepository] Stats error: $e');
      return {};
    }
  }
}
