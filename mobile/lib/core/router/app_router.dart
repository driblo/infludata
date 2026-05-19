import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/auth_controller.dart';
import '../../features/auth/auth_login_screen.dart';
import '../../features/auth/auth_register_screen.dart';
import '../../features/connections/my_accounts_screen.dart';
import '../../features/creators/creator_detail_screen.dart';
import '../../features/creators/tracked_creators_screen.dart';
import '../../features/dashboard/dashboard_screen.dart';
import '../auth/auth_state.dart';

final appRouterProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: '/',
    redirect: (context, state) {
      final auth = ref.read(authControllerProvider);
      if (auth.isLoading || auth.hasError) return null;
      final status = auth.value?.status;
      final path = state.matchedLocation;
      final isAuthRoute = path == '/login' || path == '/register';

      if (status == AuthStatus.signedOut && !isAuthRoute) return '/login';
      if (status == AuthStatus.signedIn && isAuthRoute) return '/';
      return null;
    },
    routes: [
      GoRoute(
        path: '/',
        name: 'dashboard',
        builder: (_, __) => const DashboardScreen(),
      ),
      GoRoute(
        path: '/login',
        name: 'login',
        builder: (_, __) => const AuthLoginScreen(),
      ),
      GoRoute(
        path: '/register',
        name: 'register',
        builder: (_, __) => const AuthRegisterScreen(),
      ),
      GoRoute(
        path: '/accounts',
        name: 'accounts',
        builder: (_, __) => const MyAccountsScreen(),
      ),
      GoRoute(
        path: '/creators',
        name: 'creators',
        builder: (_, __) => const TrackedCreatorsScreen(),
      ),
      GoRoute(
        path: '/creators/:id',
        name: 'creator-detail',
        builder: (_, state) => CreatorDetailScreen(
          creatorId: int.parse(state.pathParameters['id']!),
        ),
      ),
    ],
  );
});
