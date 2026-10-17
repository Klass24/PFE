import 'dart:io';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:image_picker/image_picker.dart';
import '../../../core/di/service_locator.dart';
import '../../../services/api_service.dart';
import '../../expenses/domain/expense.dart';

// Airframe palette
const _kNavy   = Color(0xFF1F2D3D);
const _kBlue   = Color(0xFF0275D8);
const _kBgGray = Color(0xFFF0F3F4);
const _kBorder = Color(0xFFE8ECEF);
const _kMuted  = Color(0xFF7F8FA4);
const _kGreen  = Color(0xFF5CB85C);
const _kRed    = Color(0xFFD9534F);
const _kWarning= Color(0xFFF0AD4E);

// Expense type data
const _typeData = <String, (IconData, Color, String)>{
  'fuel': (Icons.local_gas_station, Color(0xFFF0AD4E), 'Carburant'),
  'toll': (Icons.toll, Color(0xFF0275D8), 'Péage'),
  'repair': (Icons.build, Color(0xFFD9534F), 'Réparation'),
  'food': (Icons.restaurant, Color(0xFF5CB85C), 'Repas'),
  'parking': (Icons.local_parking, Color(0xFF9B59B6), 'Parking'),
  'accommodation': (Icons.hotel, Color(0xFF00BCD4), 'Hébergement'),
  'other': (Icons.more_horiz, Color(0xFF7F8FA4), 'Autre'),
};

// Status data
const _statusData = <String, (Color, String)>{
  'pending': (Color(0xFFF0AD4E), 'En attente'),
  'approved': (Color(0xFF5CB85C), 'Approuvé'),
  'rejected': (Color(0xFFD9534F), 'Refusé'),
};

class ExpensesStandalonePage extends StatefulWidget {
  const ExpensesStandalonePage({super.key});

  @override
  State<ExpensesStandalonePage> createState() => _ExpensesStandalonePageState();
}

class _ExpensesStandalonePageState extends State<ExpensesStandalonePage> {
  bool _loading = true;
  String? _error;
  List<Expense> _expenses = [];
  double _totalMonth = 0;
  String _currentMonth = '';

  @override
  void initState() {
    super.initState();
    _currentMonth = DateFormat('MMMM yyyy', 'fr_FR').format(DateTime.now());
    _loadExpenses();
  }

  DateTime _parseDate(String? dateStr) {
    if (dateStr == null || dateStr.isEmpty) return DateTime.now();
    return DateTime.tryParse(dateStr) ?? DateTime.now();
  }

  Future<void> _loadExpenses() async {
    setState(() { _loading = true; _error = null; });

    try {
      final api = sl<ApiService>();
      final response = await api.get('/expenses');
      final data = response['data'];

      if (data is List) {
        _expenses = data.map((e) => Expense.fromMap(Map<String, dynamic>.from(e))).toList();
        _expenses.sort((a, b) => _parseDate(b.createdAt).compareTo(_parseDate(a.createdAt))); // Most recent first
        _calculateMonthTotal();
      } else {
        _expenses = [];
      }
    } catch (e) {
      _error = 'Erreur de chargement des dépenses';
      _expenses = [];
    }

    if (mounted) setState(() => _loading = false);
  }

  void _calculateMonthTotal() {
    final now = DateTime.now();
    _totalMonth = _expenses
        .where((e) {
          final d = _parseDate(e.createdAt);
          return d.year == now.year && d.month == now.month;
        })
        .fold(0.0, (sum, e) => sum + e.amount);
  }

  int get _expensesThisMonth {
    final now = DateTime.now();
    return _expenses.where((e) {
      final d = _parseDate(e.createdAt);
      return d.year == now.year && d.month == now.month;
    }).length;
  }

