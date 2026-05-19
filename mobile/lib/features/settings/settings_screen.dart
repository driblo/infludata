import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';
import '../auth/auth_controller.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      appBar: AppBar(title: const Text('Settings')),
      body: ListView(
        children: [
          ListTile(
            leading: const Icon(Icons.download),
            title: const Text('Export my data (JSON)'),
            onTap: () async {
              final dio = ref.read(apiClientProvider);
              await dio.post<dynamic>('/api/exports', data: {'kind': 'json'});
              if (context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Export queued.')),
                );
              }
            },
          ),
          ListTile(
            leading: const Icon(Icons.shield_outlined),
            title: const Text('GDPR export'),
            subtitle: const Text('All your data in one archive'),
            onTap: () async {
              final dio = ref.read(apiClientProvider);
              await dio.post<dynamic>('/api/exports', data: {'kind': 'gdpr'});
              if (context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('GDPR export queued.')),
                );
              }
            },
          ),
          const Divider(),
          ListTile(
            leading: const Icon(Icons.delete_forever, color: Colors.red),
            title: const Text('Delete account', style: TextStyle(color: Colors.red)),
            onTap: () => _confirmDelete(context, ref),
          ),
        ],
      ),
    );
  }

  Future<void> _confirmDelete(BuildContext context, WidgetRef ref) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete account?'),
        content: const Text(
          'This permanently removes your account, connections, alerts, '
          'and exports. Shared creator profiles (which contain no PII) '
          'are retained.',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton.tonal(
            style: FilledButton.styleFrom(foregroundColor: Colors.red),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
    if (ok != true) return;
    final dio = ref.read(apiClientProvider);
    await dio.delete<dynamic>('/api/me');
    await ref.read(authControllerProvider.notifier).logout();
  }
}
