import 'package:connectivity_plus/connectivity_plus.dart';
import 'sync_service.dart';

class ConnectivityService {
  final SyncService _sync;

  ConnectivityService({required SyncService syncService}) : _sync = syncService;

  void startListening() {
    Connectivity().onConnectivityChanged.listen((results) {
      final hasInternet = results.any((r) => r != ConnectivityResult.none);
      if (hasInternet) {
        _sync.syncPendingOperations();
      }
    });
  }

  Future<bool> isConnected() async {
    final result = await Connectivity().checkConnectivity();
    return result.any((r) => r != ConnectivityResult.none);
  }
}
