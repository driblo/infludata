import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';

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
    return Scaffold(
      appBar: AppBar(title: const Text('infludata')),
      body: Center(
        child: health.when(
          loading: () => const CircularProgressIndicator(),
          error: (e, _) => Text('Health check failed: $e'),
          data: (data) => Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  'API status: ${data['status']}',
                  style: Theme.of(context).textTheme.headlineSmall,
                ),
                const SizedBox(height: 12),
                Text(data.toString()),
                const SizedBox(height: 24),
                const Text('M0 scaffolding live. M1 next: auth + Phyllo.'),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
