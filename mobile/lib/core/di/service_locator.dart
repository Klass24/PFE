import 'package:get_it/get_it.dart';
import '../../local_db/database_helper.dart';
import '../../services/api_service.dart';
import '../../services/sync_service.dart';
import '../../services/connectivity_service.dart';
import '../../features/auth/data/auth_repository.dart';
import '../../features/missions/data/mission_repository.dart';
import '../../features/expenses/data/expense_repository.dart';
import '../../features/incidents/data/incident_repository.dart';
import '../../features/checklist/data/checklist_repository.dart';
import '../../features/notifications/data/notification_repository.dart';
import '../../features/dashboard/data/dashboard_repository.dart';

final sl = GetIt.instance;

void setupLocator() {
  // Core
  sl.registerLazySingleton<DatabaseHelper>(() => DatabaseHelper());
  sl.registerLazySingleton<ApiService>(() => ApiService());
  sl.registerLazySingleton<SyncService>(
    () => SyncService(apiService: sl<ApiService>(), db: sl<DatabaseHelper>()),
  );
  sl.registerLazySingleton<ConnectivityService>(
    () => ConnectivityService(syncService: sl<SyncService>()),
  );

  // Repositories
  sl.registerLazySingleton<AuthRepository>(() => AuthRepository(api: sl<ApiService>()));
  sl.registerLazySingleton<MissionRepository>(
    () => MissionRepository(api: sl<ApiService>(), db: sl<DatabaseHelper>(), sync: sl<SyncService>()),
  );
  sl.registerLazySingleton<ExpenseRepository>(
    () => ExpenseRepository(api: sl<ApiService>(), db: sl<DatabaseHelper>(), sync: sl<SyncService>()),
  );
  sl.registerLazySingleton<IncidentRepository>(
    () => IncidentRepository(api: sl<ApiService>(), db: sl<DatabaseHelper>(), sync: sl<SyncService>()),
  );
  sl.registerLazySingleton<ChecklistRepository>(
    () => ChecklistRepository(db: sl<DatabaseHelper>(), sync: sl<SyncService>()),
  );
  sl.registerLazySingleton<NotificationRepository>(
    () => NotificationRepository(api: sl<ApiService>()),
  );
  sl.registerLazySingleton<DashboardRepository>(
    () => DashboardRepository(api: sl<ApiService>()),
  );
}
