import 'package:flutter_bloc/flutter_bloc.dart';
import '../domain/expense.dart';
import '../data/expense_repository.dart';
import 'package:uuid/uuid.dart';

// ─── Events ──────────────────────────────────────────────────────────────────
abstract class ExpenseEvent {}

class LoadExpenses extends ExpenseEvent {
  final String missionUuid;
  LoadExpenses(this.missionUuid);
}

class CreateExpense extends ExpenseEvent {
  final String missionUuid;
  final String type;
  final double amount;
  final double? liters;
  final int? mileage;
  final String? notes;
  final String? receiptImage;
  CreateExpense({
    required this.missionUuid,
    required this.type,
    required this.amount,
    this.liters,
    this.mileage,
    this.notes,
    this.receiptImage,
  });
}

// ─── States ──────────────────────────────────────────────────────────────────
abstract class ExpenseState {}
class ExpenseInitial extends ExpenseState {}
class ExpenseLoading extends ExpenseState {}
class ExpenseLoaded extends ExpenseState {
  final List<Expense> expenses;
  ExpenseLoaded(this.expenses);
}
class ExpenseError extends ExpenseState {
  final String message;
  ExpenseError(this.message);
}
class ExpenseSaved extends ExpenseState {
  final List<Expense> expenses;
  ExpenseSaved(this.expenses);
}

// ─── BLoC ────────────────────────────────────────────────────────────────────
class ExpenseBloc extends Bloc<ExpenseEvent, ExpenseState> {
  final ExpenseRepository _repository;
  final _uuid = const Uuid();

  ExpenseBloc({required ExpenseRepository repository})
      : _repository = repository,
        super(ExpenseInitial()) {
    on<LoadExpenses>(_onLoad);
    on<CreateExpense>(_onCreate);
  }

  Future<void> _onLoad(LoadExpenses event, Emitter<ExpenseState> emit) async {
    emit(ExpenseLoading());
    try {
      final rows = await _repository.getExpenses(event.missionUuid);
      emit(ExpenseLoaded(rows.map(Expense.fromMap).toList()));
    } catch (e) {
      emit(ExpenseError(e.toString()));
    }
  }

  Future<void> _onCreate(CreateExpense event, Emitter<ExpenseState> emit) async {
    try {
      await _repository.createOffline({
        'uuid': _uuid.v4(),
        'mission_uuid': event.missionUuid,
        'type': event.type,
        'amount': event.amount,
        'liters': event.liters,
        'mileage': event.mileage,
        'notes': event.notes,
        'receipt_image': event.receiptImage,
        'created_at': DateTime.now().toIso8601String(),
      });
      final rows = await _repository.getExpenses(event.missionUuid);
      emit(ExpenseSaved(rows.map(Expense.fromMap).toList()));
    } catch (e) {
      emit(ExpenseError(e.toString()));
    }
  }
}
