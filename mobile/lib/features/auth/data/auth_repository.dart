import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../services/api_service.dart';

class AuthRepository {
  final ApiService _api;

  AuthRepository({required ApiService api}) : _api = api;

  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await _api.post('/auth/login', {
      'email': email,
      'password': password,
    });
    debugPrint('[AuthRepository] Login response: $response');
    final token = response['data']?['token'] as String?;
    debugPrint('[AuthRepository] Token received: ${token != null ? '${token.substring(0, 20)}...' : 'null'}');
    if (token != null) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('auth_token', token);
      debugPrint('[AuthRepository] Token saved to SharedPreferences');
      final user = response['data']?['user'];
      if (user != null) {
        await prefs.setString('auth_user', user.toString());
      }
    }
    return response;
  }

  Future<void> logout() async {
    try {
      await _api.post('/auth/logout', {});
    } finally {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('auth_token');
      await prefs.remove('auth_user');
    }
  }

  Future<bool> isAuthenticated() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.containsKey('auth_token');
  }
}
