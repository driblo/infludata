class CreatorProfile {
  const CreatorProfile({
    required this.id,
    required this.network,
    required this.handle,
    this.displayName,
    this.avatarUrl,
    this.followerCount = 0,
    this.isVerified = false,
  });

  factory CreatorProfile.fromJson(Map<String, dynamic> j) => CreatorProfile(
        id: j['id'] as int,
        network: (j['network'] as String?) ?? '',
        handle: (j['handle'] as String?) ?? '',
        displayName: j['display_name'] as String?,
        avatarUrl: j['avatar_url'] as String?,
        followerCount: (j['follower_count'] as num?)?.toInt() ?? 0,
        isVerified: (j['is_verified'] as bool?) ?? false,
      );

  final int id;
  final String network;
  final String handle;
  final String? displayName;
  final String? avatarUrl;
  final int followerCount;
  final bool isVerified;
}

class TrackedCreator {
  const TrackedCreator({
    required this.id,
    required this.creatorProfileId,
    required this.network,
    required this.handle,
    this.label,
    this.profile,
  });

  factory TrackedCreator.fromJson(Map<String, dynamic> j) => TrackedCreator(
        id: j['id'] as int,
        creatorProfileId: j['creator_profile_id'] as int,
        network: (j['network'] as String?) ?? '',
        handle: (j['handle'] as String?) ?? '',
        label: j['label'] as String?,
        profile: (j['creator_profile'] is Map<String, dynamic>)
            ? CreatorProfile.fromJson(j['creator_profile'] as Map<String, dynamic>)
            : null,
      );

  final int id;
  final int creatorProfileId;
  final String network;
  final String handle;
  final String? label;
  final CreatorProfile? profile;
}

class MetricPoint {
  const MetricPoint({required this.capturedAt, required this.followers});

  factory MetricPoint.fromJson(Map<String, dynamic> j) => MetricPoint(
        capturedAt: DateTime.parse(j['captured_at'] as String),
        followers: (j['followers'] as num?)?.toInt() ?? 0,
      );

  final DateTime capturedAt;
  final int followers;
}
