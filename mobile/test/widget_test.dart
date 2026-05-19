import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:infludata/features/auth/auth_login_screen.dart';

void main() {
  testWidgets('login screen shows email + password fields and a sign-in button',
      (tester) async {
    await tester.pumpWidget(
      const ProviderScope(
        child: MaterialApp(home: AuthLoginScreen()),
      ),
    );

    expect(find.text('Sign in'), findsAtLeast(1));
    expect(find.byType(TextFormField), findsNWidgets(2));
    expect(find.text('Email'), findsOneWidget);
    expect(find.text('Password'), findsOneWidget);
  });
}
