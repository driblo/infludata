import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/router/app_router.dart';

class InfludataApp extends ConsumerWidget {
  const InfludataApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(appRouterProvider);
    return MaterialApp.router(
      title: 'infludata',
      theme: ThemeData(
        useMaterial3: true,
        colorSchemeSeed: const Color(0xFF7B61FF),
      ),
      darkTheme: ThemeData(
        useMaterial3: true,
        brightness: Brightness.dark,
        colorSchemeSeed: const Color(0xFF7B61FF),
      ),
      routerConfig: router,
    );
  }
}