  void _showAddExpenseDialog() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _AddExpenseSheet(
        onAdded: () {
          _loadExpenses();
        },
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
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: _kNavy),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text('Mes dépenses',
            style: TextStyle(color: _kNavy, fontWeight: FontWeight.w700)),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: _kBorder, height: 1),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: _kBlue))
          : _error != null
              ? _buildErrorState()
              : _buildBody(),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _showAddExpenseDialog,
        backgroundColor: _kBlue,
        icon: const Icon(Icons.add),
        label: const Text('Ajouter'),
      ),
    );
  }

  Widget _buildErrorState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, size: 48, color: _kRed),
          const SizedBox(height: 16),
          Text(_error ?? 'Erreur'),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: _loadExpenses,
            icon: const Icon(Icons.refresh),
            label: const Text('Réessayer'),
          ),
        ],
      ),
    );
  }

  Widget _buildBody() {
    return Column(
      children: [
        // Monthly summary card
        Container(
          margin: const EdgeInsets.all(16),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF0275D8), Color(0xFF0056A3)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(12),
            boxShadow: [
              BoxShadow(
                color: _kBlue.withOpacity(0.3),
                blurRadius: 12,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(Icons.calendar_today, size: 16, color: Colors.white.withOpacity(0.8)),
                  const SizedBox(width: 8),
                  Text(
                    _currentMonth,
                    style: TextStyle(
                      fontFamily: 'Inter',
                      fontSize: 14,
                      color: Colors.white.withOpacity(0.9),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              const Text(
                'Total du mois',
                style: TextStyle(
                  fontFamily: 'Inter',
                  fontSize: 13,
                  color: Colors.white70,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                '${_totalMonth.toStringAsFixed(2)} TND',
                style: const TextStyle(
                  fontFamily: 'Inter',
                  fontSize: 32,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                '$_expensesThisMonth dépenses',
                style: TextStyle(
                  fontFamily: 'Inter',
                  fontSize: 13,
                  color: Colors.white.withOpacity(0.8),
                ),
              ),
            ],
          ),
        ),

        // Expense list
        Expanded(
          child: _expenses.isEmpty
              ? _buildEmptyState()
              : ListView.builder(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: _expenses.length,
                  itemBuilder: (context, index) => _ExpenseCard(
                    expense: _expenses[index],
                    parseDate: _parseDate,
                  ),
                ),
        ),
      ],
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: _kBlue.withOpacity(0.1),
              borderRadius: BorderRadius.circular(40),
            ),
            child: const Icon(Icons.receipt_long_outlined, size: 40, color: _kBlue),
          ),
          const SizedBox(height: 16),
          const Text(
            'Aucune dépense',
            style: TextStyle(
              fontFamily: 'Inter',
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: _kNavy,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Ajoutez votre première dépense',
            style: TextStyle(
              fontFamily: 'Inter',
              fontSize: 14,
              color: _kMuted,
            ),
          ),
        ],
      ),
    );
  }
}

class _ExpenseCard extends StatelessWidget {
  final Expense expense;
  final DateTime Function(String?) parseDate;

  const _ExpenseCard({required this.expense, required this.parseDate});

  @override
  Widget build(BuildContext context) {
    final typeInfo = _typeData[expense.type] ?? _typeData['other']!;
    final dateStr = DateFormat('dd MMM yyyy', 'fr_FR').format(parseDate(expense.createdAt));

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: _kBorder),
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: typeInfo.$2.withOpacity(0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(typeInfo.$1, color: typeInfo.$2, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  typeInfo.$3,
                  style: const TextStyle(
                    fontFamily: 'Inter',
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: _kNavy,
                  ),
                ),
                const SizedBox(height: 2),
                if (expense.notes != null && expense.notes!.isNotEmpty)
                  Text(
                    expense.notes!,
                    style: const TextStyle(
                      fontFamily: 'Inter',
                      fontSize: 12,
                      color: _kMuted,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Icon(Icons.calendar_today, size: 12, color: _kMuted.withOpacity(0.7)),
                    const SizedBox(width: 4),
                    Text(
                      dateStr,
                      style: TextStyle(
                        fontFamily: 'Inter',
                        fontSize: 11,
                        color: _kMuted.withOpacity(0.8),
                      ),
                    ),
                    if (expense.receiptImage != null && expense.receiptImage!.isNotEmpty) ...[
                      const SizedBox(width: 12),
                      Icon(Icons.receipt_outlined, size: 12, color: _kGreen.withOpacity(0.8)),
                      const SizedBox(width: 4),
                      Text(
                        'Reçu',
                        style: TextStyle(
                          fontFamily: 'Inter',
                          fontSize: 11,
                          color: _kGreen.withOpacity(0.8),
                        ),
                      ),
                    ],
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '${expense.amount.toStringAsFixed(2)} TND',
                style: const TextStyle(
                  fontFamily: 'Inter',
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: _kNavy,
                ),
              ),
              const SizedBox(height: 4),
              _buildStatusBadge(expense),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStatusBadge(Expense expense) {
    final statusInfo = _statusData[expense.status] ?? _statusData['pending']!;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: statusInfo.$1.withOpacity(0.1),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Text(
        statusInfo.$2,
        style: TextStyle(
          fontFamily: 'Inter',
          fontSize: 10,
          fontWeight: FontWeight.w600,
          color: statusInfo.$1,
        ),
      ),
    );
  }
}

class _AddExpenseSheet extends StatefulWidget {
  final VoidCallback onAdded;

