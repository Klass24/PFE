import 'package:flutter_bloc/flutter_bloc.dart';
import '../domain/maintenance.dart';
import '../data/maintenance_repository.dart';

part 'maintenance_event.dart';
part 'maintenance_state.dart';

class MaintenanceBloc extends Bloc<MaintenanceEvent, MaintenanceState> {
  final MaintenanceRepository repository;

  MaintenanceBloc({required this.repository}) : super(MaintenanceInitial()) {
    on<FetchVehiclesMaintenance>(_onFetchVehiclesMaintenance);
    on<FetchMaintenanceAlerts>(_onFetchMaintenanceAlerts);
  }

  Future<void> _onFetchVehiclesMaintenance(
    FetchVehiclesMaintenance event,
    Emitter<MaintenanceState> emit,
  ) async {
    emit(MaintenanceLoading());
    try {
      final schedules = await repository.getVehiclesMaintenance(
        status: event.status,
        vehicleUuid: event.vehicleUuid,
      );
      emit(MaintenanceLoaded(schedules));
    } catch (e) {
      emit(MaintenanceError(e.toString()));
    }
  }

  Future<void> _onFetchMaintenanceAlerts(
    FetchMaintenanceAlerts event,
    Emitter<MaintenanceState> emit,
  ) async {
    emit(MaintenanceLoading());
    try {
      final alerts = await repository.getMaintenanceAlerts();
      emit(MaintenanceAlertsLoaded(alerts));
    } catch (e) {
      emit(MaintenanceError(e.toString()));
    }
  }
}
