import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../../core/api/api_client.dart';
import '../../core/auth/auth_state.dart';

class AuthController extends AsyncNotifier<AuthState> {
  static const _tokenKey = 'sanctum_token';

  @override
  Future<AuthState> build() async {
    final storage = ref.read(secureStorageProvider);
    final token = await storage.read(key: _tokenKey);
    if (token == null || token.isEmpty) {
      return const AuthState.signedOut();
    }
    try {
      final dio = ref.read(apiClientProvider);
      final res = await dio.get<Map<String, dynamic>>('/api/me');
      return AuthState.signedIn(token, res.data ?? <String, dynamic>{});
    } on DioException {
      await storage.delete(key: _tokenKey);
      return const AuthState.signedOut();
    }
  }

  Future<void> login({required String email, required String password}) async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() async {
      final dio = ref.read(apiClientProvider);
      final res = await dio.post<Map<String, dynamic>>(
        '/api/auth/login',
        data: {'email': email, 'password': password, 'device_name': 'flutter'},
      );
      return _persistAndReturn(res.data ?? <String, dynamic>{});
    });
  }

  Future<void> register({
    required String name,
    required String email,
    required String password,
  }) async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() async {
      final dio = ref.read(apiClientProvider);
      final res = await dio.post<Map<String, dynamic>>(
        '/api/auth/register',
        data: {'name': name, 'email': email, 'password': password},
      );
      return _persistAndReturn(res.data ?? <String, dynamic>{});
    });
  }

  Future<void> logout() async {
    final dio = ref.read(apiClientProvider);
    final storage = ref.read(secureStorageProvider);
    try {
      await dio.post<dynamic>('/api/auth/logout');
    } on DioException {
      // Ignore network errors on logout — we clear local state anyway.
    }
    await storage.delete(key: _tokenKey);
    state = const AsyncValue.data(AuthState.signedOut());
  }

  Future<AuthState> _persistAndReturn(Map<String, dynamic> body) async {
    final token = (body['token'] as String?) ?? '';
    final user = (body['user'] as Map?)?.cast<String, dynamic>() ?? <String, dynamic>{};
    if (token.isEmpty) {
      throw StateError('login response missing token');
    }
    await ref.read(secureStorageProvider).write(key: _tokenKey, value: token);
    return AuthState.signedIn(token, user);
  }
}

final authControllerProvider =
    AsyncNotifierProvider<AuthController, AuthState>(AuthController.new);
