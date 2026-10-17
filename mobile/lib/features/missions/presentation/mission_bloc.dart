import 'package:flutter/foundation.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../domain/mission.dart';
import '../data/mission_repository.dart';

// ─── Events ──────────────────────────────────────────────────────────────────
abstract class MissionEvent {}
class LoadMissions extends MissionEvent {}
class RefreshMissions extends MissionEvent {}

// ─── States ──────────────────────────────────────────────────────────────────
abstract class MissionState {}
class MissionInitial extends MissionState {}
class MissionLoading extends MissionState {}
class MissionLoaded extends MissionState {
  final List<Mission> missions;
  MissionLoaded(this.missions);
}
class MissionError extends MissionState {
  final String message;
  MissionError(this.message);
}

// ─── Bloc ─────────────────────────────────────────────────────────────────────
class MissionBloc extends Bloc<MissionEvent, MissionState> {
  final MissionRepository _repository;

  MissionBloc({required MissionRepository repository})
      : _repository = repository,
        super(MissionInitial()) {
    on<LoadMissions>(_onLoad);
    on<RefreshMissions>(_onRefresh);
  }

  Future<void> _onLoad(LoadMissions event, Emitter<MissionState> emit) async {
    emit(MissionLoading());
    try {
      final missions = await _repository.getMissions();
      debugPrint('[MissionBloc] Loaded ${missions.length} missions');
      emit(MissionLoaded(missions));
    } catch (e, stack) {
      debugPrint('[MissionBloc] Error loading: $e\n$stack');
      emit(MissionError(e.toString()));
    }
  }

  Future<void> _onRefresh(RefreshMissions event, Emitter<MissionState> emit) async {
    emit(MissionLoading());
    try {
      await _repository.fetchAndStore();
      final missions = await _repository.getMissions();
      debugPrint('[MissionBloc] Refreshed ${missions.length} missions');
      emit(MissionLoaded(missions));
    } catch (e, stack) {
      debugPrint('[MissionBloc] Error refreshing: $e\n$stack');
      emit(MissionError('Erreur de connexion: ${e.toString()}'));
    }
  }
}
