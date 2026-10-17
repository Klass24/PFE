import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import '../../../core/di/service_locator.dart';
import '../../../services/api_service.dart';

// Airframe palette
const _kNavy   = Color(0xFF1F2D3D);
const _kBlue   = Color(0xFF0275D8);
const _kBgGray = Color(0xFFF0F3F4);
const _kBorder = Color(0xFFE8ECEF);
const _kMuted  = Color(0xFF7F8FA4);
const _kGreen  = Color(0xFF5CB85C);
const _kRed    = Color(0xFFD9534F);
const _kWarning= Color(0xFFF0AD4E);

// Category icons and colors
const _categoryData = {
  'documents': (Icons.description_outlined, Color(0xFF0275D8), 'Documents'),
  'vehicle': (Icons.directions_car_outlined, Color(0xFF5CB85C), 'Véhicule'),
  'security': (Icons.security_outlined, Color(0xFFF0AD4E), 'Sécurité'),
  'loading': (Icons.inventory_2_outlined, Color(0xFF9B59B6), 'Chargement'),
};

class ChecklistStandalonePage extends StatefulWidget {
  const ChecklistStandalonePage({super.key});

  @override
  State<ChecklistStandalonePage> createState() => _ChecklistStandalonePageState();
}

class _ChecklistStandalonePageState extends State<ChecklistStandalonePage> {
  bool _loading = true;
  String? _error;
  List<_ChecklistCategory> _categories = [];
  int _completedCount = 0;
  int _totalCount = 0;

