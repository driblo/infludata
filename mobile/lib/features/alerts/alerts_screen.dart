import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'alerts_controller.dart';

class AlertsScreen extends ConsumerWidget {
  const AlertsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final alerts = ref.watch(alertsProvider);
    final actions = ref.watch(alertsActionsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Alerts')),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(alertsProvider),
        child: alerts.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => Center(child: Text('Error: $e')),
          data: (list) => list.isEmpty
              ? const Center(child: Text('No alerts yet.'))
              : ListView.separated(
                  itemCount: list.length,
                  separatorBuilder: (_, __) => const Divider(height: 0),
                  itemBuilder: (context, i) {
                    final a = list[i];
                    return ListTile(
                      leading: Icon(_iconFor(a.kind)),
                      title: Text('${a.kind} · ${a.targetType} #${a.targetId}'),
                      subtitle: Text(a.threshold.toString()),
                      trailing: IconButton(
                        icon: const Icon(Icons.delete_outline),
                        onPressed: () => actions.remove(a.id),
                      ),
                    );
                  },
                ),
        ),
      ),
    );
  }

  IconData _iconFor(String kind) => switch (kind) {
        'follower_milestone' => Icons.emoji_events_outlined,
        'engagement_drop' => Icons.trending_down,
        'new_content' => Icons.fiber_new,
        _ => Icons.notifications_outlined,
      };
}
