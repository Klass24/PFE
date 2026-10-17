import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../domain/document.dart';
import 'document_bloc.dart';

class DocumentsScreen extends StatefulWidget {
  final String? vehicleUuid;

  const DocumentsScreen({super.key, this.vehicleUuid});

  @override
  State<DocumentsScreen> createState() => _DocumentsScreenState();
}

class _DocumentsScreenState extends State<DocumentsScreen> {
  @override
  void initState() {
    super.initState();
    if (widget.vehicleUuid != null) {
      context.read<DocumentBloc>().add(
            FetchVehicleDocuments(vehicleUuid: widget.vehicleUuid!),
          );
    } else {
      context.read<DocumentBloc>().add(FetchDriverDocuments());
    }
  }

  Color _getStatusColor(Document doc) {
    if (doc.isExpired) return Colors.red;
    if (doc.expiringIn30Days) return Colors.orange;
    return Colors.green;
  }

  String _getStatusText(Document doc) {
    if (doc.isExpired) return 'Expiré';
    if (doc.expiringIn30Days) return 'Expire bientôt';
    return 'Valide';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Documents')),
      body: BlocBuilder<DocumentBloc, DocumentState>(
        builder: (context, state) {
          if (state is DocumentLoading) {
            return const Center(child: CircularProgressIndicator());
          }
          if (state is DocumentError) {
            return Center(child: Text('Erreur: ${state.message}'));
          }
          if (state is DocumentsLoaded) {
            if (state.documents.isEmpty) {
              return const Center(child: Text('Aucun document'));
            }
            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: state.documents.length,
              itemBuilder: (context, index) {
                final doc = state.documents[index];
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    leading: Icon(
                      Icons.description,
                      color: _getStatusColor(doc),
                    ),
                    title: Text(doc.type),
                    subtitle: column([
                      if (doc.issueDate != null)
                        Text('Émis: ${doc.issueDate!.toLocal().toString().split(' ')[0]}'),
                      if (doc.expiryDate != null)
                        Text('Expire: ${doc.expiryDate!.toLocal().toString().split(' ')[0]}'),
                    ]),
                    trailing: Chip(
                      label: Text(_getStatusText(doc)),
                      backgroundColor: _getStatusColor(doc).withOpacity(0.2),
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

Widget column(List<Widget> children) => Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: children,
    );
