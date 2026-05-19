import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:sentry_flutter/sentry_flutter.dart';

import 'app.dart';
import 'core/env.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  final dsn = Env.sentryDsn;
  if (dsn.isEmpty) {
    runApp(const ProviderScope(child: InfludataApp()));
    return;
  }

  await SentryFlutter.init(
    (options) {
      options.dsn = dsn;
      options.tracesSampleRate = 0.1;
    },
    appRunner: () => runApp(const ProviderScope(child: InfludataApp())),
  );
}
