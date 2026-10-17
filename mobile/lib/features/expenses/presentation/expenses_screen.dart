import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:image_picker/image_picker.dart';
import '../data/expense_repository.dart';
import '../domain/expense.dart';
import 'expense_bloc.dart';

// Airframe palette
const _kNavy   = Color(0xFF1F2D3D);
const _kBlue   = Color(0xFF0275D8);
const _kBgGray = Color(0xFFF0F3F4);
const _kBorder = Color(0xFFE8ECEF);
const _kMuted  = Color(0xFF7F8FA4);

const _typeLabels = {
  'fuel':     'Carburant',
  'toll':     'Péage',
  'repair':   'Réparation',
  'parking':  'Parking',
  'weighing': 'Pesage',
  'other':    'Autre',
};

const _typeColors = {
  'fuel':     Color(0xFF0275D8),
  'toll':     Color(0xFF5CB85C),
  'repair':   Color(0xFFF0AD4E),
  'parking':  Color(0xFF9B59B6),
  'weighing': Color(0xFF1ABC9C),
  'other':    Color(0xFF95A5A6),
};

class ExpensesScreen extends StatelessWidget {
  final String missionUuid;
  const ExpensesScreen({super.key, required this.missionUuid});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => ExpenseBloc(repository: context.read<ExpenseRepository>())
        ..add(LoadExpenses(missionUuid)),
      child: _ExpensesView(missionUuid: missionUuid),
    );
  }
}

class _ExpensesView extends StatelessWidget {
  final String missionUuid;
  const _ExpensesView({required this.missionUuid});

