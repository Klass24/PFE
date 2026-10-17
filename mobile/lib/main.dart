import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';
import 'core/di/service_locator.dart';
import 'services/connectivity_service.dart';
import 'features/auth/data/auth_repository.dart';
import 'features/missions/data/mission_repository.dart';
import 'features/expenses/data/expense_repository.dart';
import 'features/incidents/data/incident_repository.dart';
import 'app.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialiser sqflite sur les plateformes de bureau (pas sur web)
  if (!kIsWeb) {
    if (defaultTargetPlatform == TargetPlatform.windows ||
        defaultTargetPlatform == TargetPlatform.linux ||
        defaultTargetPlatform == TargetPlatform.macOS) {
      sqfliteFfiInit();
      databaseFactory = databaseFactoryFfi;
    }
  }
  
  setupLocator();
  sl<ConnectivityService>().startListening();

  runApp(
    MultiRepositoryProvider(
      providers: [
        RepositoryProvider.value(value: sl<AuthRepository>()),
        RepositoryProvider.value(value: sl<MissionRepository>()),
        RepositoryProvider.value(value: sl<ExpenseRepository>()),
        RepositoryProvider.value(value: sl<IncidentRepository>()),
      ],
      child: const FleetControlApp(),
    ),
  );
}
