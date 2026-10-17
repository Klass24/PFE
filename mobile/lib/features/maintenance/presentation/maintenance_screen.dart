import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../domain/maintenance.dart';
import 'maintenance_bloc.dart';

class MaintenanceScreen extends StatefulWidget {
  const MaintenanceScreen({super.key});

  @override
  State<MaintenanceScreen> createState() => _MaintenanceScreenState();
}

class _MaintenanceScreenState extends State<MaintenanceScreen> {
  @override
  void initState() {
    super.initState();
    context.read<MaintenanceBloc>().add(FetchVehiclesMaintenance());
  }

  Color _getStatusColor(MaintenanceSchedule maintenance) {
    if (maintenance.isOverdue) return Colors.red;
    if (maintenance.nextDueDate != null &&
        maintenance.nextDueDate!.isBefore(DateTime.now().add(const Duration(days: 30)))) {
      return Colors.orange;
    }
    return Colors.green;
  }

  String _getStatusText(MaintenanceSchedule maintenance) {
    if (maintenance.isOverdue) return 'En retard';
    if (maintenance.nextDueDate != null &&
        maintenance.nextDueDate!.isBefore(DateTime.now().add(const Duration(days: 30)))) {
      return 'Bientôt';
    }
    return 'OK';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Maintenance Prévisionnelle')),
      body: BlocBuilder<MaintenanceBloc, MaintenanceState>(
        builder: (context, state) {
          if (state is MaintenanceLoading) {
            return const Center(child: CircularProgressIndicator());
          }
          if (state is MaintenanceError) {
            return Center(child: Text('Erreur: ${state.message}'));
          }
          if (state is MaintenanceLoaded) {
            if (state.schedules.isEmpty) {
              return const Center(child: Text('Aucune maintenance planifiée'));
            }
            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: state.schedules.length,
              itemBuilder: (context, index) {
                final maintenance = state.schedules[index];
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    leading: Icon(
                      Icons.engineering,
                      color: _getStatusColor(maintenance),
                    ),
                    title: Text('${maintenance.vehiclePlate} • ${maintenance.maintenanceType}'),
                    subtitle: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const SizedBox(height: 4),
                        if (maintenance.lastServiceDate != null)
                          Text(
                            'Dernier: ${maintenance.lastServiceDate!.toLocal().toString().split(' ')[0]}',
                            style: const TextStyle(fontSize: 12),
                          ),
                        if (maintenance.nextDueDate != null)
                          Text(
                            'Prochain: ${maintenance.nextDueDate!.toLocal().toString().split(' ')[0]}',
                            style: const TextStyle(fontSize: 12),
                          ),
                      ],
                    ),
                    trailing: Chip(
                      label: Text(_getStatusText(maintenance)),
                      backgroundColor: _getStatusColor(maintenance).withOpacity(0.2),
                    ),
                  ),
                );
              },
            );
          }
          return const SizedBox();
        },
      ),
    );
  }
}
