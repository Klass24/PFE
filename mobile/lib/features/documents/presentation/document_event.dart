part of 'document_bloc.dart';

abstract class DocumentEvent {}

class FetchDriverDocuments extends DocumentEvent {
  final String? type;
  final String? status;

  FetchDriverDocuments({this.type, this.status});
}

class FetchVehicleDocuments extends DocumentEvent {
  final String vehicleUuid;
  final String? type;
  final String? status;

  FetchVehicleDocuments({
    required this.vehicleUuid,
    this.type,
    this.status,
  });
}
