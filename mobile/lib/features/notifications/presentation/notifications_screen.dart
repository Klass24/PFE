import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../../../core/di/service_locator.dart';
import '../data/notification_repository.dart';
import '../domain/notification.dart';
import 'notification_bloc.dart';

const _kNavy   = Color(0xFF1F2D3D);
const _kBlue   = Color(0xFF0275D8);
const _kBgGray = Color(0xFFF0F3F4);
const _kBorder = Color(0xFFE8ECEF);
const _kMuted  = Color(0xFF7F8FA4);
const _kGreen  = Color(0xFF5CB85C);
const _kRed    = Color(0xFFD9534F);
const _kWarning= Color(0xFFF0AD4E);

const _typeIcons = {
  'document_expiry':   Icons.description_outlined,
  'incident_reported': Icons.warning_amber_rounded,
  'expense_threshold': Icons.monetization_on_outlined,
  'mission_started':   Icons.play_circle_outline,
  'license_expiring':  Icons.badge_outlined,
};

const _typeColors = {
  'document_expiry':   Color(0xFFF0AD4E),
  'incident_reported': Color(0xFFD9534F),
  'expense_threshold': Color(0xFF0275D8),
  'mission_started':   Color(0xFF5CB85C),
  'license_expiring':  Color(0xFFF0AD4E),
};

class NotificationsScreen extends StatelessWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => NotificationBloc(repository: sl<NotificationRepository>())
        ..add(LoadNotifications()),
      child: const _NotificationsView(),
    );
  }
}

class _NotificationsView extends StatelessWidget {
  const _NotificationsView();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _kBgGray,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.menu, color: _kNavy),
          onPressed: () => Scaffold.of(context).openDrawer(),
        ),
        title: const Text('Notifications',
            style: TextStyle(color: _kNavy, fontWeight: FontWeight.w700)),
        iconTheme: const IconThemeData(color: _kNavy),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: _kBorder, height: 1),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.done_all_rounded, color: _kMuted),
            tooltip: 'Tout marquer comme lu',
            onPressed: () => context.read<NotificationBloc>().add(MarkAllRead()),
          ),
          IconButton(
            icon: const Icon(Icons.refresh_rounded, color: _kMuted),
            onPressed: () => context.read<NotificationBloc>().add(LoadNotifications()),
          ),
        ],
      ),
      body: BlocBuilder<NotificationBloc, NotificationState>(
        builder: (ctx, state) {
          if (state is NotificationLoading) {
            return const Center(child: CircularProgressIndicator(color: _kBlue));
          }
          if (state is NotificationError) {
            return Center(
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                const Icon(Icons.cloud_off, size: 48, color: _kMuted),
                const SizedBox(height: 12),
                Text(state.message, style: const TextStyle(color: _kMuted, fontSize: 13)),
                const SizedBox(height: 16),
                ElevatedButton.icon(
                  onPressed: () => ctx.read<NotificationBloc>().add(LoadNotifications()),
                  icon: const Icon(Icons.refresh, size: 18),
                  label: const Text('Réessayer'),
                ),
              ]),
            );
          }
          if (state is NotificationLoaded) {
            if (state.notifications.isEmpty) {
              return Center(
                child: Column(mainAxisSize: MainAxisSize.min, children: [
                  Icon(Icons.notifications_none_rounded, size: 56, color: _kMuted.withOpacity(0.5)),
                  const SizedBox(height: 12),
                  const Text('Aucune notification',
                      style: TextStyle(fontSize: 15, color: _kMuted)),
                ]),
              );
            }
            return RefreshIndicator(
              color: _kBlue,
              onRefresh: () async => ctx.read<NotificationBloc>().add(LoadNotifications()),
              child: ListView.separated(
                padding: const EdgeInsets.all(12),
                itemCount: state.notifications.length,
                separatorBuilder: (_, __) => const SizedBox(height: 6),
                itemBuilder: (_, i) => _NotificationCard(notification: state.notifications[i]),
              ),
            );
          }
          return const SizedBox.shrink();
        },
      ),
    );
  }
}

class _NotificationCard extends StatelessWidget {
  final AppNotification notification;
  const _NotificationCard({required this.notification});

  @override
  Widget build(BuildContext context) {
    final color = _typeColors[notification.type] ?? _kBlue;
    final icon = _typeIcons[notification.type] ?? Icons.notifications_outlined;

    return GestureDetector(
      onTap: () {
        if (!notification.isRead) {
          context.read<NotificationBloc>().add(MarkNotificationRead(notification.uuid));
        }
      },
      child: Container(
        decoration: BoxDecoration(
          color: notification.isRead ? Colors.white : const Color(0xFFF0F7FF),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: notification.isRead ? _kBorder : _kBlue.withOpacity(0.2)),
        ),
        padding: const EdgeInsets.all(14),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 40, height: 40,
              decoration: BoxDecoration(
                color: color.withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(icon, color: color, size: 20),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(children: [
                  Expanded(
                    child: Text(notification.title,
                        style: TextStyle(
                          fontFamily: 'Inter',
                          fontSize: 14,
                          fontWeight: notification.isRead ? FontWeight.w500 : FontWeight.w700,
                          color: _kNavy,
                        )),
                  ),
                  if (!notification.isRead)
                    Container(
                      width: 8, height: 8,
                      decoration: const BoxDecoration(color: _kBlue, shape: BoxShape.circle),
                    ),
                ]),
                const SizedBox(height: 4),
                Text(notification.body,
                    style: const TextStyle(fontFamily: 'Inter', fontSize: 13, color: _kMuted),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis),
                const SizedBox(height: 6),
                Text(_formatDate(notification.createdAt),
                    style: const TextStyle(fontFamily: 'Inter', fontSize: 11, color: _kMuted)),
              ]),
            ),
          ],
        ),
      ),
    );
  }

  String _formatDate(String raw) {
    try {
      final dt = DateTime.parse(raw).toLocal();
      final now = DateTime.now();
      final diff = now.difference(dt);
      if (diff.inMinutes < 60) return 'Il y a ${diff.inMinutes} min';
      if (diff.inHours < 24) return 'Il y a ${diff.inHours}h';
      if (diff.inDays < 7) return 'Il y a ${diff.inDays}j';
      return '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')}/${dt.year}';
    } catch (_) {
      return raw;
    }
  }
}