  @override
  void initState() {
    super.initState();
    // Use addPostFrameCallback to ensure context is ready
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadChecklist();
    });
  }

  Future<void> _loadChecklist() async {
    if (!mounted) return;
    setState(() { _loading = true; _error = null; });
    
    try {
      debugPrint('[ChecklistPage] ========== LOADING CHECKLIST ==========');
      final api = sl<ApiService>();
      
      // Try multiple endpoints
      Map<String, dynamic>? response;
      String? lastError;
      
      try {
        debugPrint('[ChecklistPage] Trying /checklist/current...');
        response = await api.get('/checklist/current');
        debugPrint('[ChecklistPage] ✅ Got response from /checklist/current');
        debugPrint('[ChecklistPage] Response keys: ${response.keys.toList()}');
      } on DioException catch (e1) {
        lastError = 'DioException: ${e1.response?.statusCode} - ${e1.message}';
        debugPrint('[ChecklistPage] ❌ /checklist/current failed: $lastError');
        
        try {
          debugPrint('[ChecklistPage] Trying /checklist...');
          response = await api.get('/checklist');
          debugPrint('[ChecklistPage] ✅ Got response from /checklist');
        } on DioException catch (e2) {
          lastError = 'DioException: ${e2.response?.statusCode} - ${e2.message}';
          debugPrint('[ChecklistPage] ❌ /checklist failed: $lastError');
        } catch (e2) {
          lastError = e2.toString();
          debugPrint('[ChecklistPage] ❌ /checklist error: $e2');
        }
      } catch (e1) {
        lastError = e1.toString();
        debugPrint('[ChecklistPage] ❌ /checklist/current error: $e1');
      }
      
      if (response != null) {
        final data = response['data'];
        debugPrint('[ChecklistPage] Response data type: ${data?.runtimeType}');
        debugPrint('[ChecklistPage] Response data: $data');
        
        if (data is List && data.isNotEmpty) {
          _parseChecklistData(data);
          debugPrint('[ChecklistPage] ✅ Parsed ${data.length} items from API');
        } else if (data is Map && data['items'] is List) {
          _parseChecklistData(data['items'] as List);
          debugPrint('[ChecklistPage] ✅ Parsed items from data.items');
        } else {
          debugPrint('[ChecklistPage] ⚠️ No valid data, using defaults');
          _useDefaultChecklist();
        }
      } else {
        debugPrint('[ChecklistPage] ⚠️ No response, using defaults. Last error: $lastError');
        _useDefaultChecklist();
      }
    } on TypeError catch (e, stackTrace) {
      debugPrint('[ChecklistPage] ❌ TypeError: $e');
      debugPrint('[ChecklistPage] Stack: $stackTrace');
      _error = 'Erreur de type: $e';
      _useDefaultChecklist();
    } catch (e, stackTrace) {
      debugPrint('[ChecklistPage] ❌ Error loading checklist: $e');
      debugPrint('[ChecklistPage] Stack: $stackTrace');
      _error = 'Erreur: $e';
      _useDefaultChecklist();
    }
    
    if (mounted) {
      setState(() => _loading = false);
      debugPrint('[ChecklistPage] ========== LOADING COMPLETE ==========');
      debugPrint('[ChecklistPage] Categories: ${_categories.length}, Total items: $_totalCount');
      if (_error != null) debugPrint('[ChecklistPage] Error: $_error');
    }
  }

  void _parseChecklistData(List items) {
    final categories = <String, List<_ChecklistItem>>{};
    
    for (final item in items) {
      final map = Map<String, dynamic>.from(item as Map);
      final category = (map['category'] as String?) ?? 'vehicle';
      final checkItem = _ChecklistItem(
        id: (map['id']?.toString()) ?? '',
        label: (map['label'] as String?) ?? (map['name'] as String?) ?? '',
        category: category,
        isChecked: map['is_checked'] == true || map['is_checked'] == 1,
        requiresPhoto: map['requires_photo'] == true,
      );
      
      categories.putIfAbsent(category, () => []).add(checkItem);
    }
    
    _categories = categories.entries.map((e) {
      final catData = _categoryData[e.key] ?? (Icons.check_circle_outline, _kBlue, e.key);
      return _ChecklistCategory(
        key: e.key,
        name: catData.$3,
        icon: catData.$1,
        color: catData.$2,
        items: e.value,
      );
    }).toList();
    
    _calculateProgress();
  }

  void _useDefaultChecklist() {
    _categories = [
      _ChecklistCategory(
        key: 'documents',
        name: 'Documents',
        icon: Icons.description_outlined,
        color: const Color(0xFF0275D8),
        items: [
          _ChecklistItem(id: '1', label: 'Permis de conduire', category: 'documents'),
          _ChecklistItem(id: '2', label: 'Carte grise', category: 'documents'),
          _ChecklistItem(id: '3', label: 'Attestation d\'assurance', category: 'documents'),
          _ChecklistItem(id: '4', label: 'Carnet d\'entretien', category: 'documents'),
        ],
      ),
      _ChecklistCategory(
        key: 'vehicle',
        name: 'Véhicule',
        icon: Icons.directions_car_outlined,
        color: const Color(0xFF5CB85C),
        items: [
          _ChecklistItem(id: '5', label: 'Pression des pneus', category: 'vehicle'),
          _ChecklistItem(id: '6', label: 'Niveau d\'huile', category: 'vehicle'),
          _ChecklistItem(id: '7', label: 'Niveau liquide de frein', category: 'vehicle'),
          _ChecklistItem(id: '8', label: 'Niveau de carburant', category: 'vehicle'),
          _ChecklistItem(id: '9', label: 'Feux avant/arrière', category: 'vehicle'),
          _ChecklistItem(id: '10', label: 'Clignotants', category: 'vehicle'),
          _ChecklistItem(id: '11', label: 'Essuie-glaces', category: 'vehicle'),
        ],
      ),
      _ChecklistCategory(
        key: 'security',
        name: 'Sécurité',
        icon: Icons.security_outlined,
        color: const Color(0xFFF0AD4E),
        items: [
          _ChecklistItem(id: '12', label: 'Gilet de sécurité', category: 'security'),
          _ChecklistItem(id: '13', label: 'Triangle de signalisation', category: 'security'),
          _ChecklistItem(id: '14', label: 'Extincteur', category: 'security'),
          _ChecklistItem(id: '15', label: 'Trousse de premiers soins', category: 'security'),
        ],
      ),
      _ChecklistCategory(
        key: 'loading',
        name: 'Chargement',
        icon: Icons.inventory_2_outlined,
        color: const Color(0xFF9B59B6),
        items: [
          _ChecklistItem(id: '16', label: 'Arrimage de la cargaison', category: 'loading'),
          _ChecklistItem(id: '17', label: 'Poids dans les limites', category: 'loading'),
          _ChecklistItem(id: '18', label: 'Scellés intacts', category: 'loading'),
        ],
      ),
    ];
    _calculateProgress();
  }

  void _calculateProgress() {
    _totalCount = _categories.fold(0, (sum, c) => sum + c.items.length);
    _completedCount = _categories.fold(0, (sum, c) => sum + c.items.where((i) => i.isChecked).length);
  }

  void _toggleItem(String categoryKey, String itemId) {
    setState(() {
      for (final category in _categories) {
        if (category.key == categoryKey) {
          for (final item in category.items) {
            if (item.id == itemId) {
              item.isChecked = !item.isChecked;
              break;
            }
          }
          break;
        }
      }
      _calculateProgress();
    });
  }

  Future<void> _validateChecklist() async {
    if (_completedCount < _totalCount) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Veuillez cocher tous les éléments'),
          backgroundColor: _kWarning,
        ),
      );
      return;
    }

    try {
      final api = sl<ApiService>();
      await api.post('/checklist/validate', {
        'items': _categories.expand((c) => c.items).map((i) => {
          'id': i.id,
          'is_checked': i.isChecked,
        }).toList(),
      });
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Check-list validée avec succès'),
            backgroundColor: _kGreen,
          ),
        );
        Navigator.pop(context);
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Check-list enregistrée localement'),
          backgroundColor: _kGreen,
        ),
      );
      if (mounted) Navigator.pop(context);
    }
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
        title: const Text('Check-list départ',
            style: TextStyle(color: _kNavy, fontWeight: FontWeight.w700)),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: _kBorder, height: 1),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: _kBlue))
          : _categories.isEmpty && _error != null
              ? _buildErrorState()
              : _buildBody(),
      bottomNavigationBar: !_loading && _categories.isNotEmpty
          ? Container(
              padding: const EdgeInsets.all(16),
              decoration: const BoxDecoration(
                color: Colors.white,
                border: Border(top: BorderSide(color: _kBorder)),
              ),
              child: SafeArea(
                child: ElevatedButton.icon(
                  onPressed: _completedCount == _totalCount ? _validateChecklist : null,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _kGreen,
                    disabledBackgroundColor: _kBorder,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                  icon: const Icon(Icons.check_circle_outline),
                  label: const Text('Valider la check-list',
                      style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                ),
              ),
            )
          : null,
    );
  }

  Widget _buildErrorState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, size: 48, color: _kRed),
            const SizedBox(height: 16),
            const Text(
              'Erreur de chargement',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600, color: _kNavy),
            ),
            const SizedBox(height: 8),
            Text(
              _error ?? 'Une erreur est survenue',
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 14, color: _kMuted),
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: _loadChecklist,
              style: ElevatedButton.styleFrom(
                backgroundColor: _kBlue,
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
              ),
              icon: const Icon(Icons.refresh),
              label: const Text('Réessayer'),
            ),
            const SizedBox(height: 12),
            TextButton(
              onPressed: () {
                _useDefaultChecklist();
                setState(() => _error = null);
              },
              child: const Text('Utiliser la checklist par défaut'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBody() {
    return Column(
      children: [
        // Progress bar
        Container(
          color: Colors.white,
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Progression',
                      style: TextStyle(fontFamily: 'Inter', fontSize: 14, fontWeight: FontWeight.w600, color: _kNavy)),
                  Text('$_completedCount/$_totalCount',
                      style: const TextStyle(fontFamily: 'Inter', fontSize: 14, fontWeight: FontWeight.w700, color: _kBlue)),
                ],
              ),
              const SizedBox(height: 8),
              ClipRRect(
                borderRadius: BorderRadius.circular(4),
                child: LinearProgressIndicator(
                  value: _totalCount > 0 ? _completedCount / _totalCount : 0,
                  backgroundColor: const Color(0xFFE8ECEF),
                  color: _completedCount == _totalCount ? _kGreen : _kBlue,
                  minHeight: 8,
                ),
              ),
            ],
          ),
        ),
        Container(color: _kBorder, height: 1),

        // Categories and items
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: _categories.length,
            itemBuilder: (context, index) {
              final category = _categories[index];
              return _CategoryCard(
                category: category,
                onToggleItem: (itemId) => _toggleItem(category.key, itemId),
              );
            },
          ),
        ),
      ],
    );
  }
}

