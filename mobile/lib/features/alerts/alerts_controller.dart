import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';

class AlertItem {
  const AlertItem({
    required this.id,
    required this.targetType,
    required this.targetId,
    required this.kind,
    required this.threshold,
    required this.channel,
    required this.enabled,
  });

  factory AlertItem.fromJson(Map<String, dynamic> j) => AlertItem(
        id: j['id'] as int,
        targetType: (j['target_type'] as String?) ?? '',
        targetId: (j['target_id'] as num?)?.toInt() ?? 0,
        kind: (j['kind'] as String?) ?? '',
        threshold: (j['threshold'] as Map?)?.cast<String, dynamic>() ?? <String, dynamic>{},
        channel: (j['channel'] as String?) ?? 'email',
        enabled: (j['enabled'] as bool?) ?? true,
      );

  final int id;
  final String targetType;
  final int targetId;
  final String kind;
  final Map<String, dynamic> threshold;
  final String channel;
  final bool enabled;
}

final alertsProvider = FutureProvider<List<AlertItem>>((ref) async {
  final dio = ref.watch(apiClientProvider);
  final res = await dio.get<Map<String, dynamic>>('/api/alerts');
  final data = (res.data?['data'] as List?) ?? const [];
  return data
      .whereType<Map<String, dynamic>>()
      .map(AlertItem.fromJson)
      .toList(growable: false);
});

final alertsActionsProvider =
    Provider<AlertsActions>((ref) => AlertsActions(ref));

class AlertsActions {
  AlertsActions(this._ref);

  final Ref _ref;

  Future<void> create({
    required String kind,
    required int targetId,
    required Map<String, dynamic> threshold,
  }) async {
    final dio = _ref.read(apiClientProvider);
    await dio.post<dynamic>('/api/alerts', data: {
      'target_type': 'creator',
      'target_id': targetId,
      'kind': kind,
      'threshold': threshold,
    });
    _ref.invalidate(alertsProvider);
  }

  Future<void> remove(int id) async {
    final dio = _ref.read(apiClientProvider);
    await dio.delete<dynamic>('/api/alerts/$id');
    _ref.invalidate(alertsProvider);
  }
}
