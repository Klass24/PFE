import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  // Configurable via --dart-define=API_URL=...
  // Défaut : émulateur Android (10.0.2.2) ou web (localhost:8080)
  static const _baseUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: 'http://localhost:8080/api/v1',
  );

  late final Dio _dio;

  ApiService() {
    debugPrint('[ApiService] Base URL: $_baseUrl');
    _dio = Dio(BaseOptions(
      baseUrl: _baseUrl,
      connectTimeout: const Duration(seconds: 10),
      receiveTimeout: const Duration(seconds: 30),
      headers: {'Accept': 'application/json'},
    ));

    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final prefs = await SharedPreferences.getInstance();
        final token = prefs.getString('auth_token');
        debugPrint('[ApiService] Request: ${options.method} ${options.path}');
        debugPrint('[ApiService] Token present: ${token != null}');
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onResponse: (response, handler) {
        debugPrint('[ApiService] Response: ${response.statusCode} ${response.requestOptions.path}');
        return handler.next(response);
      },
      onError: (DioException e, handler) {
        debugPrint('[ApiService] Error: ${e.response?.statusCode} ${e.message}');
        if (e.response?.statusCode == 401) {
          SharedPreferences.getInstance().then((p) {
            p.remove('auth_token');
            p.remove('auth_user');
          });
        }
        return handler.next(e);
      },
    ));
  }

  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? params}) async {
    try {
      final response = await _dio.get(path, queryParameters: params);
      debugPrint('[ApiService] GET $path response type: ${response.data.runtimeType}');
      
      if (response.data == null) {
        return {'data': null, 'message': 'Empty response'};
      }
      
      if (response.data is Map<String, dynamic>) {
        return response.data as Map<String, dynamic>;
      }
      
      if (response.data is List) {
        return {'data': response.data};
      }
      
      return {'data': response.data};
    } on DioException catch (e) {
      debugPrint('[ApiService] DioException on GET $path: ${e.message}');
      debugPrint('[ApiService] Response: ${e.response?.data}');
      debugPrint('[ApiService] Status: ${e.response?.statusCode}');
      rethrow;
    } catch (e, stack) {
      debugPrint('[ApiService] Error on GET $path: $e');
      debugPrint('[ApiService] Stack: $stack');
      rethrow;
    }
  }

  Future<Map<String, dynamic>> post(String path, Map<String, dynamic> data) async {
    try {
      final response = await _dio.post(path, data: data);
      debugPrint('[ApiService] POST $path response type: ${response.data.runtimeType}');
      
      if (response.data == null) {
        return {'data': null, 'message': 'Empty response'};
      }
      
      if (response.data is Map<String, dynamic>) {
        return response.data as Map<String, dynamic>;
      }
      
      return {'data': response.data};
    } on DioException catch (e) {
      debugPrint('[ApiService] DioException on POST $path: ${e.message}');
      debugPrint('[ApiService] Response: ${e.response?.data}');
      rethrow;
    } catch (e, stack) {
      debugPrint('[ApiService] Error on POST $path: $e');
      debugPrint('[ApiService] Stack: $stack');
      rethrow;
    }
  }

  Future<Map<String, dynamic>> put(String path, Map<String, dynamic> data) async {
    final response = await _dio.put<Map<String, dynamic>>(path, data: data);
    return response.data!;
  }

  Future<void> delete(String path) async {
    await _dio.delete<void>(path);
  }
}
