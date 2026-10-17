class ChecklistItem {
  final String itemKey;
  final String label;
  final bool isOk;
  final bool requiresPhoto;
  final String? photoPath;
  final String? note;

  const ChecklistItem({
    required this.itemKey,
    required this.label,
    this.isOk = false,
    this.requiresPhoto = false,
    this.photoPath,
    this.note,
  });

  factory ChecklistItem.fromMap(Map<String, dynamic> map) {
    try {
      return ChecklistItem(
        itemKey: (map['item_key'] as String?) ?? '',
        label: (map['label'] as String?) ?? (map['item_key'] as String?) ?? '',
        isOk: (map['is_ok'] as int?) == 1,
        requiresPhoto: (map['requires_photo'] as int?) == 1,
        photoPath: map['photo_path'] as String?,
        note: map['note'] as String?,
      );
    } catch (e) {
      print('[ChecklistItem.fromMap] Error: $e');
      rethrow;
    }
  }

  Map<String, dynamic> toMap() => {
        'item_key': itemKey,
        'label': label,
        'is_ok': isOk ? 1 : 0,
        'requires_photo': requiresPhoto ? 1 : 0,
        'photo_path': photoPath,
        'note': note,
      };

  ChecklistItem copyWith({
    bool? isOk,
    String? photoPath,
    String? note,
  }) =>
      ChecklistItem(
        itemKey: itemKey,
        label: label,
        isOk: isOk ?? this.isOk,
        requiresPhoto: requiresPhoto,
        photoPath: photoPath ?? this.photoPath,
        note: note ?? this.note,
      );
}

class Checklist {
  final String uuid;
  final String missionUuid;
  final String status; // draft, submitted, validated
  final List<ChecklistItem> items;
  final bool synced;

  const Checklist({
    required this.uuid,
    required this.missionUuid,
    this.status = 'draft',
    this.items = const [],
    this.synced = false,
  });

  bool get canSubmit {
    for (final item in items) {
      if (item.requiresPhoto && (item.photoPath == null || item.photoPath!.isEmpty)) {
        return false;
      }
    }
    return items.isNotEmpty;
  }

  int get completedCount => items.where((i) => i.isOk).length;
  int get totalCount => items.length;
}

const defaultChecklistItems = [
  ChecklistItem(itemKey: 'pression_pneus', label: 'Pression des pneus', requiresPhoto: false),
  ChecklistItem(itemKey: 'extincteur', label: 'Extincteur', requiresPhoto: false),
  ChecklistItem(itemKey: 'triangle_securite', label: 'Triangle de sécurité', requiresPhoto: false),
  ChecklistItem(itemKey: 'documents_vehicule', label: 'Documents du véhicule', requiresPhoto: true),
  ChecklistItem(itemKey: 'eclairage', label: 'Éclairage', requiresPhoto: false),
  ChecklistItem(itemKey: 'niveau_huile', label: "Niveau d'huile", requiresPhoto: false),
];
