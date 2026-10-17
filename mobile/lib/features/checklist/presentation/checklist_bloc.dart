import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:uuid/uuid.dart';
import '../domain/checklist.dart';
import '../data/checklist_repository.dart';

// ─── Events ──────────────────────────────────────────────────────────────────
abstract class ChecklistEvent {}

class LoadChecklist extends ChecklistEvent {
  final String missionUuid;
  LoadChecklist(this.missionUuid);
}

class InitChecklist extends ChecklistEvent {
  final String missionUuid;
  InitChecklist(this.missionUuid);
}

class ToggleItem extends ChecklistEvent {
  final String itemKey;
  final bool isOk;
  ToggleItem(this.itemKey, this.isOk);
}

class SetItemPhoto extends ChecklistEvent {
  final String itemKey;
  final String photoPath;
  SetItemPhoto(this.itemKey, this.photoPath);
}

class SetItemNote extends ChecklistEvent {
  final String itemKey;
  final String note;
  SetItemNote(this.itemKey, this.note);
}

class SubmitChecklist extends ChecklistEvent {}

// ─── States ──────────────────────────────────────────────────────────────────
abstract class ChecklistState {}

class ChecklistInitial extends ChecklistState {}

class ChecklistLoading extends ChecklistState {}

class ChecklistReady extends ChecklistState {
  final Checklist checklist;
  ChecklistReady(this.checklist);
}

class ChecklistSubmitted extends ChecklistState {
  final Checklist checklist;
  ChecklistSubmitted(this.checklist);
}

class ChecklistError extends ChecklistState {
  final String message;
  ChecklistError(this.message);
}

// ─── BLoC ────────────────────────────────────────────────────────────────────
class ChecklistBloc extends Bloc<ChecklistEvent, ChecklistState> {
  final ChecklistRepository _repository;
  final _uuid = const Uuid();
  Checklist? _currentChecklist;

  ChecklistBloc({required ChecklistRepository repository})
      : _repository = repository,
        super(ChecklistInitial()) {
    on<LoadChecklist>(_onLoad);
    on<InitChecklist>(_onInit);
    on<ToggleItem>(_onToggle);
    on<SetItemPhoto>(_onSetPhoto);
    on<SetItemNote>(_onSetNote);
    on<SubmitChecklist>(_onSubmit);
  }

  Future<void> _onLoad(LoadChecklist event, Emitter<ChecklistState> emit) async {
    emit(ChecklistLoading());
    try {
      final checklist = await _repository.getForMission(event.missionUuid);
      if (checklist != null) {
        _currentChecklist = checklist;
        emit(ChecklistReady(checklist));
      } else {
        add(InitChecklist(event.missionUuid));
      }
    } catch (e) {
      emit(ChecklistError(e.toString()));
    }
  }

  Future<void> _onInit(InitChecklist event, Emitter<ChecklistState> emit) async {
    _currentChecklist = Checklist(
      uuid: _uuid.v4(),
      missionUuid: event.missionUuid,
      status: 'draft',
      items: List.from(defaultChecklistItems),
    );
    emit(ChecklistReady(_currentChecklist!));
  }

  void _onToggle(ToggleItem event, Emitter<ChecklistState> emit) {
    if (_currentChecklist == null) return;
    final items = _currentChecklist!.items.map((item) {
      if (item.itemKey == event.itemKey) {
        return item.copyWith(isOk: event.isOk);
      }
      return item;
    }).toList();
    _currentChecklist = Checklist(
      uuid: _currentChecklist!.uuid,
      missionUuid: _currentChecklist!.missionUuid,
      status: _currentChecklist!.status,
      items: items,
    );
    emit(ChecklistReady(_currentChecklist!));
  }

  void _onSetPhoto(SetItemPhoto event, Emitter<ChecklistState> emit) {
    if (_currentChecklist == null) return;
    final items = _currentChecklist!.items.map((item) {
      if (item.itemKey == event.itemKey) {
        return item.copyWith(photoPath: event.photoPath);
      }
      return item;
    }).toList();
    _currentChecklist = Checklist(
      uuid: _currentChecklist!.uuid,
      missionUuid: _currentChecklist!.missionUuid,
      status: _currentChecklist!.status,
      items: items,
    );
    emit(ChecklistReady(_currentChecklist!));
  }

  void _onSetNote(SetItemNote event, Emitter<ChecklistState> emit) {
    if (_currentChecklist == null) return;
    final items = _currentChecklist!.items.map((item) {
      if (item.itemKey == event.itemKey) {
        return item.copyWith(note: event.note);
      }
      return item;
    }).toList();
    _currentChecklist = Checklist(
      uuid: _currentChecklist!.uuid,
      missionUuid: _currentChecklist!.missionUuid,
      status: _currentChecklist!.status,
      items: items,
    );
    emit(ChecklistReady(_currentChecklist!));
  }

  Future<void> _onSubmit(SubmitChecklist event, Emitter<ChecklistState> emit) async {
    if (_currentChecklist == null) return;
    if (!_currentChecklist!.canSubmit) {
      emit(ChecklistError('Photos obligatoires manquantes.'));
      emit(ChecklistReady(_currentChecklist!));
      return;
    }
    try {
      final submitted = Checklist(
        uuid: _currentChecklist!.uuid,
        missionUuid: _currentChecklist!.missionUuid,
        status: 'submitted',
        items: _currentChecklist!.items,
      );
      await _repository.submitForSync(submitted);
      _currentChecklist = submitted;
      emit(ChecklistSubmitted(submitted));
    } catch (e) {
      emit(ChecklistError(e.toString()));
    }
  }
}
