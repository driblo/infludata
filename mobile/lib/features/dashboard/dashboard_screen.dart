import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../core/api/api_client.dart';
import '../auth/auth_controller.dart';
import '../cost/cost_controller.dart';
import 'dashboard_data.dart';

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
    final data = ref.watch(dashboardDataProvider);

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
            icon: const Icon(Icons.notifications_outlined),
            tooltip: 'Alerts',
            onPressed: () => context.go('/alerts'),
          ),
          IconButton(
            icon: const Icon(Icons.link),
            tooltip: 'My accounts',
            onPressed: () => context.go('/accounts'),
          ),
          IconButton(
            icon: const Icon(Icons.settings_outlined),
            tooltip: 'Settings',
            onPressed: () => context.go('/settings'),
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Sign out',
            onPressed: () => ref.read(authControllerProvider.notifier).logout(),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(dashboardDataProvider);
          ref.invalidate(xCostProvider);
          ref.invalidate(_healthProvider);
        },
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            auth.when(
              loading: () => const SizedBox.shrink(),
              error: (e, _) => Text('Auth error: $e'),
              data: (s) => Text(
                s.user?['email']?.toString() ?? 'signed in',
                style: Theme.of(context).textTheme.titleMedium,
              ),
            ),
            const SizedBox(height: 8),
            health.when(
              loading: () => const LinearProgressIndicator(),
              error: (e, _) => Text('Health check failed: $e'),
              data: (m) => Text('API status: ${m['status']}'),
            ),
            const SizedBox(height: 16),
            data.when(
              loading: () => const Padding(
                padding: EdgeInsets.all(24),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => Text('Dashboard error: $e'),
              data: (d) => _DashboardBody(data: d),
            ),
            const SizedBox(height: 16),
            const _XCostCard(),
          ],
        ),
      ),
    );
  }
}

class _DashboardBody extends StatelessWidget {
  const _DashboardBody({required this.data});

  final DashboardData data;

  @override
  Widget build(BuildContext context) {
    final fmt = NumberFormat.compact();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(
          children: [
            Expanded(child: _KpiTile(label: 'Tracked', value: '${data.trackedCount}')),
            const SizedBox(width: 8),
            Expanded(child: _KpiTile(label: 'Total followers', value: fmt.format(data.totalFollowers))),
          ],
        ),
        const SizedBox(height: 16),
        Text('Top movers (7d)', style: Theme.of(context).textTheme.titleLarge),
        const SizedBox(height: 8),
        if (data.topMovers.isEmpty)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 8),
            child: Text('No data yet. Add creators and wait for the next sync.'),
          )
        else
          ...data.topMovers.map((m) => Builder(builder: (context) {
                final positive = m.delta7d >= 0;
                return ListTile(
                  leading: CircleAvatar(child: Text('#${m.creatorProfileId}')),
                  title: Text('Creator ${m.creatorProfileId}'),
                  subtitle: Text('${fmt.format(m.followers)} followers'),
                  trailing: Text(
                    '${positive ? '+' : ''}${fmt.format(m.delta7d)}',
                    style: TextStyle(
                      color: positive ? Colors.green : Colors.red,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  onTap: () => context.push('/creators/${m.creatorProfileId}'),
                );
              })),
      ],
    );
  }
}

class _KpiTile extends StatelessWidget {
  const _KpiTile({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: Theme.of(context).textTheme.titleSmall),
            const SizedBox(height: 4),
            Text(value, style: Theme.of(context).textTheme.headlineSmall),
          ],
        ),
      ),
    );
  }
}

class _XCostCard extends ConsumerWidget {
  const _XCostCard();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
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
  }
}
