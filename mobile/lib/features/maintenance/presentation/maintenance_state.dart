part of 'maintenance_bloc.dart';

abstract class MaintenanceState {}

class MaintenanceInitial extends MaintenanceState {}

class MaintenanceLoading extends MaintenanceState {}

class MaintenanceLoaded extends MaintenanceState {
  final List<MaintenanceSchedule> schedules;

  MaintenanceLoaded(this.schedules);
}

class MaintenanceAlertsLoaded extends MaintenanceState {
  final List<MaintenanceSchedule> alerts;

  MaintenanceAlertsLoaded(this.alerts);
}

class MaintenanceError extends MaintenanceState {
  final String message;

  MaintenanceError(this.message);
}
