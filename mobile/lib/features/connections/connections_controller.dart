import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';

class OauthAccount {
  const OauthAccount({
    required this.id,
    required this.network,
    required this.handle,
    required this.status,
    this.connectedAt,
    this.lastSyncedAt,
  });

  factory OauthAccount.fromJson(Map<String, dynamic> json) => OauthAccount(
        id: json['id'] as int,
        network: (json['network'] as String?) ?? '',
        handle: (json['external_handle'] as String?) ?? '',
        status: (json['status'] as String?) ?? '',
        connectedAt: DateTime.tryParse((json['connected_at'] as String?) ?? ''),
        lastSyncedAt: DateTime.tryParse((json['last_synced_at'] as String?) ?? ''),
      );

  final int id;
  final String network;
  final String handle;
  final String status;
  final DateTime? connectedAt;
  final DateTime? lastSyncedAt;
}

final connectionsProvider = FutureProvider<List<OauthAccount>>((ref) async {
  final dio = ref.watch(apiClientProvider);
  final res = await dio.get<Map<String, dynamic>>('/api/connections');
  final data = (res.data?['data'] as List?) ?? const [];
  return data
      .whereType<Map<String, dynamic>>()
      .map(OauthAccount.fromJson)
      .toList(growable: false);
});

final connectionsActionsProvider = Provider<ConnectionsActions>(
  (ref) => ConnectionsActions(ref),
);

class ConnectionsActions {
  ConnectionsActions(this._ref);

  final Ref _ref;

  Future<void> disconnect(int id) async {
    final dio = _ref.read(apiClientProvider);
    try {
      await dio.delete<dynamic>('/api/connections/$id');
    } on DioException catch (_) {
      rethrow;
    }
    _ref.invalidate(connectionsProvider);
  }
}