  void _showAddDialog(BuildContext context) {
    final formKey = GlobalKey<FormState>();
    String selectedType = 'fuel';
    final amountCtrl   = TextEditingController();
    final litersCtrl   = TextEditingController();
    final mileageCtrl  = TextEditingController();
    final notesCtrl    = TextEditingController();
    String? receiptPath;

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setState) => AlertDialog(
          title: const Text('Nouvelle dépense'),
          content: Form(
            key: formKey,
            child: SingleChildScrollView(
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                DropdownButtonFormField<String>(
                  value: selectedType,
                  decoration: const InputDecoration(labelText: 'Type'),
                  items: _typeLabels.entries
                      .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value)))
                      .toList(),
                  onChanged: (v) => setState(() => selectedType = v!),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: amountCtrl,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: const InputDecoration(labelText: 'Montant (XAF) *'),
                  validator: (v) => (v == null || v.isEmpty) ? 'Obligatoire' : null,
                ),
                const SizedBox(height: 12),
                if (selectedType == 'fuel') ...[
                  TextFormField(
                    controller: litersCtrl,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: const InputDecoration(labelText: 'Litres'),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: mileageCtrl,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Kilométrage'),
                  ),
                  const SizedBox(height: 12),
                ],
                TextFormField(
                  controller: notesCtrl,
                  decoration: const InputDecoration(labelText: 'Notes'),
                  maxLines: 2,
                ),
                const SizedBox(height: 12),
                // Receipt photo
                OutlinedButton.icon(
                  onPressed: () async {
                    final picker = ImagePicker();
                    final picked = await picker.pickImage(
                      source: ImageSource.camera,
                      imageQuality: 70,
                      maxWidth: 1024,
                    );
                    if (picked != null) setState(() => receiptPath = picked.path);
                  },
                  icon: Icon(receiptPath != null ? Icons.check_circle : Icons.camera_alt_outlined,
                      color: receiptPath != null ? const Color(0xFF5CB85C) : _kMuted),
                  label: Text(receiptPath != null ? 'Reçu capturé' : 'Photo du reçu'),
                ),
                if (receiptPath != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(6),
                      child: Image.file(File(receiptPath!), height: 80, width: double.infinity, fit: BoxFit.cover),
                    ),
                  ),
              ]),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Annuler'),
            ),
            ElevatedButton(
              onPressed: () {
                if (!formKey.currentState!.validate()) return;
                context.read<ExpenseBloc>().add(CreateExpense(
                  missionUuid: missionUuid,
                  type: selectedType,
                  amount: double.parse(amountCtrl.text),
                  liters: litersCtrl.text.isNotEmpty
                      ? double.tryParse(litersCtrl.text)
                      : null,
                  mileage: mileageCtrl.text.isNotEmpty
                      ? int.tryParse(mileageCtrl.text)
                      : null,
                  notes: notesCtrl.text.isNotEmpty ? notesCtrl.text : null,
                  receiptImage: receiptPath,
                ));
                Navigator.pop(ctx);
              },
              child: const Text('Enregistrer'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _kBgGray,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text('Dépenses', style: TextStyle(color: _kNavy, fontWeight: FontWeight.w700)),
        iconTheme: const IconThemeData(color: _kNavy),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: _kBorder, height: 1),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: _kMuted),
            onPressed: () => context.read<ExpenseBloc>().add(LoadExpenses(missionUuid)),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _showAddDialog(context),
        backgroundColor: _kBlue,
        icon: const Icon(Icons.add),
        label: const Text('Dépense'),
      ),
      body: BlocConsumer<ExpenseBloc, ExpenseState>(
        listener: (ctx, state) {
          if (state is ExpenseSaved || state is IncidentReported) {
            ScaffoldMessenger.of(ctx).showSnackBar(
              const SnackBar(
                content: Text('Dépense enregistrée (sync en attente)'),
                backgroundColor: Color(0xFF5CB85C),
              ),
            );
          }
          if (state is ExpenseError) {
            ScaffoldMessenger.of(ctx).showSnackBar(
              SnackBar(content: Text(state.message), backgroundColor: const Color(0xFFD9534F)),
            );
          }
        },
        builder: (ctx, state) {
          if (state is ExpenseLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          final expenses = switch (state) {
            ExpenseLoaded()  => state.expenses,
            ExpenseSaved()   => state.expenses,
            _ => <Expense>[],
          };

          if (expenses.isEmpty) {
            return Center(
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                Icon(Icons.receipt_long_outlined, size: 56, color: _kMuted.withAlpha(128)),
                const SizedBox(height: 12),
                const Text('Aucune dépense', style: TextStyle(color: _kMuted, fontSize: 15)),
              ]),
            );
          }

          // ── Total card ─────────────────────────────────────────────────────
          final total = expenses.fold<double>(0, (sum, e) => sum + e.amount);

          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Summary card
              Container(
                color: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('${expenses.length} dépense(s)',
                        style: const TextStyle(color: _kMuted, fontSize: 13)),
                    Text('Total : ${total.toStringAsFixed(2)} XAF',
                        style: const TextStyle(
                            color: _kNavy, fontWeight: FontWeight.w700, fontSize: 15)),
                  ],
                ),
              ),
              Container(height: 1, color: _kBorder),

              Expanded(
                child: ListView.separated(
                  padding: const EdgeInsets.all(12),
                  itemCount: expenses.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (_, i) => _ExpenseCard(expense: expenses[i]),
                ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _ExpenseCard extends StatelessWidget {
  final Expense expense;
  const _ExpenseCard({required this.expense});

  @override
  Widget build(BuildContext context) {
    final color = _typeColors[expense.type] ?? _kMuted;
    final label = _typeLabels[expense.type] ?? expense.type;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          children: [
            Row(
              children: [
                // Icon circle
                Container(
                  width: 42, height: 42,
                  decoration: BoxDecoration(
                    color: color.withAlpha(26),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(_typeIcon(expense.type), color: color, size: 20),
                ),
                const SizedBox(width: 12),

                // Info
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(label,
                        style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14, color: _kNavy)),
                    if (expense.notes != null && expense.notes!.isNotEmpty)
                      Text(expense.notes!,
                          style: const TextStyle(fontSize: 12, color: _kMuted),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis),
                    const SizedBox(height: 4),
                    Row(children: [
                      _SyncChip(synced: expense.synced),
                      if (expense.liters != null) ...[
                        const SizedBox(width: 8),
                        Text('${expense.liters!.toStringAsFixed(1)} L',
                            style: const TextStyle(fontSize: 11, color: _kMuted)),
                      ],
                      if (expense.receiptImage != null) ...[
                        const SizedBox(width: 8),
                        const Icon(Icons.photo_camera, size: 12, color: _kMuted),
                      ],
                    ]),
                  ]),
                ),

                // Amount
                Text('${expense.amount.toStringAsFixed(2)} XAF',
                    style: TextStyle(
                        fontWeight: FontWeight.w700, fontSize: 15, color: color)),
              ],
            ),
            if (expense.receiptImage != null)
              Padding(
                padding: const EdgeInsets.only(top: 10),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(6),
                  child: Image.file(File(expense.receiptImage!), height: 80, width: double.infinity, fit: BoxFit.cover),
                ),
              ),
          ],
        ),
      ),
    );
  }

  IconData _typeIcon(String type) {
    return switch (type) {
      'fuel'     => Icons.local_gas_station_rounded,
      'toll'     => Icons.compare_arrows_rounded,
      'repair'   => Icons.build_rounded,
      'parking'  => Icons.local_parking_rounded,
      'weighing' => Icons.scale_rounded,
      _          => Icons.receipt_rounded,
    };
  }
}

class _SyncChip extends StatelessWidget {
  final bool synced;
  const _SyncChip({required this.synced});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: synced
            ? const Color(0xFF5CB85C).withAlpha(26)
            : const Color(0xFFF0AD4E).withAlpha(26),
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        synced ? 'Sync.' : 'En attente',
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: synced ? const Color(0xFF5CB85C) : const Color(0xFFF0AD4E),
        ),
      ),
    );
  }
}

// ignore: unused_element
class IncidentReported {}