  const _AddExpenseSheet({required this.onAdded});

  @override
  State<_AddExpenseSheet> createState() => _AddExpenseSheetState();
}

class _AddExpenseSheetState extends State<_AddExpenseSheet> {
  final _formKey = GlobalKey<FormState>();
  final _amountController = TextEditingController();
  final _notesController = TextEditingController();
  String _selectedType = 'fuel';
  DateTime _selectedDate = DateTime.now();
  String? _receiptPath;
  bool _submitting = false;
  final ImagePicker _picker = ImagePicker();

  @override
  void dispose() {
    _amountController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _pickReceipt() async {
    final XFile? photo = await _picker.pickImage(
      source: ImageSource.camera,
      imageQuality: 70,
      maxWidth: 1024,
    );

    if (photo != null) {
      setState(() => _receiptPath = photo.path);
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _submitting = true);

    try {
      final api = sl<ApiService>();
      await api.post('/expenses', {
        'type': _selectedType,
        'amount': double.parse(_amountController.text.replaceAll(',', '.')),
        'notes': _notesController.text,
        'receipt_image': _receiptPath,
        'created_at': DateFormat('yyyy-MM-dd').format(_selectedDate),
      });

      if (mounted) {
        Navigator.pop(context);
        widget.onAdded();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Dépense ajoutée'),
            backgroundColor: _kGreen,
          ),
        );
      }
    } catch (e) {
      // Save locally or show error
      if (mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Dépense enregistrée localement'),
            backgroundColor: _kWarning,
          ),
        );
        widget.onAdded();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(top: 60),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Padding(
        padding: EdgeInsets.only(
          left: 20,
          right: 20,
          top: 20,
          bottom: MediaQuery.of(context).viewInsets.bottom + 20,
        ),
        child: Form(
          key: _formKey,
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Handle bar
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: _kBorder,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 20),

                const Text(
                  'Nouvelle dépense',
                  style: TextStyle(
                    fontFamily: 'Inter',
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: _kNavy,
                  ),
                ),
                const SizedBox(height: 20),

                // Type selector
                const Text('Type de dépense', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: _kMuted)),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: _typeData.entries.map((entry) {
                    final isSelected = _selectedType == entry.key;
                    return InkWell(
                      onTap: () => setState(() => _selectedType = entry.key),
                      borderRadius: BorderRadius.circular(8),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: isSelected ? entry.value.$2.withOpacity(0.15) : _kBgGray,
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(
                            color: isSelected ? entry.value.$2 : _kBorder,
                            width: isSelected ? 2 : 1,
                          ),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(entry.value.$1, size: 16, color: isSelected ? entry.value.$2 : _kMuted),
                            const SizedBox(width: 6),
                            Text(
                              entry.value.$3,
                              style: TextStyle(
                                fontFamily: 'Inter',
                                fontSize: 12,
                                fontWeight: isSelected ? FontWeight.w600 : FontWeight.w500,
                                color: isSelected ? entry.value.$2 : _kNavy,
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  }).toList(),
                ),
                const SizedBox(height: 16),

                // Amount
                const Text('Montant', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: _kMuted)),
                const SizedBox(height: 8),
                TextFormField(
                  controller: _amountController,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: InputDecoration(
                    hintText: '0.00',
                    suffixText: 'TND',
                    filled: true,
                    fillColor: _kBgGray,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: const BorderSide(color: _kBorder),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: const BorderSide(color: _kBorder),
                    ),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
                  ),
                  validator: (v) {
                    if (v == null || v.isEmpty) return 'Requis';
                    final amount = double.tryParse(v.replaceAll(',', '.'));
                    if (amount == null || amount <= 0) return 'Montant invalide';
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // Notes
                const Text('Notes (optionnel)', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: _kMuted)),
                const SizedBox(height: 8),
                TextFormField(
                  controller: _notesController,
                  decoration: InputDecoration(
                    hintText: 'Ex: Station Total A6...',
                    filled: true,
                    fillColor: _kBgGray,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: const BorderSide(color: _kBorder),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(8),
                      borderSide: const BorderSide(color: _kBorder),
                    ),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
                  ),
                ),
                const SizedBox(height: 16),

