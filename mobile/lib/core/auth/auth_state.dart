enum AuthStatus { unknown, signedOut, signedIn }

class AuthState {
  const AuthState({required this.status, this.token, this.user});

  const AuthState.unknown() : this(status: AuthStatus.unknown);
  const AuthState.signedOut() : this(status: AuthStatus.signedOut);
  const AuthState.signedIn(String token, Map<String, dynamic> user)
      : this(status: AuthStatus.signedIn, token: token, user: user);

  final AuthStatus status;
  final String? token;
  final Map<String, dynamic>? user;
}
