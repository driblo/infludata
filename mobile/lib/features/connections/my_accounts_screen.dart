import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'connections_controller.dart';
import 'phyllo_connect_service.dart';

class MyAccountsScreen extends ConsumerWidget {
  const MyAccountsScreen({super.key});

  static const _availableNetworks = <String, String>{
    'youtube': 'YouTube',
    'instagram': 'Instagram',
    'tiktok': 'TikTok',
    'x': 'X (Twitter)',
    'facebook': 'Facebook',
  };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final connections = ref.watch(connectionsProvider);
    final actions = ref.watch(connectionsActionsProvider);
    final phyllo = ref.watch(phylloConnectServiceProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('My accounts')),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(connectionsProvider),
        child: connections.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => Center(child: Text('Error: $e')),
          data: (list) => ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text('Connected', style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 8),
              if (list.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 12),
                  child: Text('No connected accounts yet.'),
                )
              else
                ...list.map(
                  (acc) => Card(
                    child: ListTile(
                      leading: CircleAvatar(child: Text(acc.network[0].toUpperCase())),
                      title: Text(acc.handle.isEmpty ? acc.network : acc.handle),
                      subtitle: Text('${acc.network} · ${acc.status}'),
                      trailing: IconButton(
                        icon: const Icon(Icons.link_off),
                        tooltip: 'Disconnect',
                        onPressed: () async {
                          await actions.disconnect(acc.id);
                          if (context.mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Disconnected')),
                            );
                          }
                        },
                      ),
                    ),
                  ),
                ),
              const SizedBox(height: 24),
              Text('Connect a new account',
                  style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: 8),
              ..._availableNetworks.entries.map(
                (e) => ListTile(
                  leading: CircleAvatar(child: Text(e.key[0].toUpperCase())),
                  title: Text(e.value),
                  trailing: const Icon(Icons.add_link),
                  onTap: () async {
                    final ok = await phyllo.connectNetwork(e.key);
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text(ok
                              ? 'Connected to ${e.value}'
                              : 'Phyllo Connect SDK plugin not wired yet (M1).'),
                        ),
                      );
                    }
                    ref.invalidate(connectionsProvider);
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
