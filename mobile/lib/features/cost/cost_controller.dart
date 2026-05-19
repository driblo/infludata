import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';

class XCost {
  const XCost({
    required this.spentToday,
    required this.remainingToday,
    required this.killSwitch,
  });

  factory XCost.fromJson(Map<String, dynamic> j) => XCost(
        spentToday: (j['spent_today_usd'] as num?)?.toDouble() ?? 0.0,
        remainingToday: (j['remaining_today_usd'] as num?)?.toDouble() ?? 0.0,
        killSwitch: (j['kill_switch'] as bool?) ?? false,
      );

  final double spentToday;
  final double remainingToday;
  final bool killSwitch;
}

final xCostProvider = FutureProvider<XCost>((ref) async {
  final dio = ref.watch(apiClientProvider);
  final res = await dio.get<Map<String, dynamic>>('/api/cost');
  final x = (res.data?['data']?['x'] as Map?)?.cast<String, dynamic>() ?? <String, dynamic>{};
  return XCost.fromJson(x);
});
