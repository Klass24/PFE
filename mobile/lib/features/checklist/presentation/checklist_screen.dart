import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:image_picker/image_picker.dart';
import '../data/checklist_repository.dart';
import '../domain/checklist.dart';
import 'checklist_bloc.dart';

const _kNavy = Color(0xFF1F2D3D);
const _kBlue = Color(0xFF0275D8);
const _kBgGray = Color(0xFFF0F3F4);
const _kBorder = Color(0xFFE8ECEF);
const _kMuted = Color(0xFF7F8FA4);
const _kGreen = Color(0xFF5CB85C);
const _kRed = Color(0xFFD9534F);

class ChecklistScreen extends StatelessWidget {
  final String missionUuid;
  const ChecklistScreen({super.key, required this.missionUuid});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => ChecklistBloc(repository: context.read<ChecklistRepository>())
        ..add(LoadChecklist(missionUuid)),
      child: _ChecklistView(missionUuid: missionUuid),
    );
  }
}

class _ChecklistView extends StatelessWidget {
  final String missionUuid;
  const _ChecklistView({required this.missionUuid});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _kBgGray,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text('Check-list départ',
            style: TextStyle(color: _kNavy, fontWeight: FontWeight.w700)),
        iconTheme: const IconThemeData(color: _kNavy),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: _kBorder, height: 1),
        ),
      ),
      body: BlocConsumer<ChecklistBloc, ChecklistState>(
        listener: (ctx, state) {
          if (state is ChecklistSubmitted) {
            ScaffoldMessenger.of(ctx).showSnackBar(
              const SnackBar(
                content: Text('Check-list soumise (sync en attente)'),
                backgroundColor: _kGreen,
              ),
            );
            Navigator.pop(ctx, true); // Return true = checklist submitted
          }
          if (state is ChecklistError) {
            ScaffoldMessenger.of(ctx).showSnackBar(
              SnackBar(content: Text(state.message), backgroundColor: _kRed),
            );
          }
        },
        builder: (ctx, state) {
          if (state is ChecklistLoading) {
            return const Center(child: CircularProgressIndicator(color: _kBlue));
          }
          if (state is ChecklistReady) {
            return _buildChecklistBody(ctx, state.checklist);
          }
          return const SizedBox.shrink();
        },
      ),
    );
  }

  Widget _buildChecklistBody(BuildContext context, Checklist checklist) {
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
                  Text('${checklist.completedCount}/${checklist.totalCount}',
                      style: const TextStyle(fontFamily: 'Inter', fontSize: 14, fontWeight: FontWeight.w700, color: _kBlue)),
                ],
              ),
              const SizedBox(height: 8),
              ClipRRect(
                borderRadius: BorderRadius.circular(4),
                child: LinearProgressIndicator(
                  value: checklist.totalCount > 0 ? checklist.completedCount / checklist.totalCount : 0,
                  backgroundColor: const Color(0xFFE8ECEF),
                  color: _kGreen,
                  minHeight: 8,
                ),
              ),
            ],
          ),
        ),
        Container(color: _kBorder, height: 1),

        // Items list
        Expanded(
          child: ListView.separated(
            padding: const EdgeInsets.all(16),
            itemCount: checklist.items.length,
            separatorBuilder: (_, __) => const SizedBox(height: 10),
            itemBuilder: (_, i) => _ChecklistItemCard(item: checklist.items[i]),
          ),
        ),

        // Submit button
        if (checklist.status == 'draft')
          Container(
            padding: const EdgeInsets.all(16),
            decoration: const BoxDecoration(
              color: Colors.white,
              border: Border(top: BorderSide(color: _kBorder)),
            ),
            child: SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: checklist.canSubmit
                    ? () => context.read<ChecklistBloc>().add(SubmitChecklist())
                    : null,
                style: ElevatedButton.styleFrom(
                  backgroundColor: _kGreen,
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                icon: const Icon(Icons.check_circle_outline),
                label: const Text('Soumettre la check-list',
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
              ),
            ),
          ),
      ],
    );
  }
}

class _ChecklistItemCard extends StatelessWidget {
  final ChecklistItem item;
  const _ChecklistItemCard({required this.item});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: item.isOk ? _kGreen.withOpacity(0.3) : _kBorder),
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(item.label,
                      style: const TextStyle(fontFamily: 'Inter', fontSize: 14, fontWeight: FontWeight.w600, color: _kNavy)),
                ),
                if (item.requiresPhoto)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFF3CD),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: const Text('Photo requise',
                        style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF856404))),
                  ),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                // OK / NOK toggle
                _ToggleButton(
                  label: 'OK',
                  selected: item.isOk,
                  color: _kGreen,
                  onTap: () => context.read<ChecklistBloc>().add(ToggleItem(item.itemKey, true)),
                ),
                const SizedBox(width: 8),
                _ToggleButton(
                  label: 'NON OK',
                  selected: !item.isOk && item.isOk == false,
                  color: _kRed,
                  onTap: () => context.read<ChecklistBloc>().add(ToggleItem(item.itemKey, false)),
                ),
                const Spacer(),
                // Photo button
                if (item.requiresPhoto || item.photoPath != null)
                  IconButton(
                    icon: Icon(
                      item.photoPath != null ? Icons.photo : Icons.camera_alt_outlined,
                      color: item.photoPath != null ? _kGreen : _kMuted,
                    ),
                    onPressed: () => _pickPhoto(context),
                  ),
              ],
            ),
            // Photo preview
            if (item.photoPath != null) ...[
              const SizedBox(height: 8),
              ClipRRect(
                borderRadius: BorderRadius.circular(6),
                child: Image.file(File(item.photoPath!), height: 100, width: double.infinity, fit: BoxFit.cover),
              ),
            ],
          ],
        ),
      ),
    );
  }

  void _pickPhoto(BuildContext context) async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(source: ImageSource.camera, imageQuality: 70, maxWidth: 1024);
    if (picked != null) {
      context.read<ChecklistBloc>().add(SetItemPhoto(item.itemKey, picked.path));
    }
  }
}

class _ToggleButton extends StatelessWidget {
  final String label;
  final bool selected;
  final Color color;
  final VoidCallback onTap;
  const _ToggleButton({required this.label, required this.selected, required this.color, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: selected ? color.withOpacity(0.1) : Colors.transparent,
          border: Border.all(color: selected ? color : _kBorder),
          borderRadius: BorderRadius.circular(6),
        ),
        child: Text(label,
            style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: selected ? color : _kMuted)),
      ),
    );
  }
}
