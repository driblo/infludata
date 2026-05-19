import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';
import 'models.dart';

final trackedCreatorsProvider =
    FutureProvider<List<TrackedCreator>>((ref) async {
  final dio = ref.watch(apiClientProvider);
  final res = await dio.get<Map<String, dynamic>>('/api/creators');
  final data = (res.data?['data'] as List?) ?? const [];
  return data
      .whereType<Map<String, dynamic>>()
      .map(TrackedCreator.fromJson)
      .toList(growable: false);
});

final creatorProfileProvider =
    FutureProvider.family<CreatorProfile, int>((ref, id) async {
  final dio = ref.watch(apiClientProvider);
  final res = await dio.get<Map<String, dynamic>>('/api/creators/$id/profile');
  final profile = (res.data?['profile'] as Map?)?.cast<String, dynamic>();
  return CreatorProfile.fromJson(profile ?? <String, dynamic>{});
});

final creatorMetricsProvider =
    FutureProvider.family<List<MetricPoint>, ({int id, String range})>(
        (ref, args) async {
  final dio = ref.watch(apiClientProvider);
  final res = await dio.get<Map<String, dynamic>>(
    '/api/creators/${args.id}/metrics',
    queryParameters: {'range': args.range},
  );
  final data = (res.data?['data'] as List?) ?? const [];
  return data
      .whereType<Map<String, dynamic>>()
      .map(MetricPoint.fromJson)
      .toList(growable: false);
});

final creatorActionsProvider = Provider<CreatorActions>(
  (ref) => CreatorActions(ref),
);

class CreatorActions {
  CreatorActions(this._ref);

  final Ref _ref;

  Future<void> track({required String network, required String handle, String? label}) async {
    final dio = _ref.read(apiClientProvider);
    await dio.post<dynamic>('/api/creators', data: {
      'network': network,
      'handle': handle,
      if (label != null) 'label': label,
    });
    _ref.invalidate(trackedCreatorsProvider);
  }

  Future<void> untrack(int trackedId) async {
    final dio = _ref.read(apiClientProvider);
    try {
      await dio.delete<dynamic>('/api/creators/$trackedId');
    } on DioException {
      rethrow;
    }
    _ref.invalidate(trackedCreatorsProvider);
  }
}
