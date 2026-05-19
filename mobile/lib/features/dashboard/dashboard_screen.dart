import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/api/api_client.dart';
import '../auth/auth_controller.dart';
import '../cost/cost_controller.dart';

final _healthProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  final dio = ref.watch(apiClientProvider);
  try {
    final res = await dio.get<Map<String, dynamic>>('/api/health');
    return res.data ?? <String, dynamic>{};
  } on DioException catch (e) {
    return <String, dynamic>{'status': 'unreachable', 'error': e.message};
  }
});

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final health = ref.watch(_healthProvider);
    final auth = ref.watch(authControllerProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('infludata'),
        actions: [
          IconButton(
            icon: const Icon(Icons.people_outline),
            tooltip: 'Tracked creators',
            onPressed: () => context.go('/creators'),
          ),
          IconButton(
            icon: const Icon(Icons.link),
            tooltip: 'My accounts',
            onPressed: () => context.go('/accounts'),
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Sign out',
            onPressed: () => ref.read(authControllerProvider.notifier).logout(),
          ),
        ],
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              auth.when(
                loading: () => const SizedBox.shrink(),
                error: (e, _) => Text('Auth error: $e'),
                data: (s) => Text(
                  s.user?['email']?.toString() ?? 'signed in',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
              ),
              const SizedBox(height: 16),
              health.when(
                loading: () => const CircularProgressIndicator(),
                error: (e, _) => Text('Health check failed: $e'),
                data: (data) => Text('API status: ${data['status']}'),
              ),
              const SizedBox(height: 24),
              FilledButton.icon(
                icon: const Icon(Icons.link),
                label: const Text('Manage connected accounts'),
                onPressed: () => context.go('/accounts'),
              ),
              const SizedBox(height: 8),
              OutlinedButton.icon(
                icon: const Icon(Icons.people_outline),
                label: const Text('Tracked creators'),
                onPressed: () => context.go('/creators'),
              ),
              const SizedBox(height: 24),
              Consumer(
                builder: (context, ref, _) {
                  final cost = ref.watch(xCostProvider);
                  return cost.when(
                    loading: () => const SizedBox.shrink(),
                    error: (_, __) => const SizedBox.shrink(),
                    data: (c) => Card(
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('X API spend today',
                                style: Theme.of(context).textTheme.titleSmall),
                            const SizedBox(height: 4),
                            Text(
                              c.killSwitch
                                  ? 'kill switch active — X disabled'
                                  : '\$${c.spentToday.toStringAsFixed(3)} spent · '
                                      '\$${c.remainingToday.toStringAsFixed(3)} remaining',
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}
