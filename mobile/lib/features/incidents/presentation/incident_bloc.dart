import 'package:flutter_bloc/flutter_bloc.dart';
import '../domain/incident.dart';
import '../data/incident_repository.dart';
import 'package:uuid/uuid.dart';

// ─── Events ──────────────────────────────────────────────────────────────────
abstract class IncidentEvent {}

class LoadIncidents extends IncidentEvent {
  final String missionUuid;
  LoadIncidents(this.missionUuid);
}

class ReportIncident extends IncidentEvent {
  final String missionUuid;
  final String type;
  final String description;
  final double? latitude;
  final double? longitude;
  ReportIncident({
    required this.missionUuid,
    required this.type,
    required this.description,
    this.latitude,
    this.longitude,
  });
}

// ─── States ──────────────────────────────────────────────────────────────────
abstract class IncidentState {}
class IncidentInitial extends IncidentState {}
class IncidentLoading extends IncidentState {}
class IncidentLoaded extends IncidentState {
  final List<Incident> incidents;
  IncidentLoaded(this.incidents);
}
class IncidentError extends IncidentState {
  final String message;
  IncidentError(this.message);
}
class IncidentReported extends IncidentState {
  final List<Incident> incidents;
  IncidentReported(this.incidents);
}

// ─── BLoC ────────────────────────────────────────────────────────────────────
class IncidentBloc extends Bloc<IncidentEvent, IncidentState> {
  final IncidentRepository _repository;
  final _uuid = const Uuid();

  IncidentBloc({required IncidentRepository repository})
      : _repository = repository,
        super(IncidentInitial()) {
    on<LoadIncidents>(_onLoad);
    on<ReportIncident>(_onReport);
  }

  Future<void> _onLoad(LoadIncidents event, Emitter<IncidentState> emit) async {
    emit(IncidentLoading());
    try {
      final rows = await _repository.getIncidents(event.missionUuid);
      emit(IncidentLoaded(rows.map(Incident.fromMap).toList()));
    } catch (e) {
      emit(IncidentError(e.toString()));
    }
  }

  Future<void> _onReport(ReportIncident event, Emitter<IncidentState> emit) async {
    try {
      await _repository.reportOffline({
        'uuid': _uuid.v4(),
        'mission_uuid': event.missionUuid,
        'type': event.type,
        'description': event.description,
        'latitude': event.latitude,
        'longitude': event.longitude,
        'created_at': DateTime.now().toIso8601String(),
      });
      final rows = await _repository.getIncidents(event.missionUuid);
      emit(IncidentReported(rows.map(Incident.fromMap).toList()));
    } catch (e) {
      emit(IncidentError(e.toString()));
    }
  }
}
