import '../../../local_db/database_helper.dart';
import '../../../services/api_service.dart';
import '../../../services/sync_service.dart';
import '../domain/document.dart';

class DocumentRepository {
  final ApiService _api;
  final DatabaseHelper _db;
  final SyncService _sync;

  DocumentRepository({
    required ApiService api,
    required DatabaseHelper db,
    required SyncService sync,
  })  : _api = api,
        _db = db,
        _sync = sync;

  /// Récupérer les documents du chauffeur depuis l'API
  Future<List<Document>> getDriverDocuments({
    String? type,
    String? status,
  }) async {
    try {
      final params = <String, dynamic>{};
      if (type != null) params['type'] = type;
      if (status != null) params['status'] = status;
      params['documentable_type'] = 'driver'; // Filtrer par chauffeur

      final response = await _api.get('/documents', queryParameters: params);
      final items = (response['data'] as List<dynamic>?) ?? [];
      return items.map((item) => Document.fromJson(Map<String, dynamic>.from(item as Map))).toList();
    } catch (e) {
      rethrow;
    }
  }

  /// Récupérer les documents d'un véhicule
  Future<List<Document>> getVehicleDocuments({
    required String vehicleUuid,
    String? type,
    String? status,
  }) async {
    try {
      final params = <String, dynamic>{
        'documentable_type': 'vehicle',
        'documentable_uuid': vehicleUuid,
      };
      if (type != null) params['type'] = type;
      if (status != null) params['status'] = status;

      final response = await _api.get('/documents', queryParameters: params);
      final items = (response['data'] as List<dynamic>?) ?? [];
      return items.map((item) => Document.fromJson(Map<String, dynamic>.from(item as Map))).toList();
    } catch (e) {
      rethrow;
    }
  }

  /// Télécharger la photo d'un document
  Future<void> downloadDocument(String uuid) async {
    try {
      // TODO: implémenter le téléchargement du fichier
      await _api.get('/documents/$uuid/download');
    } catch (e) {
      rethrow;
    }
  }
}
