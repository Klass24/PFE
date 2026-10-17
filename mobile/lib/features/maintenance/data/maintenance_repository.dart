import '../../../local_db/database_helper.dart';
import '../../../services/api_service.dart';
import '../../../services/sync_service.dart';
import '../domain/maintenance.dart';

class MaintenanceRepository {
  final ApiService _api;
  final DatabaseHelper _db;
  final SyncService _sync;

  MaintenanceRepository({
    required ApiService api,
    required DatabaseHelper db,
    required SyncService sync,
  })  : _api = api,
        _db = db,
        _sync = sync;

  /// Récupérer l'état de maintenance de tous les véhicules
  Future<List<MaintenanceSchedule>> getVehiclesMaintenance({
    String? status,
    String? vehicleUuid,
  }) async {
    try {
      final params = <String, dynamic>{};
      if (status != null) params['status'] = status;
      if (vehicleUuid != null) params['vehicle_uuid'] = vehicleUuid;

      final response = await _api.get('/maintenance', queryParameters: params);
      final items = (response['data'] as List<dynamic>?) ?? [];
      return items
          .map((item) => MaintenanceSchedule.fromJson(Map<String, dynamic>.from(item as Map)))
          .toList();
    } catch (e) {
      rethrow;
    }
  }

  /// Récupérer les alertes de maintenance (surdue)
  Future<List<MaintenanceSchedule>> getMaintenanceAlerts() async {
    try {
      final response = await _api.get('/maintenance/alerts');
      final items = (response['data'] as List<dynamic>?) ?? [];
      return items
          .map((item) => MaintenanceSchedule.fromJson(Map<String, dynamic>.from(item as Map)))
          .toList();
    } catch (e) {
      rethrow;
    }
  }
}
