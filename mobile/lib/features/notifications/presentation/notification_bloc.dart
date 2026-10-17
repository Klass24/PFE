import 'package:flutter_bloc/flutter_bloc.dart';
import '../domain/notification.dart';
import '../data/notification_repository.dart';

// ─── Events ──────────────────────────────────────────────────────────────────
abstract class NotificationEvent {}

class LoadNotifications extends NotificationEvent {}

class MarkNotificationRead extends NotificationEvent {
  final String uuid;
  MarkNotificationRead(this.uuid);
}

class MarkAllRead extends NotificationEvent {}

// ─── States ──────────────────────────────────────────────────────────────────
abstract class NotificationState {}

class NotificationInitial extends NotificationState {}

class NotificationLoading extends NotificationState {}

class NotificationLoaded extends NotificationState {
  final List<AppNotification> notifications;
  NotificationLoaded(this.notifications);
}

class NotificationError extends NotificationState {
  final String message;
  NotificationError(this.message);
}

// ─── BLoC ────────────────────────────────────────────────────────────────────
class NotificationBloc extends Bloc<NotificationEvent, NotificationState> {
  final NotificationRepository _repository;

  NotificationBloc({required NotificationRepository repository})
      : _repository = repository,
        super(NotificationInitial()) {
    on<LoadNotifications>(_onLoad);
    on<MarkNotificationRead>(_onMarkRead);
    on<MarkAllRead>(_onMarkAllRead);
  }

  Future<void> _onLoad(LoadNotifications event, Emitter<NotificationState> emit) async {
    emit(NotificationLoading());
    try {
      final rows = await _repository.getNotifications();
      emit(NotificationLoaded(rows.map(AppNotification.fromMap).toList()));
    } catch (e) {
      emit(NotificationError(e.toString()));
    }
  }

  Future<void> _onMarkRead(MarkNotificationRead event, Emitter<NotificationState> emit) async {
    try {
      await _repository.markAsRead(event.uuid);
      add(LoadNotifications());
    } catch (e) {
      emit(NotificationError(e.toString()));
    }
  }

  Future<void> _onMarkAllRead(MarkAllRead event, Emitter<NotificationState> emit) async {
    try {
      await _repository.markAllAsRead();
      add(LoadNotifications());
    } catch (e) {
      emit(NotificationError(e.toString()));
    }
  }
}