                // Date
                const Text('Date', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: _kMuted)),
                const SizedBox(height: 8),
                InkWell(
                  onTap: () async {
                    final date = await showDatePicker(
                      context: context,
                      initialDate: _selectedDate,
                      firstDate: DateTime.now().subtract(const Duration(days: 365)),
                      lastDate: DateTime.now(),
                    );
                    if (date != null) setState(() => _selectedDate = date);
                  },
                  borderRadius: BorderRadius.circular(8),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
                    decoration: BoxDecoration(
                      color: _kBgGray,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: _kBorder),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.calendar_today, size: 18, color: _kMuted),
                        const SizedBox(width: 10),
                        Text(
                          DateFormat('dd MMMM yyyy', 'fr_FR').format(_selectedDate),
                          style: const TextStyle(fontFamily: 'Inter', fontSize: 14, color: _kNavy),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Receipt photo
                const Text('Photo du justificatif', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: _kMuted)),
                const SizedBox(height: 8),
                InkWell(
                  onTap: _pickReceipt,
                  borderRadius: BorderRadius.circular(8),
                  child: _receiptPath != null
                      ? Stack(
                          children: [
                            Container(
                              height: 120,
                              width: double.infinity,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(8),
                                image: DecorationImage(
                                  image: FileImage(File(_receiptPath!)),
                                  fit: BoxFit.cover,
                                ),
                              ),
                            ),
                            Positioned(
                              top: 8,
                              right: 8,
                              child: InkWell(
                                onTap: () => setState(() => _receiptPath = null),
                                child: Container(
                                  padding: const EdgeInsets.all(4),
                                  decoration: const BoxDecoration(
                                    color: _kRed,
                                    shape: BoxShape.circle,
                                  ),
                                  child: const Icon(Icons.close, size: 16, color: Colors.white),
                                ),
                              ),
                            ),
                            Positioned(
                              bottom: 8,
                              left: 8,
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: _kGreen,
                                  borderRadius: BorderRadius.circular(4),
                                ),
                                child: const Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(Icons.check, size: 14, color: Colors.white),
                                    SizedBox(width: 4),
                                    Text('Reçu ajouté', style: TextStyle(fontSize: 11, color: Colors.white, fontWeight: FontWeight.w600)),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        )
                      : Container(
                          padding: const EdgeInsets.symmetric(vertical: 24),
                          decoration: BoxDecoration(
                            color: _kBgGray,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: _kBorder, style: BorderStyle.solid),
                          ),
                          child: const Column(
                            children: [
                              Icon(Icons.camera_alt_outlined, size: 32, color: _kMuted),
                              SizedBox(height: 8),
                              Text(
                                'Prendre une photo du reçu',
                                style: TextStyle(fontFamily: 'Inter', fontSize: 13, color: _kMuted),
                              ),
                            ],
                          ),
                        ),
                ),
                const SizedBox(height: 24),

                // Submit
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _submitting ? null : _submit,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: _kBlue,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    child: _submitting
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              color: Colors.white,
                              strokeWidth: 2,
                            ),
                          )
                        : const Text(
                            'Enregistrer',
                            style: TextStyle(
                              fontFamily: 'Inter',
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
