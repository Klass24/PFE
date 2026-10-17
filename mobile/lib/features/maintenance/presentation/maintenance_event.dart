part of 'maintenance_bloc.dart';

abstract class MaintenanceEvent {}

class FetchVehiclesMaintenance extends MaintenanceEvent {
  final String? status;
  final String? vehicleUuid;

  FetchVehiclesMaintenance({this.status, this.vehicleUuid});
}

class FetchMaintenanceAlerts extends MaintenanceEvent {}
