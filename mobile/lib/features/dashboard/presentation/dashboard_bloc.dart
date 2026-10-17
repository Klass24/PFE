import 'package:flutter/foundation.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../data/dashboard_repository.dart';

// ─── Events ──────────────────────────────────────────────────────────────────
abstract class DashboardEvent {}

class LoadDashboard extends DashboardEvent {}

class RefreshDashboard extends DashboardEvent {}

// ─── State ───────────────────────────────────────────────────────────────────
class DashboardState {
  final bool isLoading;
  final String? error;
  final Map<String, dynamic>? profile;
  final List<Map<String, dynamic>> missions;
  final List<Map<String, dynamic>> notifications;
  final int unreadNotifications;
  final int todayMissions;
  final int completedMissions;
  final double distanceKm;
  // Données graphiques
  final List<Map<String, dynamic>> recentExpenses;
  final List<Map<String, dynamic>> recentIncidents;
  final double totalExpensesMonth;
  final Map<String, double> expensesByType;
  final Map<String, int> incidentsBySeverity;
  final Map<String, int> incidentsByStatus;

  const DashboardState({
    this.isLoading = false,
    this.error,
    this.profile,
    this.missions = const [],
    this.notifications = const [],
    this.unreadNotifications = 0,
    this.todayMissions = 0,
    this.completedMissions = 0,
    this.distanceKm = 0,
    this.recentExpenses = const [],
    this.recentIncidents = const [],
    this.totalExpensesMonth = 0,
    this.expensesByType = const {},
    this.incidentsBySeverity = const {},
    this.incidentsByStatus = const {},
  });

  DashboardState copyWith({
    bool? isLoading,
    String? error,
    Map<String, dynamic>? profile,
    List<Map<String, dynamic>>? missions,
    List<Map<String, dynamic>>? notifications,
    int? unreadNotifications,
    int? todayMissions,
    int? completedMissions,
    double? distanceKm,
    List<Map<String, dynamic>>? recentExpenses,
    List<Map<String, dynamic>>? recentIncidents,
    double? totalExpensesMonth,
    Map<String, double>? expensesByType,
    Map<String, int>? incidentsBySeverity,
    Map<String, int>? incidentsByStatus,
  }) {
    return DashboardState(
      isLoading: isLoading ?? this.isLoading,
      error: error,
      profile: profile ?? this.profile,
      missions: missions ?? this.missions,
      notifications: notifications ?? this.notifications,
      unreadNotifications: unreadNotifications ?? this.unreadNotifications,
      todayMissions: todayMissions ?? this.todayMissions,
      completedMissions: completedMissions ?? this.completedMissions,
      distanceKm: distanceKm ?? this.distanceKm,
      recentExpenses: recentExpenses ?? this.recentExpenses,
      recentIncidents: recentIncidents ?? this.recentIncidents,
      totalExpensesMonth: totalExpensesMonth ?? this.totalExpensesMonth,
      expensesByType: expensesByType ?? this.expensesByType,
      incidentsBySeverity: incidentsBySeverity ?? this.incidentsBySeverity,
      incidentsByStatus: incidentsByStatus ?? this.incidentsByStatus,
    );
  }
}

// ─── BLoC ────────────────────────────────────────────────────────────────────
class DashboardBloc extends Bloc<DashboardEvent, DashboardState> {
  final DashboardRepository _repository;

  DashboardBloc({required DashboardRepository repository})
      : _repository = repository,
        super(const DashboardState(isLoading: true)) {
    on<LoadDashboard>(_onLoad);
    on<RefreshDashboard>(_onRefresh);
  }

  Future<void> _onLoad(LoadDashboard event, Emitter<DashboardState> emit) async {
    emit(state.copyWith(isLoading: true, error: null));
    await _fetchData(emit);
  }

  Future<void> _onRefresh(RefreshDashboard event, Emitter<DashboardState> emit) async {
    await _fetchData(emit);
  }

  Future<void> _fetchData(Emitter<DashboardState> emit) async {
    try {
      // Fetch all data in parallel
      final results = await Future.wait([
        _repository.getProfile(),
        _repository.getMissions(),
        _repository.getNotifications(),
        _repository.getExpenses(),
        _repository.getIncidents(),
      ]);

      final profile = results[0] as Map<String, dynamic>?;
      final missions = results[1] as List<Map<String, dynamic>>;
      final notifications = results[2] as List<Map<String, dynamic>>;
      final expenses = results[3] as List<Map<String, dynamic>>;
      final incidents = results[4] as List<Map<String, dynamic>>;

      debugPrint('[DashboardBloc] Profile: $profile');
      debugPrint('[DashboardBloc] Missions: ${missions.length}');
      debugPrint('[DashboardBloc] Notifications: ${notifications.length}');

      // Calculate stats
      final today = DateTime.now();
      final todayStr = '${today.year}-${today.month.toString().padLeft(2, '0')}-${today.day.toString().padLeft(2, '0')}';
      
      final todayMissions = missions.where((m) {
        final startDate = m['start_date'] as String?;
        return startDate?.startsWith(todayStr) == true;
      }).length;

      final completedMissions = missions.where((m) {
        final status = m['status'] as String?;
        return status == 'completed';
      }).length;

      final activeMissions = missions.where((m) {
        final status = m['status'] as String?;
        return status == 'in_progress' || status == 'planned';
      }).toList();

      final unreadNotifications = notifications.where((n) {
        return n['is_read'] != true && n['is_read'] != 1;
      }).length;

      // Distance calculation (mock - would come from backend in real app)
      final distanceKm = completedMissions * 45.0; // Average 45km per mission

      // Calculs graphiques dépenses
      final now = DateTime.now();
      final monthExpenses = expenses.where((e) {
        final d = DateTime.tryParse(e['created_at'] as String? ?? '');
        return d != null && d.year == now.year && d.month == now.month;
      }).toList();

      double totalExpensesMonth = 0;
      final expensesByType = <String, double>{};
      for (final e in monthExpenses) {
        final amount = (e['amount'] as num?)?.toDouble() ?? 0;
        final type = e['type'] as String? ?? 'other';
        totalExpensesMonth += amount;
        expensesByType[type] = (expensesByType[type] ?? 0) + amount;
      }

      // Calculs graphiques incidents
      final incidentsBySeverity = <String, int>{'low': 0, 'medium': 0, 'high': 0};
      final incidentsByStatus = <String, int>{'open': 0, 'in_progress': 0, 'closed': 0};
      for (final i in incidents) {
        final sev = i['severity'] as String? ?? 'medium';
        final status = i['status'] as String? ?? 'open';
        incidentsBySeverity[sev] = (incidentsBySeverity[sev] ?? 0) + 1;
        incidentsByStatus[status] = (incidentsByStatus[status] ?? 0) + 1;
      }

      emit(state.copyWith(
        isLoading: false,
        profile: profile,
        missions: activeMissions,
        notifications: notifications.take(3).toList(),
        unreadNotifications: unreadNotifications,
        todayMissions: todayMissions > 0 ? todayMissions : missions.length,
        completedMissions: completedMissions,
        distanceKm: distanceKm,
        recentExpenses: expenses.take(5).toList(),
        recentIncidents: incidents.take(5).toList(),
        totalExpensesMonth: totalExpensesMonth,
        expensesByType: expensesByType,
        incidentsBySeverity: incidentsBySeverity,
        incidentsByStatus: incidentsByStatus,
      ));
    } catch (e) {
      debugPrint('[DashboardBloc] Error: $e');
      emit(state.copyWith(isLoading: false, error: e.toString()));
    }
  }
}