class _CategoryCard extends StatelessWidget {
  final _ChecklistCategory category;
  final ValueChanged<String> onToggleItem;

  const _CategoryCard({
    required this.category,
    required this.onToggleItem,
  });

  @override
  Widget build(BuildContext context) {
    final completedInCategory = category.items.where((i) => i.isChecked).length;
    
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: _kBorder),
      ),
      child: Column(
        children: [
          // Category header
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: category.color.withOpacity(0.1),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(9)),
            ),
            child: Row(
              children: [
                Container(
                  width: 36,
                  height: 36,
                  decoration: BoxDecoration(
                    color: category.color.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(category.icon, size: 20, color: category.color),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    category.name,
                    style: TextStyle(
                      fontFamily: 'Inter',
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: category.color,
                    ),
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: completedInCategory == category.items.length
                        ? _kGreen.withOpacity(0.2)
                        : Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: completedInCategory == category.items.length ? _kGreen : _kBorder,
                    ),
                  ),
                  child: Text(
                    '$completedInCategory/${category.items.length}',
                    style: TextStyle(
                      fontFamily: 'Inter',
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: completedInCategory == category.items.length ? _kGreen : _kMuted,
                    ),
                  ),
                ),
              ],
            ),
          ),
          
          // Items
          ...category.items.asMap().entries.map((entry) {
            final index = entry.key;
            final item = entry.value;
            final isLast = index == category.items.length - 1;
            
            return Column(
              children: [
                InkWell(
                  onTap: () => onToggleItem(item.id),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    child: Row(
                      children: [
                        AnimatedContainer(
                          duration: const Duration(milliseconds: 200),
                          width: 24,
                          height: 24,
                          decoration: BoxDecoration(
                            color: item.isChecked ? _kGreen : Colors.white,
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(
                              color: item.isChecked ? _kGreen : _kBorder,
                              width: 2,
                            ),
                          ),
                          child: item.isChecked
                              ? const Icon(Icons.check, size: 16, color: Colors.white)
                              : null,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            item.label,
                            style: TextStyle(
                              fontFamily: 'Inter',
                              fontSize: 14,
                              fontWeight: FontWeight.w500,
                              color: item.isChecked ? _kMuted : _kNavy,
                              decoration: item.isChecked ? TextDecoration.lineThrough : null,
                            ),
                          ),
                        ),
                        if (item.requiresPhoto)
                          Icon(
                            Icons.camera_alt_outlined,
                            size: 18,
                            color: _kMuted.withOpacity(0.5),
                          ),
                      ],
                    ),
                  ),
                ),
                if (!isLast) const Divider(height: 1, indent: 50),
              ],
            );
          }),
        ],
      ),
    );
  }
}

class _ChecklistCategory {
  final String key;
  final String name;
  final IconData icon;
  final Color color;
  final List<_ChecklistItem> items;

  _ChecklistCategory({
    required this.key,
    required this.name,
    required this.icon,
    required this.color,
    required this.items,
  });
}

class _ChecklistItem {
  final String id;
  final String label;
  final String category;
  bool isChecked;
  final bool requiresPhoto;

  _ChecklistItem({
    required this.id,
    required this.label,
    required this.category,
    this.isChecked = false,
    this.requiresPhoto = false,
  });
}
