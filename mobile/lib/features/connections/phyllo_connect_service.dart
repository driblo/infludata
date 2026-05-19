import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/api/api_client.dart';

/// Service that wraps the Phyllo Connect SDK flow.
///
/// The official `phyllo_connect_sdk` Flutter plugin handles the modal; this
/// service is responsible for minting the short-lived SDK token from our
/// backend (`POST /api/connections/phyllo-token`) and surfacing connection
/// status back to the rest of the app.
///
/// The actual native SDK invocation is intentionally stubbed in this
/// milestone — wiring up the plugin's platform channels requires runtime
/// Phyllo credentials, which are provisioned during integration testing.
class PhylloSdkToken {
  const PhylloSdkToken({required this.sdkToken, required this.phylloUserId, required this.expiresAt});

  final String sdkToken;
  final String phylloUserId;
  final DateTime expiresAt;
}

class PhylloConnectService {
  PhylloConnectService(this._dio);

  final Dio _dio;

  Future<PhylloSdkToken> mintSdkToken() async {
    final res = await _dio.post<Map<String, dynamic>>('/api/connections/phyllo-token');
    final body = res.data ?? <String, dynamic>{};
    return PhylloSdkToken(
      sdkToken: (body['sdk_token'] as String?) ?? '',
      phylloUserId: (body['phyllo_user_id'] as String?) ?? '',
      expiresAt: DateTime.tryParse((body['expires_at'] as String?) ?? '') ?? DateTime.now(),
    );
  }

  /// Launches the Phyllo Connect modal for [network] (e.g. `youtube`,
  /// `instagram`). Returns true when the user finished the flow.
  ///
  /// In M1 we expose the contract; the native plugin invocation lives in a
  /// follow-up patch that ships once the Phyllo Flutter SDK is added to
  /// pubspec.yaml.
  Future<bool> connectNetwork(String network) async {
    final token = await mintSdkToken();
    // ignore: avoid_print
    print('TODO: invoke PhylloConnectSDK with sdk_token=${token.sdkToken.substring(0, 8)}…');
    return false;
  }
}

final phylloConnectServiceProvider = Provider<PhylloConnectService>(
  (ref) => PhylloConnectService(ref.watch(apiClientProvider)),
);
