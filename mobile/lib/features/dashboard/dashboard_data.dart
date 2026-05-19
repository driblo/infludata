import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';

class TopMover {
  const TopMover({
    required this.creatorProfileId,
    required this.followers,
    required this.delta7d,
  });

  factory TopMover.fromJson(Map<String, dynamic> j) => TopMover(
        creatorProfileId: j['creator_profile_id'] as int,
        followers: (j['followers'] as num?)?.toInt() ?? 0,
        delta7d: (j['delta_7d'] as num?)?.toInt() ?? 0,
      );

  final int creatorProfileId;
  final int followers;
  final int delta7d;
}

class DashboardData {
  const DashboardData({
    required this.trackedCount,
    required this.totalFollowers,
    required this.topMovers,
  });

  factory DashboardData.fromJson(Map<String, dynamic> j) {
    final totals = (j['totals'] as Map?)?.cast<String, dynamic>() ?? const <String, dynamic>{};
    final movers = (j['top_movers'] as List?) ?? const [];
    return DashboardData(
      trackedCount: (totals['tracked_count'] as num?)?.toInt() ?? 0,
      totalFollowers: (totals['total_followers'] as num?)?.toInt() ?? 0,
      topMovers: movers
          .whereType<Map<String, dynamic>>()
          .map(TopMover.fromJson)
          .toList(growable: false),
    );
  }

  final int trackedCount;
  final int totalFollowers;
  final List<TopMover> topMovers;
}

final dashboardDataProvider = FutureProvider<DashboardData>((ref) async {
  final dio = ref.watch(apiClientProvider);
  final res = await dio.get<Map<String, dynamic>>('/api/dashboard');
  return DashboardData.fromJson(res.data ?? <String, dynamic>{});
});
