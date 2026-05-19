import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'creators_controller.dart';

class TrackedCreatorsScreen extends ConsumerWidget {
  const TrackedCreatorsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final tracked = ref.watch(trackedCreatorsProvider);
    final actions = ref.watch(creatorActionsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Tracked creators')),
      floatingActionButton: FloatingActionButton.extended(
        icon: const Icon(Icons.add),
        label: const Text('Track creator'),
        onPressed: () => _showAddDialog(context, actions),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(trackedCreatorsProvider),
        child: tracked.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => Center(child: Text('Error: $e')),
          data: (list) => list.isEmpty
              ? const Center(
                  child: Text('No tracked creators yet. Tap + to add one.'),
                )
              : ListView.separated(
                  itemCount: list.length,
                  separatorBuilder: (_, __) => const Divider(height: 0),
                  itemBuilder: (context, i) {
                    final t = list[i];
                    final p = t.profile;
                    return ListTile(
                      leading: CircleAvatar(
                        backgroundImage: (p?.avatarUrl != null)
                            ? NetworkImage(p!.avatarUrl!)
                            : null,
                        child: (p?.avatarUrl == null)
                            ? Text(t.network[0].toUpperCase())
                            : null,
                      ),
                      title: Text(p?.displayName ?? t.handle),
                      subtitle: Text(
                        '${t.network} · @${t.handle}'
                        '${p == null ? '' : '  ·  ${p.followerCount} followers'}',
                      ),
                      trailing: IconButton(
                        icon: const Icon(Icons.delete_outline),
                        onPressed: () => actions.untrack(t.id),
                      ),
                      onTap: () => context.push('/creators/${t.creatorProfileId}'),
                    );
                  },
                ),
        ),
      ),
    );
  }

  Future<void> _showAddDialog(BuildContext context, CreatorActions actions) async {
    final handleCtrl = TextEditingController();
    String network = 'youtube';
    String? label;

    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Track a creator'),
        content: StatefulBuilder(
          builder: (ctx, set) => Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              DropdownButtonFormField<String>(
                initialValue: network,
                decoration: const InputDecoration(labelText: 'Network'),
                items: const [
                  DropdownMenuItem(value: 'youtube', child: Text('YouTube')),
                  DropdownMenuItem(value: 'instagram', child: Text('Instagram')),
                  DropdownMenuItem(value: 'tiktok', child: Text('TikTok')),
                  DropdownMenuItem(value: 'x', child: Text('X (Twitter)')),
                  DropdownMenuItem(value: 'facebook', child: Text('Facebook')),
                ],
                onChanged: (v) => set(() => network = v ?? network),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: handleCtrl,
                decoration: const InputDecoration(labelText: 'Handle (without @)'),
              ),
              const SizedBox(height: 12),
              TextField(
                decoration: const InputDecoration(labelText: 'Label (optional)'),
                onChanged: (v) => label = v.isEmpty ? null : v,
              ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Track')),
        ],
      ),
    );

    if (ok != true) return;
    if (handleCtrl.text.trim().isEmpty) return;
    try {
      await actions.track(
        network: network,
        handle: handleCtrl.text.trim(),
        label: label,
      );
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Creator added. Backfill running.')),
        );
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed: $e')),
        );
      }
    }
  }
}
